// داده‌های تست - بعداً با API واقعی جایگزین میشه

export const specialtiesData = [
  { id: 1, icon: '❤️', name: 'قلب و عروق', count: 42 },
  { id: 2, icon: '🧠', name: 'مغز و اعصاب', count: 38 },
  { id: 3, icon: '🦴', name: 'ارتوپدی', count: 35 },
  { id: 4, icon: '🏥', name: 'داخلی', count: 40 },
  { id: 5, icon: '👶', name: 'اطفال', count: 28 },
  { id: 6, icon: '👩‍⚕️', name: 'زنان و زایمان', count: 32 },
  { id: 7, icon: '🧴', name: 'پوست و مو', count: 25 },
  { id: 8, icon: '👁️', name: 'چشم پزشکی', count: 20 },
  { id: 9, icon: '🦷', name: 'دندانپزشکی', count: 18 },
  { id: 10, icon: '🧪', name: 'آزمایشگاه', count: 15 },
  { id: 11, icon: '💊', name: 'داروخانه', count: 12 },
  { id: 12, icon: '🧘', name: 'روانشناسی', count: 22 },
];

export const doctorsData = [
  {
    id: 1,
    name: 'دکتر محمد رضایی',
    specialty: 'متخصص قلب و عروق',
    clinic: 'بیمارستان قلب تهران',
    rating: 4.9,
    reviews: 324,
    available: true,
    fee: 350000,
    featured: 'ویژه',
    avatar: 'م',
  },
  {
    id: 2,
    name: 'دکتر سارا حسینی',
    specialty: 'متخصص مغز و اعصاب',
    clinic: 'کلینیک مغز و اعصاب',
    rating: 4.8,
    reviews: 256,
    available: true,
    fee: 400000,
    featured: null,
    avatar: 'س',
  },
  {
    id: 3,
    name: 'دکتر علی کریمی',
    specialty: 'جراح ارتوپد',
    clinic: 'بیمارستان ارتوپدی',
    rating: 4.7,
    reviews: 189,
    available: false,
    fee: 450000,
    featured: null,
    avatar: 'ع',
  },
  {
    id: 4,
    name: 'دکتر ندا محمدی',
    specialty: 'متخصص داخلی',
    clinic: 'کلینیک داخلی تهران',
    rating: 4.9,
    reviews: 412,
    available: true,
    fee: 300000,
    featured: 'جدید',
    avatar: 'ن',
  },
];

export const statsData = [
  { id: 1, icon: 'fa-user-md', number: '۵۰۰+', label: 'پزشک متخصص', color: 'blue' },
  { id: 2, icon: 'fa-calendar-check', number: '۱۲,۴۰۰+', label: 'نوبت رزرو شده', color: 'green' },
  { id: 3, icon: 'fa-star', number: '۴.۹', label: 'میانگین امتیاز', color: 'purple' },
  { id: 4, icon: 'fa-clock', number: '۹۸٪', label: 'رضایت بیماران', color: 'orange' },
];

export const bannersData = [
  {
    id: 1,
    icon: '📱',
    title: 'نوبت‌دهی آنلاین',
    desc: '۲۴ ساعته، ۷ روز هفته',
    link: '#',
    className: 'b1',
  },
  {
    id: 2,
    icon: '💳',
    title: 'پرداخت امن',
    desc: 'زرین‌پال | آسان‌پرداخت | درگاه ملی',
    link: '#',
    className: 'b2',
  },
  {
    id: 3,
    icon: '📋',
    title: 'پرونده الکترونیک',
    desc: 'دسترسی به سوابق پزشکی در هر زمان',
    link: '#',
    className: 'b3',
  },
];

export const testimonialsData = [
  {
    id: 1,
    stars: 5,
    text: '«عالی بود! خیلی راحت تونستم نوبت بگیرم. دکتر خیلی خوب و با حوصله توضیح دادن.»',
    name: 'مریم احمدی',
    role: 'بیمار دکتر رضایی',
    avatar: 'م',
  },
  {
    id: 2,
    stars: 5,
    text: '«سیستم عالی، پیگیری نوبت خیلی راحت بود. حتماً به دیگران پیشنهاد می‌کنم.»',
    name: 'علی کریمی',
    role: 'بیمار دکتر حسینی',
    avatar: 'ع',
  },
  {
    id: 3,
    stars: 4.5,
    text: '«پرونده الکترونیک خیلی کمک کرد، دیگه نگران گم شدن مدارک نیستم.»',
    name: 'ندا حسینی',
    role: 'بیمار دکتر محمدی',
    avatar: 'ن',
  },
];

export const trustData = [
  { id: 1, icon: 'fa-shield-alt', title: 'ضمانت اصالت', desc: 'پزشکان معتبر و متخصص' },
  { id: 2, icon: 'fa-calendar-check', title: 'نوبت‌دهی سریع', desc: 'بدون معطلی و انتظار' },
  { id: 3, icon: 'fa-headset', title: 'پشتیبانی ۲۴/۷', desc: 'همیشه در دسترس' },
  { id: 4, icon: 'fa-clock', title: 'یادآوری هوشمند', desc: 'پیامک و ایمیل' },
  { id: 5, icon: 'fa-lock', title: 'پرداخت امن', desc: 'درگاه معتبر بانکی' },
  { id: 6, icon: 'fa-sync-alt', title: 'لغو آسان', desc: 'تا ۲۴ ساعت قبل' },
];

// داده‌های داروخانه
export const pharmacyData = [
  {
    id: 1,
    name: 'آموکسی‌سیلین',
    category: 'آنتی‌بیوتیک',
    price: 25000,
    stock: 45,
    image: '💊',
    requiresPrescription: true,
  },
  {
    id: 2,
    name: 'ایبوپروفن',
    category: 'مسکن',
    price: 15000,
    stock: 120,
    image: '💊',
    requiresPrescription: false,
  },
  {
    id: 3,
    name: 'لوزارتان',
    category: 'فشار خون',
    price: 35000,
    stock: 30,
    image: '💊',
    requiresPrescription: true,
  },
  {
    id: 4,
    name: 'پروپرانولول',
    category: 'قلبی',
    price: 28000,
    stock: 25,
    image: '💊',
    requiresPrescription: true,
  },
];
