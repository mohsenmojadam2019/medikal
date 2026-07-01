import client from '../client';

export const chatService = {
  // ===== دریافت مکالمات =====
  getConversations: async () => {
    return client.get('/api/chat/conversations');
  },

  // ===== دریافت پیام‌ها =====
  getMessages: async (userId) => {
    return client.get(`/api/chat/messages/${userId}`);
  },

  // ===== ارسال پیام =====
  sendMessage: async (receiverId, message) => {
    return client.post('/api/chat/send', { receiver_id: receiverId, message });
  },

  // ===== تعداد پیام‌های خوانده نشده =====
  getUnreadCount: async () => {
    return client.get('/api/chat/unread-count');
  },

  // ===== علامت‌گذاری به عنوان خوانده شده =====
  markAsRead: async (userId) => {
    return client.post(`/api/chat/mark-as-read/${userId}`);
  },

  // ===== دریافت مکالمات اخیر =====
  getRecent: async () => {
    return client.get('/api/chat/recent');
  },
};

export default chatService;
