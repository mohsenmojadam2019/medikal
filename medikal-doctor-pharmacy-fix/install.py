#!/usr/bin/env python3
from __future__ import annotations

import argparse
import shutil
import sys
from datetime import datetime
from pathlib import Path


def resolve_front_root(value: Path) -> Path:
    value = value.expanduser().resolve()
    if value.name == 'front' and (value / 'package.json').exists():
        return value
    candidate = value / 'front'
    if candidate.exists() and (candidate / 'package.json').exists():
        return candidate
    raise SystemExit(f'Front project not found under: {value}')


def main() -> int:
    parser = argparse.ArgumentParser(description='Install the Medikal frontend API/doctor/home fix.')
    parser.add_argument('project', nargs='?', default='.', help='Medikal project root or front directory')
    args = parser.parse_args()

    bundle_root = Path(__file__).resolve().parent
    source_root = bundle_root / 'files'
    front_root = resolve_front_root(Path(args.project))
    timestamp = datetime.now().strftime('%Y%m%d-%H%M%S')
    backup_root = front_root.parent / f'front-targeted-backup-{timestamp}'

    copied = []
    for source in sorted(source_root.rglob('*')):
        if not source.is_file():
            continue
        relative = source.relative_to(source_root)
        target = front_root / relative
        if target.exists():
            backup = backup_root / relative
            backup.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(target, backup)
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)
        copied.append(relative.as_posix())

    print(f'Installed {len(copied)} files into {front_root}')
    print(f'Backup: {backup_root}')
    print('\nChanged files:')
    for item in copied:
        print(f'  - {item}')
    print('\nNext commands:')
    print(f'  cd {front_root.parent}')
    print('  docker compose up -d --force-recreate front')
    print("  docker compose exec front sh -lc 'NODE_ENV=production npm run build'")
    print("  docker compose exec front sh -lc 'npm run lint'")
    return 0


if __name__ == '__main__':
    sys.exit(main())
