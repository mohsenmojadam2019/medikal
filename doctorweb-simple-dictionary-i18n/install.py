#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import re
import shutil
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path
from typing import Any

CODE_EXTENSIONS = {".js", ".jsx", ".ts", ".tsx", ".json"}
SKIP_PARTS = {
    "node_modules",
    ".next",
    ".git",
    "dist",
    "build",
    "coverage",
    "__pycache__",
}
RTL_RE = re.compile(r"[\u0600-\u06FF]")
PLACEHOLDER_RE = re.compile(r"\{[A-Za-z0-9_]+\}")

LANGUAGE_CONTEXT = r"""'use client';

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import dictionary from '@/i18n/dictionary.generated.json';

const LanguageContext = createContext(null);

export const SUPPORTED_LANGUAGES = Object.freeze({
  fa: { code: 'fa', nativeName: 'فارسی', direction: 'rtl' },
  en: { code: 'en', nativeName: 'English', direction: 'ltr' },
  ar: { code: 'ar', nativeName: 'العربية', direction: 'rtl' },
});

const DEFAULT_LOCALE = 'fa';
const STORAGE_KEY = 'locale';
const SKIP_TAGS = new Set([
  'SCRIPT',
  'STYLE',
  'NOSCRIPT',
  'CODE',
  'PRE',
  'TEXTAREA',
  'SVG',
  'PATH',
]);

const TRANSLATABLE_ATTRIBUTES = [
  'placeholder',
  'title',
  'aria-label',
  'alt',
];

function normalizeLocale(value) {
  return Object.hasOwn(SUPPORTED_LANGUAGES, value)
    ? value
    : DEFAULT_LOCALE;
}

function interpolate(value, variables = {}) {
  return Object.entries(variables).reduce(
    (output, [key, replacement]) =>
      output.replaceAll(`{${key}}`, String(replacement ?? '')),
    String(value ?? ''),
  );
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function compileDynamicEntries(locale) {
  const selected = dictionary?.phrases?.[locale] || {};

  return Object.entries(selected)
    .filter(([source]) => /\{[A-Za-z0-9_]+\}/.test(source))
    .map(([source, target]) => {
      const names = [];
      let cursor = 0;
      let pattern = '^';

      source.replace(
        /\{([A-Za-z0-9_]+)\}/g,
        (match, name, offset) => {
          pattern += escapeRegExp(source.slice(cursor, offset));
          pattern += '(.+?)';
          names.push(name);
          cursor = offset + match.length;
          return match;
        },
      );

      pattern += escapeRegExp(source.slice(cursor));
      pattern += '$';

      return {
        source,
        target,
        names,
        regex: new RegExp(pattern, 'u'),
      };
    })
    .sort((a, b) => b.source.length - a.source.length);
}

function createTranslator(locale) {
  const phrases = dictionary?.phrases?.[locale] || {};
  const dynamicEntries = compileDynamicEntries(locale);

  const staticEntries = Object.entries(phrases)
    .filter(([source]) => !/\{[A-Za-z0-9_]+\}/.test(source))
    .filter(([source, target]) => source && target && source !== target)
    .sort(([a], [b]) => b.length - a.length);

  return (input) => {
    if (
      locale === 'fa' ||
      typeof input !== 'string' ||
      !input.trim()
    ) {
      return input;
    }

    const leading = input.match(/^\s*/u)?.[0] || '';
    const trailing = input.match(/\s*$/u)?.[0] || '';
    const core = input.trim();

    if (Object.hasOwn(phrases, core)) {
      return `${leading}${phrases[core]}${trailing}`;
    }

    for (const entry of dynamicEntries) {
      const match = core.match(entry.regex);
      if (!match) continue;

      const variables = {};
      entry.names.forEach((name, index) => {
        variables[name] = match[index + 1];
      });

      return `${leading}${interpolate(entry.target, variables)}${trailing}`;
    }

    let output = core;

    for (const [source, target] of staticEntries) {
      if (source.length < 3 || !output.includes(source)) continue;
      output = output.split(source).join(target);
    }

    return `${leading}${output}${trailing}`;
  };
}

function elementFor(node) {
  if (!node) return null;
  return node.nodeType === Node.ELEMENT_NODE
    ? node
    : node.parentElement;
}

function shouldSkip(node) {
  const element = elementFor(node);
  if (!element) return true;
  if (SKIP_TAGS.has(element.tagName)) return true;

  return Boolean(
    element.closest(
      '[data-i18n-skip], .notranslate, [translate="no"], [contenteditable="true"]',
    ),
  );
}

function GlobalDictionaryTranslator({ locale, revision }) {
  const translateText = useMemo(
    () => createTranslator(locale),
    [locale],
  );

  const originalTextRef = useRef(new WeakMap());
  const lastAppliedTextRef = useRef(new WeakMap());
  const originalAttributeRef = useRef(new WeakMap());
  const lastAppliedAttributeRef = useRef(new WeakMap());

  useEffect(() => {
    if (typeof document === 'undefined') return undefined;

    const originalText = originalTextRef.current;
    const lastAppliedText = lastAppliedTextRef.current;
    const originalAttributes = originalAttributeRef.current;
    const lastAppliedAttributes = lastAppliedAttributeRef.current;

    let applying = false;
    let frame = null;

    const translateTextNode = (node) => {
      if (!node?.nodeValue || shouldSkip(node)) return;

      const current = node.nodeValue;
      const lastApplied = lastAppliedText.get(node);

      if (!originalText.has(node) || current !== lastApplied) {
        originalText.set(node, current);
      }

      const source = originalText.get(node) ?? current;
      const next =
        locale === 'fa' ? source : translateText(source);

      if (next !== current) {
        applying = true;
        node.nodeValue = next;
        applying = false;
      }

      lastAppliedText.set(node, next);
    };

    const translateElement = (element) => {
      if (!(element instanceof Element) || shouldSkip(element)) {
        return;
      }

      let originals = originalAttributes.get(element);
      let lasts = lastAppliedAttributes.get(element);

      if (!originals) {
        originals = {};
        originalAttributes.set(element, originals);
      }

      if (!lasts) {
        lasts = {};
        lastAppliedAttributes.set(element, lasts);
      }

      for (const attribute of TRANSLATABLE_ATTRIBUTES) {
        if (!element.hasAttribute(attribute)) continue;

        const current = element.getAttribute(attribute) || '';

        if (
          !Object.hasOwn(originals, attribute) ||
          current !== lasts[attribute]
        ) {
          originals[attribute] = current;
        }

        const source = originals[attribute];
        const next =
          locale === 'fa' ? source : translateText(source);

        if (next !== current) {
          applying = true;
          element.setAttribute(attribute, next);
          applying = false;
        }

        lasts[attribute] = next;
      }

      if (
        element instanceof HTMLInputElement &&
        ['button', 'submit', 'reset'].includes(element.type)
      ) {
        const current = element.value;

        if (
          !Object.hasOwn(originals, 'value') ||
          current !== lasts.value
        ) {
          originals.value = current;
        }

        const source = originals.value;
        const next =
          locale === 'fa' ? source : translateText(source);

        if (next !== current) {
          applying = true;
          element.value = next;
          applying = false;
        }

        lasts.value = next;
      }
    };

    const translateTree = (root) => {
      if (!root) return;

      if (root.nodeType === Node.TEXT_NODE) {
        translateTextNode(root);
        return;
      }

      if (
        root.nodeType !== Node.ELEMENT_NODE &&
        root.nodeType !== Node.DOCUMENT_NODE
      ) {
        return;
      }

      if (root.nodeType === Node.ELEMENT_NODE) {
        translateElement(root);
      }

      const walker = document.createTreeWalker(
        root,
        NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
      );

      let node = walker.currentNode;

      while (node) {
        if (node.nodeType === Node.TEXT_NODE) {
          translateTextNode(node);
        } else if (node.nodeType === Node.ELEMENT_NODE) {
          translateElement(node);
        }

        node = walker.nextNode();
      }
    };

    const schedule = (root = document.body) => {
      if (frame) cancelAnimationFrame(frame);

      frame = requestAnimationFrame(() => {
        translateTree(root);
        frame = null;
      });
    };

    schedule(document.body);

    const observer = new MutationObserver((mutations) => {
      if (applying) return;

      for (const mutation of mutations) {
        if (mutation.type === 'characterData') {
          schedule(mutation.target);
          continue;
        }

        mutation.addedNodes.forEach((node) => schedule(node));
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });

    return () => {
      observer.disconnect();
      if (frame) cancelAnimationFrame(frame);
    };
  }, [locale, revision, translateText]);

  return null;
}

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState(DEFAULT_LOCALE);
  const [revision, setRevision] = useState(0);

  useEffect(() => {
    const saved = normalizeLocale(
      window.localStorage.getItem(STORAGE_KEY),
    );

    setLocale(saved);
    document.documentElement.lang = saved;
    document.documentElement.dir =
      SUPPORTED_LANGUAGES[saved].direction;
    document.body?.setAttribute(
      'dir',
      SUPPORTED_LANGUAGES[saved].direction,
    );
  }, []);

  const changeLanguage = useCallback((requestedLocale) => {
    const nextLocale = normalizeLocale(requestedLocale);

    setLocale(nextLocale);
    setRevision((value) => value + 1);

    window.localStorage.setItem(STORAGE_KEY, nextLocale);
    document.cookie =
      `locale=${nextLocale}; Path=/; Max-Age=31536000; SameSite=Lax`;

    const direction =
      SUPPORTED_LANGUAGES[nextLocale].direction;

    document.documentElement.lang = nextLocale;
    document.documentElement.dir = direction;
    document.body?.setAttribute('dir', direction);
  }, []);

  const t = useCallback(
    (keyOrPersian, fallback = '', variables = {}) => {
      const keyTranslations =
        dictionary?.keys?.[locale] || {};
      const phraseTranslations =
        dictionary?.phrases?.[locale] || {};

      const fallbackText = fallback || keyOrPersian;

      const value =
        keyTranslations[keyOrPersian] ??
        phraseTranslations[keyOrPersian] ??
        phraseTranslations[fallbackText] ??
        fallbackText;

      return interpolate(value, variables);
    },
    [locale],
  );

  const value = useMemo(
    () => ({
      locale,
      direction: SUPPORTED_LANGUAGES[locale].direction,
      languages: Object.values(SUPPORTED_LANGUAGES),
      t,
      changeLanguage,
      switchLanguage: changeLanguage,
    }),
    [locale, t, changeLanguage],
  );

  return (
    <LanguageContext.Provider value={value}>
      <GlobalDictionaryTranslator
        locale={locale}
        revision={revision}
      />
      {children}
    </LanguageContext.Provider>
  );
}

export function useLanguage() {
  const context = useContext(LanguageContext);

  if (!context) {
    throw new Error(
      'useLanguage must be used within LanguageProvider',
    );
  }

  return context;
}

export default LanguageContext;
"""

