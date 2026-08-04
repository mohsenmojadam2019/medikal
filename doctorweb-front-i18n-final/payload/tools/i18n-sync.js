#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const http = require('http');
const https = require('https');

function parseArgs(argv) {
  const args = {
    root: process.cwd(),
    model: '',
    ollamaUrl: 'http://127.0.0.1:11434',
    batchSize: 20,
  };

  for (let i = 2; i < argv.length; i += 1) {
    if (argv[i] === '--root') args.root = argv[++i];
    else if (argv[i] === '--model') args.model = argv[++i];
    else if (argv[i] === '--ollama-url') args.ollamaUrl = argv[++i];
    else if (argv[i] === '--batch-size') args.batchSize = Number(argv[++i] || 20);
  }
  return args;
}

const args = parseArgs(process.argv);
const root = path.resolve(args.root);
const srcRoot = path.join(root, 'src');
const outputPath = path.join(srcRoot, 'i18n', 'messages.generated.json');
const PERSIAN_RE = /[\u0600-\u06FF]/;
const CODE_EXTENSIONS = new Set(['.js', '.jsx', '.ts', '.tsx']);

let parser;
try {
  parser = require('next/dist/compiled/babel/parser');
} catch {
  console.error('Next.js Babel parser not found. Run in the frontend project with node_modules installed.');
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
    else if (CODE_EXTENSIONS.has(path.extname(entry.name))) output.push(absolute);
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
    if (Array.isArray(value)) {
      for (const item of value) {
        if (item && typeof item === 'object') walk(item, ancestors, visitor);
      }
    } else if (value && typeof value === 'object' && typeof value.type === 'string') {
      walk(value, ancestors, visitor);
    }
  }
}

function normalizePhrase(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function keyForPhrase(phrase) {
  const digest = crypto.createHash('sha1').update(phrase).digest('hex').slice(0, 14);
  return `front.auto_${digest}`;
}

function shouldSkipString(node, ancestors) {
  const parent = ancestors[ancestors.length - 1];
  const grandparent = ancestors[ancestors.length - 2];
  if (!parent) return false;
  if (['ImportDeclaration', 'ExportAllDeclaration', 'ExportNamedDeclaration'].includes(parent.type)) return true;
  if (['ObjectProperty', 'ObjectMethod'].includes(parent.type) && parent.key === node && !parent.computed) return true;
  if (parent.type === 'CallExpression' && parent.callee?.type === 'Identifier' && parent.callee.name === 't') return true;
  if (parent.type === 'Directive' || grandparent?.type === 'Directive') return true;
  return false;
}

function functionName(node, ancestors) {
  if (['FunctionDeclaration', 'FunctionExpression'].includes(node.type) && node.id?.name) return node.id.name;
  const parent = ancestors[ancestors.length - 1];
  if (['ArrowFunctionExpression', 'FunctionExpression'].includes(node.type) &&
      parent?.type === 'VariableDeclarator' && parent.id?.type === 'Identifier') {
    return parent.id.name;
  }
  return '';
}

function isHookableFunction(node, ancestors) {
  const name = functionName(node, ancestors);
  return /^[A-Z]/.test(name) || /^use[A-Z0-9_]/.test(name);
}

function findHookableFunction(ancestors) {
  for (let i = ancestors.length - 1; i >= 0; i -= 1) {
    const node = ancestors[i];
    if (['FunctionDeclaration', 'FunctionExpression', 'ArrowFunctionExpression'].includes(node.type) &&
        node.body?.type === 'BlockStatement' && isHookableFunction(node, ancestors.slice(0, i))) {
      return node;
    }
  }
  return null;
}

function applyReplacements(code, replacements) {
  const unique = [];
  const seen = new Set();
  for (const item of replacements) {
    const signature = `${item.start}:${item.end}:${item.value}`;
    if (seen.has(signature)) continue;
    seen.add(signature);
    unique.push(item);
  }
  unique.sort((a, b) => b.start - a.start || b.end - a.end);
  let output = code;
  let lastStart = Infinity;
  for (const item of unique) {
    if (item.end > lastStart) continue;
    output = output.slice(0, item.start) + item.value + output.slice(item.end);
    lastStart = item.start;
  }
  return output;
}

function ensureImportAndHooks(code, ast, replacements, hookFunctions) {
  if (!hookFunctions.size) return;
  if (!code.includes("from '@/lib/context/LanguageContext'") &&
      !code.includes('from "@/lib/context/LanguageContext"')) {
    const imports = ast.program.body.filter((node) => node.type === 'ImportDeclaration');
    const insertAt = imports.length
      ? imports[imports.length - 1].end
      : (ast.program.directives?.at(-1)?.end || 0);
    replacements.push({
      start: insertAt,
      end: insertAt,
      value: "\nimport { useLanguage } from '@/lib/context/LanguageContext';",
    });
  }

  for (const fn of hookFunctions) {
    const bodySource = code.slice(fn.body.start, fn.body.end);
    if (/const\s*\{[^}]*\bt\b[^}]*\}\s*=\s*useLanguage\s*\(\s*\)/.test(bodySource)) continue;
    replacements.push({
      start: fn.body.start + 1,
      end: fn.body.start + 1,
      value: '\n  const { t } = useLanguage();',
    });
  }
}

