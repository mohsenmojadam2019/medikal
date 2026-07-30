// src/services/api/admin/products.js

import client from '../client';

export const productsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/products', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/products', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/products/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/products/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/products/${id}`);
  },

  toggleStatus: async (id) => {
    return client.post(`/api/admin/products/${id}/toggle-status`);
  },

  increaseStock: async (id, quantity) => {
    return client.post(`/api/admin/products/${id}/increase-stock`, { quantity });
  },

  decreaseStock: async (id, quantity) => {
    return client.post(`/api/admin/products/${id}/decrease-stock`, { quantity });
  },

  search: async (query) => {
    return client.get('/api/products/search', { params: { q: query } });
  },

  getCategories: async () => {
    return client.get('/api/admin/products/categories');
  },

  getActive: async (params = {}) => {
    return client.get('/api/products/active', { params });
  },
};

export default productsService;