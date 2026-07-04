// src/services/api/admin/prescriptions.js

import client from '../client';

export const prescriptionsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/prescriptions', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/prescriptions', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/prescriptions/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/prescriptions/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/prescriptions/${id}`);
  },

  changeStatus: async (id, status) => {
    return client.post(`/api/admin/prescriptions/${id}/status`, { status });
  },

  print: async (id) => {
    return client.get(`/api/admin/prescriptions/${id}/print`);
  },

  checkInteractions: async (id) => {
    return client.get(`/api/admin/prescriptions/${id}/interactions`);
  },

  getStats: async () => {
    return client.get('/api/admin/prescriptions/stats');
  },
};

export default prescriptionsService;