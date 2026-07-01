import client from '../client';

export const schedulesService = {
  // ===== دریافت زمان‌بندی هفتگی پزشک =====
  getWeekly: async (doctorId) => {
    return client.get(`/api/schedules/doctors/${doctorId}/weekly`);
  },

  // ===== دریافت زمان‌بندی یک روز خاص =====
  getDaySchedule: async (doctorId, date) => {
    return client.get(`/api/schedules/doctors/${doctorId}/day`, { params: { date } });
  },

  // ===== دریافت زمان‌های ویژه =====
  getSpecialSchedules: async (doctorId) => {
    return client.get(`/api/schedules/doctors/${doctorId}/special`);
  },

  // ===== تنظیم زمان‌بندی هفتگی =====
  setWeekly: async (doctorId, data) => {
    return client.post(`/api/schedules/doctors/${doctorId}/weekly`, data);
  },

  // ===== تنظیم زمان ویژه =====
  setSpecial: async (doctorId, data) => {
    return client.post(`/api/schedules/doctors/${doctorId}/special`, data);
  },

  // ===== حذف زمان ویژه =====
  deleteSpecial: async (scheduleId) => {
    return client.delete(`/api/schedules/special/${scheduleId}`);
  },

  // ===== کپی از هفته قبل =====
  copyFromPreviousWeek: async (doctorId) => {
    return client.post(`/api/schedules/doctors/${doctorId}/copy-previous-week`);
  },

  // ===== ایجاد زمان‌بندی جدید =====
  create: async (data) => {
    return client.post('/api/admin/schedules', data);
  },

  // ===== مشاهده زمان‌بندی =====
  getById: async (id) => {
    return client.get(`/api/admin/schedules/${id}`);
  },

  // ===== ویرایش زمان‌بندی =====
  update: async (id, data) => {
    return client.put(`/api/admin/schedules/${id}`, data);
  },

  // ===== حذف زمان‌بندی =====
  delete: async (id) => {
    return client.delete(`/api/admin/schedules/${id}`);
  },

  // ===== تغییر وضعیت =====
  toggleStatus: async (id, isActive) => {
    return client.post(`/api/admin/schedules/${id}/toggle-status`, { is_active: isActive });
  },
};

export default schedulesService;
