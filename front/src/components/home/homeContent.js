export const HOME_CONTENT = {
  fa: {
    brand: 'دکتر وب',
    brandCaption: 'پلتفرم هوشمند خدمات سلامت',
    search: 'جستجوی پزشک، تخصص، بیماری، دارو یا مرکز درمانی',
    login: 'ورود / ثبت‌نام',
    profile: 'پروفایل',
    advertise: 'تبلیغات در دکتر وب',

    nav: {
      home: 'خانه',
      doctors: 'پزشکان',
      pharmacy: 'داروخانه',
      homeDoctor: 'پزشک در منزل',
      tourism: 'توریست درمانی',
      map: 'نقشه مراکز',
      allServices: 'همه خدمات',
    },

    hero: {
      badge: 'پلتفرم جامع خدمات پزشکی و درمانی',
      title: 'پزشک مناسب را پیدا کنید',
      accent: 'درمان مطمئن را آغاز کنید',
      description:
        'پزشکان معتبر را بر اساس تخصص و خدمات درمانی بررسی کنید و به داروخانه، آزمایشگاه، پزشک در منزل و سایر خدمات سلامت دسترسی داشته باشید.',
      primary: 'مشاهده پزشکان',
      secondary: 'پزشک در منزل',
      verified: 'پزشکان احراز هویت‌شده',
      support: 'پشتیبانی شبانه‌روزی',
      privacy: 'حفظ حریم خصوصی',
    },

    section: {
      services: 'خدمات درمانی دکتر وب',
      servicesDescription: 'دسترسی سریع به خدمات پزشکی و سلامت',
      ads: 'پیشنهادهای ویژه مراکز درمانی',
      adsDescription: 'تبلیغات پزشکی، درمانی و زیبایی مراکز معتبر',
      doctors: 'پزشکان منتخب',
      doctorsDescription: 'پزشکان فعال و ثبت‌شده در سامانه',
      homeDoctor: 'پزشک در منزل',
      pharmacy: 'داروخانه آنلاین',
      map: 'مراکز درمانی روی نقشه',
      tourism: 'گردشگری درمانی در ایران',
      specialties: 'تخصص‌های پرطرفدار',
      viewAll: 'مشاهده همه',
      noData: 'اطلاعاتی برای نمایش وجود ندارد',
    },

    services: [
      {
        key: 'doctors',
        title: 'پزشکان',
        description: 'جستجو و بررسی پزشکان متخصص',
        href: '/doctors',
      },
      {
        key: 'homeDoctor',
        title: 'پزشک در منزل',
        description: 'درخواست ویزیت در محل',
        href: '/home-doctor',
      },
      {
        key: 'pharmacy',
        title: 'داروخانه آنلاین',
        description: 'دارو و محصولات سلامت',
        href: '/pharmacy',
      },
      {
        key: 'lab',
        title: 'آزمایشگاه',
        description: 'ثبت خدمات آزمایشگاهی',
        href: '/lab',
      },
      {
        key: 'imaging',
        title: 'تصویربرداری',
        description: 'رادیولوژی و تصویربرداری پزشکی',
        href: '/imaging',
      },
      {
        key: 'map',
        title: 'نقشه مراکز',
        description: 'مشاهده مراکز درمانی روی نقشه',
        href: '/map',
      },
      {
        key: 'tourism',
        title: 'توریست درمانی',
        description: 'خدمات بیماران بین‌المللی',
        href: '/medical-tourism',
      },
      {
        key: 'ai',
        title: 'هوش مصنوعی پزشکی',
        description: 'راهنمای هوشمند خدمات سلامت',
        href: '/ai-chat',
      },
      {
        key: 'blog',
        title: 'مجله پزشکی',
        description: 'مطالب آموزشی و تازه‌های سلامت',
        href: '/blog',
      },
    ],

    megaMenu: [
      {
        key: 'medical',
        title: 'پزشکان و درمان',
        columns: [
          {
            title: 'پزشکان',
            items: [
              ['پزشک عمومی', '/doctors?type=general'],
              ['پزشکان متخصص', '/doctors'],
              ['مشاوره آنلاین', '/doctors?visit=online'],
              ['پزشک در منزل', '/home-doctor'],
            ],
          },
          {
            title: 'تخصص‌ها',
            items: [
              ['قلب و عروق', '/doctors?specialty=cardiology'],
              ['پوست و مو', '/doctors?specialty=dermatology'],
              ['چشم‌پزشکی', '/doctors?specialty=ophthalmology'],
              ['ارتوپدی', '/doctors?specialty=orthopedics'],
            ],
          },
        ],
      },
      {
        key: 'centers',
        title: 'مراکز درمانی',
        columns: [
          {
            title: 'مراکز',
            items: [
              ['کلینیک‌ها', '/map?type=clinic'],
              ['بیمارستان‌ها', '/map?type=hospital'],
              ['آزمایشگاه‌ها', '/map?type=lab'],
              ['تصویربرداری', '/map?type=imaging'],
            ],
          },
          {
            title: 'خدمات',
            items: [
              ['آزمایش در منزل', '/lab'],
              ['تصویربرداری پزشکی', '/imaging'],
              ['پرستار در منزل', '/home-care'],
              ['فیزیوتراپی', '/physiotherapy'],
            ],
          },
        ],
      },
      {
        key: 'pharmacy',
        title: 'داروخانه و سلامت',
        columns: [
          {
            title: 'داروخانه',
            items: [
              ['داروها', '/pharmacy/drugs'],
              ['ارسال نسخه', '/pharmacy/prescription-request'],
              ['داروخانه‌ها', '/pharmacy'],
              ['مکمل‌های غذایی', '/pharmacy?category=supplement'],
            ],
          },
          {
            title: 'محصولات',
            items: [
              ['آرایشی و بهداشتی', '/pharmacy?category=beauty'],
              ['لوازم پزشکی', '/pharmacy?category=equipment'],
              ['مراقبت پوست', '/pharmacy?category=skin'],
              ['مراقبت مو', '/pharmacy?category=hair'],
            ],
          },
        ],
      },
      {
        key: 'special',
        title: 'خدمات ویژه',
        columns: [
          {
            title: 'جراحی و زیبایی',
            items: [
              ['جراحی چشم', '/search?q=جراحی چشم'],
              ['جراحی زیبایی بینی', '/search?q=جراحی بینی'],
              ['کاشت مو و PRP', '/search?q=کاشت مو'],
              ['خدمات پوست و زیبایی', '/search?q=زیبایی'],
            ],
          },
          {
            title: 'بین‌الملل',
            items: [
              ['گردشگری درمانی', '/medical-tourism'],
              ['درخواست مشاوره', '/medical-tourism'],
              ['ارسال مدارک پزشکی', '/medical-tourism'],
              ['پیگیری درخواست', '/profile'],
            ],
          },
        ],
      },
    ],

    ads: [
      {
        campaignId: 'eye-surgery-001',
        sponsor: 'مرکز تخصصی چشم',
        badge: 'تبلیغات',
        title: 'جراحی لیزیک و فمتولیزیک',
        description:
          'بررسی تخصصی شرایط چشم، معرفی پزشک و دریافت مشاوره قبل از عمل.',
        button: 'مشاهده جزئیات',
        href: '/search?q=جراحی چشم',
        image: '/image/banners/lizik.png',
        theme: 'blue',
      },
      {
        campaignId: 'rhinoplasty-001',
        sponsor: 'کلینیک زیبایی',
        badge: 'تبلیغات',
        title: 'جراحی زیبایی و ترمیمی بینی',
        description:
          'مشاوره تخصصی، مشاهده پزشکان فعال و بررسی خدمات مرکز.',
        button: 'دریافت مشاوره',
        href: '/search?q=جراحی بینی',
        image: '/image/banners/bini.png',
        theme: 'rose',
      },
      {
        campaignId: 'hair-prp-001',
        sponsor: 'مرکز پوست و مو',
        badge: 'تبلیغات',
        title: 'کاشت مو، PRP و خدمات پوست',
        description:
          'ارزیابی شرایط، دریافت مشاوره و انتخاب مرکز مناسب.',
        button: 'مشاهده خدمات',
        href: '/search?q=کاشت مو',
        image: '/image/banners/moo.png',
        theme: 'green',
      },
    ],

    specialties: [
      'قلب و عروق',
      'پوست و مو',
      'چشم‌پزشکی',
      'زنان و زایمان',
      'ارتوپدی',
      'کودکان',
      'گوش، حلق و بینی',
      'روان‌شناسی',
    ],

    footer: {
      description:
        'دکتر وب، پلتفرم جامع دسترسی به پزشکان، مراکز درمانی، داروخانه و خدمات سلامت است.',
      services: 'خدمات دکتر وب',
      company: 'درباره دکتر وب',
      support: 'راهنما و پشتیبانی',
      contact: 'ارتباط با ما',
      copyright: 'تمامی حقوق برای دکتر وب محفوظ است.',
    },
  },

  en: {
    brand: 'Doctor Web',
    brandCaption: 'Smart healthcare platform',
    search: 'Search doctors, specialties, medicines, or medical centers',
    login: 'Login / Sign up',
    profile: 'Profile',
    advertise: 'Advertise on Doctor Web',

    nav: {
      home: 'Home',
      doctors: 'Doctors',
      pharmacy: 'Pharmacy',
      homeDoctor: 'Doctor at home',
      tourism: 'Medical tourism',
      map: 'Medical map',
      allServices: 'All services',
    },

    hero: {
      badge: 'Integrated healthcare platform',
      title: 'Find the right doctor',
      accent: 'Start trusted treatment',
      description:
        'Discover verified doctors and access pharmacy, laboratory, home doctor, imaging, and medical tourism services.',
      primary: 'Browse doctors',
      secondary: 'Doctor at home',
      verified: 'Verified doctors',
      support: '24/7 support',
      privacy: 'Privacy protected',
    },

    section: {
      services: 'Doctor Web services',
      servicesDescription: 'Quick access to healthcare services',
      ads: 'Medical center offers',
      adsDescription: 'Sponsored medical and beauty services',
      doctors: 'Featured doctors',
      doctorsDescription: 'Active doctors registered on the platform',
      homeDoctor: 'Doctor at home',
      pharmacy: 'Online pharmacy',
      map: 'Medical centers map',
      tourism: 'Medical tourism in Iran',
      specialties: 'Popular specialties',
      viewAll: 'View all',
      noData: 'No information available',
    },

    services: [
      { key: 'doctors', title: 'Doctors', description: 'Search specialist doctors', href: '/doctors' },
      { key: 'homeDoctor', title: 'Doctor at home', description: 'Request a home visit', href: '/home-doctor' },
      { key: 'pharmacy', title: 'Online pharmacy', description: 'Medicines and health products', href: '/pharmacy' },
      { key: 'lab', title: 'Laboratory', description: 'Laboratory services', href: '/lab' },
      { key: 'imaging', title: 'Imaging', description: 'Medical imaging services', href: '/imaging' },
      { key: 'map', title: 'Medical map', description: 'View registered centers', href: '/map' },
      { key: 'tourism', title: 'Medical tourism', description: 'International patient services', href: '/medical-tourism' },
      { key: 'ai', title: 'Medical AI', description: 'Intelligent health guidance', href: '/ai-chat' },
      { key: 'blog', title: 'Medical magazine', description: 'Health education and latest articles', href: '/blog' },
    ],

    megaMenu: [],
    ads: [],
    specialties: [
      'Cardiology',
      'Dermatology',
      'Ophthalmology',
      'Gynecology',
      'Orthopedics',
      'Pediatrics',
      'ENT',
      'Psychology',
    ],

    footer: {
      description:
        'Doctor Web is an integrated platform for doctors, medical centers, pharmacies, and healthcare services.',
      services: 'Services',
      company: 'Doctor Web',
      support: 'Support',
      contact: 'Contact',
      copyright: 'All rights reserved for Doctor Web.',
    },
  },

  ar: {
    brand: 'دكتور ويب',
    brandCaption: 'منصة ذكية للخدمات الصحية',
    search: 'ابحث عن طبيب أو تخصص أو دواء أو مركز طبي',
    login: 'الدخول / التسجيل',
    profile: 'الملف الشخصي',
    advertise: 'الإعلان في دكتور ويب',

    nav: {
      home: 'الرئيسية',
      doctors: 'الأطباء',
      pharmacy: 'الصيدلية',
      homeDoctor: 'طبيب في المنزل',
      tourism: 'السياحة العلاجية',
      map: 'خريطة المراكز',
      allServices: 'جميع الخدمات',
    },

    hero: {
      badge: 'منصة متكاملة للخدمات الطبية',
      title: 'اعثر على الطبيب المناسب',
      accent: 'وابدأ علاجاً موثوقاً',
      description:
        'اكتشف الأطباء الموثقين وخدمات الصيدلية والمختبر والطبيب المنزلي والتصوير والسياحة العلاجية.',
      primary: 'عرض الأطباء',
      secondary: 'طبيب في المنزل',
      verified: 'أطباء موثّقون',
      support: 'دعم 24 ساعة',
      privacy: 'حماية الخصوصية',
    },

    section: {
      services: 'خدمات دكتور ويب',
      servicesDescription: 'وصول سريع إلى الخدمات الصحية',
      ads: 'عروض المراكز الطبية',
      adsDescription: 'إعلانات طبية وتجميلية',
      doctors: 'الأطباء المختارون',
      doctorsDescription: 'الأطباء النشطون في المنصة',
      homeDoctor: 'طبيب في المنزل',
      pharmacy: 'الصيدلية الإلكترونية',
      map: 'خريطة المراكز الطبية',
      tourism: 'السياحة العلاجية في إيران',
      specialties: 'التخصصات الشائعة',
      viewAll: 'عرض الكل',
      noData: 'لا توجد معلومات',
    },

    services: [],
    megaMenu: [],
    ads: [],
    specialties: [
      'القلب والأوعية',
      'الجلدية والشعر',
      'طب العيون',
      'النساء والولادة',
      'العظام',
      'الأطفال',
      'الأنف والأذن والحنجرة',
      'علم النفس',
    ],

    footer: {
      description:
        'دكتور ويب منصة متكاملة للأطباء والمراكز الطبية والصيدليات والخدمات الصحية.',
      services: 'الخدمات',
      company: 'دكتور ويب',
      support: 'الدعم',
      contact: 'اتصل بنا',
      copyright: 'جميع الحقوق محفوظة لدكتور ويب.',
    },
  },
};

export function getHomeContent(locale) {
  return HOME_CONTENT[locale] || HOME_CONTENT.fa;
}

