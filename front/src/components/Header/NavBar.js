'use client';

import { Menu } from 'antd';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

const navItems = [
  { key: 'home', label: 'صفحه اصلی', icon: 'fa-home', path: '/' },
  { key: 'doctors', label: 'پزشکان', icon: 'fa-user-md', path: '/doctors' },
  { key: 'specialties', label: 'تخصص‌ها', icon: 'fa-stethoscope', path: '/specialties' },
  { key: 'appointments', label: 'نوبت‌دهی', icon: 'fa-calendar-check', path: '/appointments' },
  { key: 'lab', label: 'آزمایشگاه', icon: 'fa-flask', path: '/lab' },
  { key: 'pharmacy', label: 'داروخانه', icon: 'fa-pills', path: '/pharmacy' },
  { key: 'records', label: 'پرونده الکترونیک', icon: 'fa-file-medical', path: '/records' },
  { key: 'blog', label: 'مجله سلامت', icon: 'fa-blog', path: '/blog' },
  { key: 'support', label: 'پشتیبانی', icon: 'fa-headset', path: '/support' },
];

export default function NavBar() {
  const pathname = usePathname();
  const selectedKey = navItems.find(item => pathname === item.path)?.key || 'home';

  return (
    <div className="nav-bar">
      <div className="container">
        <Menu
          mode="horizontal"
          selectedKeys={[selectedKey]}
          items={navItems.map(item => ({
            key: item.key,
            label: (
              <Link href={item.path}>
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
