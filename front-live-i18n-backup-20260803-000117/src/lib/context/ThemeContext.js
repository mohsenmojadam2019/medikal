'use client';

import { createContext, useContext, useState, useEffect } from 'react';

const ThemeContext = createContext();

export function ThemeProvider({ children }) {
  const [theme, setTheme] = useState('light');

  useEffect(() => {
    const storedTheme = localStorage.getItem('theme') || 'light';
    setTheme(storedTheme);
    applyTheme(storedTheme);
  }, []);

  const applyTheme = (newTheme) => {
    document.documentElement.setAttribute('data-theme', newTheme);
    if (newTheme === 'dark') {
      document.documentElement.style.setProperty('--gray-50', '#0f172a');
      document.documentElement.style.setProperty('--gray-100', '#1e293b');
      document.documentElement.style.setProperty('--gray-200', '#334155');
      document.documentElement.style.setProperty('--gray-300', '#475569');
      document.documentElement.style.setProperty('--gray-400', '#64748b');
      document.documentElement.style.setProperty('--gray-500', '#94a3b8');
      document.documentElement.style.setProperty('--gray-600', '#cbd5e1');
      document.documentElement.style.setProperty('--gray-700', '#e2e8f0');
      document.documentElement.style.setProperty('--gray-800', '#f1f5f9');
      document.documentElement.style.setProperty('--gray-900', '#f8fafc');
    } else {
      document.documentElement.style.setProperty('--gray-50', '#f8fafc');
      document.documentElement.style.setProperty('--gray-100', '#f1f5f9');
      document.documentElement.style.setProperty('--gray-200', '#e2e8f0');
      document.documentElement.style.setProperty('--gray-300', '#cbd5e1');
      document.documentElement.style.setProperty('--gray-400', '#94a3b8');
      document.documentElement.style.setProperty('--gray-500', '#64748b');
      document.documentElement.style.setProperty('--gray-600', '#475569');
      document.documentElement.style.setProperty('--gray-700', '#334155');
      document.documentElement.style.setProperty('--gray-800', '#1e293b');
      document.documentElement.style.setProperty('--gray-900', '#0f172a');
    }
  };

  const toggleTheme = () => {
    const newTheme = theme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    applyTheme(newTheme);
  };

  const value = {
    theme,
    toggleTheme,
    isDark: theme === 'dark',
  };

  return (
    <ThemeContext.Provider value={value}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return context;
}
