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
    source = backup / "src"
    target = front / "src"

    if not source.exists():
        raise SystemExit("Backup src directory not found.")

    if target.exists():
        shutil.rmtree(target)

    shutil.copytree(source, target)

    report = front / "LIVE_I18N_FIX_V2.json"
    if report.exists():
        report.unlink()

    print("Rollback completed.")

if __name__ == "__main__":
    main()
