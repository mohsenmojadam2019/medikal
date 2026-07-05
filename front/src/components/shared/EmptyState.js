'use client';

import { Empty, Button } from 'antd';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function EmptyState({ 
  title = 'هیچ داده‌ای یافت نشد',
  description = 'موردی برای نمایش وجود ندارد',
  icon = '📭',
  actionText,
  actionLink,
  onAction,
}) {
  const router = useRouter();
  const { locale } = useLanguage();

  const handleAction = () => {
    if (onAction) {
      onAction();
    } else if (actionLink) {
      router.push(`/${locale}${actionLink}`);
    }
  };

  return (
    <div style={{ padding: '40px 20px' }}>
      <Empty
        image={<span style={{ fontSize: '64px' }}>{icon}</span>}
        description={
          <div>
            <p style={{ fontSize: '18px', fontWeight: 'bold' }}>{title}</p>
            <p style={{ color: '#94a3b8' }}>{description}</p>
          </div>
        }
      >
        {actionText && (
          <Button type="primary" onClick={handleAction}>
            {actionText}
          </Button>
        )}
      </Empty>
    </div>
  );
}
