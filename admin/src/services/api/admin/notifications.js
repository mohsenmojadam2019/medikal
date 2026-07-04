// src/services/api/admin/notifications.js

import client from '../client';

export const notificationsService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/notifications', { params });
  },

  getById: async (id) => {
    return client.get(`/api/admin/notifications/${id}`);
  },

  delete: async (id) => {
    // ✅ مسیر درست برای حذف
    return client.delete(`/api/admin/notifications/${id}`);
  },

  deleteAllRead: async () => {
    // ✅ مسیر درست برای حذف همه خوانده شده‌ها
    return client.delete('/api/admin/notifications/delete-all-read');
  },

  markAsRead: async (id) => {
    return client.post(`/api/admin/notifications/${id}/mark-as-read`);
  },

  markAllAsRead: async () => {
    return client.post('/api/admin/notifications/mark-all-as-read');
  },

  sendToAll: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-all', { title, message, priority });
  },

  sendToDoctors: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-doctors', { title, message, priority });
  },

  sendToPatients: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-patients', { title, message, priority });
  },

  sendToUser: async (userId, title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-user', { user_id: userId, title, message, priority });
  },

  getStats: async () => {
    return client.get('/api/admin/notifications/stats');
  },
};

export default notificationsService;