import client from '../client';

export const doctorsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/doctors', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/doctors', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/doctors/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/doctors/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/doctors/${id}`);
  },

  toggleAvailability: async (id) => {
    return client.post(`/api/admin/doctors/${id}/toggle-availability`);
  },

  verify: async (id) => {
    return client.post(`/api/admin/doctors/${id}/verify`);
  },
};

export default doctorsService;
