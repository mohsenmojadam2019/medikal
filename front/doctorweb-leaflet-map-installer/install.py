#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import shutil
import subprocess
import sys
from datetime import datetime
from pathlib import Path

DEPENDENCIES = {
    "leaflet": "^1.9.4",
    "react-leaflet": "^5.0.0",
}

def backup_and_copy(source: Path, destination: Path, project_root: Path, backup_root: Path) -> None:
    relative = destination.relative_to(project_root)
    if destination.exists():
        backup_file = backup_root / relative
        backup_file.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(destination, backup_file)
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(source, destination)

def update_package_json(package_path: Path, project_root: Path, backup_root: Path) -> None:
    relative = package_path.relative_to(project_root)
    backup_file = backup_root / relative
    backup_file.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(package_path, backup_file)

    package = json.loads(package_path.read_text(encoding="utf-8"))
    dependencies = package.setdefault("dependencies", {})
    for name, version in DEPENDENCIES.items():
        dependencies[name] = version

    package_path.write_text(
        json.dumps(package, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

def run_npm_install(project_root: Path) -> None:
    npm = shutil.which("npm")
    if not npm:
        print("هشدار: npm پیدا نشد؛ فایل‌ها ایجاد شدند ولی وابستگی‌ها نصب نشدند.")
        return

    print("در حال نصب Leaflet و React-Leaflet...")
    result = subprocess.run(
        [npm, "install", "leaflet@1.9.4", "react-leaflet@5.0.0"],
        cwd=project_root,
        check=False,
    )
    if result.returncode:
        print("هشدار: npm install ناموفق بود؛ بعداً دستی اجرا کنید.")
    else:
        print("وابستگی‌ها نصب شدند.")

def main() -> int:
    parser = argparse.ArgumentParser(description="نصب نقشه Leaflet دکتر وب")
    parser.add_argument("project_root", nargs="?", default=".")
    parser.add_argument("--skip-npm", action="store_true")
    args = parser.parse_args()

    project_root = Path(args.project_root).expanduser().resolve()
    package_path = project_root / "package.json"
    app_dir = project_root / "src" / "app"

    if not package_path.is_file():
        print(f"خطا: package.json در {project_root} پیدا نشد.", file=sys.stderr)
        return 1

    if not app_dir.is_dir():
        print("خطا: پوشه src/app پیدا نشد؛ این بسته برای App Router است.", file=sys.stderr)
        return 1

    try:
        package = json.loads(package_path.read_text(encoding="utf-8"))
    except Exception as error:
        print(f"خطا در خواندن package.json: {error}", file=sys.stderr)
        return 1

    if "next" not in package.get("dependencies", {}) and "next" not in package.get("devDependencies", {}):
        print("خطا: پروژه Next.js تشخیص داده نشد.", file=sys.stderr)
        return 1

    installer_root = Path(__file__).resolve().parent
    payload_root = installer_root / "payload"
    if not payload_root.is_dir():
        print("خطا: پوشه payload پیدا نشد.", file=sys.stderr)
        return 1

    timestamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    backup_root = project_root / ".doctorweb-map-backup" / timestamp
    backup_root.mkdir(parents=True, exist_ok=True)

    update_package_json(package_path, project_root, backup_root)

    created = []
    for source in sorted(payload_root.rglob("*")):
        if not source.is_file():
            continue
        relative = source.relative_to(payload_root)
        destination = project_root / relative
        backup_and_copy(source, destination, project_root, backup_root)
        created.append(str(relative))

    print("\nفایل‌های ایجادشده:")
    for item in created:
        print(f"  - {item}")

    print(f"\nنسخه پشتیبان: {backup_root}")

    if not args.skip_npm:
        run_npm_install(project_root)

    print(
        "\nنصب تمام شد.\n"
        "۱) مقادیر doctorweb-map.env.example را به .env.local اضافه کنید.\n"
        "۲) npm run build را اجرا کنید.\n"
        "۳) صفحه http://localhost:3000/map را باز کنید."
    )
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
