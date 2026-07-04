// src/services/api/admin/webhook.js

import client from '../client';

export const webhookService = {
    getStatus: async () => {
        return client.get('/api/admin/webhook/status');
    },

    toggle: async () => {
        return client.post('/api/admin/webhook/toggle');
    },

    getLogs: async (params = {}) => {
        return client.get('/api/admin/webhook/logs', { params });
    },

    test: async (data) => {
        return client.post('/api/admin/webhook/test', data);
    },

    getSettings: async () => {
        return client.get('/api/admin/webhook/settings');
    },

    updateSettings: async (data) => {
        return client.put('/api/admin/webhook/settings', data);
    },
};

export default webhookService;