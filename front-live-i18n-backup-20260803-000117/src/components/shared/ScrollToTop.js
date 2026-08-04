'use client';

import { useState, useEffect } from 'react';
import { Button } from 'antd';
import { UpOutlined } from '@ant-design/icons';

export default function ScrollToTop() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setVisible(window.scrollY > 300);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  if (!visible) return null;

  return (
    <Button
      type="primary"
      shape="circle"
      icon={<UpOutlined />}
      onClick={scrollToTop}
      style={{
        position: 'fixed',
        bottom: '80px',
        right: '24px',
        zIndex: 1000,
        boxShadow: '0 4px 12px rgba(37,99,235,0.3)',
      }}
      size="large"
    />
  );
}
