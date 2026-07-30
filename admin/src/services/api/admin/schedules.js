import client from '../client';

const BASE_PATH = '/api/admin/schedules';

export const schedulesService = {
  getByDoctor: async (doctorId) => {
    return client.get(
      `${BASE_PATH}/doctors/${doctorId}/weekly`
    );
  },

  getWeekly: async (doctorId) => {
    return client.get(
      `${BASE_PATH}/doctors/${doctorId}/weekly`
    );
  },

  save: async (data) => {
    const { doctor_id, schedules = [] } = data;

    return client.post(
      `${BASE_PATH}/doctors/${doctor_id}/weekly`,
      { schedules }
    );
  },

  setWeekly: async (doctorId, data) => {
    const schedules = Array.isArray(data)
      ? data
      : data?.schedules || [];

    return client.post(
      `${BASE_PATH}/doctors/${doctorId}/weekly`,
      { schedules }
    );
  },

  getDaySchedule: async (doctorId, date) => {
    return client.get(
      `${BASE_PATH}/doctors/${doctorId}/day`,
      {
        params: { date },
      }
    );
  },

  getSpecialSchedules: async (doctorId) => {
    return client.get(
      `${BASE_PATH}/doctors/${doctorId}/special`
    );
  },

  setSpecial: async (doctorId, data) => {
    return client.post(
      `${BASE_PATH}/doctors/${doctorId}/special`,
      data
    );
  },

  deleteSpecial: async (scheduleId) => {
    return client.delete(
      `${BASE_PATH}/special/${scheduleId}`
    );
  },

  copyFromPreviousWeek: async (doctorId) => {
    return client.post(
      `${BASE_PATH}/doctors/${doctorId}/copy-previous-week`
    );
  },
};

export default schedulesService;