function collectPhrase(node, code, ancestors) {
  if (node.type === 'JSXText') return normalizePhrase(node.value);
  if (node.type === 'StringLiteral' && !shouldSkipString(node, ancestors)) return normalizePhrase(node.value);
  if (node.type === 'TemplateLiteral') {
    let fallback = '';
    node.quasis.forEach((quasi, index) => {
      fallback += quasi.value.cooked || quasi.value.raw || '';
      if (index < node.expressions.length) fallback += `{v${index}}`;
    });
    return normalizePhrase(fallback);
  }
  return '';
}

function extractFile(filename, phraseMap) {
  const code = fs.readFileSync(filename, 'utf8');
  const ast = parseCode(code, filename);
  walk(ast.program, [], (node, ancestors) => {
    const phrase = collectPhrase(node, code, ancestors);
    if (phrase && PERSIAN_RE.test(phrase)) phraseMap.set(keyForPhrase(phrase), phrase);
  });
}

function codemodFile(filename) {
  const code = fs.readFileSync(filename, 'utf8');
  const ast = parseCode(code, filename);
  const isClient = ast.program.directives?.some((d) => d.value?.value === 'use client') || /^['"]use client['"];?/.test(code.trimStart());
  if (!isClient) return { changed: false, converted: 0 };

  const replacements = [];
  const hookFunctions = new Set();
  let converted = 0;

  walk(ast.program, [], (node, ancestors) => {
    const phrase = collectPhrase(node, code, ancestors);
    if (!phrase || !PERSIAN_RE.test(phrase)) return;
    const fn = findHookableFunction(ancestors);
    if (!fn) return;
    const key = keyForPhrase(phrase);

    if (node.type === 'JSXText') {
      const leading = node.value.match(/^\s*/)?.[0] || '';
      const trailing = node.value.match(/\s*$/)?.[0] || '';
      replacements.push({
        start: node.start,
        end: node.end,
        value: `${leading}{t(${JSON.stringify(key)}, ${JSON.stringify(phrase)})}${trailing}`,
      });
    } else if (node.type === 'StringLiteral') {
      const parent = ancestors[ancestors.length - 1];
      replacements.push({
        start: node.start,
        end: node.end,
        value: parent?.type === 'JSXAttribute' && parent.value === node
          ? `{t(${JSON.stringify(key)}, ${JSON.stringify(phrase)})}`
          : `t(${JSON.stringify(key)}, ${JSON.stringify(phrase)})`,
      });
    } else if (node.type === 'TemplateLiteral') {
      let fallback = '';
      const variables = [];
      node.quasis.forEach((quasi, index) => {
        fallback += quasi.value.cooked || quasi.value.raw || '';
        if (index < node.expressions.length) {
          const name = `v${index}`;
          fallback += `{${name}}`;
          variables.push(`${name}: ${code.slice(node.expressions[index].start, node.expressions[index].end)}`);
        }
      });
      replacements.push({
        start: node.start,
        end: node.end,
        value: `t(${JSON.stringify(key)}, ${JSON.stringify(normalizePhrase(fallback))}, { ${variables.join(', ')} })`,
      });
    }

    hookFunctions.add(fn);
    converted += 1;
  });

  ensureImportAndHooks(code, ast, replacements, hookFunctions);
  const output = applyReplacements(code, replacements);
  if (output !== code) {
    parseCode(output, filename);
    fs.writeFileSync(filename, output);
  }
  return { changed: output !== code, converted };
}

function requestJson(urlString, options = {}) {
  return new Promise((resolve, reject) => {
    const url = new URL(urlString);
    const transport = url.protocol === 'https:' ? https : http;
    const body = options.body ? Buffer.from(JSON.stringify(options.body)) : null;
    const request = transport.request({
      method: options.method || 'GET',
      hostname: url.hostname,
      port: url.port,
      path: `${url.pathname}${url.search}`,
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json', 'Content-Length': body.length } : {}),
      },
      timeout: options.timeout || 120000,
    }, (response) => {
      const chunks = [];
      response.on('data', (chunk) => chunks.push(chunk));
      response.on('end', () => {
        const text = Buffer.concat(chunks).toString('utf8');
        if (response.statusCode < 200 || response.statusCode >= 300) {
          reject(new Error(`HTTP ${response.statusCode}: ${text.slice(0, 500)}`));
          return;
        }
        try { resolve(JSON.parse(text)); }
        catch { reject(new Error(`Invalid JSON response: ${text.slice(0, 500)}`)); }
      });
    });
    request.on('timeout', () => request.destroy(new Error('Request timed out')));
    request.on('error', reject);
    if (body) request.write(body);
    request.end();
  });
}

