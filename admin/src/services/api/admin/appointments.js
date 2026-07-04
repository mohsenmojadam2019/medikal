// src/services/api/admin/appointments.js

import client from '../client';

export const appointmentsService = {
  getAll: async (params = {}) => {
    // ✅ مسیر درست (بدون /api اضافی چون client baseURL داره)
    return client.get('/admin/appointments', { params });
  },

  create: async (data) => {
    return client.post('/admin/appointments', data);
  },

  getById: async (id) => {
    return client.get(`/admin/appointments/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/admin/appointments/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/admin/appointments/${id}`);
  },

  changeStatus: async (id, status) => {
    return client.post(`/admin/appointments/${id}/status`, { status });
  },

  confirm: async (id) => {
    return client.post(`/admin/appointments/${id}/confirm`);
  },

  cancel: async (id) => {
    return client.post(`/admin/appointments/${id}/cancel`);
  },

  start: async (id) => {
    return client.post(`/admin/appointments/${id}/start`);
  },

  complete: async (id) => {
    return client.post(`/admin/appointments/${id}/complete`);
  },

  getAvailableSlots: async (doctorId, date) => {
    return client.get(`/admin/appointments/doctors/${doctorId}/available-slots`, { params: { date } });
  },

  getStats: async () => {
    return client.get('/admin/appointments/stats');
  },
};

export default appointmentsService;