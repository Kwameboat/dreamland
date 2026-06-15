'use strict';

const SUBSTITUTIONS = {
  '@': 'a', '4': 'a', '8': 'b', '3': 'e', '1': 'i', '!': 'i', '0': 'o', '$': 's', '5': 's', '7': 't',
};

const GH_VARIANTS = [
  ['kwasia', 'kwasea', 'kwasi aa', 'kwasia', 'kwa sia'],
  ['gyimii', 'gyimi', 'gyimie', 'gyimy', 'gymii'],
  ['ashawo', 'ashaw', 'ashaw0', 'a shawo'],
  ['aboa', 'ab0a', 'aboaa'],
  ['chale', 'charle', 'chalee'],
  ['momo', 'mo mo', 'mobile money'],
];

/**
 * Normalize text for Ghana multilingual moderation matching.
 * @param {string} text
 * @returns {{ raw: string, normalized: string, compact: string }}
 */
function normalizeText(text) {
  let raw = String(text || '');
  raw = raw.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
  raw = raw.replace(/[\u200B-\u200D\uFEFF]/g, '');

  let normalized = raw.toLowerCase();
  normalized = normalized.replace(/[_*~`"'[\]{}|\\/<>]+/g, ' ');
  normalized = normalized.replace(/([a-z])\1{2,}/gi, (_, ch) => ch + ch);

  for (const [from, to] of Object.entries(SUBSTITUTIONS)) {
    normalized = normalized.split(from).join(to);
  }

  normalized = normalized.replace(/\s+/g, ' ').trim();
  const compact = normalized.replace(/[^a-z0-9\u00C0-\u024F\u1E00-\u1EFF]+/gi, '');

  return { raw, normalized, compact };
}

/**
 * Build fuzzy regex allowing spaces/dots between letters (obfuscation).
 * @param {string} term
 */
function termPattern(term) {
  const parts = String(term).trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return null;
  const chunks = parts.map((part) => {
    const chars = part.split('').map((ch) => ch.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    return chars.join('[\\s._\\-*]*');
  });
  return new RegExp(`(?:^|[^a-z0-9])(${chunks.join('[\\s._\\-*]+')})(?:[^a-z0-9]|$)`, 'iu');
}

function expandGhanaVariants(text) {
  let out = text;
  for (const group of GH_VARIANTS) {
    for (const variant of group.slice(1)) {
      if (out.includes(variant)) {
        out = out.replace(new RegExp(variant.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi'), group[0]);
      }
    }
  }
  return out;
}

module.exports = {
  normalizeText,
  termPattern,
  expandGhanaVariants,
};
