'use client';

import { createContext, useState, useEffect, useContext } from 'react';
import { useRouter } from 'next/navigation';
import { authService } from '@/services/api/auth';
import { message } from 'antd';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  // ===== بررسی توکن در شروع =====
  useEffect(() => {
    const token = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');

    if (token && savedUser) {
      try {
        setUser(JSON.parse(savedUser));
      } catch (e) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      }
    }
    setLoading(false);
  }, []);

  // ===== ورود =====
  const loginWithEmail = async (email, password) => {
    try {
      const response = await authService.loginWithEmail(email, password);

      if (response.data?.success) {
        const { user, token } = response.data.data;

        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));

        setUser(user);
        message.success('ورود با موفقیت انجام شد');
        return true;
      }
    } catch (error) {
      message.error(error.response?.data?.message || 'خطا در ورود');
      return false;
    }
  };

  // ===== خروج =====
  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    message.success('خروج با موفقیت انجام شد');
    window.location.href = '/admin/login';
  };

  return (
      <AuthContext.Provider
          value={{
            user,
            loading,
            loginWithEmail,
            logout,
            isAuthenticated: !!user,
          }}
      >
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
