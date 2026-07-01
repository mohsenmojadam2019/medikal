import client from '../client';

export const walletService = {
  // ===== لیست کیف پول‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/wallet', { params });
  },

  // ===== مشاهده کیف پول کاربر =====
  getByUser: async (userId) => {
    return client.get(`/api/admin/wallet/${userId}`);
  },

  // ===== تغییر وضعیت =====
  toggleStatus: async (userId) => {
    return client.post(`/api/admin/wallet/${userId}/toggle-status`);
  },

  // ===== افزودن پاداش =====
  addBonus: async (userId, amount, description) => {
    return client.post(`/api/admin/wallet/${userId}/add-bonus`, { amount, description });
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/admin/wallet/stats');
  },

  // ===== دریافت موجودی کاربر =====
  getBalance: async () => {
    return client.get('/api/wallet/balance');
  },

  // ===== دریافت تراکنش‌های کاربر =====
  getTransactions: async (params = {}) => {
    return client.get('/api/wallet/transactions', { params });
  },
};

export default walletService;
