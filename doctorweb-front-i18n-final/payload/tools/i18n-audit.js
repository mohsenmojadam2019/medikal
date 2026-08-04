#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

function args(argv) {
  const output = { root: process.cwd() };
  for (let i = 2; i < argv.length; i += 1) {
    if (argv[i] === '--root') output.root = argv[++i];
  }
  return output;
}

const root = path.resolve(args(process.argv).root);
const srcRoot = path.join(root, 'src');
const messagesPath = path.join(srcRoot, 'i18n', 'messages.generated.json');
const PERSIAN_RE = /[\u0600-\u06FF]/;
const EXTENSIONS = new Set(['.js', '.jsx', '.ts', '.tsx']);

let parser;
try {
  parser = require('next/dist/compiled/babel/parser');
} catch {
  console.error('Next.js Babel parser not found.');
  process.exit(2);
}

function parseCode(code, filename) {
  return parser.parse(code, {
    sourceType: 'module',
    sourceFilename: filename,
    plugins: [
      'jsx',
      'typescript',
      'classProperties',
      'objectRestSpread',
      'optionalChaining',
      'nullishCoalescingOperator',
      'dynamicImport',
      'topLevelAwait',
      'decorators-legacy',
    ],
  });
}

function listFiles(directory) {
  const output = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    if (['node_modules', '.next', '.git'].includes(entry.name)) continue;
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) output.push(...listFiles(absolute));
    else if (EXTENSIONS.has(path.extname(entry.name))) output.push(absolute);
  }
  return output;
}

function walk(node, ancestors, visitor) {
  if (!node || typeof node !== 'object') return;
  if (typeof node.type === 'string') {
    visitor(node, ancestors);
    ancestors = [...ancestors, node];
  }
  for (const [key, value] of Object.entries(node)) {
    if (['loc', 'start', 'end', 'extra', 'tokens', 'comments'].includes(key)) continue;
    if (Array.isArray(value)) value.forEach((item) => walk(item, ancestors, visitor));
    else if (value && typeof value === 'object') walk(value, ancestors, visitor);
  }
}

function normalize(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function keyFor(phrase) {
  return `front.auto_${crypto.createHash('sha1').update(phrase).digest('hex').slice(0, 14)}`;
}

function skipString(node, ancestors) {
  const parent = ancestors[ancestors.length - 1];
  const grandparent = ancestors[ancestors.length - 2];
  if (!parent) return false;
  if (['ImportDeclaration', 'ExportAllDeclaration', 'ExportNamedDeclaration'].includes(parent.type)) return true;
  if (['ObjectProperty', 'ObjectMethod'].includes(parent.type) && parent.key === node && !parent.computed) return true;
  if (parent.type === 'CallExpression' && parent.callee?.type === 'Identifier' && parent.callee.name === 't') return true;
  if (parent.type === 'Directive' || grandparent?.type === 'Directive') return true;
  return false;
}

if (!fs.existsSync(messagesPath)) {
  console.error(`Missing ${messagesPath}`);
  process.exit(1);
}

const messages = JSON.parse(fs.readFileSync(messagesPath, 'utf8'));
const phrases = new Map();
const syntaxErrors = [];

for (const filename of listFiles(srcRoot)) {
  const code = fs.readFileSync(filename, 'utf8');
  let ast;
  try {
    ast = parseCode(code, filename);
  } catch (error) {
    syntaxErrors.push({
      file: path.relative(root, filename),
      line: error.loc?.line,
      column: error.loc?.column,
      message: error.message,
    });
    continue;
  }

  walk(ast.program, [], (node, ancestors) => {
    let phrase = '';
    if (node.type === 'JSXText') phrase = normalize(node.value);
    else if (node.type === 'StringLiteral' && !skipString(node, ancestors)) phrase = normalize(node.value);
    else if (node.type === 'TemplateLiteral') {
      let fallback = '';
      node.quasis.forEach((quasi, index) => {
        fallback += quasi.value.cooked || quasi.value.raw || '';
        if (index < node.expressions.length) fallback += `{v${index}}`;
      });
      phrase = normalize(fallback);
    }
    if (phrase && PERSIAN_RE.test(phrase)) phrases.set(keyFor(phrase), phrase);
  });
}

const missing = [];
for (const [key, phrase] of phrases) {
  const en = messages.en?.[key];
  const ar = messages.ar?.[key];
  if (!en || !ar || en === phrase || ar === phrase) {
    missing.push({ key, phrase, en: en || null, ar: ar || null });
  }
}

const translated = phrases.size - missing.length;
const coverage = phrases.size === 0 ? 100 : Number(((translated / phrases.size) * 100).toFixed(2));
const report = {
  generated_at: new Date().toISOString(),
  total_static_persian_phrases: phrases.size,
  translated_phrases: translated,
  missing_phrases: missing.length,
  coverage_percent: coverage,
  syntax_errors: syntaxErrors,
  missing,
};
const reportPath = path.join(root, '.doctorweb-i18n-coverage.json');
fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));

console.log(`Static phrase coverage: ${coverage}% (${translated}/${phrases.size})`);
console.log(`Coverage report: ${reportPath}`);

if (syntaxErrors.length) {
  console.error(`Syntax errors: ${syntaxErrors.length}`);
  syntaxErrors.slice(0, 20).forEach((item) => {
    console.error(`${item.file}:${item.line || '?'}:${item.column || '?'} ${item.message}`);
  });
  process.exit(1);
}

if (missing.length) {
  console.error('Untranslated phrases found. Run: npm run i18n:sync');
  missing.slice(0, 30).forEach((item) => console.error(`- ${item.phrase}`));
  process.exit(1);
}
