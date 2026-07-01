import client from '../client';

export const referralsService = {
  // ===== لیست ارجاعات =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/referrals', { params });
  },

  // ===== ایجاد ارجاع جدید =====
  create: async (data) => {
    return client.post('/api/admin/referrals', data);
  },

  // ===== مشاهده ارجاع =====
  getById: async (id) => {
    return client.get(`/api/admin/referrals/${id}`);
  },

  // ===== ویرایش ارجاع =====
  update: async (id, data) => {
    return client.put(`/api/admin/referrals/${id}`, data);
  },

  // ===== حذف ارجاع =====
  delete: async (id) => {
    return client.delete(`/api/admin/referrals/${id}`);
  },

  // ===== پذیرش ارجاع =====
  accept: async (id) => {
    return client.post(`/api/admin/referrals/${id}/accept`);
  },

  // ===== رد ارجاع =====
  reject: async (id) => {
    return client.post(`/api/admin/referrals/${id}/reject`);
  },

  // ===== تکمیل ارجاع =====
  complete: async (id) => {
    return client.post(`/api/admin/referrals/${id}/complete`);
  },

  // ===== دریافت ارجاعات بیمار =====
  getPatientReferrals: async (patientId) => {
    return client.get(`/api/referrals/patients/${patientId}`);
  },

  // ===== دریافت ارجاعات پزشک =====
  getDoctorReferrals: async () => {
    return client.get('/api/referrals/doctor');
  },
};

export default referralsService;
