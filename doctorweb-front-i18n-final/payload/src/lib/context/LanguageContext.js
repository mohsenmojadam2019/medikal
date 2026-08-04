'use client';

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import generatedMessages from '@/i18n/messages.generated.json';

const LanguageContext = createContext(null);

export const SUPPORTED_LANGUAGES = Object.freeze({
  fa: Object.freeze({
    code: 'fa',
    name: 'Persian',
    nativeName: 'فارسی',
    direction: 'rtl',
    intl: 'fa-IR',
  }),
  ar: Object.freeze({
    code: 'ar',
    name: 'Arabic',
    nativeName: 'العربية',
    direction: 'rtl',
    intl: 'ar-SA',
  }),
  en: Object.freeze({
    code: 'en',
    name: 'English',
    nativeName: 'English',
    direction: 'ltr',
    intl: 'en-US',
  }),
});

const DEFAULT_LOCALE = 'fa';
const STORAGE_KEY = 'locale';

function normalizeLocale(value) {
  return Object.hasOwn(SUPPORTED_LANGUAGES, value)
    ? value
    : DEFAULT_LOCALE;
}

function interpolate(value, variables = {}) {
  return Object.entries(variables).reduce(
    (output, [key, replacement]) =>
      output.replaceAll(`{${key}}`, String(replacement ?? '')),
    String(value ?? ''),
  );
}

function unwrapPayload(payload) {
  return payload?.data?.data ?? payload?.data ?? payload ?? {};
}

async function fetchJson(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'include',
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
  });

  if (!response.ok) {
    throw new Error(`Language API returned ${response.status}`);
  }

  return response.json();
}

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState(DEFAULT_LOCALE);
  const [remoteTranslations, setRemoteTranslations] = useState({});
  const [loading, setLoading] = useState(true);
  const [revision, setRevision] = useState(0);

  const languages = useMemo(
    () => Object.values(SUPPORTED_LANGUAGES),
    [],
  );

  const direction =
    SUPPORTED_LANGUAGES[locale]?.direction || 'rtl';

  const applyDocumentLanguage = useCallback((nextLocale) => {
    const language =
      SUPPORTED_LANGUAGES[nextLocale] ||
      SUPPORTED_LANGUAGES[DEFAULT_LOCALE];

    document.documentElement.lang = language.code;
    document.documentElement.dir = language.direction;
    document.body?.setAttribute('dir', language.direction);
  }, []);

  const loadRemoteTranslations = useCallback(async (nextLocale) => {
    try {
      const payload = await fetchJson(
        `/backend-api/api/language/translations?locale=${encodeURIComponent(nextLocale)}`,
      );
      const data = unwrapPayload(payload);
      setRemoteTranslations(data.translations || {});
    } catch {
      setRemoteTranslations({});
    }
  }, []);

  useEffect(() => {
    const saved = window.localStorage.getItem(STORAGE_KEY);
    const initialLocale = normalizeLocale(saved);

    setLocale(initialLocale);
    applyDocumentLanguage(initialLocale);

    loadRemoteTranslations(initialLocale)
      .finally(() => setLoading(false));
  }, [applyDocumentLanguage, loadRemoteTranslations]);

  const switchLanguage = useCallback(async (requestedLocale) => {
    const nextLocale = normalizeLocale(requestedLocale);

    setLocale(nextLocale);
    setLoading(true);
    setRevision((value) => value + 1);

    window.localStorage.setItem(STORAGE_KEY, nextLocale);
    document.cookie =
      `locale=${nextLocale}; Path=/; Max-Age=31536000; SameSite=Lax`;

    applyDocumentLanguage(nextLocale);

    try {
      const payload = await fetchJson(
        '/backend-api/api/language/switch',
        {
          method: 'POST',
          body: JSON.stringify({ locale: nextLocale }),
        },
      );
      const data = unwrapPayload(payload);
      setRemoteTranslations(data.translations || {});
    } catch {
      await loadRemoteTranslations(nextLocale);
    } finally {
      setLoading(false);
      setRevision((value) => value + 1);
    }
  }, [applyDocumentLanguage, loadRemoteTranslations]);

  const localMessages =
    generatedMessages?.[locale] || generatedMessages?.fa || {};

  const t = useCallback((key, fallback = key, variables = {}) => {
    const value =
      remoteTranslations?.[key] ??
      localMessages?.[key] ??
      fallback ??
      key;

    return interpolate(value, variables);
  }, [localMessages, remoteTranslations]);

  const phraseIndex = useMemo(() => {
    const index = new Map();
    const persian = generatedMessages?.fa || {};
    const selected = generatedMessages?.[locale] || {};

    Object.entries(persian).forEach(([key, source]) => {
      if (typeof source !== 'string') return;
      index.set(source.trim(), selected[key] ?? source);
    });

    return index;
  }, [locale]);

  const translateText = useCallback((input) => {
    if (locale === 'fa' || typeof input !== 'string') {
      return input;
    }

    const leading = input.match(/^\s*/)?.[0] || '';
    const trailing = input.match(/\s*$/)?.[0] || '';
    const core = input.trim();

    if (!core) return input;

    const direct = phraseIndex.get(core);
    if (direct) return `${leading}${direct}${trailing}`;

    let output = core;
    const entries = [...phraseIndex.entries()]
      .filter(([source]) => source.length >= 4)
      .sort(([a], [b]) => b.length - a.length);

    for (const [source, translated] of entries) {
      if (output.includes(source)) {
        output = output.split(source).join(translated);
      }
    }

    return `${leading}${output}${trailing}`;
  }, [locale, phraseIndex]);

  const value = useMemo(() => ({
    locale,
    direction,
    languages,
    loading,
    revision,
    t,
    translateText,
    switchLanguage,
    changeLanguage: switchLanguage,
  }), [
    locale,
    direction,
    languages,
    loading,
    revision,
    t,
    translateText,
    switchLanguage,
  ]);

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
      'useLanguage must be used within LanguageProvider',
    );
  }

  return context;
}

export default LanguageContext;
