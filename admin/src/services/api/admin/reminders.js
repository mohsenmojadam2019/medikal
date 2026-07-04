// src/services/api/admin/reminders.js

import client from '../client';

export const remindersService = {
    getAll: async (params = {}) => {
        return client.get('/api/admin/reminders', { params });
    },

    getById: async (id) => {
        return client.get(`/api/admin/reminders/${id}`);
    },

    create: async (data) => {
        return client.post('/api/admin/reminders', data);
    },

    update: async (id, data) => {
        return client.put(`/api/admin/reminders/${id}`, data);
    },

    delete: async (id) => {
        return client.delete(`/api/admin/reminders/${id}`);
    },

    process: async () => {
        return client.post('/api/admin/reminders/process');
    },

    getSettings: async () => {
        return client.get('/api/admin/reminders/settings');
    },

    updateSettings: async (data) => {
        return client.put('/api/admin/reminders/settings', data);
    },

    getStats: async () => {
        return client.get('/api/admin/reminders/stats');
    },
};

export default remindersService;