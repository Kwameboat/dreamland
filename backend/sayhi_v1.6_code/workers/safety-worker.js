/**
 * Dreamland Safety Worker — delegates to inbuilt Ghana AI moderation agent.
 * Prefer: dreamland/moderation-agent (npm run worker)
 * Legacy fallback: node workers/safety-worker.js
 */
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const configPath = path.join(__dirname, '..', 'chat', 'config.json');
const config = fs.existsSync(configPath) ? JSON.parse(fs.readFileSync(configPath, 'utf8')) : {};
const dbConfig = config.db || {
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3309),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || 'root',
  database: process.env.DB_NAME || 'yii2advanced',
};

const AGENT_URL = process.env.DREAMLAND_MODERATION_URL || 'http://127.0.0.1:4444';
const AGENT_SECRET = process.env.DREAMLAND_MOD_SECRET || 'dreamland-mod-dev-secret';
const POLL_MS = Number(process.env.DREAMLAND_MOD_POLL_MS || 4000);

async function getPool() {
  return mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 4 });
}

async function loadBlacklist(pool) {
  try {
    const [rows] = await pool.query('SELECT keyword FROM local_blacklist_keywords WHERE is_active = 1');
    return rows.map((r) => r.keyword).filter(Boolean);
  } catch {
    return [];
  }
}

async function agentModerate(blacklist, payload, mediaUrl) {
  const res = await fetch(`${AGENT_URL}/api/moderate/content`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Dreamland-Secret': AGENT_SECRET,
    },
    body: JSON.stringify({
      title: payload.title,
      description: payload.description,
      tags: payload.tags || [],
      blacklist,
      media_url: mediaUrl,
    }),
  });
  if (!res.ok) {
    const err = await res.text();
    throw new Error(`Moderation agent error: ${err}`);
  }
  return res.json();
}

async function finalizePost(pool, videoId, decision) {
  const conn = await pool.getConnection();
  try {
    const [[post]] = await conn.query('SELECT is_paid FROM post WHERE id = ?', [videoId]);
    if (decision === 'block') {
      await conn.query("UPDATE post SET appraisal_status='rejected', status=9 WHERE id=?", [videoId]);
      return 'rejected';
    }
    if (decision === 'review' || (post && Number(post.is_paid) === 1)) {
      await conn.query("UPDATE post SET appraisal_status='pending_review', status=9 WHERE id=?", [videoId]);
      return 'pending_review';
    }
    await conn.query("UPDATE post SET appraisal_status='active', status=10 WHERE id=?", [videoId]);
    return 'active';
  } finally {
    conn.release();
  }
}

async function processJob(pool, job) {
  await pool.query("UPDATE safety_scan_queue SET status='processing' WHERE id=?", [job.id]);
  const payload = job.text_payload ? JSON.parse(job.text_payload) : {};
  const blacklist = await loadBlacklist(pool);
  const result = await agentModerate(blacklist, payload, job.media_url);
  const decision = result.decision || (result.passed ? 'allow' : 'block');
  const passed = decision === 'allow';
  const resultStatus = await finalizePost(pool, job.video_id, passed ? 'allow' : decision);

  await pool.query(
    "UPDATE safety_scan_queue SET status='completed', result_status=?, processed_at=NOW(), failure_reason=? WHERE id=?",
    [
      resultStatus,
      passed ? null : JSON.stringify({
        agent: result.agent,
        decision,
        score: result.score,
        summary: result.summary,
        matches: result.matches,
        categories: result.categories,
        languages: result.languages,
      }),
      job.id,
    ]
  );
}

async function loop() {
  const pool = await getPool();
  console.log('[Dreamland Safety Worker] using AI agent at', AGENT_URL);
  setInterval(async () => {
    try {
      const [jobs] = await pool.query(
        "SELECT * FROM safety_scan_queue WHERE status='queued' ORDER BY id ASC LIMIT 5"
      );
      for (const job of jobs) {
        try {
          await processJob(pool, job);
          console.log(`[Safety] processed video ${job.video_id}`);
        } catch (err) {
          await pool.query(
            "UPDATE safety_scan_queue SET status='failed', failure_reason=? WHERE id=?",
            [String(err.message || err), job.id]
          );
        }
      }
    } catch (err) {
      console.error('[Safety Worker Error]', err.message);
    }
  }, POLL_MS);
}

loop();
