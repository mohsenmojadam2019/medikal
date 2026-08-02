#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${1:-/home/god/Videos/medikal/front}"
PAGE="$PROJECT_ROOT/src/app/fa/doctors/page.js"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${PROJECT_ROOT}-doctors-api-backup-${STAMP}"

if [[ ! -f "$PAGE" ]]; then
  echo "ERROR: doctors page not found: $PAGE" >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"
cp -a "$PAGE" "$BACKUP_DIR/page.js"

mkdir -p "$PROJECT_ROOT/src/app/api/doctors"
mkdir -p "$PROJECT_ROOT/src/app/api/specialties"

cat > "$PROJECT_ROOT/src/app/api/doctors/route.js" <<'EOF'
const BACKEND_URL =
  process.env.API_INTERNAL_URL ||
  process.env.BACKEND_INTERNAL_URL ||
  'http://medikall-webserver';

export const dynamic = 'force-dynamic';

export async function GET(request) {
  const incomingUrl = new URL(request.url);
  const upstreamUrl = new URL('/api/doctors', BACKEND_URL);
  upstreamUrl.search = incomingUrl.search;

  try {
    const upstreamResponse = await fetch(upstreamUrl, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
      cache: 'no-store',
    });

    const responseBody = await upstreamResponse.arrayBuffer();

    return new Response(responseBody, {
      status: upstreamResponse.status,
      headers: {
        'Content-Type':
          upstreamResponse.headers.get('content-type') ||
          'application/json; charset=utf-8',
        'Cache-Control': 'no-store',
      },
    });
  } catch (error) {
    console.error('[doctors proxy] Backend request failed:', error);

    return Response.json(
      {
        success: false,
        message: 'ارتباط داخلی با سرویس پزشکان برقرار نشد',
      },
      { status: 502 }
    );
  }
}
EOF

cat > "$PROJECT_ROOT/src/app/api/specialties/route.js" <<'EOF'
const BACKEND_URL =
  process.env.API_INTERNAL_URL ||
  process.env.BACKEND_INTERNAL_URL ||
  'http://medikall-webserver';

export const dynamic = 'force-dynamic';

export async function GET(request) {
  const incomingUrl = new URL(request.url);
  const upstreamUrl = new URL('/api/specialties', BACKEND_URL);
  upstreamUrl.search = incomingUrl.search;

  try {
    const upstreamResponse = await fetch(upstreamUrl, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
      cache: 'no-store',
    });

    const responseBody = await upstreamResponse.arrayBuffer();

    return new Response(responseBody, {
      status: upstreamResponse.status,
      headers: {
        'Content-Type':
          upstreamResponse.headers.get('content-type') ||
          'application/json; charset=utf-8',
        'Cache-Control': 'no-store',
      },
    });
  } catch (error) {
    console.error('[specialties proxy] Backend request failed:', error);

    return Response.json(
      {
        success: false,
        message: 'ارتباط داخلی با سرویس تخصص‌ها برقرار نشد',
      },
      { status: 502 }
    );
  }
}
EOF

python3 - "$PAGE" <<'PY'
from pathlib import Path
import sys

page = Path(sys.argv[1])
text = page.read_text(encoding="utf-8")
original = text

replacements = {
    "`${API_URL}/api/doctors`": "'/api/doctors'",
    "`${API_URL}/api/specialties`": "'/api/specialties'",
}

for old, new in replacements.items():
    text = text.replace(old, new)

# Remove the now-unused direct browser API base declaration only from this page.
lines = text.splitlines()
lines = [
    line for line in lines
    if "const API_URL = process.env.NEXT_PUBLIC_API_URL" not in line
]
text = "\n".join(lines) + ("\n" if original.endswith("\n") else "")

if text == original:
    raise SystemExit(
        "ERROR: expected API URL patterns were not found; no page changes were made."
    )

page.write_text(text, encoding="utf-8")
PY

echo
echo "Doctors API fix installed."
echo "Backup: $BACKUP_DIR"
echo
echo "Changed only:"
echo "  - src/app/fa/doctors/page.js"
echo "  - src/app/api/doctors/route.js"
echo "  - src/app/api/specialties/route.js"
echo
echo "Now run:"
echo "  cd /home/god/Videos/medikal"
echo "  docker compose restart front"
echo "  curl -i 'http://localhost:3000/api/doctors?per_page=5'"
