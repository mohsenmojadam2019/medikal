'use client';

import { Breadcrumb as AntBreadcrumb } from 'antd';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { HomeOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

const routeNames = {
  'profile': 'پروفایل',
  'doctors': 'پزشکان',
  'specialties': 'تخصص‌ها',
  'appointments': 'نوبت‌ها',
  'pharmacy': 'داروخانه',
  'lab': 'آزمایشگاه',
  'blog': 'وبلاگ',
  'faq': 'سوالات متداول',
  'about': 'درباره ما',
  'edit': 'ویرایش',
  'change-password': 'تغییر رمز',
  'upload-avatar': 'تغییر عکس',
  'prescriptions': 'نسخه‌ها',
  'wallet': 'کیف پول',
  'medical-records': 'پرونده پزشکی',
  'payments': 'پرداخت‌ها',
  'new': 'جدید',
  'checkout': 'پرداخت',
  'confirmation': 'تایید',
};

export default function Breadcrumb() {
  const pathname = usePathname();
  const { locale } = useLanguage();
  const pathSegments = pathname.split('/').filter(Boolean);

  const locales = ['fa', 'en', 'ar'];
  const cleanSegments = pathSegments.filter(seg => !locales.includes(seg));

  const items = [
    {
      title: (
        <Link href={`/${locale}`}>
          <HomeOutlined /> خانه
        </Link>
      ),
    },
    ...cleanSegments.map((segment, index) => {
      const url = '/' + cleanSegments.slice(0, index + 1).join('/');
      const isLast = index === cleanSegments.length - 1;
      const label = routeNames[segment] || segment;

      return {
        title: isLast ? label : <Link href={`/${locale}${url}`}>{label}</Link>,
      };
    }),
  ];

  return (
    <div style={{ marginBottom: '16px' }}>
      <AntBreadcrumb items={items} />
    </div>
  );
}