async function selectModel(baseUrl, preferred) {
  const tags = await requestJson(`${baseUrl}/api/tags`, { timeout: 10000 });
  const models = (tags.models || []).map((item) => item.name || item.model).filter(Boolean);
  if (preferred) {
    const exact = models.find((model) => model === preferred || model.split(':')[0] === preferred.split(':')[0]);
    if (!exact) throw new Error(`Model ${preferred} is not installed. Installed: ${models.join(', ')}`);
    return exact;
  }
  const priorities = [/qwen.*(7b|8b)/i, /qwen.*(3b|4b)/i, /qwen/i, /llama.*8b/i, /llama/i, /gemma/i];
  for (const pattern of priorities) {
    const selected = models.find((model) => pattern.test(model));
    if (selected) return selected;
  }
  if (models.length) return models[0];
  throw new Error('No Ollama model is installed.');
}

function parseModelJson(text) {
  const cleaned = String(text || '')
    .replace(/<think>[\s\S]*?<\/think>/gi, '')
    .replace(/```json/gi, '')
    .replace(/```/g, '')
    .trim();
  const start = cleaned.indexOf('[');
  const end = cleaned.lastIndexOf(']');
  if (start === -1 || end === -1 || end <= start) throw new Error(`No JSON array: ${cleaned.slice(0, 500)}`);
  return JSON.parse(cleaned.slice(start, end + 1));
}

async function translateBatch(baseUrl, model, items) {
  const prompt = `/no_think\nTranslate every Persian healthcare UI phrase into concise natural English and Modern Standard Arabic. Preserve placeholders such as {v0}, {name}, {count} exactly. Return ONLY JSON array with schema [{"id":"...","en":"...","ar":"..."}]. Every id exactly once.\nInput:\n${JSON.stringify(items.map((item) => ({ id: item.key, fa: item.phrase })))}`;
  const response = await requestJson(`${baseUrl}/api/generate`, {
    method: 'POST',
    timeout: 600000,
    body: {
      model,
      prompt,
      stream: false,
      format: 'json',
      options: { temperature: 0.1, num_predict: 8192 },
    },
  });
  const parsed = parseModelJson(response.response);
  const map = new Map(parsed
    .filter((item) => item && typeof item.id === 'string' && typeof item.en === 'string' && typeof item.ar === 'string')
    .map((item) => [item.id, item]));

  for (const item of items) {
    if (!map.has(item.key)) throw new Error(`Missing translated key ${item.key}`);
    const translated = map.get(item.key);
    for (const placeholder of item.phrase.match(/\{[^}]+\}/g) || []) {
      if (!translated.en.includes(placeholder) || !translated.ar.includes(placeholder)) {
        throw new Error(`Placeholder ${placeholder} lost in ${item.key}`);
      }
    }
  }
  return map;
}

