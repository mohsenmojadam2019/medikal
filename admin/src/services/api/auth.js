import client from './client';

export const authService = {
  loginWithMobile: async (mobile) => {
    return client.post('/auth/login/mobile', { mobile });
  },

  verifyOtp: async (mobile, code) => {
    return client.post('/auth/login/mobile/verify', { mobile, code });
  },

  loginWithEmail: async (email, password) => {
    return client.post('/auth/login/email', { email, password });
  },

  getCurrentUser: async () => {
    return client.get('/auth/me');
  },

  logout: async () => {
    return client.post('/auth/logout');
  },
};

export default authService;
