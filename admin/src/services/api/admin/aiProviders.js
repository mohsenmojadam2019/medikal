import client from '../client';

const BASE_PATH = '/api/admin/ai/providers';

export const aiProvidersService = {
  getAll: async () => {
    return client.get(BASE_PATH);
  },

  create: async (data) => {
    return client.post(BASE_PATH, data);
  },

  update: async (id, data) => {
    return client.put(`${BASE_PATH}/${id}`, data);
  },

  remove: async (id) => {
    return client.delete(`${BASE_PATH}/${id}`);
  },

  setDefault: async (id) => {
    return client.post(`${BASE_PATH}/${id}/default`);
  },

  test: async (id, prompt) => {
    return client.post(`${BASE_PATH}/${id}/test`, {
      prompt,
    });
  },

  getModels: async (id) => {
    return client.get(`${BASE_PATH}/${id}/models`);
  },

  chatTest: async (data) => {
    return client.post(`${BASE_PATH}/chat-test`, data);
  },
};

export default aiProvidersService;
