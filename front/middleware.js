// /home/god/Videos/medikal/front/src/middleware.js
import { NextResponse } from 'next/server';

// ✅ مسیرهای عمومی (بدون نیاز به لاگین)
const publicPaths = [
  '/',
  '/login',
  '/register',
  '/verify',
  '/doctors',
  '/about',
  '/contact'
];

// ✅ مسیرهای محافظت‌شده (نیاز به لاگین)
const protectedPaths = [
  '/appointments/checkout', // ✅ تسویه حساب نیاز به لاگین دارد
  '/appointments/my', // ✅ لیست نوبت‌های من
  '/profile',
  '/dashboard',
  '/appointments/history',
  '/appointments/confirmation' // ✅ صفحه تأیید نهایی
];

export function middleware(request) {
  const token = request.cookies.get('token')?.value ||
      request.headers.get('authorization')?.replace('Bearer ', '');

  const { pathname } = request.nextUrl;

  // ✅ اجازه دسترسی به فایل‌های استاتیک
  if (pathname.startsWith('/_next') ||
      pathname.startsWith('/images') ||
      pathname.startsWith('/fonts') ||
      pathname.startsWith('/api')) {
    return NextResponse.next();
  }

  // ✅ اگر کاربر لاگین است و به صفحات لاگین/ثبت‌نام برود
  if (token && ['/login', '/register', '/verify'].includes(pathname)) {
    return NextResponse.redirect(new URL('/', request.url));
  }

  // ✅ بررسی مسیرهای محافظت‌شده
  const isProtected = protectedPaths.some(path => pathname.startsWith(path));

  // ✅ اگر کاربر لاگین نیست و به مسیر محافظت‌شده رفته
  if (!token && isProtected) {
    const url = new URL('/login', request.url);
    // ✅ ذخیره مسیر فعلی برای بازگشت بعد از لاگین
    url.searchParams.set('redirect', pathname);
    return NextResponse.redirect(url);
  }

  // ✅ مسیر /appointments/new آزاد است (بدون لاگین)
  return NextResponse.next();
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|images|fonts).*)',
  ],
};