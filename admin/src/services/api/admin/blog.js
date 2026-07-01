import client from '../client';

export const blogService = {
  // ===== لیست مقالات =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/blog/posts', { params });
  },

  // ===== ایجاد مقاله جدید =====
  create: async (data) => {
    return client.post('/api/admin/blog/posts', data);
  },

  // ===== مشاهده مقاله =====
  getById: async (id) => {
    return client.get(`/api/admin/blog/posts/${id}`);
  },

  // ===== ویرایش مقاله =====
  update: async (id, data) => {
    return client.post(`/api/admin/blog/posts/${id}?_method=PUT`, data);
  },

  // ===== حذف مقاله =====
  delete: async (id) => {
    return client.delete(`/api/admin/blog/posts/${id}`);
  },

  // ===== انتشار مقاله =====
  publish: async (id) => {
    return client.post(`/api/admin/blog/posts/${id}/publish`);
  },

  // ===== خارج از انتشار =====
  unpublish: async (id) => {
    return client.post(`/api/admin/blog/posts/${id}/unpublish`);
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/admin/blog/stats');
  },

  // ===== دریافت مقالات عمومی =====
  getPublicPosts: async (params = {}) => {
    return client.get('/api/blog/posts', { params });
  },

  // ===== دریافت مقاله عمومی با اسلاگ =====
  getPublicBySlug: async (slug) => {
    return client.get(`/api/blog/posts/${slug}`);
  },
};

export const categoriesService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/blog/categories', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/blog/categories', data);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/blog/categories/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/blog/categories/${id}`);
  },
};

export const tagsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/blog/tags', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/blog/tags', data);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/blog/tags/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/blog/tags/${id}`);
  },
};

export const commentsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/blog/comments', { params });
  },

  approve: async (id) => {
    return client.post(`/api/admin/blog/comments/${id}/approve`);
  },

  reject: async (id) => {
    return client.post(`/api/admin/blog/comments/${id}/reject`);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/blog/comments/${id}`);
  },
};
