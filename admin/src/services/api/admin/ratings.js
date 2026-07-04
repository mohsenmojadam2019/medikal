// src/services/api/admin/ratings.js

import client from '../client';

export const ratingsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/ratings', { params });
  },

  getById: async (id) => {
    return client.get(`/api/admin/ratings/${id}`);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/ratings/${id}`);
  },

  approve: async (id) => {
    return client.post(`/api/admin/ratings/${id}/approve`);
  },

  reject: async (id) => {
    return client.post(`/api/admin/ratings/${id}/reject`);
  },

  reply: async (id, reply) => {
    return client.post(`/api/admin/ratings/${id}/reply`, { reply });
  },

  deleteReply: async (id) => {
    return client.post(`/api/admin/ratings/${id}/delete-reply`);
  },

  getStats: async () => {
    return client.get('/api/admin/ratings/stats');
  },
};

export default ratingsService;