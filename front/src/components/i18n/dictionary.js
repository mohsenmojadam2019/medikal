const dictionaries = {
  en: {
    'خانه': 'Home',
    'صفحه اصلی': 'Home',
    'پزشکان': 'Doctors',
    'پزشک': 'Doctor',
    'داروخانه': 'Pharmacy',
    'داروخانه آنلاین': 'Online pharmacy',
    'آزمایشگاه': 'Laboratory',
    'تصویربرداری': 'Imaging',
    'هوش مصنوعی': 'AI assistant',
    'دستیار هوشمند': 'AI assistant',
    'نوبت‌ها': 'Appointments',
    'نوبت': 'Appointment',
    'پرونده پزشکی': 'Medical records',
    'پروفایل': 'Profile',
    'حساب من': 'My account',
    'ورود': 'Login',
    'ثبت‌نام': 'Sign up',
    'خروج': 'Logout',
    'جستجو': 'Search',
    'جست‌وجو': 'Search',
    'مشاهده همه': 'View all',
    'دریافت نوبت': 'Book appointment',
    'رزرو نوبت': 'Book appointment',
    'پزشک در منزل': 'Doctor at home',
    'ویزیت آنلاین': 'Online consultation',
    'آزمایش در منزل': 'Home laboratory test',
    'نقشه مراکز': 'Medical centers map',
    'نزدیک‌ترین': 'Nearest',
    'فاصله': 'Distance',
    'لیست انتظار': 'Waiting list',
    'صف انتظار': 'Waiting queue',
    'گردشگری درمانی': 'Medical tourism',
    'به‌زودی': 'Coming soon',
    'تماس با ما': 'Contact us',
    'درباره ما': 'About us',
    'پشتیبانی': 'Support',
    'حریم خصوصی': 'Privacy',
    'سوالات متداول': 'Frequently asked questions',
    'در حال بارگذاری...': 'Loading...',
    'تلاش مجدد': 'Try again',
    'نتیجه‌ای یافت نشد': 'No results found',
    'تومان': 'Toman',
    'موجود': 'Available',
    'ناموجود': 'Unavailable',
    'امروز': 'Today',
    'فردا': 'Tomorrow',
    'سلامت شما، اولویت ماست': 'Your health is our priority',
    'همه خدمات سلامت در یکجا': 'All healthcare services in one place',
  },
  ar: {
    'خانه': 'الرئيسية',
    'صفحه اصلی': 'الرئيسية',
    'پزشکان': 'الأطباء',
    'پزشک': 'طبيب',
    'داروخانه': 'الصيدلية',
    'داروخانه آنلاین': 'الصيدلية الإلكترونية',
    'آزمایشگاه': 'المختبر',
    'تصویربرداری': 'التصوير الطبي',
    'هوش مصنوعی': 'المساعد الذكي',
    'دستیار هوشمند': 'المساعد الذكي',
    'نوبت‌ها': 'المواعيد',
    'نوبت': 'موعد',
    'پرونده پزشکی': 'السجل الطبي',
    'پروفایل': 'الملف الشخصي',
    'حساب من': 'حسابي',
    'ورود': 'تسجيل الدخول',
    'ثبت‌نام': 'إنشاء حساب',
    'خروج': 'تسجيل الخروج',
    'جستجو': 'بحث',
    'جست‌وجو': 'بحث',
    'مشاهده همه': 'عرض الكل',
    'دریافت نوبت': 'حجز موعد',
    'رزرو نوبت': 'حجز موعد',
    'پزشک در منزل': 'طبيب في المنزل',
    'ویزیت آنلاین': 'استشارة عبر الإنترنت',
    'آزمایش در منزل': 'تحاليل منزلية',
    'نقشه مراکز': 'خريطة المراكز الطبية',
    'نزدیک‌ترین': 'الأقرب',
    'فاصله': 'المسافة',
    'لیست انتظار': 'قائمة الانتظار',
    'صف انتظار': 'طابور الانتظار',
    'گردشگری درمانی': 'السياحة العلاجية',
    'به‌زودی': 'قريبًا',
    'تماس با ما': 'اتصل بنا',
    'درباره ما': 'من نحن',
    'پشتیبانی': 'الدعم',
    'حریم خصوصی': 'الخصوصية',
    'سوالات متداول': 'الأسئلة الشائعة',
    'در حال بارگذاری...': 'جارٍ التحميل...',
    'تلاش مجدد': 'إعادة المحاولة',
    'نتیجه‌ای یافت نشد': 'لا توجد نتائج',
    'تومان': 'تومان',
    'موجود': 'متوفر',
    'ناموجود': 'غير متوفر',
    'امروز': 'اليوم',
    'فردا': 'غدًا',
    'سلامت شما، اولویت ماست': 'صحتك أولويتنا',
    'همه خدمات سلامت در یکجا': 'كل خدمات الصحة في مكان واحد',
  },
};

export function translateVisibleText(value, locale) {
  if (locale === 'fa') return value;

  const dictionary = dictionaries[locale] || {};
  const source = String(value || '');
  const trimmed = source.trim();

  if (!trimmed) return source;
  if (dictionary[trimmed]) {
    return source.replace(trimmed, dictionary[trimmed]);
  }

  let output = source;
  Object.entries(dictionary)
    .sort(([a], [b]) => b.length - a.length)
    .forEach(([from, to]) => {
      if (from.length >= 4 && output.includes(from)) {
        output = output.split(from).join(to);
      }
    });

  return output;
}
