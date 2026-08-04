'use client';

import { Button, Dropdown } from 'antd';
import { GlobalOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

const languages = {
  fa: { label: 'فارسی', flag: '🇮🇷' },
  en: { label: 'English', flag: '🇬🇧' },
  ar: { label: 'العربية', flag: '🇸🇦' },
};

export default function LanguageSwitcher() {
  const { locale, changeLanguage } = useLanguage();

  const items = Object.entries(languages).map(([key, item]) => ({
    key,
    label: `${item.flag} ${item.label}`,
  }));

  return (
    <Dropdown
      trigger={['click']}
      placement={locale === 'en' ? 'bottomLeft' : 'bottomRight'}
      menu={{
        items,
        selectable: true,
        selectedKeys: [locale],
        onClick: ({ key }) => changeLanguage(key),
      }}
    >
      <Button type="text" icon={<GlobalOutlined />}>
        {languages[locale]?.flag} {languages[locale]?.label}
      </Button>
    </Dropdown>
  );
}
