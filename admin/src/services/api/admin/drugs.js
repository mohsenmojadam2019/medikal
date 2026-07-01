import client from '../client';

export const drugsService = {
  // ===== لیست داروها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/drugs', { params });
  },

  // ===== ایجاد دارو جدید =====
  create: async (data) => {
    return client.post('/api/admin/drugs', data);
  },

  // ===== مشاهده دارو =====
  getById: async (id) => {
    return client.get(`/api/admin/drugs/${id}`);
  },

  // ===== ویرایش دارو =====
  update: async (id, data) => {
    return client.put(`/api/admin/drugs/${id}`, data);
  },

  // ===== حذف دارو =====
  delete: async (id) => {
    return client.delete(`/api/admin/drugs/${id}`);
  },

  // ===== تغییر وضعیت =====
  toggleStatus: async (id) => {
    return client.post(`/api/admin/drugs/${id}/toggle-status`);
  },

  // ===== افزایش موجودی =====
  increaseStock: async (id, quantity) => {
    return client.post(`/api/admin/drugs/${id}/increase-stock`, { quantity });
  },

  // ===== کاهش موجودی =====
  decreaseStock: async (id, quantity) => {
    return client.post(`/api/admin/drugs/${id}/decrease-stock`, { quantity });
  },

  // ===== جستجوی عمومی =====
  search: async (query) => {
    return client.get('/api/drugs/search', { params: { q: query } });
  },

  // ===== دریافت دسته‌بندی‌ها =====
  getCategories: async () => {
    return client.get('/api/admin/drugs/categories');
  },

  // ===== دریافت داروهای فعال =====
  getActive: async (params = {}) => {
    return client.get('/api/drugs/active', { params });
  },
};

export default drugsService;
