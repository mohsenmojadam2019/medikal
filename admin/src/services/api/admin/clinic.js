import client from '../client';

export const clinicService = {
  // ===== دریافت اطلاعات کلینیک =====
  getSettings: async () => {
    return client.get('/api/admin/clinic');
  },

  // ===== به‌روزرسانی کلینیک =====
  update: async (data) => {
    return client.post('/api/admin/clinic?_method=PUT', data);
  },

  // ===== آپلود لوگو =====
  uploadLogo: async (file) => {
    const formData = new FormData();
    formData.append('logo', file);
    return client.post('/api/admin/clinic/upload-logo', formData);
  },

  // ===== تغییر وضعیت =====
  toggleStatus: async () => {
    return client.post('/api/admin/clinic/toggle-status');
  },

  // ===== دریافت تنظیمات عمومی =====
  getPublicSettings: async () => {
    return client.get('/api/clinic/settings');
  },
};

export default clinicService;
