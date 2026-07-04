// src/services/api/admin/notifications.js

import client from '../client';

export const notificationsService = {
  // ===== لیست اعلان‌ها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/notifications', { params });
  },

  // ===== دریافت اعلان‌های خوانده نشده =====
  getUnread: async () => {
    return client.get('/api/admin/notifications/unread');
  },

  // ===== دریافت تعداد اعلان‌های خوانده نشده =====
  getUnreadCount: async () => {
    return client.get('/api/admin/notifications/unread/count');
  },

  // ===== مشاهده اعلان =====
  getById: async (id) => {
    return client.get(`/api/admin/notifications/${id}`);
  },

  // ===== حذف اعلان =====
  delete: async (id) => {
    return client.delete(`/api/admin/notifications/${id}`);
  },

  // ===== حذف همه اعلان‌های خوانده شده =====
  deleteAllRead: async () => {
    return client.delete('/api/admin/notifications/delete-all-read');
  },

  // ===== علامت‌گذاری به عنوان خوانده شده =====
  markAsRead: async (id) => {
    return client.post(`/api/admin/notifications/${id}/mark-as-read`);
  },

  // ===== علامت‌گذاری همه به عنوان خوانده شده =====
  markAllAsRead: async () => {
    return client.post('/api/admin/notifications/mark-all-as-read');
  },

  // ===== ارسال اعلان به همه =====
  sendToAll: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-all', { title, message, priority });
  },

  // ===== ارسال اعلان به پزشکان =====
  sendToDoctors: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-doctors', { title, message, priority });
  },

  // ===== ارسال اعلان به بیماران =====
  sendToPatients: async (title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-patients', { title, message, priority });
  },

  // ===== ارسال اعلان به کاربر خاص =====
  sendToUser: async (userId, title, message, priority = 'medium') => {
    return client.post('/api/admin/notifications/send-to-user', { user_id: userId, title, message, priority });
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/admin/notifications/stats');
  },
};

export default notificationsService;