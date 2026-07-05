// src/services/api/admin/profile.js

import client from '../client';

export const profileService = {
    /**
     * دریافت اطلاعات پروفایل کاربر جاری
     */
    getProfile: async () => {
        return client.get('/api/admin/profile');
    },

    /**
     * به‌روزرسانی اطلاعات پروفایل
     */
    updateProfile: async (data) => {
        return client.put('/api/admin/profile', data);
    },

    /**
     * آپلود عکس پروفایل
     */
    uploadAvatar: async (formData) => {
        return client.post('/api/admin/profile/avatar', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
    },

    /**
     * حذف عکس پروفایل
     */
    deleteAvatar: async () => {
        return client.delete('/api/admin/profile/avatar');
    },

    /**
     * تغییر رمز عبور
     */
    changePassword: async (data) => {
        return client.post('/api/admin/profile/change-password', data);
    },

    /**
     * دریافت فعالیت‌های اخیر
     */
    getActivities: async () => {
        return client.get('/api/admin/profile/activities');
    },
};

export default profileService;