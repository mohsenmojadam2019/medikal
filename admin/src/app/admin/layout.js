'use client';

import { useState, useEffect } from 'react';
import { usePathname, useRouter } from 'next/navigation';
import { ConfigProvider, App as AntdApp } from 'antd';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { LanguageProvider, useLanguage } from '@/context/LanguageContext';
import AdminLayoutComponent from '@/components/admin/layouts/AdminLayout';
import Loading from '@/components/admin/common/Loading';
import faIR from 'antd/locale/fa_IR';
import dayjs from 'dayjs';
import jalali from 'dayjs-jalali';
import 'dayjs/locale/fa';

dayjs.extend(jalali);
dayjs.locale('fa');

function AdminLayoutContent({ children }) {
  const { loading, isAuthenticated } = useAuth();
  const { direction } = useLanguage();
  const router = useRouter();
  const pathname = usePathname();

  // اگر در صفحه لاگین هستیم، Layout ادمین را اعمال نکن
  const isLoginPage = pathname === '/admin/login';

  useEffect(() => {
    if (!loading && !isAuthenticated && !isLoginPage) {
      router.push('/admin/login');
    }
  }, [loading, isAuthenticated, router, isLoginPage]);

  if (loading) {
    return <Loading fullScreen />;
  }

  // اگر در صفحه لاگین هستیم، فقط محتوای صفحه را نشان بده
  if (isLoginPage) {
    return children;
  }

  if (!isAuthenticated) {
    return null;
  }

  return (
    <ConfigProvider
      locale={faIR}
      direction={direction}
      theme={{
        token: {
          colorPrimary: '#2563eb',
          borderRadius: 8,
          // fontFamily: 'Vazirmatn, sans-serif',
        },
        components: {
          Layout: {
            headerBg: '#ffffff',
            siderBg: '#ffffff',
          },
          Menu: {
            itemSelectedBg: '#dbeafe',
            itemSelectedColor: '#2563eb',
          },
          Card: {
            borderRadius: 12,
          },
          Button: {
            borderRadius: 8,
          },
        },
      }}
    >
      <AntdApp>
        <AdminLayoutComponent>{children}</AdminLayoutComponent>
      </AntdApp>
    </ConfigProvider>
  );
}

export default function AdminLayout({ children }) {
  return (
    <LanguageProvider>
      <AuthProvider>
        <AdminLayoutContent>{children}</AdminLayoutContent>
      </AuthProvider>
    </LanguageProvider>
  );
}
