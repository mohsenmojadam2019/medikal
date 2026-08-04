'use client';

import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import { ConfigProvider } from 'antd';
import faIR from 'antd/locale/fa_IR';

export default function ProfileLayout({ children }) {
  const { locale } = useLanguage();

  return (
    <ConfigProvider direction="rtl" locale={faIR}>
      <div style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
        <Header />
        <main style={{ flex: 1, background: '#f8fafc' }}>
          <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
            {children}
          </div>
        </main>
        <Footer />
      </div>
    </ConfigProvider>
  );
}
