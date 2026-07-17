import { ConfigProvider } from 'antd';
import { AntdRegistry } from '@ant-design/nextjs-registry';
import { AuthProvider } from '@/lib/context/AuthContext';
import { LanguageProvider } from '@/lib/context/LanguageContext';
import { ThemeProvider } from '@/lib/context/ThemeContext';
import './globals.css';

export const metadata = {
  title: 'دکتر وب | سیستم مدیریت جامع سلامت',
  description: 'سیستم جامع نوبت‌دهی و مدیریت سلامت',
};

export default function RootLayout({ children }) {
  return (
    <html lang="fa" dir="rtl">
      <head>
        <link
          href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap"
          rel="stylesheet"
        />
        <link
          rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
      </head>
      <body>
        <AntdRegistry>
          <ConfigProvider
            direction="rtl"
            theme={{
              token: {
                colorPrimary: '#2563eb',
                colorSuccess: '#10b981',
                colorWarning: '#f59e0b',
                colorError: '#ef4444',
                borderRadius: 12,
                fontFamily: 'Vazirmatn, sans-serif',
              },
              components: {
                Button: {
                  borderRadius: 12,
                  fontWeight: 600,
                  controlHeight: 44,
                },
                Input: {
                  borderRadius: 12,
                  controlHeight: 44,
                },
                Card: {
                  borderRadius: 16,
                },
              },
            }}
          >
            <LanguageProvider>
              <ThemeProvider>
                <AuthProvider>
                  {children}
                </AuthProvider>
              </ThemeProvider>
            </LanguageProvider>
          </ConfigProvider>
        </AntdRegistry>
      </body>
    </html>
  );
}
