'use client';

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import {
  getTranslation,
  locales,
  defaultLocale,
  directionMap,
} from '@/lib/i18n';

const LanguageContext = createContext(null);
const STORAGE_KEY = 'locale';
const COOKIE_NAME = 'doctorweb_locale';

function normalizeLocale(value) {
  return locales.includes(value) ? value : defaultLocale;
}

function writeLocaleCookie(locale) {
  document.cookie = `${COOKIE_NAME}=${locale}; Path=/; Max-Age=31536000; SameSite=Lax`;
}

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState(defaultLocale);

  useEffect(() => {
    const savedLocale = normalizeLocale(
      window.localStorage.getItem(STORAGE_KEY)
    );

    setLocale(savedLocale);
  }, []);

  useEffect(() => {
    const direction = directionMap[locale] || 'rtl';

    document.documentElement.lang = locale;
    document.documentElement.dir = direction;
    document.body?.setAttribute('dir', direction);
  }, [locale]);

  const changeLanguage = useCallback((nextLocale) => {
    const normalized = normalizeLocale(nextLocale);

    setLocale(normalized);
    window.localStorage.setItem(STORAGE_KEY, normalized);
    writeLocaleCookie(normalized);

    // URL intentionally remains unchanged.
  }, []);

  const translations = useMemo(
    () => getTranslation(locale) || {},
    [locale]
  );

  const t = useCallback((key, fallback) => {
    const value = key
      .split('.')
      .reduce((current, part) => {
        if (
          current &&
          typeof current === 'object' &&
          Object.prototype.hasOwnProperty.call(current, part)
        ) {
          return current[part];
        }

        return undefined;
      }, translations);

    return value ?? fallback ?? key;
  }, [translations]);

  const direction = directionMap[locale] || 'rtl';

  const value = useMemo(() => ({
    locale,
    direction,
    isRtl: direction === 'rtl',
    t,
    changeLanguage,
  }), [locale, direction, t, changeLanguage]);

  return (
    <LanguageContext.Provider value={value}>
      {children}
    </LanguageContext.Provider>
  );
}

export function useLanguage() {
  const context = useContext(LanguageContext);

  if (!context) {
    throw new Error(
      'useLanguage must be used within LanguageProvider'
    );
  }

  return context;
}

export default LanguageContext;
