'use client';

import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { getTranslation, locales, defaultLocale, directionMap } from '@/lib/i18n';

const LanguageContext = createContext();

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState(defaultLocale);
  const [translations, setTranslations] = useState({});

  const t = useCallback((key) => {
    const keys = key.split('.');
    let value = translations;
    for (const k of keys) {
      if (value && typeof value === 'object' && k in value) {
        value = value[k];
      } else {
        return key;
      }
    }
    return value || key;
  }, [translations]);

  useEffect(() => {
    const savedLocale = localStorage.getItem('locale') || defaultLocale;
    setLocale(savedLocale);
    setTranslations(getTranslation(savedLocale));
  }, []);

  const changeLanguage = (newLocale) => {
    if (locales.includes(newLocale)) {
      setLocale(newLocale);
      localStorage.setItem('locale', newLocale);
      setTranslations(getTranslation(newLocale));
      document.documentElement.dir = directionMap[newLocale];
    }
  };

  useEffect(() => {
    document.documentElement.dir = directionMap[locale];
  }, [locale]);

  return (
    <LanguageContext.Provider value={{ locale, t, changeLanguage }}>
      {children}
    </LanguageContext.Provider>
  );
}

export function useLanguage() {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within a LanguageProvider');
  }
  return context;
}
