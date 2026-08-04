'use client';

import { useLanguage } from '@/lib/context/LanguageContext';

export default function TopBar() {
  const { t } = useLanguage();

  return (
    <div className="header-top">
      <div className="container header-top-inner">
        <div className="left">
          <span>
            <i className="fas fa-phone" /> {t('common.phone')}
          </span>
          <span>
            <i className="fas fa-clock" /> {t('common.time')}
          </span>
          <span>
            <i className="fas fa-map-marker-alt" /> {t('common.address')}
          </span>
        </div>
        <div className="right">
          <a href="#">
            <i className="fas fa-headset" /> پشتیبانی
          </a>
          <a href="#">
            <i className="fas fa-download" /> دانلود اپلیکیشن
          </a>
          <a href="#">
            <i className="fas fa-question-circle" /> راهنما
          </a>
        </div>
      </div>
    </div>
  );
}
