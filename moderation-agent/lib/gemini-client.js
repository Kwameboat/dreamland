'use strict';

const GHANA_LOCALES = [
  'English (Ghana)',
  'Twi / Akan',
  'Ga',
  'Ewe',
  'Hausa',
  'Dagbani',
  'Ghana Pidgin',
];

const MODERATION_JSON_SCHEMA = `
Return ONLY valid JSON (no markdown):
{
  "decision": "allow" | "review" | "block",
  "score": 0-100,
  "summary": "one sentence",
  "languages_detected": ["Twi", "English"],
  "categories": ["profanity"],
  "ghana_context": "brief note on cultural/code-switch context"
}`;

function parseJsonResponse(text) {
  const raw = String(text || '').trim();
  const fenced = raw.match(/```(?:json)?\s*([\s\S]*?)```/i);
  const candidate = (fenced ? fenced[1] : raw).trim();
  try {
    return JSON.parse(candidate);
  } catch {
    const start = candidate.indexOf('{');
    const end = candidate.lastIndexOf('}');
    if (start >= 0 && end > start) {
      return JSON.parse(candidate.slice(start, end + 1));
    }
    throw new Error('Gemini returned non-JSON response');
  }
}

function guessMime(url, contentType) {
  if (contentType && !contentType.includes('text/html')) return contentType.split(';')[0].trim();
  const lower = String(url || '').toLowerCase();
  if (/\.(jpg|jpeg)$/.test(lower)) return 'image/jpeg';
  if (/\.png$/.test(lower)) return 'image/png';
  if (/\.webp$/.test(lower)) return 'image/webp';
  if (/\.gif$/.test(lower)) return 'image/gif';
  if (/\.mp4$/.test(lower)) return 'video/mp4';
  if (/\.webm$/.test(lower)) return 'video/webm';
  if (/\.mov$/.test(lower)) return 'video/quicktime';
  return 'application/octet-stream';
}

async function fetchMediaInlinePart(mediaUrl, maxBytes = 18 * 1024 * 1024) {
  if (!mediaUrl || !String(mediaUrl).startsWith('http')) return null;
  try {
    const res = await fetch(mediaUrl, { signal: AbortSignal.timeout(20000) });
    if (!res.ok) return null;
    const mime = guessMime(mediaUrl, res.headers.get('content-type'));
    if (!mime.startsWith('image/') && !mime.startsWith('video/')) return null;
    const buf = Buffer.from(await res.arrayBuffer());
    if (buf.length > maxBytes) {
      return { skipped: true, reason: 'media_too_large', mimeType: mime, bytes: buf.length };
    }
    return {
      inlineData: {
        mimeType: mime,
        data: buf.toString('base64'),
      },
    };
  } catch (err) {
    return { skipped: true, reason: err.message || 'media_fetch_failed' };
  }
}

