'use strict';

require('./lib/load-env').loadEnv();

const express = require('express');
const cors = require('cors');
const config = require('./config');
const { GhanaModerator, CATEGORY_LABELS } = require('./lib/ghana-moderator');
const { createDreamlandAi } = require('./lib/dreamland-ai');
const { createGeminiClient } = require('./lib/gemini-client');

const moderator = new GhanaModerator({
  blockThreshold: config.blockThreshold,
  reviewThreshold: config.reviewThreshold,
});

const gemini = createGeminiClient({
  apiKey: config.geminiApiKey,
  model: config.geminiModel,
  enabled: config.useGemini,
});

const dreamlandAi = createDreamlandAi({
  moderator,
  gemini,
  blockThreshold: config.blockThreshold,
  reviewThreshold: config.reviewThreshold,
});

function requireSecret(req, res, next) {
  if (req.headers['x-dreamland-secret'] !== config.internalSecret) {
    return res.status(403).json({ ok: false, message: 'Forbidden' });
  }
  next();
}

const app = express();
app.use(cors({ origin: config.corsOrigins, credentials: true }));
app.use(express.json({ limit: '2mb' }));

app.get('/health', (_req, res) => {
  const geminiStatus = gemini.status();
  res.json({
    ok: true,
    service: 'dreamland-moderation-agent',
    agent: geminiStatus.configured ? 'dreamland-gemini-ghana' : 'dreamland-ghana-moderator',
    ai: dreamlandAi.status(),
    gemini: geminiStatus,
    locales: moderator.lexicons.locales.map((l) => l.label),
    blockThreshold: config.blockThreshold,
    reviewThreshold: config.reviewThreshold,
    geminiEnabled: config.useGemini,
    geminiConfigured: geminiStatus.configured,
    uptime: process.uptime(),
  });
});

app.get('/api/ai/status', (_req, res) => {
  res.json({ ok: true, ...dreamlandAi.status() });
});

app.post('/api/ai/check-text', requireSecret, async (req, res) => {
  try {
    const result = await dreamlandAi.checkText({
      text: req.body.text,
      title: req.body.title,
      description: req.body.description,
      tags: req.body.tags,
      blacklist: req.body.blacklist,
    });
    res.json({ ok: true, ...result });
  } catch (err) {
    res.status(500).json({ ok: false, message: err.message });
  }
});

app.post('/api/ai/rank-feed', requireSecret, (req, res) => {
  const posts = Array.isArray(req.body.posts) ? req.body.posts : [];
  const ranked = dreamlandAi.rankPosts(posts, req.body.preferences || {});
  res.json({ ok: true, posts: ranked, provider: gemini.isConfigured() ? 'google-gemini+dreamland' : 'dreamland-ai' });
});

app.post('/api/ai/caption-suggest', requireSecret, async (req, res) => {
  try {
    const suggestion = await dreamlandAi.suggestCaptions(req.body || {});
    res.json({ ok: true, ...suggestion });
  } catch (err) {
    res.status(500).json({ ok: false, message: err.message });
  }
});

app.get('/api/config', (_req, res) => {
  res.json({
    categories: CATEGORY_LABELS,
    locales: moderator.lexicons.locales,
    blockThreshold: config.blockThreshold,
    reviewThreshold: config.reviewThreshold,
    gemini: gemini.status(),
  });
});

app.post('/api/moderate/text', requireSecret, async (req, res) => {
  try {
    const result = await dreamlandAi.moderateWithGemini({
      text: req.body.text,
      title: req.body.title,
      description: req.body.description,
      tags: req.body.tags,
      blacklist: req.body.blacklist,
    });
    res.json({ ok: true, ...result });
  } catch (err) {
    res.status(500).json({ ok: false, message: err.message });
  }
});

app.post('/api/moderate/content', requireSecret, async (req, res) => {
  try {
    const result = await dreamlandAi.moderateWithGemini({
      text: req.body.text,
      title: req.body.title,
      description: req.body.description,
      tags: req.body.tags,
      blacklist: req.body.blacklist,
      media_url: req.body.media_url,
    });
    res.json({ ok: true, ...result });
  } catch (err) {
    res.status(500).json({ ok: false, message: err.message });
  }
});

app.post('/api/reload-lexicons', requireSecret, (_req, res) => {
  moderator.reload();
  res.json({ ok: true, message: 'Lexicons reloaded' });
});

app.listen(config.port, () => {
  const g = gemini.status();
  console.log(`Dreamland Moderation Agent on http://localhost:${config.port}`);
  console.log(`Ghana languages: ${moderator.lexicons.locales.map((l) => l.label).join(', ')}`);
  if (g.configured) {
    console.log(`Gemini multimodal AI: ${g.model} (text + image + video)`);
  } else {
    console.log('Gemini: not configured — add GEMINI_API_KEY to moderation-agent/.env');
    console.log('Running lexicon-only Ghana moderation until Gemini key is set.');
  }
});
