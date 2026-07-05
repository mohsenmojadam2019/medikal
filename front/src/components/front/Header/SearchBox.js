'use client';

import { Input } from 'antd';
import { SearchOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function SearchBox() {
  const { t } = useLanguage();

  return (
    <div className="search-box">
      <Input
        size="large"
        placeholder={t('common.search')}
        prefix={<SearchOutlined />}
        suffix={<span className="search-shortcut">{t('common.searchShortcut')}</span>}
        onPressEnter={(e) => {
          console.log('Search:', e.target.value);
        }}
      />
    </div>
  );
}
