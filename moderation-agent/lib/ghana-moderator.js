'use strict';

const { loadLexicons } = require('./lexicons');
const { normalizeText, termPattern, expandGhanaVariants } = require('./normalize');

const CATEGORY_WEIGHTS = {
  profanity: 18,
  sexual: 28,
  hate: 30,
  violence: 32,
  fraud: 26,
  self_harm: 35,
  harassment: 16,
};

const CATEGORY_LABELS = {
  profanity: 'Profanity / insults',
  sexual: 'Sexual content',
  hate: 'Hate speech',
  violence: 'Violence / threats',
  fraud: 'Scams / fraud',
  self_harm: 'Self-harm',
  harassment: 'Harassment',
};

/**
 * Dreamland inbuilt moderation agent — Ghana multilingual rules engine.
 */
class GhanaModerator {
  constructor(options = {}) {
    this.blockThreshold = options.blockThreshold ?? 70;
    this.reviewThreshold = options.reviewThreshold ?? 40;
    this.lexicons = loadLexicons();
  }

  reload() {
    const { reloadLexicons } = require('./lexicons');
    this.lexicons = reloadLexicons();
  }

  /**
   * @param {object} input
   * @param {string} [input.text]
   * @param {string} [input.title]
   * @param {string} [input.description]
   * @param {string[]} [input.tags]
   * @param {string[]} [input.blacklist]
   * @param {string} [input.media_url]
   */
  moderate(input = {}) {
    const parts = [
      input.title,
      input.description,
      input.text,
      ...(input.tags || []),
    ].filter(Boolean);

    const blob = parts.join(' ').trim();
    const { normalized, compact } = normalizeText(blob);
    const expanded = expandGhanaVariants(normalized);

    const matches = [];
    const categories = {};
    const localesHit = new Set();

    const scoreTerm = (termDef, source, locale, localeLabel) => {
      const pattern = termPattern(termDef.term || termDef.keyword || source);
      if (!pattern) return;
      if (pattern.test(expanded) || pattern.test(normalized) || expanded.includes(String(termDef.term).toLowerCase())) {
        const severity = Number(termDef.severity || 1);
        const category = termDef.category || 'profanity';
        const weight = (CATEGORY_WEIGHTS[category] || 15) * severity;
        matches.push({
          type: 'term',
          term: termDef.term || source,
          category,
          categoryLabel: CATEGORY_LABELS[category] || category,
          severity,
          locale: locale || termDef.locale || 'gh',
          localeLabel: localeLabel || termDef.localeLabel || 'Ghana',
          score: weight,
        });
        categories[category] = (categories[category] || 0) + weight;
        localesHit.add(locale || 'gh');
      }
    };

    for (const termDef of this.lexicons.terms) {
      scoreTerm(termDef, termDef.term, termDef.locale, termDef.localeLabel);
    }

    for (const keyword of input.blacklist || []) {
      scoreTerm({ term: keyword, category: 'profanity', severity: 2 }, keyword, 'db', 'Custom blacklist');
    }

    for (const pat of this.lexicons.patterns) {
      const regex = new RegExp(pat.pattern, 'iu');
      if (regex.test(expanded) || regex.test(normalized)) {
        const severity = Number(pat.severity || 2);
        const category = pat.category || 'profanity';
        const weight = (CATEGORY_WEIGHTS[category] || 15) * severity;
        matches.push({
          type: 'pattern',
          term: pat.label || pat.pattern,
          category,
          categoryLabel: CATEGORY_LABELS[category] || category,
          severity,
          locale: 'pattern',
          localeLabel: 'Pattern rules',
          score: weight,
        });
        categories[category] = (categories[category] || 0) + weight;
      }
    }

    if (input.media_url) {
      const urlNorm = normalizeText(input.media_url).normalized;
      for (const keyword of input.blacklist || []) {
        if (urlNorm.includes(String(keyword).toLowerCase())) {
          matches.push({
            type: 'media_url',
            term: keyword,
            category: 'fraud',
            categoryLabel: CATEGORY_LABELS.fraud,
            severity: 2,
            locale: 'url',
            localeLabel: 'Media URL',
            score: 40,
          });
          categories.fraud = (categories.fraud || 0) + 40;
        }
      }
    }

    const score = matches.reduce((sum, m) => sum + m.score, 0);
    const hasCritical = matches.some((m) => Number(m.severity) >= 3 && ['violence', 'self_harm', 'sexual', 'hate', 'fraud'].includes(m.category));

    let decision = 'allow';
    if (score >= this.blockThreshold || hasCritical) decision = 'block';
    else if (score >= this.reviewThreshold) decision = 'review';

    const passed = decision === 'allow';

    return {
      passed,
      decision,
      score,
      blockThreshold: this.blockThreshold,
      reviewThreshold: this.reviewThreshold,
      matches,
      categories,
      languages: [...localesHit],
      summary: this.buildSummary(decision, matches, score),
      agent: 'dreamland-ghana-moderator',
      agentVersion: '1.0.0',
      textLength: blob.length,
      normalizedPreview: normalized.slice(0, 120),
    };
  }

  buildSummary(decision, matches, score) {
    if (decision === 'allow') {
      return 'Content passed Dreamland AI moderation — no significant policy issues detected in Ghana language scan.';
    }
    const top = matches
      .sort((a, b) => b.score - a.score)
      .slice(0, 3)
      .map((m) => `${m.categoryLabel} (${m.localeLabel}: "${m.term}")`)
      .join('; ');
    if (decision === 'block') {
      return `Blocked (score ${score}). Flagged: ${top}`;
    }
    return `Needs human review (score ${score}). Flagged: ${top}`;
  }
}

module.exports = { GhanaModerator, CATEGORY_LABELS };
