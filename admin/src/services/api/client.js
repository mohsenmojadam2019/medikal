import axios from 'axios';

// آدرس پایه API
const BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

const client = axios.create({
    baseURL: BASE_URL,
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// ===== Interceptor برای افزودن توکن =====
client.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// ===== Interceptor برای مدیریت خطاها =====
client.interceptors.response.use(
    (response) => response,
    (error) => {
        // اگر توکن منقضی شده بود (401)
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('roles');
            localStorage.removeItem('permissions');
            // هدایت به صفحه لاگین
            if (typeof window !== 'undefined') {
                window.location.href = '/admin/login';
            }
        }
        return Promise.reject(error);
    }
);

export default client;