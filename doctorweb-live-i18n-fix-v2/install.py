#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import re
import shutil
import sys
from pathlib import Path

CODE_EXTENSIONS = {".js", ".jsx", ".ts", ".tsx"}

LANGUAGE_SWITCHER = """'use client';

import { Button, Dropdown } from 'antd';
import { GlobalOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

const languages = {
  fa: { label: 'فارسی', flag: '🇮🇷' },
  en: { label: 'English', flag: '🇬🇧' },
  ar: { label: 'العربية', flag: '🇸🇦' },
};

export default function LanguageSwitcher() {
  const { locale, changeLanguage } = useLanguage();

  const items = Object.entries(languages).map(([key, item]) => ({
    key,
    label: `${item.flag} ${item.label}`,
  }));

  return (
    <Dropdown
      trigger={['click']}
      placement={locale === 'en' ? 'bottomLeft' : 'bottomRight'}
      menu={{
        items,
        selectable: true,
        selectedKeys: [locale],
        onClick: ({ key }) => changeLanguage(key),
      }}
    >
      <Button type="text" icon={<GlobalOutlined />}>
        {languages[locale]?.flag} {languages[locale]?.label}
      </Button>
    </Dropdown>
  );
}
"""

AUTO_TRANSLATE = """'use client';

import { useEffect } from 'react';
import { useLanguage } from '@/lib/context/LanguageContext';
import { translateVisibleText } from './dictionary';

const skipTags = new Set([
  'SCRIPT', 'STYLE', 'NOSCRIPT', 'CODE', 'PRE', 'SVG', 'PATH'
]);

const textOriginal = new WeakMap();
const attributeOriginal = new WeakMap();
const attributes = ['placeholder', 'title', 'aria-label', 'alt'];

function translateNode(root, locale) {
  if (!root || typeof Node === 'undefined') return;

  const translateText = (node) => {
    const parent = node.parentElement;
    if (!parent || skipTags.has(parent.tagName)) return;
    if (parent.closest('[data-no-auto-translate="true"]')) return;
    if (!node.nodeValue?.trim()) return;

    if (!textOriginal.has(node)) {
      textOriginal.set(node, node.nodeValue);
    }

    const source = textOriginal.get(node);
    node.nodeValue =
      locale === 'fa' ? source : translateVisibleText(source, locale);
  };

  const translateAttributes = (element) => {
    if (!(element instanceof Element)) return;
    if (element.closest('[data-no-auto-translate="true"]')) return;

    let originals = attributeOriginal.get(element);
    if (!originals) {
      originals = {};
      attributeOriginal.set(element, originals);
    }

    attributes.forEach((name) => {
      if (!element.hasAttribute(name)) return;
      if (!(name in originals)) {
        originals[name] = element.getAttribute(name) || '';
      }
      const source = originals[name];
      element.setAttribute(
        name,
        locale === 'fa' ? source : translateVisibleText(source, locale)
      );
    });
  };

  if (root.nodeType === Node.TEXT_NODE) {
    translateText(root);
    return;
  }

  const walker = document.createTreeWalker(
    root,
    NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT
  );

  let current = walker.currentNode;
  while (current) {
    if (current.nodeType === Node.TEXT_NODE) translateText(current);
    if (current.nodeType === Node.ELEMENT_NODE) translateAttributes(current);
    current = walker.nextNode();
  }
}

export default function AutoTranslate() {
  const { locale } = useLanguage();

  useEffect(() => {
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'en' ? 'ltr' : 'rtl';
    document.body?.setAttribute('dir', locale === 'en' ? 'ltr' : 'rtl');

    let frame = requestAnimationFrame(() => {
      translateNode(document.body, locale);
    });

    const observer = new MutationObserver((mutations) => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        for (const mutation of mutations) {
          if (mutation.type === 'characterData') {
            translateNode(mutation.target, locale);
          } else {
            mutation.addedNodes.forEach((node) => translateNode(node, locale));
          }
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });

    return () => {
      observer.disconnect();
      cancelAnimationFrame(frame);
    };
  }, [locale]);

  return null;
}
"""

def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)

def ensure_import(text: str) -> str:
    import_line = "import { useLanguage } from '@/lib/context/LanguageContext';"
    if import_line in text:
        return text

    lines = text.splitlines()
    insert_at = 1 if lines and lines[0].strip() in ("'use client';", '"use client";') else 0

    while insert_at < len(lines) and (
        lines[insert_at].startswith("import ") or not lines[insert_at].strip()
    ):
        insert_at += 1

    lines.insert(insert_at, import_line)
    return "\n".join(lines) + ("\n" if text.endswith("\n") else "")

def remove_locale_from_destructuring(params: str) -> tuple[str, bool]:
    parts = [part.strip() for part in params.split(",")]
    kept = []
    removed = False

    for part in parts:
        if re.fullmatch(r"locale\s*=\s*['\"](?:fa|en|ar)['\"]", part):
            removed = True
            continue
        if part == "locale":
            removed = True
            continue
        if part:
            kept.append(part)

    return ", ".join(kept), removed

