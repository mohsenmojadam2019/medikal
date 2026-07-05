import { NextResponse } from 'next/server';

// مسیرهای عمومی (نیاز به لاگین ندارن)
const publicPaths = ['/login', '/register', '/verify', '/'];

// مسیرهای لاگین و ثبت‌نام (اگه لاگین باشی، نمیتونی بری)
const authPaths = ['/login', '/register', '/verify'];

export function middleware(request) {
  const token = request.cookies.get('token')?.value || 
                request.headers.get('authorization')?.replace('Bearer ', '');
  
  const { pathname } = request.nextUrl;
  
  // اگه کاربر لاگین هست و میخواد بره به صفحات لاگین/ثبت‌نام
  if (token && authPaths.includes(pathname)) {
    return NextResponse.redirect(new URL('/', request.url));
  }
  
  // اگه کاربر لاگین نیست و میخواد بره به صفحات محافظت شده
  if (!token && !publicPaths.includes(pathname) && !pathname.startsWith('/_next')) {
    return NextResponse.redirect(new URL('/login', request.url));
  }
  
  return NextResponse.next();
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|images|fonts).*)',
  ],
};
