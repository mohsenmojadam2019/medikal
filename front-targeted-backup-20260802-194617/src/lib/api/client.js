const DEFAULT_TIMEOUT = 15000;

export class ApiError extends Error {
  constructor(message, { status = 0, payload = null, path = '' } = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.payload = payload;
    this.path = path;
  }
}

export function getStoredToken() {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('token');
}

function createUrl(path) {
  const base = process.env.NEXT_PUBLIC_API_URL || '/backend-api';
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${normalizedPath}`;
}

export async function apiFetch(path, options = {}) {
  const {
    token,
    timeout = DEFAULT_TIMEOUT,
    headers,
    body,
    ...requestOptions
  } = options;

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeout);
  const requestHeaders = new Headers(headers || {});

  if (token) requestHeaders.set('Authorization', `Bearer ${token}`);
  if (body && !(body instanceof FormData) && !requestHeaders.has('Content-Type')) {
    requestHeaders.set('Content-Type', 'application/json');
  }
  requestHeaders.set('Accept', 'application/json');

  try {
    const response = await fetch(createUrl(path), {
      ...requestOptions,
      body,
      headers: requestHeaders,
      signal: controller.signal,
      cache: 'no-store',
    });

    const text = await response.text();
    let payload = null;

    if (text) {
      try {
        payload = JSON.parse(text);
      } catch {
        payload = { message: text.slice(0, 500) };
      }
    }

    if (!response.ok || payload?.success === false) {
      throw new ApiError(
        payload?.message || `درخواست با کد ${response.status} ناموفق بود`,
        { status: response.status, payload, path },
      );
    }

    return payload ?? { success: true, data: null };
  } catch (error) {
    if (error instanceof ApiError) throw error;
    if (error?.name === 'AbortError') {
      throw new ApiError('زمان پاسخ‌گویی سرور بیش از حد طول کشید', { path });
    }
    throw new ApiError('ارتباط با سرویس برقرار نشد', { path, payload: error });
  } finally {
    clearTimeout(timer);
  }
}

export function extractCollection(payload) {
  const data = payload?.data ?? payload;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  if (Array.isArray(data?.items)) return data.items;
  if (Array.isArray(data?.results)) return data.results;
  return [];
}

export function extractPagination(payload, fallbackLength = 0) {
  const data = payload?.data ?? payload ?? {};
  return {
    total: Number(data.total ?? data.meta?.total ?? fallbackLength),
    currentPage: Number(data.current_page ?? data.meta?.current_page ?? 1),
    perPage: Number((data.per_page ?? data.meta?.per_page ?? fallbackLength) || 1),
    lastPage: Number(data.last_page ?? data.meta?.last_page ?? 1),
  };
}

export function getApiErrorMessage(error, fallback) {
  if (error instanceof ApiError && error.message) return error.message;
  return fallback;
}
