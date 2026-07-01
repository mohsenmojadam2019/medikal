import client from '../client';

export const prescriptionsService = {
  // ===== لیست نسخه‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/prescriptions', { params });
  },

  // ===== ایجاد نسخه جدید =====
  create: async (data) => {
    return client.post('/api/admin/prescriptions', data);
  },

  // ===== مشاهده نسخه =====
  getById: async (id) => {
    return client.get(`/api/admin/prescriptions/${id}`);
  },

  // ===== ویرایش نسخه =====
  update: async (id, data) => {
    return client.put(`/api/admin/prescriptions/${id}`, data);
  },

  // ===== حذف نسخه =====
  delete: async (id) => {
    return client.delete(`/api/admin/prescriptions/${id}`);
  },

  // ===== تغییر وضعیت =====
  changeStatus: async (id, status) => {
    return client.post(`/api/admin/prescriptions/${id}/status`, { status });
  },

  // ===== چاپ نسخه =====
  print: async (id) => {
    return client.get(`/api/admin/prescriptions/${id}/print`);
  },

  // ===== بررسی تداخلات =====
  checkInteractions: async (id) => {
    return client.get(`/api/admin/prescriptions/${id}/interactions`);
  },

  // ===== آمار نسخه‌ها =====
  getStats: async () => {
    return client.get('/api/admin/prescriptions/stats');
  },
};

export default prescriptionsService;
