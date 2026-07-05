'use client';

import { Dropdown, Button } from 'antd';
import { GlobalOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function LanguageSwitcher() {
  const { locale, changeLanguage } = useLanguage();

  const languages = {
    fa: { label: 'فارسی', flag: '🇮🇷' },
    en: { label: 'English', flag: '🇬🇧' },
    ar: { label: 'العربية', flag: '🇸🇦' },
  };

  const items = [
    {
      key: 'fa',
      label: (
        <span onClick={() => changeLanguage('fa')}>
          {languages.fa.flag} {languages.fa.label}
        </span>
      ),
    },
    {
      key: 'en',
      label: (
        <span onClick={() => changeLanguage('en')}>
          {languages.en.flag} {languages.en.label}
        </span>
      ),
    },
    {
      key: 'ar',
      label: (
        <span onClick={() => changeLanguage('ar')}>
          {languages.ar.flag} {languages.ar.label}
        </span>
      ),
    },
  ];

  return (
    <Dropdown menu={{ items }} placement="bottomRight">
      <Button type="text" icon={<GlobalOutlined />}>
        {languages[locale]?.flag} {languages[locale]?.label}
      </Button>
    </Dropdown>
  );
}
