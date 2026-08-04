'use client';

import { App as AntdApp, ConfigProvider } from 'antd';
import { AuthProvider } from '@/lib/context/AuthContext';
import { LanguageProvider, useLanguage } from '@/lib/context/LanguageContext';
import { ThemeProvider } from '@/lib/context/ThemeContext';
import MobileBottomNav from './MobileBottomNav';

function DirectionalProviders({ children }) {
  const { direction } = useLanguage();
  return (
    <ConfigProvider
      direction={direction}
      theme={{
        token: {
          colorPrimary: '#6d28d9',
          colorSuccess: '#10b981',
          colorWarning: '#f59e0b',
          colorError: '#ef4444',
          colorBgLayout: '#f7f7fb',
          borderRadius: 14,
          fontFamily: 'Vazirmatn, Inter, sans-serif',
        },
        components: {
          Button: { borderRadius: 12, fontWeight: 700, controlHeight: 44 },
          Input: { borderRadius: 12, controlHeight: 44 },
          Select: { borderRadius: 12, controlHeight: 44 },
          Card: { borderRadiusLG: 20 },
        },
      }}
    >
      <AntdApp>
        <ThemeProvider>
          <AuthProvider>
            {children}
            <MobileBottomNav />
          </AuthProvider>
        </ThemeProvider>
      </AntdApp>
    </ConfigProvider>
  );
}

export default function AppProviders({ children }) {
  return (
    <LanguageProvider>
      <DirectionalProviders>{children}</DirectionalProviders>
    </LanguageProvider>
  );
}
