// src/services/api/admin/roles.js

import client from '../client';

export const rolesService = {
    // ===== لیست نقش‌ها =====
    getAll: async (params = {}) => {
        return client.get('/api/admin/roles', { params });
    },

    // ===== دریافت نقش =====
    getById: async (id) => {
        return client.get(`/api/admin/roles/${id}`);
    },

    // ===== ایجاد نقش =====
    create: async (data) => {
        return client.post('/api/admin/roles', data);
    },

    // ===== ویرایش نقش =====
    update: async (id, data) => {
        return client.put(`/api/admin/roles/${id}`, data);
    },

    // ===== حذف نقش =====
    delete: async (id) => {
        return client.delete(`/api/admin/roles/${id}`);
    },

    // ===== دریافت مجوزهای نقش =====
    getPermissions: async (id) => {
        return client.get(`/api/admin/roles/${id}/permissions`);
    },

    // ===== اختصاص مجوز به نقش =====
    assignPermissions: async (id, permissions) => {
        return client.post(`/api/admin/roles/${id}/permissions`, { permissions });
    },
};

export default rolesService;