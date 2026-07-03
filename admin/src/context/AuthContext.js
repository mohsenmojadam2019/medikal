'use client';

import { createContext, useState, useEffect, useContext } from 'react';
import { useRouter } from 'next/navigation';
import { authService } from '@/services/api';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const router = useRouter();

  useEffect(() => {
    const initAuth = async () => {
      try {
        const token = localStorage.getItem('token');
        const savedUser = localStorage.getItem('user');

        if (token && savedUser) {
          setUser(JSON.parse(savedUser));
          await authService.getCurrentUser();
        } else if (token) {
          const userData = await authService.getCurrentUser();
          setUser(userData);
          localStorage.setItem('user', JSON.stringify(userData));
        }
      } catch (err) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    initAuth();
  }, []);

  const loginWithEmail = async (email, password) => {
    setError(null);
    try {
      const response = await authService.loginWithEmail(email, password);
      const { data } = response;

      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      setUser(data.user);

      return response;
    } catch (err) {
      setError(err.message || 'ایمیل یا رمز عبور اشتباه است');
      throw err;
    }
  };

  const loginWithMobile = async (mobile) => {
    setError(null);
    try {
      const response = await authService.loginWithMobile(mobile);
      return response;
    } catch (err) {
      setError(err.message || 'خطا در ارسال کد');
      throw err;
    }
  };

  const verifyOtp = async (mobile, code) => {
    setError(null);
    try {
      const response = await authService.verifyOtp(mobile, code);
      const { data } = response;

      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      setUser(data.user);

      return response;
    } catch (err) {
      setError(err.message || 'کد تایید نامعتبر است');
      throw err;
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
      router.push('/admin/login');
    }
  };

  const value = {
    user,
    loading,
    error,
    loginWithEmail,
    loginWithMobile,
    verifyOtp,
    logout,
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
