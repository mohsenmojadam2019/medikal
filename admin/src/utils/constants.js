// ===== وضعیت‌های نوبت =====
export const APPOINTMENT_STATUS = {
  PENDING: 'pending',
  CONFIRMED: 'confirmed',
  ARRIVED: 'arrived',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
  NO_SHOW: 'no_show',
};

export const APPOINTMENT_STATUS_MAP = {
  [APPOINTMENT_STATUS.PENDING]: { label: 'در انتظار تایید', color: 'orange' },
  [APPOINTMENT_STATUS.CONFIRMED]: { label: 'تایید شده', color: 'blue' },
  [APPOINTMENT_STATUS.ARRIVED]: { label: 'حاضر در مطب', color: 'purple' },
  [APPOINTMENT_STATUS.IN_PROGRESS]: { label: 'در حال ویزیت', color: 'cyan' },
  [APPOINTMENT_STATUS.COMPLETED]: { label: 'انجام شده', color: 'green' },
  [APPOINTMENT_STATUS.CANCELLED]: { label: 'لغو شده', color: 'red' },
  [APPOINTMENT_STATUS.NO_SHOW]: { label: 'حاضر نشده', color: 'default' },
};

// ===== وضعیت‌های فاکتور =====
export const INVOICE_STATUS = {
  DRAFT: 'draft',
  ISSUED: 'issued',
  PAID: 'paid',
  CANCELLED: 'cancelled',
  OVERDUE: 'overdue',
};

export const INVOICE_STATUS_MAP = {
  [INVOICE_STATUS.DRAFT]: { label: 'پیش‌نویس', color: 'default' },
  [INVOICE_STATUS.ISSUED]: { label: 'صادر شده', color: 'blue' },
  [INVOICE_STATUS.PAID]: { label: 'پرداخت شده', color: 'green' },
  [INVOICE_STATUS.CANCELLED]: { label: 'لغو شده', color: 'red' },
  [INVOICE_STATUS.OVERDUE]: { label: 'سررسید گذشته', color: 'orange' },
};

// ===== وضعیت‌های پرداخت =====
export const PAYMENT_STATUS = {
  PENDING: 'pending',
  SUCCESS: 'success',
  FAILED: 'failed',
  REFUNDED: 'refunded',
};

export const PAYMENT_STATUS_MAP = {
  [PAYMENT_STATUS.PENDING]: { label: 'در انتظار', color: 'orange' },
  [PAYMENT_STATUS.SUCCESS]: { label: 'موفق', color: 'green' },
  [PAYMENT_STATUS.FAILED]: { label: 'ناموفق', color: 'red' },
  [PAYMENT_STATUS.REFUNDED]: { label: 'عودت داده شده', color: 'blue' },
};

// ===== وضعیت‌های نسخه =====
export const PRESCRIPTION_STATUS = {
  PENDING: 'pending',
  ACTIVE: 'active',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
  EXPIRED: 'expired',
};

export const PRESCRIPTION_STATUS_MAP = {
  [PRESCRIPTION_STATUS.PENDING]: { label: 'در انتظار تایید', color: 'orange' },
  [PRESCRIPTION_STATUS.ACTIVE]: { label: 'فعال', color: 'green' },
  [PRESCRIPTION_STATUS.COMPLETED]: { label: 'تکمیل شده', color: 'blue' },
  [PRESCRIPTION_STATUS.CANCELLED]: { label: 'لغو شده', color: 'red' },
  [PRESCRIPTION_STATUS.EXPIRED]: { label: 'منقضی شده', color: 'default' },
};

// ===== روزهای هفته =====
export const DAYS_OF_WEEK = [
  { value: 'saturday', label: 'شنبه' },
  { value: 'sunday', label: 'یکشنبه' },
  { value: 'monday', label: 'دوشنبه' },
  { value: 'tuesday', label: 'سه‌شنبه' },
  { value: 'wednesday', label: 'چهارشنبه' },
  { value: 'thursday', label: 'پنج‌شنبه' },
  { value: 'friday', label: 'جمعه' },
];

// ===== گروه‌های خونی =====
export const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

