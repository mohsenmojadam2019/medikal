import fa from './fa.json';
import en from './en.json';
import ar from './ar.json';

const translations = { fa, en, ar };

export function getTranslation(locale = 'fa') {
  return translations[locale] || translations.fa;
}

export const locales = ['fa', 'en', 'ar'];
export const defaultLocale = 'fa';

export const directionMap = {
  fa: 'rtl',
  ar: 'rtl',
  en: 'ltr',
};
