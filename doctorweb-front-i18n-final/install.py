#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
import shutil
import subprocess
import sys
from pathlib import Path


def fail(message: str) -> None:
    print(f"\nERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def run(command: list[str], cwd: Path) -> None:
    print("+", " ".join(command))
    result = subprocess.run(command, cwd=cwd)
    if result.returncode != 0:
        fail(f"Command failed ({result.returncode}): {' '.join(command)}")


def patch_app_providers(path: Path) -> None:
    text = path.read_text(encoding="utf-8")

    text = text.replace(
        "import AutoTranslate from '@/components/i18n/AutoTranslate';\n",
        "",
    )
    text = text.replace("            <AutoTranslate />\n", "")

    bridge_import = "import I18nBridge from '@/components/i18n/I18nBridge';"
    if bridge_import not in text:
        anchor = "import MobileBottomNav from './MobileBottomNav';"
        if anchor in text:
            text = text.replace(anchor, anchor + "\n" + bridge_import, 1)
        else:
            text = text.replace("'use client';", "'use client';\n\n" + bridge_import, 1)

    old = """    <LanguageProvider>
      <DirectionalProviders>{children}</DirectionalProviders>
    </LanguageProvider>"""
    new = """    <LanguageProvider>
      <I18nBridge>
        <DirectionalProviders>{children}</DirectionalProviders>
      </I18nBridge>
    </LanguageProvider>"""

    if old in text:
        text = text.replace(old, new, 1)
    elif "<I18nBridge>" not in text:
        text = text.replace(
            "<LanguageProvider>",
            "<LanguageProvider>\n      <I18nBridge>",
            1,
        )
        text = text.replace(
            "</LanguageProvider>",
            "      </I18nBridge>\n    </LanguageProvider>",
            1,
        )

    path.write_text(text, encoding="utf-8")


def patch_package_json(path: Path) -> None:
    data = json.loads(path.read_text(encoding="utf-8"))
    scripts = data.setdefault("scripts", {})
    scripts["i18n:sync"] = "node tools/i18n-sync.js --root ."
    scripts["i18n:audit"] = "node tools/i18n-audit.js --root ."
    scripts.setdefault("prebuild", "node tools/i18n-audit.js --root .")
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Install permanent DoctorWeb frontend multilingual support."
    )
    parser.add_argument("front_root")
    parser.add_argument("--model", default="")
    parser.add_argument("--ollama-url", default="http://127.0.0.1:11434")
    parser.add_argument("--batch-size", type=int, default=20)
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    front = Path(args.front_root).expanduser().resolve()
    package_root = Path(__file__).resolve().parent
    payload = package_root / "payload"

    if not (front / "package.json").exists():
        fail("package.json not found in frontend root.")
    if not (front / "src").exists():
        fail("src directory not found.")

    providers = front / "src/components/platform/AppProviders.js"
    if not providers.exists():
        fail("src/components/platform/AppProviders.js not found.")

    timestamp = dt.datetime.now().strftime("%Y%m%d-%H%M%S")
    backup = front.parent / f"{front.name}-i18n-final-backup-{timestamp}"

    print(f"Frontend: {front}")
    print(f"Backup:   {backup}")
    print("Plan:")
    print("  1. Full backup of src/package.json/tools")
    print("  2. Scan every frontend phrase")
    print("  3. Translate every phrase to English and Arabic with local Ollama")
    print("  4. Convert safe client strings to t(key, fallback)")
    print("  5. Install admin-style LanguageContext and I18nBridge")
    print("  6. Enforce 100% phrase coverage before build")

    if args.dry_run:
        print("Dry run complete. No files changed.")
        return

    if shutil.which("node") is None:
        fail("Node.js is not available.")

    parser_check = subprocess.run(
        ["node", "-e", "require('next/dist/compiled/babel/parser')"],
        cwd=front,
        capture_output=True,
        text=True,
    )
    if parser_check.returncode != 0:
        fail(
            "Next.js parser is unavailable. Run this where front/node_modules exists "
            "or execute it inside the frontend container."
        )

    backup.mkdir(parents=True, exist_ok=False)
    shutil.copytree(front / "src", backup / "src")
    shutil.copy2(front / "package.json", backup / "package.json")
    if (front / "tools").exists():
        shutil.copytree(front / "tools", backup / "tools")

    (backup / "manifest.json").write_text(
        json.dumps(
            {
                "created_at": timestamp,
                "front": str(front),
                "backup": str(backup),
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    for source in payload.rglob("*"):
        if source.is_dir():
            continue
        relative = source.relative_to(payload)
        target = front / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)

    patch_app_providers(providers)
    patch_package_json(front / "package.json")

    command = [
        "node",
        "tools/i18n-sync.js",
        "--root",
        str(front),
        "--ollama-url",
        args.ollama_url,
        "--batch-size",
        str(args.batch_size),
    ]
    if args.model:
        command += ["--model", args.model]

    run(command, front)
    run(["node", "tools/i18n-audit.js", "--root", str(front)], front)

    (front / "DOCTORWEB_I18N_FINAL.json").write_text(
        json.dumps(
            {
                "installed_at": timestamp,
                "backup": str(backup),
                "future_command": "npm run i18n:sync",
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    print("\nPermanent multilingual system installed.")
    print(f"Backup: {backup}")
    print("\nRun:")
    print("  rm -rf .next")
    print("  npm run build")
    print("  npm run lint")
    print("\nFor every new page or sentence:")
    print("  npm run i18n:sync")


if __name__ == "__main__":
    main()
