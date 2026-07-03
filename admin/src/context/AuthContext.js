'use client';

import { createContext, useState, useEffect, useContext } from 'react';
import { useRouter } from 'next/navigation';
import { authService } from '@/services/api/auth';
import { message } from 'antd';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const router = useRouter();

  // ===== بررسی وضعیت احراز هویت در شروع =====
  useEffect(() => {
    const initAuth = async () => {
      try {
        const token = localStorage.getItem('token');
        const savedUser = localStorage.getItem('user');

        if (token && savedUser) {
          setUser(JSON.parse(savedUser));
          // اعتبارسنجی توکن با سرور
          try {
            await authService.getCurrentUser();
          } catch (err) {
            // اگر توکن نامعتبر بود، پاک کن
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            setUser(null);
          }
        } else if (token) {
          // اگر توکن داشت ولی کاربر ذخیره نشده بود
          const response = await authService.getCurrentUser();
          if (response.data?.success) {
            const userData = response.data.data.user;
            setUser(userData);
            localStorage.setItem('user', JSON.stringify(userData));
          }
        }
      } catch (err) {
        console.error('Auth initialization error:', err);
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    initAuth();
  }, []);

  // ===== ورود با ایمیل (ادمین) =====
  const loginWithEmail = async (email, password) => {
    setError(null);
    try {
      const response = await authService.loginWithEmail(email, password);

      // ساختار پاسخ بک‌اند:
      // { success: true, message: "...", data: { user, token, roles, permissions } }
      if (response.data?.success) {
        const { user, token, roles, permissions } = response.data.data;

        // ذخیره توکن و اطلاعات کاربر
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('roles', JSON.stringify(roles));
        localStorage.setItem('permissions', JSON.stringify(permissions));

        setUser(user);
        message.success(response.data.message || 'ورود با موفقیت انجام شد');

        return response.data;
      } else {
        throw new Error(response.data?.message || 'خطا در ورود');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || err.message || 'ایمیل یا رمز عبور اشتباه است';
      setError(errorMessage);
      message.error(errorMessage);
      throw err;
    }
  };

  // ===== ورود با موبایل (ارسال کد OTP) =====
  const loginWithMobile = async (mobile) => {
    setError(null);
    try {
      const response = await authService.loginWithMobile(mobile);
      if (response.data?.success) {
        message.success(response.data.message || 'کد تایید ارسال شد');
        return response.data;
      } else {
        throw new Error(response.data?.message || 'خطا در ارسال کد');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || err.message || 'خطا در ارسال کد';
      setError(errorMessage);
      message.error(errorMessage);
      throw err;
    }
  };

  // ===== تایید کد OTP =====
  const verifyOtp = async (mobile, code) => {
    setError(null);
    try {
      const response = await authService.verifyOtp(mobile, code);
      if (response.data?.success) {
        const { user, token } = response.data.data;

        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        setUser(user);

        message.success(response.data.message || 'ورود با موفقیت انجام شد');
        return response.data;
      } else {
        throw new Error(response.data?.message || 'کد تایید نامعتبر است');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || err.message || 'کد تایید نامعتبر است';
      setError(errorMessage);
      message.error(errorMessage);
      throw err;
    }
  };

  // ===== خروج از سیستم =====
  const logout = async () => {
    try {
      const token = localStorage.getItem('token');
      if (token) {
        await authService.logout();
      }
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      // پاک کردن همه داده‌های محلی
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('roles');
      localStorage.removeItem('permissions');
      setUser(null);
      message.success('خروج با موفقیت انجام شد');
      router.push('/admin/login');
    }
  };

  // ===== بررسی نقش کاربر =====
  const hasRole = (role) => {
    const roles = JSON.parse(localStorage.getItem('roles') || '[]');
    return roles.includes(role);
  };

  // ===== بررسی مجوز کاربر =====
  const hasPermission = (permission) => {
    const permissions = JSON.parse(localStorage.getItem('permissions') || '[]');
    return permissions.includes(permission);
  };

  const value = {
    user,
    loading,
    error,
    loginWithEmail,
    loginWithMobile,
    verifyOtp,
    logout,
    hasRole,
    hasPermission,
    isAuthenticated: !!user,
  };

  return (
      <AuthContext.Provider value={value}>
        {children}
      </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default AuthContext;