function createGeminiClient(config = {}) {
  const apiKey = config.apiKey || process.env.GEMINI_API_KEY || process.env.GOOGLE_API_KEY || '';
  const modelName = config.model || process.env.DREAMLAND_GEMINI_MODEL || 'gemini-2.0-flash';
  const enabled = Boolean(config.enabled !== false && apiKey);
  let genAI = null;
  let model = null;

  if (enabled) {
    try {
      const { GoogleGenerativeAI } = require('@google/generative-ai');
      genAI = new GoogleGenerativeAI(apiKey);
      model = genAI.getGenerativeModel({
        model: modelName,
        generationConfig: {
          temperature: 0.2,
          maxOutputTokens: 1024,
          responseMimeType: 'application/json',
        },
      });
    } catch (err) {
      console.warn('[Gemini] SDK init failed:', err.message);
    }
  }

  function isConfigured() {
    return Boolean(model);
  }

  function status() {
    return {
      configured: isConfigured(),
      model: modelName,
      provider: 'google-gemini',
      multimodal: true,
      locales: GHANA_LOCALES,
    };
  }

  async function generateJson(promptParts) {
    if (!model) throw new Error('Gemini not configured — set GEMINI_API_KEY in moderation-agent/.env');
    const result = await model.generateContent(promptParts);
    const text = result.response?.text?.() || '';
    return parseJsonResponse(text);
  }

  async function moderateContent(input = {}) {
    const textBlob = [
      input.title,
      input.description,
      input.text,
      ...(input.tags || []),
    ].filter(Boolean).join('\n');

    const prompt = [
      'You are Dreamland\'s Ghana-focused multimodal content safety AI.',
      'Analyze ALL text and any attached image/video for policy violations.',
      `Understand code-switching across: ${GHANA_LOCALES.join(', ')}.`,
      'Flag: profanity, sexual content, hate speech, violence, fraud/scams (incl. MoMo fraud), harassment, self-harm.',
      'Be strict on scams targeting Ghanaian users. Allow benign cultural humor.',
      MODERATION_JSON_SCHEMA,
      '',
      '--- CONTENT ---',
      `Title: ${input.title || '(none)'}`,
      `Description: ${input.description || '(none)'}`,
      `Text: ${input.text || textBlob || '(none)'}`,
      `Tags: ${(input.tags || []).join(', ') || '(none)'}`,
      `Media URL: ${input.media_url || '(none)'}`,
    ].join('\n');

    const parts = [{ text: prompt }];
    let mediaMeta = null;
    if (input.media_url) {
      const mediaPart = await fetchMediaInlinePart(input.media_url);
      if (mediaPart?.inlineData) {
        parts.push(mediaPart);
        mediaMeta = { analyzed: true, mimeType: mediaPart.inlineData.mimeType };
      } else if (mediaPart?.skipped) {
        mediaMeta = { analyzed: false, ...mediaPart };
      }
    }

    const parsed = await generateJson(parts);
    const decision = ['allow', 'review', 'block'].includes(parsed.decision) ? parsed.decision : 'review';
    const score = Math.max(0, Math.min(100, Number(parsed.score) || 0));

    return {
      passed: decision === 'allow',
      decision,
      score,
      summary: parsed.summary || 'Gemini multimodal scan complete',
      languages: parsed.languages_detected || [],
      categories: parsed.categories || [],
      ghana_context: parsed.ghana_context || '',
      provider: 'google-gemini',
      model: modelName,
      multimodal: Boolean(mediaMeta?.analyzed),
      media: mediaMeta,
      matches: (parsed.categories || []).map((cat) => ({
        type: 'gemini',
        term: cat,
        category: String(cat).toLowerCase().replace(/\s+/g, '_'),
        categoryLabel: cat,
        severity: decision === 'block' ? 3 : 2,
        locale: 'gh',
        localeLabel: 'Gemini Ghana AI',
        score,
      })),
    };
  }

  async function checkText(input = {}) {
    return moderateContent(input);
  }

  async function suggestCaptions(input = {}) {
    const prompt = [
      'You are Dreamland\'s Ghana creator AI assistant (Gemini).',
      'Write premium short-video captions for a Ghanaian audience.',
      'Mix English and light Ghana Pidgin where natural. Include Ghana-relevant hashtags.',
      'Return ONLY JSON:',
      '{"captions":["...","...","..."],"hashtags":["#Dreamland","#Accra"],"hook":"best hook line","tone":"premium-ghana"}',
      '',
      `Title: ${input.title || ''}`,
      `Description: ${input.description || ''}`,
      `Genre: ${input.genre || ''}`,
    ].join('\n');

    const parsed = await generateJson([{ text: prompt }]);
    return {
      captions: parsed.captions || [],
      hashtags: parsed.hashtags || [],
      hook: parsed.hook || parsed.captions?.[0] || '',
      tone: parsed.tone || 'premium-ghana',
      provider: 'google-gemini',
      model: modelName,
    };
  }

  return {
    isConfigured,
    status,
    moderateContent,
    checkText,
    suggestCaptions,
    fetchMediaInlinePart,
  };
}

module.exports = { createGeminiClient, GHANA_LOCALES, parseJsonResponse };
