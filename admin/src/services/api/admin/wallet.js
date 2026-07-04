// src/services/api/admin/wallet.js

import client from '../client';

export const walletService = {
  getAll: async (params = {}) => {
    return client.get('/api/admin/wallet', { params });
  },

  getByUser: async (userId) => {
    return client.get(`/api/admin/wallet/${userId}`);
  },

  getTransactions: async (userId, params = {}) => {
    return client.get(`/api/admin/wallet/${userId}/transactions`, { params });
  },

  toggleStatus: async (userId) => {
    return client.post(`/api/admin/wallet/${userId}/toggle-status`);
  },

  addBonus: async (userId, amount, description) => {
    return client.post(`/api/admin/wallet/${userId}/add-bonus`, { amount, description });
  },

  getStats: async () => {
    return client.get('/api/admin/wallet/stats');
  },

  getBalance: async () => {
    return client.get('/api/wallet/balance');
  },
};

export default walletService;