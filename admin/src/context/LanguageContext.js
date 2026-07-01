'use client';

import { createContext, useState, useEffect, useContext, useCallback } from 'react';
import { languageService } from '@/services/api';

const LanguageContext = createContext();

export const SUPPORTED_LANGUAGES = {
  fa: { code: 'fa', name: 'فارسی', direction: 'rtl', nativeName: 'فارسی' },
  ar: { code: 'ar', name: 'العربية', direction: 'rtl', nativeName: 'العربية' },
  en: { code: 'en', name: 'English', direction: 'ltr', nativeName: 'English' },
};

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState('fa');
  const [direction, setDirection] = useState('rtl');
  const [translations, setTranslations] = useState({});
  const [loading, setLoading] = useState(true);
  const [languages, setLanguages] = useState([]);

  const loadTranslations = async (localeCode) => {
    try {
      const response = await languageService.getTranslations(localeCode);
      if (response?.data?.translations) {
        setTranslations(response.data.translations);
      }
    } catch (error) {
      console.error('Error loading translations:', error);
      setTranslations({});
    }
  };

  const loadLanguage = useCallback(async () => {
    try {
      const savedLocale = localStorage.getItem('locale');
      if (savedLocale && SUPPORTED_LANGUAGES[savedLocale]) {
        setLocale(savedLocale);
        setDirection(SUPPORTED_LANGUAGES[savedLocale].direction);
        await loadTranslations(savedLocale);
        setLoading(false);
        return;
      }

      const response = await languageService.getCurrent();
      if (response?.data?.locale) {
        const localeFromServer = response.data.locale;
        if (SUPPORTED_LANGUAGES[localeFromServer]) {
          setLocale(localeFromServer);
          setDirection(response.data.direction || SUPPORTED_LANGUAGES[localeFromServer].direction);
          localStorage.setItem('locale', localeFromServer);
          await loadTranslations(localeFromServer);
          setLoading(false);
          return;
        }
      }

      setLocale('fa');
      setDirection('rtl');
      localStorage.setItem('locale', 'fa');
      await loadTranslations('fa');
    } catch (error) {
      console.error('Error loading language:', error);
      setLocale('fa');
      setDirection('rtl');
      await loadTranslations('fa');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadLanguages = useCallback(async () => {
    try {
      const response = await languageService.getLanguages();
      if (response?.data?.languages) {
        setLanguages(response.data.languages);
      } else {
        setLanguages(Object.values(SUPPORTED_LANGUAGES));
      }
    } catch (error) {
      console.error('Error loading languages:', error);
      setLanguages(Object.values(SUPPORTED_LANGUAGES));
    }
  }, []);

  const switchLanguage = useCallback(async (newLocale) => {
    if (newLocale === locale) return;

    setLoading(true);
    try {
      const response = await languageService.switch(newLocale);
      if (response?.data) {
        const { locale: newLocaleCode, direction: newDirection } = response.data;

        setLocale(newLocaleCode);
        setDirection(newDirection || SUPPORTED_LANGUAGES[newLocaleCode]?.direction || 'ltr');
        localStorage.setItem('locale', newLocaleCode);

        await loadTranslations(newLocaleCode);

        document.documentElement.dir = newDirection || 'ltr';
        document.documentElement.lang = newLocaleCode;

        return true;
      }
      return false;
    } catch (error) {
      console.error('Error switching language:', error);
      return false;
    } finally {
      setLoading(false);
    }
  }, [locale]);

  const t = useCallback((key, fallback = '') => {
    const parts = key.split('.');
    const group = parts.length > 1 ? parts[0] : 'messages';
    const keyName = parts.length > 1 ? parts.slice(1).join('.') : parts[0];

    if (translations[group] && translations[group][keyName]) {
      return translations[group][keyName];
    }

    return fallback || key;
  }, [translations]);

  useEffect(() => {
    loadLanguage();
    loadLanguages();
  }, [loadLanguage, loadLanguages]);

  useEffect(() => {
    if (direction) {
      document.documentElement.dir = direction;
      document.documentElement.lang = locale;
    }
  }, [direction, locale]);

  const value = {
    locale,
    direction,
    translations,
    languages,
    loading,
    t,
    switchLanguage,
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
    throw new Error('useLanguage must be used within a LanguageProvider');
  }
  return context;
}

export default LanguageContext;
