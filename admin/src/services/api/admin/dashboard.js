import client from '../client';

export const dashboardService = {
  getStats: async () => {
    return client.get('/api/admin/dashboard/management/stats');
  },

  getCharts: async (period = 'weekly') => {
    return client.get('/api/admin/dashboard/management/charts', { params: { period } });
  },

  getQuickStats: async () => {
    return client.get('/api/admin/dashboard/management/quick-stats');
  },

  getRecentActivities: async () => {
    return client.get('/api/admin/dashboard/management/recent-activities');
  },

  getTopDoctors: async (limit = 5) => {
    return client.get('/api/admin/dashboard/management/top-doctors', { params: { limit } });
  },

  getSummary: async () => {
    return client.get('/api/admin/dashboard/management/summary');
  },
};

export default dashboardService;
