import client from '../client';

export const ratingsService = {
  // ===== لیست نظرات =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/ratings', { params });
  },

  // ===== مشاهده نظر =====
  getById: async (id) => {
    return client.get(`/api/admin/ratings/${id}`);
  },

  // ===== حذف نظر =====
  delete: async (id) => {
    return client.delete(`/api/admin/ratings/${id}`);
  },

  // ===== پاسخ به نظر =====
  reply: async (id, reply) => {
    return client.post(`/api/admin/ratings/${id}/reply`, { reply });
  },

  // ===== حذف پاسخ =====
  deleteReply: async (id) => {
    return client.delete(`/api/admin/ratings/${id}/reply`);
  },

  // ===== دریافت نظرات پزشک =====
  getDoctorRatings: async (doctorId) => {
    return client.get(`/api/ratings/doctors/${doctorId}`);
  },

  // ===== دریافت آمار پزشک =====
  getDoctorStats: async (doctorId) => {
    return client.get(`/api/ratings/doctors/${doctorId}/stats`);
  },

  // ===== دریافت پزشکان برتر =====
  getTopDoctors: async () => {
    return client.get('/api/ratings/top-doctors');
  },

  // ===== دریافت آمار کلی =====
  getStats: async () => {
    return client.get('/api/admin/ratings/stats');
  },
};

export default ratingsService;
