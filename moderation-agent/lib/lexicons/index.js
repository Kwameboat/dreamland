'use strict';

const fs = require('fs');
const path = require('path');

const LEXICON_DIR = __dirname;

function loadJson(name) {
  const file = path.join(LEXICON_DIR, name);
  if (!fs.existsSync(file)) return null;
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

let cached = null;

function loadLexicons() {
  if (cached) return cached;

  const locales = loadJson('locales.json') || [];
  const patterns = loadJson('patterns.json') || [];
  const terms = [];

  for (const locale of locales) {
    const file = loadJson(`${locale.code}.json`);
    if (!file || !Array.isArray(file.terms)) continue;
    for (const term of file.terms) {
      terms.push({
        ...term,
        locale: locale.code,
        localeLabel: locale.label || locale.code,
      });
    }
  }

  cached = { locales, terms, patterns };
  return cached;
}

function reloadLexicons() {
  cached = null;
  return loadLexicons();
}

module.exports = {
  loadLexicons,
  reloadLexicons,
};
