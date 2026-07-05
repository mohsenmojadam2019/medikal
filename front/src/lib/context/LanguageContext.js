'use client';

import { createContext, useContext, useState, useEffect } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { locales, defaultLocale, directionMap } from '@/lib/i18n';

const LanguageContext = createContext();

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState(defaultLocale);
  const [direction, setDirection] = useState('rtl');
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    const storedLocale = localStorage.getItem('locale') || defaultLocale;
    // اگه زبان در URL موجود نیست، تنظیم کن
    if (pathname) {
      const segments = pathname.split('/');
      if (segments.length > 1 && locales.includes(segments[1])) {
        setLocale(segments[1]);
      } else {
        // اگه زبان در URL نیست، به مسیر با زبان هدایت کن
        router.push(`/${storedLocale}${pathname}`);
      }
    }
  }, []);

  const changeLanguage = (newLocale) => {
    if (!locales.includes(newLocale)) return;

    setLocale(newLocale);
    setDirection(directionMap[newLocale]);
    localStorage.setItem('locale', newLocale);

    // تغییر مسیر با زبان جدید
    if (pathname) {
      const segments = pathname.split('/');
      if (segments.length > 1 && locales.includes(segments[1])) {
        segments[1] = newLocale;
        router.push(segments.join('/'));
      } else {
        router.push(`/${newLocale}${pathname}`);
      }
    }
  };

  const t = (key) => {
    try {
      const translations = require(`@/lib/i18n/${locale}.json`);
      return key.split('.').reduce((obj, k) => obj?.[k] || key, translations);
    } catch (error) {
      console.warn('Translation error for key:', key, error);
      return key;
    }
  };

  const value = {
    locale,
    direction,
    changeLanguage,
    t,
  };

  return (
    <LanguageContext.Provider value={value}>
      {children}
    </LanguageContext.Provider>
  );
}

export function useLanguage() {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within LanguageProvider');
  }
  return context;
}
