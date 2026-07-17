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
  '/contact',
  '/pharmacy',
  '/lab',
  '/ai-chat',        // ✅ هوش مصنوعی عمومی - اضافه شد
  '/specialties',
  '/blog',
  '/faq',
  '/support',
  '/search',
  '/appointments',
];

// ✅ مسیرهای محافظت‌شده (نیاز به لاگین)
const protectedPaths = [
  '/profile',
  '/dashboard',
  '/wallet',
  '/records',
  '/appointments/checkout',
  '/appointments/my',
  '/appointments/confirmation',
  '/appointments/new',
  '/appointments/history',
  '/pharmacy/checkout',
  '/pharmacy/cart',
  '/pharmacy/orders',
  '/lab/orders',
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

  // ✅ بررسی دقیق مسیرهای محافظت‌شده
  const isProtected = protectedPaths.some(path => pathname.startsWith(path));

  // ✅ اگر کاربر لاگین نیست و به مسیر محافظت‌شده رفته
  if (!token && isProtected) {
    // اگر مسیر /appointments/new است، اجازه دسترسی بده (چون داخل خود صفحه چک می‌شه)
    if (pathname === '/appointments/new') {
      return NextResponse.next();
    }

    const url = new URL('/login', request.url);
    // ✅ ذخیره مسیر قبلی برای بازگشت
    const referer = request.headers.get('referer') || '/';
    url.searchParams.set('redirect', referer);
    return NextResponse.redirect(url);
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|images|fonts).*)',
  ],
};