async function main() {
  if (!fs.existsSync(srcRoot)) throw new Error(`src not found: ${srcRoot}`);
  const files = listFiles(srcRoot);
  const phraseMap = new Map();
  for (const filename of files) extractFile(filename, phraseMap);

  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  let messages = { fa: {}, en: {}, ar: {} };
  if (fs.existsSync(outputPath)) {
    try { messages = JSON.parse(fs.readFileSync(outputPath, 'utf8')); }
    catch { messages = { fa: {}, en: {}, ar: {} }; }
  }
  messages.fa ||= {};
  messages.en ||= {};
  messages.ar ||= {};
  for (const [key, phrase] of phraseMap) messages.fa[key] = phrase;

  const missing = [...phraseMap.entries()]
    .filter(([key, phrase]) => !messages.en[key] || !messages.ar[key] || messages.en[key] === phrase || messages.ar[key] === phrase)
    .map(([key, phrase]) => ({ key, phrase }));

  console.log(`Persian UI phrases: ${phraseMap.size}`);
  console.log(`Missing translations: ${missing.length}`);

  if (missing.length) {
    let model;
    try { model = await selectModel(args.ollamaUrl, args.model); }
    catch (error) {
      console.error(error.message);
      console.error('Start Ollama and install a multilingual model, then rerun.');
      process.exit(3);
    }
    console.log(`Ollama model: ${model}`);

    for (let offset = 0; offset < missing.length; offset += args.batchSize) {
      const batch = missing.slice(offset, offset + args.batchSize);
      let translated = null;
      let lastError = null;
      for (let attempt = 1; attempt <= 3; attempt += 1) {
        try {
          translated = await translateBatch(args.ollamaUrl, model, batch);
          break;
        } catch (error) {
          lastError = error;
          console.error(`Batch ${offset + 1}-${offset + batch.length}, attempt ${attempt}: ${error.message}`);
        }
      }
      if (!translated) throw lastError;
      for (const item of batch) {
        const value = translated.get(item.key);
        messages.en[item.key] = value.en.trim();
        messages.ar[item.key] = value.ar.trim();
      }
      fs.writeFileSync(outputPath, JSON.stringify(messages, null, 2));
      console.log(`Translated ${Math.min(offset + batch.length, missing.length)}/${missing.length}`);
    }
  }

  for (const locale of ['fa', 'en', 'ar']) {
    for (const key of Object.keys(messages[locale])) {
      if (key.startsWith('front.auto_') && !phraseMap.has(key)) delete messages[locale][key];
    }
  }
  fs.writeFileSync(outputPath, JSON.stringify(messages, null, 2));

  let changedFiles = 0;
  let converted = 0;
  for (const filename of files) {
    const result = codemodFile(filename);
    if (result.changed) changedFiles += 1;
    converted += result.converted;
  }

  const report = {
    generated_at: new Date().toISOString(),
    total_phrases: phraseMap.size,
    safely_converted_to_t: converted,
    changed_files: changedFiles,
  };
  fs.writeFileSync(path.join(root, '.doctorweb-i18n-sync.json'), JSON.stringify(report, null, 2));
  console.log(`Safe t() conversions: ${converted} in ${changedFiles} files`);
  console.log('Module-level and server-rendered phrases are covered by the generated dictionary and I18nBridge.');
}

main().catch((error) => {
  console.error(error.stack || error.message);
  process.exit(1);
});
