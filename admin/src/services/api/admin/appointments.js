import client from '../client';

export const appointmentsService = {
  // ===== لیست نوبت‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/appointments', { params });
  },

  // ===== ایجاد نوبت جدید =====
  create: async (data) => {
    return client.post('/api/admin/appointments', data);
  },

  // ===== مشاهده نوبت =====
  getById: async (id) => {
    return client.get(`/api/admin/appointments/${id}`);
  },

  // ===== ویرایش نوبت =====
  update: async (id, data) => {
    return client.put(`/api/admin/appointments/${id}`, data);
  },

  // ===== حذف نوبت =====
  delete: async (id) => {
    return client.delete(`/api/admin/appointments/${id}`);
  },

  // ===== تغییر وضعیت نوبت =====
  changeStatus: async (id, status) => {
    return client.post(`/api/admin/appointments/${id}/status`, { status });
  },

  // ===== تایید نوبت =====
  confirm: async (id) => {
    return client.post(`/api/admin/appointments/${id}/confirm`);
  },

  // ===== لغو نوبت =====
  cancel: async (id) => {
    return client.post(`/api/admin/appointments/${id}/cancel`);
  },

  // ===== شروع نوبت =====
  start: async (id) => {
    return client.post(`/api/admin/appointments/${id}/start`);
  },

  // ===== تکمیل نوبت =====
  complete: async (id) => {
    return client.post(`/api/admin/appointments/${id}/complete`);
  },

  // ===== دریافت زمان‌های موجود =====
  getAvailableSlots: async (doctorId, date) => {
    return client.get(`/api/appointments/doctors/${doctorId}/available-slots`, { params: { date } });
  },

  // ===== آمار نوبت‌ها =====
  getStats: async () => {
    return client.get('/api/admin/appointments/stats');
  },
};

export default appointmentsService;
