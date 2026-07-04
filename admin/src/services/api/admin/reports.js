// src/services/api/admin/reports.js

import client from '../client';

export const reportsService = {
    getTypes: async () => {
        return client.get('/api/admin/reports/types');
    },

    generate: async (data) => {
        return client.post('/api/admin/reports/generate', data);
    },

    exportExcel: async (data) => {
        return client.post('/api/admin/reports/export-excel', data, {
            responseType: 'blob',
        });
    },

    exportPdf: async (data) => {
        return client.post('/api/admin/reports/export-pdf', data, {
            responseType: 'blob',
        });
    },

    streamPdf: async (data) => {
        return client.post('/api/admin/reports/stream-pdf', data, {
            responseType: 'blob',
        });
    },
};

export default reportsService;