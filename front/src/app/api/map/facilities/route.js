import { NextResponse } from 'next/server';
import { MOCK_FACILITIES } from '@/lib/map/mockFacilities';

export const dynamic = 'force-dynamic';
export const runtime = 'nodejs';

function parseBoolean(value) {
  return value === '1' || value === 'true';
}

function parseBounds(value) {
  if (!value) return null;
  const parts = value.split(',').map(Number);
  if (parts.length !== 4 || parts.some((item) => !Number.isFinite(item))) return null;
  const [west, south, east, north] = parts;
  return { west, south, east, north };
}

function filterMock(searchParams) {
  const bounds = parseBounds(searchParams.get('bbox'));
  const types = (searchParams.get('types') || searchParams.get('type') || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
  const search = (searchParams.get('search') || '').trim().toLocaleLowerCase('fa');
  const openNow = parseBoolean(searchParams.get('open_now'));
  const is24Hours = parseBoolean(searchParams.get('is_24_hours'));

  return MOCK_FACILITIES.filter((facility) => {
    if (types.length && !types.includes(facility.type)) return false;
    if (openNow && !facility.is_open) return false;
    if (is24Hours && !facility.is_24_hours) return false;

    if (search) {
      const haystack = [
        facility.name,
        facility.address,
        facility.type,
        ...(facility.services || []),
      ]
        .join(' ')
        .toLocaleLowerCase('fa');
      if (!haystack.includes(search)) return false;
    }

    if (
      bounds &&
      (facility.longitude < bounds.west ||
        facility.longitude > bounds.east ||
        facility.latitude < bounds.south ||
        facility.latitude > bounds.north)
    ) {
      return false;
    }

    return true;
  });
}

function backendUrlFor(requestUrl) {
  const base = process.env.MEDIKAL_MAP_FACILITIES_URL;
  if (!base) return null;

  const incoming = new URL(requestUrl);
  const destination = new URL(base);
  for (const [key, value] of incoming.searchParams.entries()) {
    destination.searchParams.append(key, value);
  }
  return destination;
}

export async function GET(request) {
  const backendUrl = backendUrlFor(request.url);

  if (backendUrl) {
    try {
      const authorization = request.headers.get('authorization');
      const cookie = request.headers.get('cookie');
      const response = await fetch(backendUrl, {
        headers: {
          Accept: 'application/json',
          ...(authorization ? { Authorization: authorization } : {}),
          ...(cookie ? { Cookie: cookie } : {}),
        },
        cache: 'no-store',
      });

      if (!response.ok) throw new Error(`Backend returned ${response.status}`);
      return NextResponse.json(await response.json());
    } catch (error) {
      console.error('[map/facilities] backend failed; mock fallback is active:', error);
    }
  }

  const url = new URL(request.url);
  return NextResponse.json({
    data: filterMock(url.searchParams),
    meta: {
      source: 'mock',
      message: 'برای داده واقعی MEDIKAL_MAP_FACILITIES_URL را تنظیم کنید.',
    },
  });
}
