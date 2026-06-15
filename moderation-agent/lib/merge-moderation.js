'use strict';

const DECISION_RANK = { allow: 0, review: 1, block: 2 };

function pickStricter(a, b) {
  const ra = DECISION_RANK[a] ?? 1;
  const rb = DECISION_RANK[b] ?? 1;
  if (rb > ra) return b;
  if (ra > rb) return a;
  return rb >= ra ? b : a;
}

function mergeModeration(lexiconResult, geminiResult) {
  if (!geminiResult || geminiResult.skipped) {
    return {
      ...lexiconResult,
      providers: ['dreamland-lexicon'],
      gemini: geminiResult || null,
    };
  }

  const decision = pickStricter(lexiconResult.decision || 'allow', geminiResult.decision || 'allow');
  const score = Math.max(Number(lexiconResult.score) || 0, Number(geminiResult.score) || 0);
  const passed = decision === 'allow';
  const matches = [
    ...(lexiconResult.matches || []),
    ...(geminiResult.matches || []),
  ];
  const languages = [...new Set([
    ...(lexiconResult.languages || []),
    ...(geminiResult.languages || []),
  ])];

  let summary = lexiconResult.summary || '';
  if (geminiResult.summary) {
    summary = summary
      ? `${summary} · Gemini: ${geminiResult.summary}`
      : geminiResult.summary;
  }

  return {
    ...lexiconResult,
    passed,
    decision,
    score,
    summary,
    matches,
    languages,
    categories: {
      ...(lexiconResult.categories || {}),
      gemini: geminiResult.categories || [],
    },
    ghana_context: geminiResult.ghana_context || lexiconResult.ghana_context || '',
    multimodal: Boolean(geminiResult.multimodal),
    gemini: {
      model: geminiResult.model,
      media: geminiResult.media,
      provider: geminiResult.provider,
    },
    providers: ['dreamland-lexicon', 'google-gemini'],
    agent: 'dreamland-gemini-ghana',
  };
}

module.exports = { mergeModeration, pickStricter };
