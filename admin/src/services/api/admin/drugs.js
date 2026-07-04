// src/services/api/admin/drugs.js

import client from '../client';

export const drugsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/drugs', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/drugs', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/drugs/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/drugs/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/drugs/${id}`);
  },

  toggleStatus: async (id) => {
    return client.post(`/api/admin/drugs/${id}/toggle-status`);
  },

  increaseStock: async (id, quantity) => {
    return client.post(`/api/admin/drugs/${id}/increase-stock`, { quantity });
  },

  decreaseStock: async (id, quantity) => {
    return client.post(`/api/admin/drugs/${id}/decrease-stock`, { quantity });
  },

  search: async (query) => {
    return client.get('/api/drugs/search', { params: { q: query } });
  },

  getCategories: async () => {
    return client.get('/api/admin/drugs/categories');
  },

  getActive: async (params = {}) => {
    return client.get('/api/drugs/active', { params });
  },
};

export default drugsService;