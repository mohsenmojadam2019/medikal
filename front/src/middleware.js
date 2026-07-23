import { NextResponse } from 'next/server';

// ============================================================
// ✅ تنظیمات مسیرها
// ============================================================

const PUBLIC_PATHS = [
  '/',
  '/about',
  '/contact',
  '/faq',
  '/support',
  '/search',
  '/login',
  '/register',
  '/verify',
  '/forgot-password',
  '/reset-password',
  '/doctors',          // ✅ لیست پزشکان عمومی
  '/specialties',
  '/pharmacy',
  '/lab',
  '/ai-chat',
  '/blog',
  '/imaging',
  '/appointments/new', // ✅ صفحه جدید نوبت عمومی (با doctorId)
];

const PROTECTED_PATHS = [
  '/profile',
  '/dashboard',
  '/settings',
  '/wallet',
  '/wallet/transactions',
  '/wallet/deposit',
  '/wallet/withdraw',
  '/records',
  '/records/*',
  '/appointments',         // ✅ صفحه نوبت‌های من محافظت شده
  '/appointments/my',
  '/appointments/checkout',
  '/appointments/confirmation',
  '/appointments/history',
  '/appointments/cancel',
  '/appointments/reschedule',
  '/pharmacy/checkout',
  '/pharmacy/cart',
  '/pharmacy/orders',
  '/pharmacy/orders/*',
  '/lab/orders',
  '/lab/results',
  '/imaging/upload',
  '/imaging/my',
  '/imaging/delete',
  '/messages',
  '/notifications',
];

const STATIC_PATHS = [
  '/_next',
  '/images',
  '/fonts',
  '/api',
  '/favicon.ico',
  '/robots.txt',
  '/sitemap.xml',
];

const AUTH_PATHS = ['/login', '/register', '/verify', '/forgot-password', '/reset-password'];

export function middleware(request) {
  const { pathname } = request.nextUrl;

  if (STATIC_PATHS.some(path => pathname.startsWith(path))) {
    return NextResponse.next();
  }

  const token = getAuthToken(request);
  const isLoggedIn = !!token;

  if (process.env.NODE_ENV === 'development') {
    console.log(`🔍 [Middleware] ${pathname} | LoggedIn: ${isLoggedIn}`);
  }

  if (isLoggedIn && AUTH_PATHS.includes(pathname)) {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  const isProtected = PROTECTED_PATHS.some(path => {
    if (path.endsWith('/*')) {
      const basePath = path.slice(0, -2);
      return pathname.startsWith(basePath);
    }
    return pathname === path || pathname.startsWith(path + '/');
  });

  if (!isLoggedIn && isProtected) {
    if (isExceptionPath(pathname)) {
      return NextResponse.next();
    }
    return redirectToLogin(request);
  }

  const response = NextResponse.next();
  setSecurityHeaders(response);

  return response;
}

function getAuthToken(request) {
  return (
      request.cookies.get('token')?.value ||
      request.headers.get('authorization')?.replace('Bearer ', '') ||
      null
  );
}

function isExceptionPath(pathname) {
  const exceptions = [
    '/appointments/new',
    '/imaging',
    '/pharmacy',
  ];
  return exceptions.some(path => pathname === path || pathname.startsWith(path + '/'));
}

function redirectToLogin(request) {
  const { pathname, search } = request.nextUrl;
  const url = new URL('/login', request.url);
  const redirectPath = pathname + search;
  url.searchParams.set('redirect', redirectPath);
  return NextResponse.redirect(url);
}

function setSecurityHeaders(response) {
  const headers = {
    'X-Content-Type-Options': 'nosniff',
    'X-Frame-Options': 'DENY',
    'X-XSS-Protection': '1; mode=block',
    'Referrer-Policy': 'strict-origin-when-cross-origin',
    'Permissions-Policy': 'camera=(), microphone=(), geolocation=()',
  };
  Object.entries(headers).forEach(([key, value]) => {
    response.headers.set(key, value);
  });
  return response;
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|images|fonts|robots.txt|sitemap.xml).*)',
  ],
};