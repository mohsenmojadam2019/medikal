import client from './client';

export const languageService = {
  getLanguages: async () => {
    return client.get('/api/language');
  },

  getCurrent: async () => {
    return client.get('/api/language/current');
  },

  switch: async (locale) => {
    return client.post('/api/language/switch', { locale });
  },

  getTranslations: async (locale) => {
    return client.get('/api/language/translations', { params: { locale } });
  },

  getTranslation: async (key, locale) => {
    return client.get('/api/language/translate', { params: { key, locale } });
  },
};

export default languageService;
