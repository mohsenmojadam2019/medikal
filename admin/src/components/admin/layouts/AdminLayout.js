'use client';

import { useState, useEffect } from 'react';
import { Layout } from 'antd';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';
import Sidebar from './Sidebar';
import Header from './Header';
import Loading from '../common/Loading';

const { Content } = Layout;

export default function AdminLayout({ children }) {
  const { loading } = useAuth();
  const { direction } = useLanguage();
  const [collapsed, setCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const checkMobile = () => {
      const mobile = window.innerWidth < 768;
      setIsMobile(mobile);
      if (mobile) {
        setCollapsed(true);
      }
    };
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  if (loading) {
    return <Loading fullScreen />;
  }

  return (
    <Layout style={{ minHeight: '100vh', background: '#f0f2f5' }}>
      <Sidebar collapsed={collapsed} onCollapse={setCollapsed} />
      <Layout
        style={{
          marginRight: collapsed ? 80 : 280,
          transition: 'all 0.2s',
          background: '#f0f2f5',
        }}
      >
        <Header collapsed={collapsed} onToggle={() => setCollapsed(!collapsed)} />
        <Content
          style={{
            padding: 24,
            minHeight: 'calc(100vh - 72px)',
            background: '#f0f2f5',
          }}
        >
          {children}
        </Content>
      </Layout>
    </Layout>
  );
}
