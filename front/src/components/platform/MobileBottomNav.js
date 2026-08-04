'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

import {
  EnvironmentFilled,
  HomeFilled,
  MedicineBoxFilled,
  ShopFilled,
  UserOutlined,
} from '@ant-design/icons';

export default function MobileBottomNav() {
  const pathname = usePathname() || '/';

  const items = [
    {
      label: 'خانه',
      href: '/',
      icon: <HomeFilled />,
    },
    {
      label: 'پزشکان',
      href: '/doctors',
      icon: <MedicineBoxFilled />,
    },
    {
      label: 'داروخانه',
      href: '/pharmacy',
      icon: <ShopFilled />,
    },
    {
      label: 'نقشه',
      href: '/map',
      icon: <EnvironmentFilled />,
    },
    {
      label: 'پروفایل',
      href: '/profile',
      icon: <UserOutlined />,
    },
  ];

  return (
    <>
      <nav className="doctorweb-mobile-bottom">
        {items.map((item) => {
          const active =
            item.href === '/'
              ? pathname === '/'
              : pathname.startsWith(item.href);

          return (
            <Link
              key={item.href}
              href={item.href}
              className={active ? 'active' : ''}
            >
              <span>{item.icon}</span>
              <small>{item.label}</small>
            </Link>
          );
        })}
      </nav>

      <style jsx global>{`
        .doctorweb-mobile-bottom {
          display: none;
        }

        @media (max-width: 820px) {
          .doctorweb-mobile-bottom {
            position: fixed;
            z-index: 3000;
            right: 10px;
            bottom: 10px;
            left: 10px;
            min-height: 67px;
            padding: 7px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            align-items: center;
            border: 1px solid #dce7f0;
            border-radius: 21px;
            background: rgba(255, 255, 255, 0.97);
            box-shadow: 0 15px 45px rgba(28, 62, 91, 0.22);
            backdrop-filter: blur(16px);
          }

          .doctorweb-mobile-bottom a {
            min-height: 52px;
            display: grid;
            place-items: center;
            align-content: center;
            gap: 4px;
            color: #7d8c9b;
            border-radius: 15px;
          }

          .doctorweb-mobile-bottom a span {
            font-size: 20px;
          }

          .doctorweb-mobile-bottom a small {
            font-size: 9px;
          }

          .doctorweb-mobile-bottom a.active {
            color: #087fdd;
            background: #edf7ff;
          }

          body {
            padding-bottom: 88px;
          }
        }
      `}</style>
    </>
  );
}
