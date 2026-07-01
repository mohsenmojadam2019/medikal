import client from '../client';

export const landingService = {
  // ===== دریافت تنظیمات صفحه اصلی =====
  getSettings: async () => {
    return client.get('/api/landing');
  },

  // ===== به‌روزرسانی صفحه اصلی =====
  update: async (data) => {
    return client.post('/api/admin/landing', data);
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/landing/stats');
  },

  // ===== دریافت پزشکان برتر =====
  getTopDoctors: async () => {
    return client.get('/api/landing/top-doctors');
  },

  // ===== دریافت نظرات اخیر =====
  getRecentReviews: async () => {
    return client.get('/api/landing/recent-reviews');
  },
};

export default landingService;
