import client from '../client';

export const labService = {
  // ============================================
  // سفارشات
  // ============================================
  getOrders: async (params = {}) => {
    return client.get('/admin/lab/orders', { params });
  },

  getOrder: async (id) => {
    return client.get(`/admin/lab/orders/${id}`);
  },

  updateOrderStatus: async (id, data) => {
    return client.put(`/admin/lab/orders/${id}/status`, data);
  },

  deleteOrder: async (id) => {
    return client.delete(`/admin/lab/orders/${id}`);
  },

  // ============================================
  // تست‌ها
  // ============================================
  getTests: async (params = {}) => {
    return client.get('/admin/lab/tests', { params });
  },

  getTest: async (id) => {
    return client.get(`/admin/lab/tests/${id}`);
  },

  createTest: async (data) => {
    return client.post('/admin/lab/tests', data);
  },

  updateTest: async (id, data) => {
    return client.put(`/admin/lab/tests/${id}`, data);
  },

  deleteTest: async (id) => {
    return client.delete(`/admin/lab/tests/${id}`);
  },

  toggleTestStatus: async (id) => {
    return client.post(`/admin/lab/tests/${id}/toggle`);
  },

  // ============================================
  // دسته‌بندی‌ها
  // ============================================
  getCategories: async (params = {}) => {
    return client.get('/admin/lab/categories', { params });
  },

  getCategory: async (id) => {
    return client.get(`/admin/lab/categories/${id}`);
  },

  createCategory: async (data) => {
    return client.post('/admin/lab/categories', data);
  },

  updateCategory: async (id, data) => {
    return client.put(`/admin/lab/categories/${id}`, data);
  },

  deleteCategory: async (id) => {
    return client.delete(`/admin/lab/categories/${id}`);
  },

  toggleCategoryStatus: async (id) => {
    return client.post(`/admin/lab/categories/${id}/toggle`);
  },

  // ============================================
  // آمار
  // ============================================
  getStats: async (params = {}) => {
    return client.get('/admin/lab/stats', { params });
  },
};

export default labService;
