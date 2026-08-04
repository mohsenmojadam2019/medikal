#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import shutil
import sys
from pathlib import Path

AUTO_TRANSLATE_IMPORT = "import AutoTranslate from '@/components/i18n/AutoTranslate';"
AUTO_TRANSLATE_NODE = "            <AutoTranslate />"

def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)

def main() -> None:
    parser = argparse.ArgumentParser(
        description="Install DoctorWeb automatic in-place translation hotfix."
    )
    parser.add_argument(
        "front_root",
        help="Frontend path, e.g. /home/god/Videos/medikal/front",
    )
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    package_root = Path(__file__).resolve().parent
    source_files = package_root / "files"
    front = Path(args.front_root).expanduser().resolve()

    if not (front / "package.json").exists():
        fail("package.json not found in frontend root.")

    providers = front / "src/components/platform/AppProviders.js"
    if not providers.exists():
        fail("src/components/platform/AppProviders.js not found.")

    timestamp = dt.datetime.now().strftime("%Y%m%d-%H%M%S")
    backup = front.parent / f"{front.name}-translation-backup-{timestamp}"

    print(f"Frontend: {front}")
    print(f"Backup:   {backup}")
    print("Will add:")
    print("  - automatic translation of hardcoded visible UI text")
    print("  - live translation after changing FA / EN / AR")
    print("  - RTL for Persian/Arabic and LTR for English")
    print("  - translation of dynamically rendered Ant Design content")
    print("  - translation of placeholder/title/aria-label attributes")
    print("  - no language prefix in URL")

    if args.dry_run:
        print("Dry run complete. No files changed.")
        return

    backup.mkdir(parents=True, exist_ok=False)
    backup_items = [
        providers,
        front / "src/lib/context/LanguageContext.js",
        front / "src/components/front/Header/Header.js",
        front / "src/components/front/Header/NavBar.js",
        front / "src/components/front/Footer/Footer.js",
    ]

    manifest = []
    for item in backup_items:
        if not item.exists():
            continue
        rel = item.relative_to(front)
        target = backup / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(item, target)
        manifest.append(str(rel))

    (backup / "manifest.json").write_text(
        json.dumps(
            {"front": str(front), "items": manifest},
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    for source in source_files.rglob("*"):
        if source.is_dir():
            continue
        rel = source.relative_to(source_files)
        target = front / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)

    text = providers.read_text(encoding="utf-8")

    if AUTO_TRANSLATE_IMPORT not in text:
        import_anchor = "import MobileBottomNav from './MobileBottomNav';"
        if import_anchor not in text:
            fail("Could not find MobileBottomNav import in AppProviders.js.")
        text = text.replace(
            import_anchor,
            import_anchor + "\n" + AUTO_TRANSLATE_IMPORT,
            1,
        )

    if "<AutoTranslate />" not in text:
        anchor = "            <MobileBottomNav />"
        if anchor not in text:
            fail("Could not find MobileBottomNav component in AppProviders.js.")
        text = text.replace(
            anchor,
            AUTO_TRANSLATE_NODE + "\n" + anchor,
            1,
        )

    providers.write_text(text, encoding="utf-8")

    report = {
        "installed_at": timestamp,
        "backup": str(backup),
        "files": [
            "src/components/i18n/AutoTranslate.js",
            "src/components/i18n/dictionary.js",
            "src/components/platform/AppProviders.js",
        ],
    }
    (front / "AUTO_TRANSLATE_INSTALL.json").write_text(
        json.dumps(report, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print()
    print("Installed successfully.")
    print(f"Backup created at: {backup}")
    print()
    print("Run:")
    print("  NODE_ENV=production npm run build")
    print("  npm run lint")

if __name__ == "__main__":
    main()
