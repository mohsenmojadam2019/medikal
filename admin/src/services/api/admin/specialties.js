import client from '../client';

export const specialtiesService = {
  // ===== لیست تخصص‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/specialties', { params });
  },

  // ===== ایجاد تخصص جدید =====
  create: async (data) => {
    return client.post('/api/admin/specialties', data);
  },

  // ===== مشاهده تخصص =====
  getById: async (id) => {
    return client.get(`/api/admin/specialties/${id}`);
  },

  // ===== ویرایش تخصص =====
  update: async (id, data) => {
    return client.post(`/api/admin/specialties/${id}?_method=PUT`, data);
  },

  // ===== حذف تخصص =====
  delete: async (id) => {
    return client.delete(`/api/admin/specialties/${id}`);
  },

  // ===== تغییر وضعیت =====
  toggleStatus: async (id) => {
    return client.post(`/api/admin/specialties/${id}/toggle`);
  },

  // ===== آپلود آیکون =====
  uploadIcon: async (id, file) => {
    const formData = new FormData();
    formData.append('icon', file);
    return client.post(`/api/admin/specialties/${id}/icon`, formData);
  },

  // ===== حذف آیکون =====
  deleteIcon: async (id) => {
    return client.delete(`/api/admin/specialties/${id}/icon`);
  },
};

export default specialtiesService;
