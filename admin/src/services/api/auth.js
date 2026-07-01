import client from './client';

export const authService = {
  loginWithMobile: async (mobile) => {
    return client.post('/api/auth/login/mobile', { mobile });
  },

  verifyOtp: async (mobile, code) => {
    return client.post('/api/auth/login/mobile/verify', { mobile, code });
  },

  loginWithEmail: async (email, password) => {
    return client.post('/api/auth/login/email', { email, password });
  },

  getCurrentUser: async () => {
    return client.get('/api/auth/me');
  },

  logout: async () => {
    return client.post('/api/auth/logout');
  },
};

export default authService;
