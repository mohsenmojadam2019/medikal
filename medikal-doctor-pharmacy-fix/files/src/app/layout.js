import { App as AntdApp, ConfigProvider } from 'antd';
import { AntdRegistry } from '@ant-design/nextjs-registry';
import { AuthProvider } from '@/lib/context/AuthContext';
import { LanguageProvider } from '@/lib/context/LanguageContext';
import { ThemeProvider } from '@/lib/context/ThemeContext';
import MobileBottomNav from '@/components/platform/MobileBottomNav';
import './globals.css';

export const metadata = {
  title: 'مدیکال | پلتفرم جامع سلامت',
  description: 'پلتفرم یکپارچه پزشک، داروخانه، آزمایشگاه و خدمات سلامت',
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
              <LanguageProvider>
                <ThemeProvider>
                  <AuthProvider>
                    {children}
                    <MobileBottomNav />
                  </AuthProvider>
                </ThemeProvider>
              </LanguageProvider>
            </AntdApp>
          </ConfigProvider>
        </AntdRegistry>
      </body>
    </html>
  );
}
