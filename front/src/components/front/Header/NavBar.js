'use client';

import { useState, useEffect } from 'react';
import { Menu, Button, Drawer, Dropdown, Space, Badge, Avatar, Divider, Typography } from 'antd';
import { 
  HomeOutlined, CalendarOutlined, MedicineBoxOutlined,
  UserOutlined, LogoutOutlined, 
  MenuOutlined, GlobalOutlined, ShoppingCartOutlined
} from '@ant-design/icons';
import Link from 'next/link';
import { useRouter, usePathname } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';

const { Text } = Typography;

const NavBar = () => {
  const router = useRouter();
  const pathname = usePathname();
  const { t, locale, changeLanguage } = useLanguage();
  const [mobileMenuVisible, setMobileMenuVisible] = useState(false);
  const [user, setUser] = useState(null);
  const [cartCount, setCartCount] = useState(0);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      fetchUser();
    }
    updateCartCount();
    
    // گوش دادن به تغییرات سبد خرید
    const handleCartUpdate = () => {
      updateCartCount();
    };
    window.addEventListener('cartUpdated', handleCartUpdate);
    return () => window.removeEventListener('cartUpdated', handleCartUpdate);
  }, []);

  const fetchUser = async () => {
    try {
      const token = localStorage.getItem('token');
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/auth/me`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json();
      if (data.success) {
        setUser(data.data);
      }
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const updateCartCount = () => {
    const cart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');
    setCartCount(cart.reduce((sum, item) => sum + item.quantity, 0));
  };

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    router.push(`/${locale}/login`);
  };

  const menuItems = [
    {
      key: 'home',
      icon: <HomeOutlined />,
      label: <Link href={`/${locale}`}>{t('nav.home')}</Link>,
    },
    {
      key: 'appointments',
      icon: <CalendarOutlined />,
      label: <Link href={`/${locale}/appointments/new`}>{t('nav.appointments')}</Link>,
    },
    {
      key: 'pharmacy',
      icon: <MedicineBoxOutlined />,
      label: <Link href={`/${locale}/pharmacy`}>🏥 {t('nav.pharmacy')}</Link>,
    },
    {
      key: 'cart',
      icon: <Badge count={cartCount} size="small">
        <ShoppingCartOutlined style={{ fontSize: '20px' }} />
      </Badge>,
      label: <Link href={`/${locale}/pharmacy/cart`}>{t('pharmacy.cart')}</Link>,
    },
    {
      key: 'profile',
      icon: <UserOutlined />,
      label: user ? (
        <Dropdown
          menu={{
            items: [
              {
                key: 'profile',
                label: <Link href={`/${locale}/profile`}>{t('profile.title')}</Link>,
              },
              {
                key: 'appointments',
                label: <Link href={`/${locale}/profile/appointments`}>{t('profile.appointmentsList')}</Link>,
              },
              {
                key: 'pharmacy-orders',
                label: <Link href={`/${locale}/profile/pharmacy-orders`}>سفارشات داروخانه</Link>,
              },
              {
                key: 'logout',
                label: t('auth.logout') || 'خروج',
                onClick: handleLogout,
              },
            ]
          }}
        >
          <Space>
            <Avatar size="small" icon={<UserOutlined />} />
            <span>{user?.name || t('profile.title')}</span>
          </Space>
        </Dropdown>
      ) : (
        <Link href={`/${locale}/login`}>{t('auth.login')}</Link>
      ),
    },
  ];

  return (
    <div style={{ 
      display: 'flex', 
      alignItems: 'center', 
      justifyContent: 'space-between',
      width: '100%',
      gap: '16px'
    }}>
      <div style={{ 
        display: 'flex', 
        alignItems: 'center', 
        flex: 1,
        gap: '8px'
      }}>
        <Menu
          mode="horizontal"
          selectedKeys={[pathname.split('/')[2] || 'home']}
          items={menuItems}
          style={{ 
            border: 'none', 
            background: 'transparent',
            minWidth: 'auto',
            flex: 1,
          }}
        />
      </div>

      <div style={{ 
        display: 'flex', 
        alignItems: 'center', 
        gap: '8px',
      }}>
        <Dropdown
          menu={{
            items: [
              { key: 'fa', label: 'فارسی', onClick: () => changeLanguage('fa') },
              { key: 'en', label: 'English', onClick: () => changeLanguage('en') },
              { key: 'ar', label: 'العربية', onClick: () => changeLanguage('ar') },
            ]
          }}
        >
          <Button type="text" icon={<GlobalOutlined />} style={{ fontSize: '14px' }}>
            {locale === 'fa' ? 'فا' : locale === 'en' ? 'EN' : 'ع'}
          </Button>
        </Dropdown>
      </div>
    </div>
  );
};

export default NavBar;
