// src/services/api/admin/users.js

import client from '../client';

export const usersService = {
    // ===== لیست کاربران =====
    getAll: async (params = {}) => {
        return client.get('/api/admin/users', { params });
    },

    // ===== دریافت کاربر =====
    getById: async (id) => {
        return client.get(`/api/admin/users/${id}`);
    },

    // ===== ایجاد کاربر =====
    create: async (data) => {
        return client.post('/api/admin/users', data);
    },

    // ===== ویرایش کاربر =====
    update: async (id, data) => {
        return client.put(`/api/admin/users/${id}`, data);
    },

    // ===== حذف کاربر =====
    delete: async (id) => {
        return client.delete(`/api/admin/users/${id}`);
    },

    // ===== تغییر وضعیت =====
    toggleStatus: async (id) => {
        return client.post(`/api/admin/users/${id}/toggle-status`);
    },

    // ===== تغییر نقش =====
    changeRole: async (id, role) => {
        return client.post(`/api/admin/users/${id}/change-role`, { role });
    },

    // ===== آمار کاربران =====
    getStats: async () => {
        return client.get('/api/admin/users/stats');
    },

    // ===== جستجوی کاربران =====
    search: async (query) => {
        return client.get('/api/admin/users/search', { params: { q: query } });
    },
};

export default usersService;