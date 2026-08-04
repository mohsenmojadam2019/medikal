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
