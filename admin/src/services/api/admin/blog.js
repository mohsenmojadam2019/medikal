// src/services/api/admin/blog.js

import client from '../client';

// ===== سرویس‌ها با Named Export =====
export const blogService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/blog/posts', { params });
  },
  create: async (data) => {
    return client.post('/api/admin/blog/posts', data);
  },
  getById: async (id) => {
    return client.get(`/api/admin/blog/posts/${id}`);
  },
  update: async (id, data) => {
    return client.post(`/api/admin/blog/posts/${id}?_method=PUT`, data);
  },
  delete: async (id) => {
    return client.delete(`/api/admin/blog/posts/${id}`);
  },
  publish: async (id) => {
    return client.post(`/api/admin/blog/posts/${id}/publish`);
  },
  unpublish: async (id) => {
    return client.post(`/api/admin/blog/posts/${id}/unpublish`);
  },
  getStats: async () => {
    return client.get('/api/admin/blog/stats');
  },
  getPublicPosts: async (params = {}) => {
    return client.get('/api/blog/posts', { params });
  },
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

// ===== Default Export برای راحتی =====
const blogServices = {
  blogService,
  categoriesService,
  tagsService,
  commentsService,
};

export default blogServices;