import client from './client';

export const authService = {
  // ===== ورود با ایمیل (ادمین) =====
  loginWithEmail: async (email, password) => {
    return client.post('/api/admin/login', { email, password });
  },

  // ===== ورود با موبایل (کاربران عادی) =====
  loginWithMobile: async (mobile) => {
    return client.post('/auth/login/mobile', { mobile });
  },

  // ===== تایید کد OTP =====
  verifyOtp: async (mobile, code) => {
    return client.post('/auth/login/mobile/verify', { mobile, code });
  },

  // ===== دریافت اطلاعات کاربر جاری =====
  getCurrentUser: async () => {
    return client.get('/api/admin/me');
  },

  // ===== خروج از سیستم =====
  logout: async () => {
    return client.post('/api/admin/logout');
  },
};

export default authService;