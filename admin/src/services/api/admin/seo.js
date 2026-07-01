import client from '../client';

export const seoService = {
  // ===== لیست سئوها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/seo', { params });
  },

  // ===== مشاهده سئو =====
  getById: async (id) => {
    return client.get(`/api/admin/seo/${id}`);
  },

  // ===== دریافت سئوی یک مدل =====
  getByModel: async (type, id) => {
    return client.get('/api/admin/seo/model', { params: { type, id } });
  },

  // ===== ایجاد سئو =====
  create: async (data) => {
    return client.post('/api/admin/seo', data);
  },

  // ===== ویرایش سئو =====
  update: async (id, data) => {
    return client.put(`/api/admin/seo/${id}`, data);
  },

  // ===== حذف سئو =====
  delete: async (id) => {
    return client.delete(`/api/admin/seo/${id}`);
  },
};

export default seoService;
