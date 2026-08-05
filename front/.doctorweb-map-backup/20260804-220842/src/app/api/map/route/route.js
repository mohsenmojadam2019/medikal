import { NextResponse } from 'next/server';

export const dynamic = 'force-dynamic';
export const runtime = 'nodejs';

function validCoordinate(value) {
  const latitude = Number(value?.latitude);
  const longitude = Number(value?.longitude);
  return (
    Number.isFinite(latitude) &&
    Number.isFinite(longitude) &&
    latitude >= -90 &&
    latitude <= 90 &&
    longitude >= -180 &&
    longitude <= 180
  );
}

function cleanBaseUrl(value) {
  return String(value || 'https://router.project-osrm.org').replace(/\/+$/, '');
}

export async function POST(request) {
  let body;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ message: 'بدنه JSON معتبر نیست.' }, { status: 400 });
  }

  const { origin, destination } = body || {};
  if (!validCoordinate(origin) || !validCoordinate(destination)) {
    return NextResponse.json(
      { message: 'مختصات مبدا یا مقصد معتبر نیست.' },
      { status: 422 },
    );
  }

  const baseUrl = cleanBaseUrl(process.env.OSRM_BASE_URL);
  const profile = process.env.OSRM_PROFILE || 'driving';
  const coordinates = [
    `${Number(origin.longitude)},${Number(origin.latitude)}`,
    `${Number(destination.longitude)},${Number(destination.latitude)}`,
  ].join(';');

  const url = new URL(`${baseUrl}/route/v1/${encodeURIComponent(profile)}/${coordinates}`);
  url.searchParams.set('overview', 'full');
  url.searchParams.set('geometries', 'geojson');
  url.searchParams.set('steps', 'true');
  url.searchParams.set('alternatives', 'false');

  try {
    const response = await fetch(url, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    const payload = await response.json();

    if (!response.ok || payload.code !== 'Ok' || !payload.routes?.length) {
      return NextResponse.json(
        {
          message: 'سرویس مسیریابی مسیر معتبری برنگرداند.',
          provider_code: payload?.code || null,
        },
        { status: 502 },
      );
    }

    const route = payload.routes[0];
    return NextResponse.json({
      distance_meters: route.distance,
      duration_seconds: route.duration,
      geometry: route.geometry,
      steps: route.legs?.flatMap((leg) => leg.steps || []) || [],
      provider: 'osrm',
    });
  } catch (error) {
    console.error('[map/route] OSRM failed:', error);
    return NextResponse.json(
      { message: 'ارتباط با سرویس مسیریابی برقرار نشد.' },
      { status: 502 },
    );
  }
}
