'use client';

import { Button, Dropdown, message } from 'antd';
import { ShareAltOutlined, CopyOutlined, WhatsAppOutlined, TelegramOutlined, TwitterOutlined } from '@ant-design/icons';

export default function ShareButton({ url, title }) {
  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(url);
      message.success('✅ لینک کپی شد');
    } catch {
      message.error('❌ خطا در کپی لینک');
    }
  };

  const items = [
    {
      key: 'copy',
      label: 'کپی لینک',
      icon: <CopyOutlined />,
      onClick: handleCopy,
    },
    {
      key: 'whatsapp',
      label: 'واتساپ',
      icon: <WhatsAppOutlined />,
      onClick: () => window.open(`https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`, '_blank'),
    },
    {
      key: 'telegram',
      label: 'تلگرام',
      icon: <TelegramOutlined />,
      onClick: () => window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank'),
    },
    {
      key: 'twitter',
      label: 'توییتر',
      icon: <TwitterOutlined />,
      onClick: () => window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`, '_blank'),
    },
  ];

  return (
    <Dropdown menu={{ items }} placement="bottomRight">
      <Button icon={<ShareAltOutlined />}>اشتراک‌گذاری</Button>
    </Dropdown>
  );
}
