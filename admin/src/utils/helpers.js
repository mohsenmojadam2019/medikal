import dayjs from 'dayjs';
import jalali from 'dayjs-jalali';

dayjs.extend(jalali);

// ===== تبدیل تاریخ میلادی به شمسی =====
export const toJalali = (date, format = 'jYYYY/jMM/jDD') => {
  if (!date) return '';
  const d = dayjs(date);
  if (!d.isValid()) return '';
  return d.format(format);
};

// ===== تبدیل تاریخ شمسی به میلادی =====
export const toGregorian = (jalaliDate) => {
  if (!jalaliDate) return null;
  const d = dayjs(jalaliDate, 'jYYYY/jMM/jDD');
  return d.isValid() ? d.toISOString() : null;
};

// ===== فرمت قیمت =====
export const formatPrice = (price, currency = 'تومان') => {
  if (!price && price !== 0) return '—';
  return `${Number(price).toLocaleString()} ${currency}`;
};

// ===== فرمت شماره موبایل =====
export const formatMobile = (mobile) => {
  if (!mobile) return '';
  return mobile.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
};

// ===== کوتاه کردن متن =====
export const truncateText = (text, maxLength = 50) => {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength) + '...';
};

// ===== تولید کد یکتا =====
export const generateCode = (prefix, length = 6) => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return `${prefix}-${result}`;
};

// ===== اعتبارسنجی کدملی =====
export const validateNationalCode = (code) => {
  if (!code || code.length !== 10) return false;
  const digits = code.split('').map(Number);
  const check = digits[9];
  let sum = 0;
  for (let i = 0; i < 9; i++) {
    sum += digits[i] * (10 - i);
  }
  const remainder = sum % 11;
  if (remainder < 2) {
    return check === remainder;
  }
  return check === 11 - remainder;
};

// ===== اعتبارسنجی شماره موبایل =====
export const validateMobile = (mobile) => {
  return /^09[0-9]{9}$/.test(mobile);
};

// ===== اعتبارسنجی ایمیل =====
export const validateEmail = (email) => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

// ===== دریافت وضعیت به فارسی =====
export const getStatusLabel = (status, statusMap) => {
  return statusMap[status]?.label || status;
};

// ===== دریافت رنگ وضعیت =====
export const getStatusColor = (status, statusMap) => {
  return statusMap[status]?.color || 'default';
};

// ===== گروه‌بندی آرایه بر اساس کلید =====
export const groupBy = (array, key) => {
  return array.reduce((result, item) => {
    const groupKey = item[key];
    if (!result[groupKey]) {
      result[groupKey] = [];
    }
    result[groupKey].push(item);
    return result;
  }, {});
};

// ===== مرتب‌سازی آرایه =====
export const sortBy = (array, key, ascending = true) => {
  return [...array].sort((a, b) => {
    const aVal = a[key];
    const bVal = b[key];
    if (aVal < bVal) return ascending ? -1 : 1;
    if (aVal > bVal) return ascending ? 1 : -1;
    return 0;
  });
};

// ===== فیلتر آرایه بر اساس جستجو =====
export const filterBySearch = (array, search, fields) => {
  if (!search) return array;
  const lowerSearch = search.toLowerCase();
  return array.filter(item => {
    return fields.some(field => {
      const value = item[field];
      return value && String(value).toLowerCase().includes(lowerSearch);
    });
  });
};

// ===== تبدیل به فرمت فایل برای دانلود =====
export const downloadFile = (content, filename, type = 'text/plain') => {
  const blob = new Blob([content], { type });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

// ===== کپی به کلیپ‌بورد =====
export const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    return false;
  }
};

export default {
  toJalali,
  toGregorian,
  formatPrice,
  formatMobile,
  truncateText,
  generateCode,
  validateNationalCode,
  validateMobile,
  validateEmail,
  getStatusLabel,
  getStatusColor,
  groupBy,
  sortBy,
  filterBySearch,
  downloadFile,
  copyToClipboard,
};
