'use client';

import { Menu } from 'antd';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function NavBar() {
  const { t, locale } = useLanguage();
  const pathname = usePathname();

  const navItems = [
    { key: 'home', label: t('nav.home'), icon: 'fa-home', path: '/' },
    { key: 'doctors', label: t('nav.doctors'), icon: 'fa-user-md', path: '/doctors' },
    { key: 'specialties', label: t('nav.specialties'), icon: 'fa-stethoscope', path: '/specialties' },
    { key: 'appointments', label: t('nav.appointments'), icon: 'fa-calendar-check', path: '/appointments' },
    { key: 'lab', label: t('nav.lab'), icon: 'fa-flask', path: '/lab' },
    { key: 'pharmacy', label: t('nav.pharmacy'), icon: 'fa-pills', path: '/pharmacy' },
    { key: 'records', label: t('nav.records'), icon: 'fa-file-medical', path: '/records' },
    { key: 'blog', label: t('nav.blog'), icon: 'fa-blog', path: '/blog' },
    { key: 'support', label: t('nav.support'), icon: 'fa-headset', path: '/support' },
  ];

  const selectedKey = navItems.find(item => pathname?.includes(item.path))?.key || 'home';

  return (
    <div className="nav-bar">
      <div className="container">
        <Menu
          mode="horizontal"
          selectedKeys={[selectedKey]}
          items={navItems.map(item => ({
            key: item.key,
            label: (
              <Link href={`/${locale}${item.path}`}>
                <i className={`fas ${item.icon}`} /> {item.label}
              </Link>
            ),
          }))}
          style={{ background: 'transparent', border: 'none' }}
        />
      </div>
    </div>
  );
}
