// src/services/api/admin/invoices.js

import client from '../client';

export const invoicesService = {
  getAll: async (params = {}) => {
    // ✅ مسیر درست (با /api)
    return client.get('/api/admin/invoices', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/invoices', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/invoices/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/invoices/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/invoices/${id}`);
  },

  changeStatus: async (id, status) => {
    return client.post(`/api/admin/invoices/${id}/status`, { status });
  },

  print: async (id) => {
    return client.get(`/api/admin/invoices/${id}/print`);
  },

  getStats: async () => {
    return client.get('/api/admin/invoices/stats');
  },

  getPatientInvoices: async (patientId) => {
    return client.get(`/api/invoices/patient/${patientId}`);
  },
};

export default invoicesService;