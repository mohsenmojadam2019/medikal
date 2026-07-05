'use client';

import { Spin } from 'antd';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function LoadingSpinner() {
  const { t } = useLanguage();

  return (
    <div style={{ 
      display: 'flex', 
      justifyContent: 'center', 
      alignItems: 'center', 
      minHeight: '400px',
      flexDirection: 'column',
      gap: '16px'
    }}>
      <Spin size="large" />
      <p style={{ color: '#94a3b8' }}>{t('common.loading')}</p>
    </div>
  );
}
