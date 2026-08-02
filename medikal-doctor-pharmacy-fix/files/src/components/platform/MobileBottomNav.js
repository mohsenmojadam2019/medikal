'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  HomeFilled,
  MedicineBoxFilled,
  ShopFilled,
  RobotFilled,
  UserOutlined,
} from '@ant-design/icons';
import { getPlatformContent } from './content';
import styles from './MobileBottomNav.module.css';

const hiddenRoutes = ['/login', '/register', '/verify', '/checkout', '/confirmation'];

export default function MobileBottomNav() {
  const pathname = usePathname() || '/fa';
  const firstSegment = pathname.split('/').filter(Boolean)[0];
  const locale = ['fa', 'en', 'ar'].includes(firstSegment) ? firstSegment : 'fa';
  const copy = getPlatformContent(locale);

  if (hiddenRoutes.some((route) => pathname.includes(route))) return null;

  const items = [
    { key: 'home', label: copy.home, href: `/${locale}`, icon: <HomeFilled /> },
    { key: 'doctors', label: copy.doctors, href: `/${locale}/doctors`, icon: <MedicineBoxFilled /> },
    { key: 'pharmacy', label: copy.pharmacy, href: `/${locale}/pharmacy`, icon: <ShopFilled /> },
    { key: 'ai-chat', label: copy.ai, href: `/${locale}/ai-chat`, icon: <RobotFilled /> },
    { key: 'profile', label: copy.profile, href: `/${locale}/profile`, icon: <UserOutlined /> },
  ];

  return (
    <nav className={styles.nav} aria-label="Mobile navigation">
      {items.map((item) => {
        const active = item.key === 'home' ? pathname === item.href : pathname.startsWith(item.href);
        return (
          <Link
            key={item.key}
            href={item.href}
            className={active ? styles.active : ''}
            aria-current={active ? 'page' : undefined}
          >
            <span className={styles.icon}>{item.icon}</span>
            <span>{item.label}</span>
          </Link>
        );
      })}
    </nav>
  );
}
