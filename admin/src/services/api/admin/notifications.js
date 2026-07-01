import client from '../client';

export const notificationsService = {
  // ===== لیست اعلان‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/notifications', { params });
  },

  // ===== مشاهده اعلان =====
  getById: async (id) => {
    return client.get(`/api/admin/notifications/${id}`);
  },

  // ===== حذف اعلان =====
  delete: async (id) => {
    return client.delete(`/api/admin/notifications/${id}`);
  },

  // ===== علامت‌گذاری به عنوان خوانده شده =====
  markAsRead: async (id) => {
    return client.put(`/api/admin/notifications/${id}/read`);
  },

  // ===== علامت‌گذاری همه به عنوان خوانده شده =====
  markAllAsRead: async () => {
    return client.put('/api/admin/notifications/read-all');
  },

  // ===== حذف همه اعلان‌های خوانده شده =====
  deleteAllRead: async () => {
    return client.delete('/api/admin/notifications/read/all');
  },

  // ===== ارسال به کاربر خاص =====
  sendToUser: async (userId, title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-user', {
      user_id: userId,
      title,
      message,
      priority,
    });
  },

  // ===== ارسال به چند کاربر =====
  sendToUsers: async (userIds, title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-users', {
      user_ids: userIds,
      title,
      message,
      priority,
    });
  },

  // ===== ارسال به نقش =====
  sendToRole: async (role, title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-role', {
      role,
      title,
      message,
      priority,
    });
  },

  // ===== ارسال به همه =====
  sendToAll: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-all', {
      title,
      message,
      priority,
    });
  },

  // ===== ارسال به همه پزشکان =====
  sendToDoctors: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-doctors', {
      title,
      message,
      priority,
    });
  },

  // ===== ارسال به همه بیماران =====
  sendToPatients: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-patients', {
      title,
      message,
      priority,
    });
  },

  // ===== ارسال به بیماران یک پزشک =====
  sendToDoctorPatients: async (doctorId, title, message, priority = 'medium') => {
    return client.post(`/api/admin/notifications/send-to-doctor-patients/${doctorId}`, {
      title,
      message,
      priority,
    });
  },

  // ===== ارسال با فیلتر =====
  sendFiltered: async (filters, title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-filtered', {
      filters,
      title,
      message,
      priority,
    });
  },

  // ===== دریافت اعلان‌های یک کاربر =====
  getUserNotifications: async (userId) => {
    return client.get(`/api/admin/notifications/user/${userId}`);
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/admin/notifications/stats');
  },

  // ===== دریافت اعلان‌های کاربر جاری =====
  getMyNotifications: async (params = {}) => {
    return client.get('/api/notifications', { params });
  },

  // ===== دریافت تعداد خوانده نشده =====
  getUnreadCount: async () => {
    return client.get('/api/notifications/unread-count');
  },
};

export default notificationsService;
