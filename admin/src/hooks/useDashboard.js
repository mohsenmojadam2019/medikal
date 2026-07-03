import { useState, useEffect } from 'react';
import { dashboardService } from '@/services/api';
import { message } from 'antd';
import { useLanguage } from './useLanguage';

export const useDashboard = () => {
  const { t } = useLanguage();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await dashboardService.getStats();
        setStats(response.data);
      } catch (err) {
        setError(err.message || t('fetch_error', 'خطا در دریافت اطلاعات'));
        message.error(err.message || t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, [t]);

  return { stats, loading, error };
};

export default useDashboard;
