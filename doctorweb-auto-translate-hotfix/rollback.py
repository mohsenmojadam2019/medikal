#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import shutil
from pathlib import Path

def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("front_root")
    parser.add_argument("backup_root")
    args = parser.parse_args()

    front = Path(args.front_root).expanduser().resolve()
    backup = Path(args.backup_root).expanduser().resolve()
    manifest = json.loads(
        (backup / "manifest.json").read_text(encoding="utf-8")
    )

    for rel_text in manifest["items"]:
        rel = Path(rel_text)
        source = backup / rel
        target = front / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)

    for rel in [
        Path("src/components/i18n/AutoTranslate.js"),
        Path("src/components/i18n/dictionary.js"),
        Path("AUTO_TRANSLATE_INSTALL.json"),
    ]:
        target = front / rel
        if target.exists():
            target.unlink()

    print("Rollback completed.")

if __name__ == "__main__":
    main()
