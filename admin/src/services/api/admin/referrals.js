// src/services/api/admin/referrals.js

import client from '../client';

export const referralsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/referrals', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/referrals', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/referrals/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/referrals/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/referrals/${id}`);
  },

  accept: async (id) => {
    return client.post(`/api/admin/referrals/${id}/accept`);
  },

  reject: async (id) => {
    return client.post(`/api/admin/referrals/${id}/reject`);
  },

  complete: async (id) => {
    return client.post(`/api/admin/referrals/${id}/complete`);
  },

  getStats: async () => {
    return client.get('/api/admin/referrals/stats');
  },
};

export default referralsService;