def patch_component(text: str) -> tuple[str, bool]:
    original = text
    if "'use client'" not in text and '"use client"' not in text:
        return text, False

    signatures = []

    pattern = re.compile(
        r"(export\s+default\s+function\s+[A-Za-z0-9_]+\s*)"
        r"\(\s*\{([^}]*)\}\s*\)\s*\{"
    )

    def replace_function(match: re.Match) -> str:
        prefix = match.group(1)
        params = match.group(2)
        new_params, removed = remove_locale_from_destructuring(params)
        if not removed:
            return match.group(0)

        signatures.append(True)
        if new_params:
            return f"{prefix}({{{new_params}}}) {{\n  const {{ locale }} = useLanguage();"
        return f"{prefix}() {{\n  const {{ locale }} = useLanguage();"

    text = pattern.sub(replace_function, text)

    arrow_pattern = re.compile(
        r"(const\s+[A-Za-z0-9_]+\s*=\s*)"
        r"\(\s*\{([^}]*)\}\s*\)\s*=>\s*\{"
    )

    def replace_arrow(match: re.Match) -> str:
        prefix = match.group(1)
        params = match.group(2)
        new_params, removed = remove_locale_from_destructuring(params)
        if not removed:
            return match.group(0)

        signatures.append(True)
        if new_params:
            return f"{prefix}({{{new_params}}}) => {{\n  const {{ locale }} = useLanguage();"
        return f"{prefix}() => {{\n  const {{ locale }} = useLanguage();"

    text = arrow_pattern.sub(replace_arrow, text)

    if signatures:
        text = ensure_import(text)

    return text, text != original

def patch_jsx_locale_props(text: str) -> tuple[str, bool]:
    original = text
    text = re.sub(r"\s+locale\s*=\s*['\"](?:fa|en|ar)['\"]", "", text)
    text = re.sub(
        r"\s+locale\s*=\s*\{\s*['\"](?:fa|en|ar)['\"]\s*\}",
        "",
        text,
    )
    return text, text != original

def patch_app_providers(file: Path) -> bool:
    text = file.read_text(encoding="utf-8")
    original = text

    auto_import = "import AutoTranslate from '@/components/i18n/AutoTranslate';"
    if auto_import not in text:
        anchor = "import MobileBottomNav from './MobileBottomNav';"
        if anchor in text:
            text = text.replace(anchor, anchor + "\n" + auto_import, 1)

    if "<AutoTranslate />" not in text:
        anchor = "            <MobileBottomNav />"
        if anchor in text:
            text = text.replace(
                anchor,
                "            <AutoTranslate />\n" + anchor,
                1,
            )

    if text != original:
        file.write_text(text, encoding="utf-8")
        return True
    return False

def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("front_root")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    front = Path(args.front_root).expanduser().resolve()
    if not (front / "package.json").exists():
        fail("Frontend package.json not found.")

    src = front / "src"
    if not src.exists():
        fail("src directory not found.")

    timestamp = dt.datetime.now().strftime("%Y%m%d-%H%M%S")
    backup = front.parent / f"{front.name}-live-i18n-backup-{timestamp}"

    candidates = []
    for file in src.rglob("*"):
        if file.is_file() and file.suffix in CODE_EXTENSIONS:
            text = file.read_text(encoding="utf-8", errors="ignore")
            if (
                re.search(r"locale\s*=\s*['\"](?:fa|en|ar)['\"]", text)
                or re.search(r"locale\s*=\s*\{\s*['\"](?:fa|en|ar)['\"]\s*\}", text)
            ):
                candidates.append(file)

    print(f"Frontend: {front}")
    print(f"Backup:   {backup}")
    print(f"Fixed-locale files found: {len(candidates)}")
    print("Main fix: components will read locale from LanguageContext instead of a hardcoded locale prop.")

    if args.dry_run:
        for file in candidates[:30]:
            print(" -", file.relative_to(front))
        print("Dry run complete. No files changed.")
        return

    backup.mkdir(parents=True, exist_ok=False)
    backup_src = backup / "src"
    shutil.copytree(src, backup_src)

    changed = []

    for file in src.rglob("*"):
        if not file.is_file() or file.suffix not in CODE_EXTENSIONS:
            continue

        text = file.read_text(encoding="utf-8", errors="ignore")
        text, changed_component = patch_component(text)
        text, changed_props = patch_jsx_locale_props(text)

        if changed_component or changed_props:
            file.write_text(text, encoding="utf-8")
            changed.append(str(file.relative_to(front)))

    package_root = Path(__file__).resolve().parent
    supplied = package_root / "files"

    for source in supplied.rglob("*"):
        if source.is_dir():
            continue
        relative = source.relative_to(supplied)
        target = front / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)
        if str(relative) not in changed:
            changed.append(str(relative))

    switcher = front / "src/components/shared/LanguageSwitcher.js"
    switcher.parent.mkdir(parents=True, exist_ok=True)
    switcher.write_text(LANGUAGE_SWITCHER, encoding="utf-8")
    changed.append(str(switcher.relative_to(front)))

    auto_file = front / "src/components/i18n/AutoTranslate.js"
    auto_file.parent.mkdir(parents=True, exist_ok=True)
    auto_file.write_text(AUTO_TRANSLATE, encoding="utf-8")
    changed.append(str(auto_file.relative_to(front)))

    providers = front / "src/components/platform/AppProviders.js"
    if not providers.exists():
        fail("AppProviders.js not found after backup.")
    if patch_app_providers(providers):
        changed.append(str(providers.relative_to(front)))

    report = {
        "installed_at": timestamp,
        "backup": str(backup),
        "changed_files": sorted(set(changed)),
        "reason": (
            "Shared page components were receiving locale='fa' as a fixed prop, "
            "so LanguageContext changes could not update their copy."
        ),
    }

    (front / "LIVE_I18N_FIX_V2.json").write_text(
        json.dumps(report, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print()
    print("Fix installed.")
    print(f"Backup created at: {backup}")
    print(f"Changed files: {len(set(changed))}")
    print()
    print("Run:")
    print("  rm -rf .next")
    print("  NODE_ENV=production npm run build")
    print("  npm run lint")

if __name__ == "__main__":
    main()
