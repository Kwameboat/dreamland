'use strict';

require('./lib/load-env').loadEnv();

/**
 * Polls safety_scan_queue and runs Dreamland Gemini + Ghana moderation agent.
 * Run alongside server.js: npm run worker
 */
const mysql = require('mysql2/promise');
const config = require('./config');
const { GhanaModerator } = require('./lib/ghana-moderator');

const AGENT_URL = `http://127.0.0.1:${config.port}`;
const moderator = new GhanaModerator({
  blockThreshold: config.blockThreshold,
  reviewThreshold: config.reviewThreshold,
});

let cachedBlacklist = null;

async function getPool() {
  return mysql.createPool({ ...config.db, waitForConnections: true, connectionLimit: 4 });
}

async function loadBlacklist(pool) {
  if (cachedBlacklist) return cachedBlacklist;
  try {
    const [rows] = await pool.query(
      'SELECT keyword FROM local_blacklist_keywords WHERE is_active = 1'
    );
    cachedBlacklist = rows.map((r) => r.keyword).filter(Boolean);
  } catch {
    cachedBlacklist = [];
  }
  return cachedBlacklist;
}

async function agentModerate(payload, blacklist, mediaUrl) {
  try {
    const res = await fetch(`${AGENT_URL}/api/moderate/content`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Dreamland-Secret': config.internalSecret,
      },
      body: JSON.stringify({
        title: payload.title,
        description: payload.description,
        tags: payload.tags || [],
        blacklist,
        media_url: mediaUrl,
      }),
    });
    if (res.ok) return res.json();
  } catch (_) {
    /* fall through to in-process */
  }

  return moderator.moderate({
    title: payload.title,
    description: payload.description,
    tags: payload.tags || [],
    blacklist,
    media_url: mediaUrl,
  });
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
  const result = await agentModerate(payload, blacklist, job.media_url);

  const decision = result.decision || (result.passed ? 'allow' : 'block');
  const passed = decision === 'allow';
  const resultStatus = await finalizePost(pool, job.video_id, passed ? 'allow' : decision);

  await pool.query(
    "UPDATE safety_scan_queue SET status='completed', result_status=?, processed_at=NOW(), failure_reason=? WHERE id=?",
    [
      resultStatus,
      passed && decision === 'allow' ? null : JSON.stringify({
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
  console.log('[Dreamland Moderation Worker] polling safety_scan_queue');
  setInterval(async () => {
    try {
      const [jobs] = await pool.query(
        "SELECT * FROM safety_scan_queue WHERE status='queued' ORDER BY id ASC LIMIT 5"
      );
      for (const job of jobs) {
        try {
          await processJob(pool, job);
          console.log(`[Moderation] video ${job.video_id} → processed`);
        } catch (err) {
          await pool.query(
            "UPDATE safety_scan_queue SET status='failed', failure_reason=? WHERE id=?",
            [String(err.message || err), job.id]
          );
        }
      }
    } catch (err) {
      console.error('[Moderation Worker Error]', err.message);
    }
  }, config.pollMs);
}

loop();
