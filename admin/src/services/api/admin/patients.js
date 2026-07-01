import client from '../client';

export const patientsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/patients', { params });
  },

  create: async (data) => {
    return client.post('/api/admin/patients', data);
  },

  getById: async (id) => {
    return client.get(`/api/admin/patients/${id}`);
  },

  update: async (id, data) => {
    return client.put(`/api/admin/patients/${id}`, data);
  },

  delete: async (id) => {
    return client.delete(`/api/admin/patients/${id}`);
  },

  toggleStatus: async (id) => {
    return client.post(`/api/admin/patients/${id}/toggle-status`);
  },

  verify: async (id) => {
    return client.post(`/api/admin/patients/${id}/verify`);
  },

  unverify: async (id) => {
    return client.post(`/api/admin/patients/${id}/unverify`);
  },

  assignDoctor: async (id, doctorId) => {
    return client.post(`/api/admin/patients/${id}/assign-doctor`, { doctor_id: doctorId });
  },

  getMedicalHistory: async (id) => {
    return client.get(`/api/admin/patients/${id}/medical-history`);
  },

  getStatistics: async (id) => {
    return client.get(`/api/admin/patients/${id}/statistics`);
  },

  getWithoutDoctor: async () => {
    return client.get('/api/admin/patients/without-doctor');
  },

  getTopPatients: async (limit = 10) => {
    return client.get('/api/admin/patients/top', { params: { limit } });
  },
};

export default patientsService;
