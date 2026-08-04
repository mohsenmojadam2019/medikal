'use client';

import { Breadcrumb as AntBreadcrumb } from 'antd';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { HomeOutlined } from '@ant-design/icons';

const routeNames = {
  profile: 'پروفایل',
  doctors: 'پزشکان',
  specialties: 'تخصص‌ها',
  appointments: 'نوبت‌ها',
  pharmacy: 'داروخانه',
  lab: 'آزمایشگاه',
  blog: 'وبلاگ',
  faq: 'سوالات متداول',
  about: 'درباره ما',
  edit: 'ویرایش',
  'change-password': 'تغییر رمز',
  'upload-avatar': 'تغییر عکس',
  prescriptions: 'نسخه‌ها',
  wallet: 'کیف پول',
  'medical-records': 'پرونده پزشکی',
  payments: 'پرداخت‌ها',
  new: 'جدید',
  checkout: 'پرداخت',
  confirmation: 'تایید',
};

export default function Breadcrumb() {
  const pathname = usePathname() || '/';
  const segments = pathname.split('/').filter(Boolean);

  const items = [
    {
      title: (
        <Link href="/">
          <HomeOutlined /> خانه
        </Link>
      ),
    },
    ...segments.map((segment, index) => {
      const href = `/${segments.slice(0, index + 1).join('/')}`;
      const isLast = index === segments.length - 1;
      const title = routeNames[segment] || segment;

      return {
        title: isLast ? title : <Link href={href}>{title}</Link>,
      };
    }),
  ];

  return (
    <div style={{ marginBottom: 16 }}>
      <AntBreadcrumb items={items} />
    </div>
  );
}