// ===== درگاه‌های پرداخت =====
export const PAYMENT_GATEWAYS = {
  ZARINPAL: 'zarinpal',
  PAYPAL: 'paypal',
  CASH: 'cash',
  WALLET: 'wallet',
};

export const PAYMENT_GATEWAY_LABELS = {
  [PAYMENT_GATEWAYS.ZARINPAL]: 'زرین‌پال',
  [PAYMENT_GATEWAYS.PAYPAL]: 'پی‌پال',
  [PAYMENT_GATEWAYS.CASH]: 'نقدی',
  [PAYMENT_GATEWAYS.WALLET]: 'کیف پول',
};

// ===== اولویت‌های اعلان =====
export const NOTIFICATION_PRIORITY = {
  LOW: 'low',
  MEDIUM: 'medium',
  HIGH: 'high',
  URGENT: 'urgent',
};

export const NOTIFICATION_PRIORITY_MAP = {
  [NOTIFICATION_PRIORITY.LOW]: { label: 'معمولی', color: 'default' },
  [NOTIFICATION_PRIORITY.MEDIUM]: { label: 'متوسط', color: 'blue' },
  [NOTIFICATION_PRIORITY.HIGH]: { label: 'بالا', color: 'orange' },
  [NOTIFICATION_PRIORITY.URGENT]: { label: 'فوری', color: 'red' },
};

// ===== نقش‌های کاربر =====
export const USER_ROLES = {
  SUPER_ADMIN: 'super_admin',
  ADMIN: 'admin',
  DOCTOR: 'doctor',
  PATIENT: 'patient',
  RECEPTIONIST: 'receptionist',
};

// ===== زبان‌های پشتیبانی شده =====
export const SUPPORTED_LOCALES = {
  FA: 'fa',
  AR: 'ar',
  EN: 'en',
};

export const LOCALE_LABELS = {
  [SUPPORTED_LOCALES.FA]: 'فارسی',
  [SUPPORTED_LOCALES.AR]: 'العربية',
  [SUPPORTED_LOCALES.EN]: 'English',
};

export const LOCALE_DIRECTIONS = {
  [SUPPORTED_LOCALES.FA]: 'rtl',
  [SUPPORTED_LOCALES.AR]: 'rtl',
  [SUPPORTED_LOCALES.EN]: 'ltr',
};

// ===== فرمت‌های تاریخ =====
export const DATE_FORMATS = {
  JALALI: 'jYYYY/jMM/jDD',
  JALALI_FULL: 'jYYYY/jMM/jDD HH:mm',
  GREGORIAN: 'YYYY/MM/DD',
  GREGORIAN_FULL: 'YYYY/MM/DD HH:mm',
  TIME: 'HH:mm',
};

// ===== آستانه‌های موجودی =====
export const STOCK_THRESHOLDS = {
  LOW: 10,
  MEDIUM: 50,
  HIGH: 100,
};

// ===== محدودیت‌های فایل =====
export const FILE_LIMITS = {
  MAX_IMAGE_SIZE: 2 * 1024 * 1024, // 2MB
  MAX_DOCUMENT_SIZE: 5 * 1024 * 1024, // 5MB
  ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
  ALLOWED_DOCUMENT_TYPES: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
};

// ===== آستانه‌های صفحه‌بندی =====
export const PAGINATION = {
  DEFAULT_PAGE_SIZE: 10,
  PAGE_SIZE_OPTIONS: [10, 25, 50, 100],
};

export default {
  APPOINTMENT_STATUS,
  APPOINTMENT_STATUS_MAP,
  INVOICE_STATUS,
  INVOICE_STATUS_MAP,
  PAYMENT_STATUS,
  PAYMENT_STATUS_MAP,
  PRESCRIPTION_STATUS,
  PRESCRIPTION_STATUS_MAP,
  DAYS_OF_WEEK,
  BLOOD_TYPES,
  PAYMENT_GATEWAYS,
  PAYMENT_GATEWAY_LABELS,
  NOTIFICATION_PRIORITY,
  NOTIFICATION_PRIORITY_MAP,
  USER_ROLES,
  SUPPORTED_LOCALES,
  LOCALE_LABELS,
  LOCALE_DIRECTIONS,
  DATE_FORMATS,
  STOCK_THRESHOLDS,
  FILE_LIMITS,
  PAGINATION,
};