LANGUAGE_SWITCHER = r"""'use client';

import { Button, Dropdown } from 'antd';
import { GlobalOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

const LANGUAGE_OPTIONS = {
  fa: { label: 'فارسی', flag: '🇮🇷' },
  en: { label: 'English', flag: '🇬🇧' },
  ar: { label: 'العربية', flag: '🇸🇦' },
};

export default function LanguageSwitcher() {
  const { locale, changeLanguage } = useLanguage();

  const items = Object.entries(LANGUAGE_OPTIONS).map(
    ([key, item]) => ({
      key,
      label: `${item.flag} ${item.label}`,
    }),
  );

  return (
    <Dropdown
      trigger={['click']}
      menu={{
        items,
        selectable: true,
        selectedKeys: [locale],
        onClick: ({ key }) => changeLanguage(key),
      }}
    >
      <Button type="text" icon={<GlobalOutlined />}>
        {LANGUAGE_OPTIONS[locale]?.flag}{' '}
        {LANGUAGE_OPTIONS[locale]?.label}
      </Button>
    </Dropdown>
  );
}
"""


def fail(message: str) -> None:
    print(f"\nERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def normalize_phrase(value: str) -> str:
    value = value.replace("\\n", " ")
    value = value.replace("\\t", " ")
    value = re.sub(r"\s+", " ", value).strip()
    return value


def should_keep_phrase(value: str) -> bool:
    value = normalize_phrase(value)
    if not value or not RTL_RE.search(value):
        return False
    if len(value) > 500:
        return False
    if value.startswith(("http://", "https://", "/api/")):
        return False
    if value.count("{") != value.count("}"):
        return False
    return True


def scan_quoted_strings(text: str) -> list[str]:
    phrases: list[str] = []
    index = 0
    length = len(text)

    while index < length:
        char = text[index]

        if char not in {"'", '"', "`"}:
            index += 1
            continue

        quote = char
        index += 1
        buffer: list[str] = []
        placeholder_index = 0

        while index < length:
            char = text[index]

            if char == "\\" and index + 1 < length:
                buffer.append(text[index:index + 2])
                index += 2
                continue

            if quote == "`" and char == "$" and index + 1 < length and text[index + 1] == "{":
                depth = 1
                index += 2

                while index < length and depth:
                    if text[index] == "{":
                        depth += 1
                    elif text[index] == "}":
                        depth -= 1
                    index += 1

                buffer.append(f"{{v{placeholder_index}}}")
                placeholder_index += 1
                continue

            if char == quote:
                index += 1
                break

            buffer.append(char)
            index += 1

        phrase = normalize_phrase("".join(buffer))

        if should_keep_phrase(phrase):
            phrases.append(phrase)

    return phrases


def scan_jsx_text(text: str) -> list[str]:
    phrases: list[str] = []

    for match in re.finditer(r">([^<>{}]+)<", text, flags=re.S):
        phrase = normalize_phrase(match.group(1))
        if should_keep_phrase(phrase):
            phrases.append(phrase)

    return phrases


def scan_frontend(front: Path) -> list[str]:
    phrases: set[str] = set()

    for file in front.joinpath("src").rglob("*"):
        if not file.is_file() or file.suffix not in CODE_EXTENSIONS:
            continue

        if any(part in SKIP_PARTS for part in file.parts):
            continue

        relative = file.relative_to(front).as_posix()

        # Old duplicated locale trees are not the active source anymore.
        if relative.startswith(("src/app/en/", "src/app/ar/")):
            continue

        if relative.endswith((
            "src/lib/i18n/en.json",
            "src/lib/i18n/ar.json",
            "dictionary.generated.json",
        )):
            continue

        text = file.read_text(encoding="utf-8", errors="ignore")

        for phrase in scan_quoted_strings(text):
            phrases.add(phrase)

        if file.suffix in {".js", ".jsx", ".ts", ".tsx"}:
            for phrase in scan_jsx_text(text):
                phrases.add(phrase)

    return sorted(phrases, key=lambda item: (len(item), item))


def flatten_json(value: Any, prefix: str = "") -> dict[str, str]:
    output: dict[str, str] = {}

    if not isinstance(value, dict):
        return output

    for key, child in value.items():
        full_key = f"{prefix}.{key}" if prefix else str(key)

        if isinstance(child, dict):
            output.update(flatten_json(child, full_key))
        elif isinstance(child, str):
            output[full_key] = child

    return output


def load_existing_locale_json(front: Path) -> tuple[dict[str, dict[str, str]], dict[str, dict[str, str]]]:
    flattened: dict[str, dict[str, str]] = {}
    keys: dict[str, dict[str, str]] = {"fa": {}, "en": {}, "ar": {}}

    for locale in ("fa", "en", "ar"):
        path = front / f"src/lib/i18n/{locale}.json"

        if not path.exists():
            flattened[locale] = {}
            continue

        try:
            data = json.loads(path.read_text(encoding="utf-8"))
            flattened[locale] = flatten_json(data)
            keys[locale].update(flattened[locale])
        except Exception:
            flattened[locale] = {}

    phrase_seed = {"fa": {}, "en": {}, "ar": {}}

    common_keys = (
        set(flattened.get("fa", {}))
        & set(flattened.get("en", {}))
        & set(flattened.get("ar", {}))
    )

    for key in common_keys:
        source = normalize_phrase(flattened["fa"][key])
        if not source:
            continue

        phrase_seed["fa"][source] = source
        phrase_seed["en"][source] = flattened["en"][key]
        phrase_seed["ar"][source] = flattened["ar"][key]

    return keys, phrase_seed


def ollama_json(
    base_url: str,
    model: str,
    items: list[dict[str, str]],
    timeout: int = 900,
) -> dict[str, dict[str, str]]:
    prompt = f"""
/no_think
You are translating interface text for a professional healthcare website.

Translate every source string into:
- concise natural English
- clear Modern Standard Arabic

Rules:
1. Keep placeholders exactly unchanged, for example {{v0}}, {{name}}, {{count}}.
2. Preserve numbers, URLs, medical abbreviations, emojis and brand names.
3. Do not omit any item.
4. Return ONLY valid JSON in exactly this shape:
{{"items":[{{"id":"p1","en":"...","ar":"..."}}]}}

Input:
{json.dumps(items, ensure_ascii=False)}
""".strip()

    body = json.dumps({
        "model": model,
        "prompt": prompt,
        "stream": False,
        "format": "json",
        "options": {
            "temperature": 0.1,
            "num_predict": 8192,
        },
    }).encode("utf-8")

    request = urllib.request.Request(
        f"{base_url.rstrip('/')}/api/generate",
        data=body,
        method="POST",
        headers={"Content-Type": "application/json"},
    )

    with urllib.request.urlopen(request, timeout=timeout) as response:
        payload = json.loads(response.read().decode("utf-8"))

    raw = str(payload.get("response", "")).strip()
    raw = re.sub(r"<think>[\s\S]*?</think>", "", raw).strip()
    raw = raw.replace("```json", "").replace("```", "").strip()

    parsed = json.loads(raw)
    output: dict[str, dict[str, str]] = {}

    for item in parsed.get("items", []):
        item_id = item.get("id")
        en = item.get("en")
        ar = item.get("ar")

        if isinstance(item_id, str) and isinstance(en, str) and isinstance(ar, str):
            output[item_id] = {"en": en.strip(), "ar": ar.strip()}

    return output


def check_ollama(base_url: str, model: str) -> None:
    try:
        with urllib.request.urlopen(
            f"{base_url.rstrip('/')}/api/tags",
            timeout=10,
        ) as response:
            payload = json.loads(response.read().decode("utf-8"))
    except Exception as error:
        fail(f"Ollama is not reachable at {base_url}: {error}")

    names = {
        item.get("name") or item.get("model")
        for item in payload.get("models", [])
    }

    if model not in names:
        fail(
            f'Ollama model "{model}" is not installed. '
            f"Installed models: {', '.join(sorted(name for name in names if name))}"
        )


def translate_missing(
    phrases: list[str],
    phrase_dictionary: dict[str, dict[str, str]],
    base_url: str,
    model: str,
    batch_size: int,
) -> None:
    missing = [
        phrase
        for phrase in phrases
        if not phrase_dictionary["en"].get(phrase)
        or not phrase_dictionary["ar"].get(phrase)
    ]

    print(f"Persian/RTL phrases found: {len(phrases)}")
    print(f"Already translated from existing dictionaries: {len(phrases) - len(missing)}")
    print(f"New phrases to translate: {len(missing)}")

    if not missing:
        return

    check_ollama(base_url, model)

    for offset in range(0, len(missing), batch_size):
        batch = missing[offset:offset + batch_size]
        items = [
            {"id": f"p{index}", "source": phrase}
            for index, phrase in enumerate(batch)
        ]

        translated: dict[str, dict[str, str]] | None = None
        last_error: Exception | None = None

        for attempt in range(1, 4):
            try:
                translated = ollama_json(
                    base_url=base_url,
                    model=model,
                    items=items,
                )
                break
            except Exception as error:
                last_error = error
                print(
                    f"Batch {offset + 1}-{offset + len(batch)} "
                    f"attempt {attempt} failed: {error}"
                )
                time.sleep(1)

        if translated is None:
            fail(f"Translation failed: {last_error}")

        for index, phrase in enumerate(batch):
            key = f"p{index}"
            value = translated.get(key)

            if not value:
                fail(f"Ollama missed this phrase: {phrase}")

            placeholders = PLACEHOLDER_RE.findall(phrase)

            for placeholder in placeholders:
                if (
                    placeholder not in value["en"]
                    or placeholder not in value["ar"]
                ):
                    fail(
                        f"Placeholder {placeholder} was not preserved: {phrase}"
                    )

            phrase_dictionary["fa"][phrase] = phrase
            phrase_dictionary["en"][phrase] = value["en"]
            phrase_dictionary["ar"][phrase] = value["ar"]

        completed = min(offset + len(batch), len(missing))
        print(f"Translated {completed}/{len(missing)}")


def clean_previous_hotfixes(front: Path) -> None:
    candidates = [
        front / "src/components/platform/AppProviders.js",
        front / "src/app/layout.js",
    ]

    for file in candidates:
        if not file.exists():
            continue

        text = file.read_text(encoding="utf-8")
        original = text

        text = re.sub(
            r"^import\s+AutoTranslate\s+from\s+['\"][^'\"]+['\"];\s*$",
            "",
            text,
            flags=re.M,
        )
        text = re.sub(
            r"^import\s+I18nBridge\s+from\s+['\"][^'\"]+['\"];\s*$",
            "",
            text,
            flags=re.M,
        )
        text = re.sub(r"\s*<AutoTranslate\s*/>\s*", "\n", text)
        text = re.sub(r"\s*<I18nBridge>\s*", "\n", text)
        text = re.sub(r"\s*</I18nBridge>\s*", "\n", text)

        if text != original:
            file.write_text(text, encoding="utf-8")


def main() -> None:
    parser = argparse.ArgumentParser(
        description=(
            "Build one complete FA/EN/AR dictionary from all frontend text "
            "and switch the visible page instantly when the user changes language."
        )
    )
    parser.add_argument("front_root")
    parser.add_argument("--model", default="qwen3.5:4b")
    parser.add_argument(
        "--ollama-url",
        default="http://127.0.0.1:11434",
    )
    parser.add_argument("--batch-size", type=int, default=12)
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    front = Path(args.front_root).expanduser().resolve()

    if not (front / "package.json").exists():
        fail("package.json was not found in the frontend root.")

    if not (front / "src").exists():
        fail("src directory was not found.")

    context_path = front / "src/lib/context/LanguageContext.js"
    switcher_path = front / "src/components/shared/LanguageSwitcher.js"

    if not context_path.parent.exists():
        fail("src/lib/context directory was not found.")

    phrases = scan_frontend(front)
    keys, phrase_dictionary = load_existing_locale_json(front)

    for phrase in phrases:
        phrase_dictionary["fa"].setdefault(phrase, phrase)

    print(f"Frontend: {front}")
    print(f"Dictionary phrases: {len(phrases)}")
    print("Method: one dictionary + instant body replacement on language change")
    print("No route changes. No t() conversion of every component.")

    if args.dry_run:
        print("Dry run complete. No files changed.")
        return

    translate_missing(
        phrases=phrases,
        phrase_dictionary=phrase_dictionary,
        base_url=args.ollama_url,
        model=args.model,
        batch_size=max(1, args.batch_size),
    )

    timestamp = dt.datetime.now().strftime("%Y%m%d-%H%M%S")
    backup = front.parent / f"{front.name}-simple-i18n-backup-{timestamp}"

    print(f"Creating backup: {backup}")
    shutil.copytree(front / "src", backup / "src")
    shutil.copy2(front / "package.json", backup / "package.json")

    clean_previous_hotfixes(front)

    dictionary_path = front / "src/i18n/dictionary.generated.json"
    dictionary_path.parent.mkdir(parents=True, exist_ok=True)
    dictionary_path.write_text(
        json.dumps(
            {
                "phrases": phrase_dictionary,
                "keys": keys,
                "meta": {
                    "generated_at": timestamp,
                    "source_phrase_count": len(phrases),
                },
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    context_path.parent.mkdir(parents=True, exist_ok=True)
    context_path.write_text(LANGUAGE_CONTEXT, encoding="utf-8")

    switcher_path.parent.mkdir(parents=True, exist_ok=True)
    switcher_path.write_text(LANGUAGE_SWITCHER, encoding="utf-8")

    report = {
        "installed_at": timestamp,
        "backup": str(backup),
        "phrases": len(phrases),
        "dictionary": str(dictionary_path),
        "model": args.model,
    }

    (front / "SIMPLE_DICTIONARY_I18N.json").write_text(
        json.dumps(report, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print()
    print("Installed successfully.")
    print(f"Backup: {backup}")
    print(f"Dictionary: {dictionary_path}")
    print()
    print("Now clear Next.js cache and rebuild:")
    print("  rm -rf .next")
    print("  npm run build")


if __name__ == "__main__":
    main()
