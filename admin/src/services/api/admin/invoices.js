import client from '../client';

export const invoicesService = {
  // ===== لیست فاکتورها =====
  getAll: async (params = {}) => {
    return client.get('/api/admin/invoices', { params });
  },

  // ===== ایجاد فاکتور جدید =====
  create: async (data) => {
    return client.post('/api/admin/invoices', data);
  },

  // ===== مشاهده فاکتور =====
  getById: async (id) => {
    return client.get(`/api/admin/invoices/${id}`);
  },

  // ===== ویرایش فاکتور =====
  update: async (id, data) => {
    return client.put(`/api/admin/invoices/${id}`, data);
  },

  // ===== حذف فاکتور =====
  delete: async (id) => {
    return client.delete(`/api/admin/invoices/${id}`);
  },

  // ===== چاپ فاکتور =====
  print: async (id) => {
    return client.get(`/api/admin/invoices/${id}/print`);
  },

  // ===== دریافت آمار =====
  getStats: async () => {
    return client.get('/api/admin/invoices/stats');
  },

  // ===== دریافت فاکتورهای بیمار =====
  getPatientInvoices: async (patientId) => {
    return client.get(`/api/invoices/patient/${patientId}`);
  },
};

export default invoicesService;
