import { useState, useCallback } from 'react';
import { message } from 'antd';
import { useLanguage } from './useLanguage';

export const useForm = (submitCallback, initialValues = {}) => {
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [values, setValues] = useState(initialValues);
  const [errors, setErrors] = useState({});

  const handleChange = useCallback((field, value) => {
    setValues(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }));
    }
  }, [errors]);

  const handleSubmit = useCallback(async () => {
    setLoading(true);
    try {
      await submitCallback(values);
      message.success(t('success', 'عملیات با موفقیت انجام شد'));
      return true;
    } catch (error) {
      if (error.errors) {
        setErrors(error.errors);
      }
      message.error(error.message || t('error', 'خطا در انجام عملیات'));
      return false;
    } finally {
      setLoading(false);
    }
  }, [values, submitCallback, t]);

  const reset = useCallback(() => {
    setValues(initialValues);
    setErrors({});
  }, [initialValues]);

  return {
    values,
    errors,
    loading,
    handleChange,
    handleSubmit,
    reset,
    setValues,
  };
};

export default useForm;
