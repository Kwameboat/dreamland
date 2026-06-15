'use strict';

const { mergeModeration } = require('./merge-moderation');

const GHANA_TAGS = ['Accra', 'Kumasi', 'Tema', 'Ghana', 'Twi', 'Afrobeats', 'Dreamland'];
const GENRE_TAGS = {
  comedy: ['funny', 'comedy', 'skit', 'laugh'],
  music: ['music', 'afrobeats', 'dance', 'viral'],
  lifestyle: ['lifestyle', 'vlog', 'daily', 'ghana'],
  sports: ['sports', 'football', 'fitness', 'game'],
  education: ['learn', 'tips', 'howto', 'education'],
};

function clamp(n, min, max) {
  return Math.max(min, Math.min(max, n));
}

function scorePost(post, prefs = {}) {
  const views = Number(post.total_view || post.views || 0);
  const likes = Number(post.total_like || post.likes || 0);
  const comments = Number(post.total_comment || post.comments || 0);
  const created = Number(post.created_at || post.createdAt || 0);
  const ageHours = created ? (Date.now() / 1000 - created) / 3600 : 999;
  const recency = clamp(1 - ageHours / (24 * 7), 0, 1);
  const engagement = Math.log1p(views) * 0.22 + Math.log1p(likes) * 0.48 + Math.log1p(comments) * 0.18;
  let genreBoost = 0;
  if (prefs.category_id && String(post.category_id || '') === String(prefs.category_id)) genreBoost += 0.35;
  if (prefs.account_type === 'creator' && Number(post.is_paid) === 1) genreBoost += 0.08;
  const jitter = Math.random() * 0.04;
  const score = engagement + recency * 0.42 + genreBoost + jitter;
  const reasons = [];
  if (recency > 0.7) reasons.push('Fresh drop');
  if (likes > 20) reasons.push('Trending engagement');
  if (genreBoost > 0.3) reasons.push('Matches your genre');
  if (!reasons.length) reasons.push('Picked for you');
  return { score, reasons, label: reasons[0] };
}

function rankPosts(posts, prefs = {}) {
  return [...posts]
    .map((post) => {
      const ranked = scorePost(post, prefs);
      return { ...post, ai_score: ranked.score, ai_label: ranked.label, ai_reasons: ranked.reasons };
    })
    .sort((a, b) => b.ai_score - a.ai_score);
}

function fallbackCaptions(payload = {}) {
  const title = String(payload.title || '').trim();
  const description = String(payload.description || '').trim();
  const genre = String(payload.genre || '').trim().toLowerCase();
  const base = title || description || 'Dreamland moment';
  const genreTags = GENRE_TAGS[genre] || ['dreamland', 'reels'];
  const hashtags = [...new Set([...genreTags, ...GHANA_TAGS.slice(0, 3), 'DreamlandReels'])]
    .slice(0, 6)
    .map((t) => `#${t.replace(/\s+/g, '')}`);
  const hooks = [
    `${base} — watch till the end on Dreamland`,
    `New on Dreamland: ${base}`,
    `${base} | Play · Watch · Earn`,
  ];
  return {
    captions: hooks,
    hashtags,
    hook: hooks[0],
    tone: 'premium-ghana',
    provider: 'dreamland-lexicon-fallback',
  };
}

function createDreamlandAi(options = {}) {
  const moderator = options.moderator;
  const gemini = options.gemini || null;
  const blockThreshold = options.blockThreshold ?? 70;
  const reviewThreshold = options.reviewThreshold ?? 40;

  async function runLexicon(payload = {}) {
    return moderator.moderate({
      text: payload.text,
      title: payload.title,
      description: payload.description,
      tags: payload.tags,
      blacklist: payload.blacklist,
      media_url: payload.media_url,
    });
  }

  async function moderateWithGemini(payload = {}) {
    const lex = await runLexicon(payload);
    if (!gemini?.isConfigured?.()) {
      return { ...lex, providers: ['dreamland-lexicon'], primary_provider: 'dreamland-lexicon' };
    }
    try {
      const gem = await gemini.moderateContent(payload);
      const merged = mergeModeration(lex, gem);
      merged.primary_provider = 'google-gemini';
      if (merged.score >= blockThreshold) merged.decision = 'block';
      else if (merged.score >= reviewThreshold && merged.decision === 'allow') merged.decision = 'review';
      merged.passed = merged.decision === 'allow';
      return merged;
    } catch (err) {
      return {
        ...lex,
        gemini_error: err.message,
        providers: ['dreamland-lexicon'],
        primary_provider: 'dreamland-lexicon',
      };
    }
  }

  async function checkText(payload = {}) {
    const result = await moderateWithGemini(payload);
    return {
      ok: result.passed !== false && result.decision !== 'block',
      allowed: result.decision === 'allow',
      decision: result.decision,
      score: result.score,
      summary: result.summary,
      matches: result.matches || [],
      languages: result.languages || [],
      provider: result.primary_provider || 'dreamland-ghana-ai',
      gemini: result.gemini || null,
      multimodal: result.multimodal || false,
    };
  }

  async function suggestCaptions(payload = {}) {
    if (gemini?.isConfigured?.()) {
      try {
        return await gemini.suggestCaptions(payload);
      } catch (err) {
        return { ...fallbackCaptions(payload), gemini_error: err.message };
      }
    }
    return fallbackCaptions(payload);
  }

  function status() {
    const geminiStatus = gemini?.status?.() || { configured: false };
    return {
      ok: true,
      agent: geminiStatus.configured ? 'dreamland-gemini-ghana' : 'dreamland-ghana-ai',
      primary_provider: geminiStatus.configured ? 'google-gemini' : 'dreamland-lexicon',
      capabilities: [
        geminiStatus.configured ? 'gemini_multimodal_moderation' : 'lexicon_moderation',
        'ghana_multilingual_safety',
        'smart_feed_ranking',
        'caption_assist',
        'signup_safety',
        'content_safety_queue',
      ],
      locales: moderator.lexicons?.locales?.map((l) => l.label) || [],
      gemini: geminiStatus,
    };
  }

  return {
    rankPosts,
    suggestCaptions,
    checkText,
    moderateWithGemini,
    status,
    scorePost,
  };
}

module.exports = { createDreamlandAi, rankPosts, suggestCaptions, scorePost };
