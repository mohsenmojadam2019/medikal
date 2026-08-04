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
import { useLanguage } from '@/lib/context/LanguageContext';
import { getPlatformContent } from './content';

const hiddenRoutes = [
  '/login',
  '/register',
  '/verify',
  '/checkout',
  '/confirmation',
];

export default function MobileBottomNav() {
  const pathname = usePathname() || '/';
  const { locale } = useLanguage();
  const copy = getPlatformContent(locale);

  if (hiddenRoutes.some((route) => pathname.includes(route))) {
    return null;
  }

  const items = [
    {
      key: 'home',
      label: copy.home,
      href: '/',
      icon: <HomeFilled />,
    },
    {
      key: 'doctors',
      label: copy.doctors,
      href: '/doctors',
      icon: <MedicineBoxFilled />,
    },
    {
      key: 'pharmacy',
      label: copy.pharmacy,
      href: '/pharmacy',
      icon: <ShopFilled />,
    },
    {
      key: 'ai-chat',
      label: copy.ai,
      href: '/ai-chat',
      icon: <RobotFilled />,
    },
    {
      key: 'profile',
      label: copy.profile,
      href: '/profile',
      icon: <UserOutlined />,
    },
  ];

  return (
    <nav
      className="medikal-bottom-nav"
      aria-label="Mobile navigation"
    >
      {items.map((item) => {
        const active =
          item.key === 'home'
            ? pathname === '/'
            : pathname.startsWith(item.href);

        return (
          <Link
            key={item.key}
            href={item.href}
            className={active ? 'active' : ''}
            aria-current={active ? 'page' : undefined}
          >
            <span className="medikal-bottom-nav__icon">
              {item.icon}
            </span>
            <span>{item.label}</span>
          </Link>
        );
      })}
    </nav>
  );
}
