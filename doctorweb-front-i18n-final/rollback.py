#!/usr/bin/env python3
from __future__ import annotations

import argparse
import shutil
from pathlib import Path


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("front_root")
    parser.add_argument("backup_root")
    args = parser.parse_args()

    front = Path(args.front_root).expanduser().resolve()
    backup = Path(args.backup_root).expanduser().resolve()

    if not (backup / "src").exists():
        raise SystemExit("Backup src directory not found.")

    if (front / "src").exists():
        shutil.rmtree(front / "src")
    shutil.copytree(backup / "src", front / "src")

    if (backup / "package.json").exists():
        shutil.copy2(backup / "package.json", front / "package.json")

    if (front / "tools").exists():
        shutil.rmtree(front / "tools")
    if (backup / "tools").exists():
        shutil.copytree(backup / "tools", front / "tools")

    report = front / "DOCTORWEB_I18N_FINAL.json"
    if report.exists():
        report.unlink()

    print(f"Rollback completed from: {backup}")


if __name__ == "__main__":
    main()
