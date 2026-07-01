import client from '../client';

export const paymentsService = {
  // ===== لیست پرداخت‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/payments', { params });
  },

  // ===== مشاهده پرداخت =====
  getById: async (id) => {
    return client.get(`/api/admin/payments/${id}`);
  },

  // ===== بازگشت وجه =====
  refund: async (id) => {
    return client.post(`/api/admin/payments/${id}/refund`);
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/admin/payments/stats');
  },

  // ===== دریافت درگاه‌های پرداخت =====
  getGateways: async () => {
    return client.get('/api/payments/gateways');
  },
};

export default paymentsService;
