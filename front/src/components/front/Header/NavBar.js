// /home/god/Videos/medikal/front/src/components/front/Header/NavBar.jsx
'use client';

import { useState, useEffect } from 'react';
import { Menu, Button, Drawer, Dropdown, Space, Badge, Avatar, Divider, Typography } from 'antd';
import {
  HomeOutlined, CalendarOutlined, MedicineBoxOutlined,
  UserOutlined, LogoutOutlined,
  MenuOutlined, GlobalOutlined, ShoppingCartOutlined,
  RobotOutlined
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
      key: 'ai-chat',
      icon: <RobotOutlined style={{ color: '#1890ff' }} />,
      label: (
          <Link href={`/${locale}/ai-chat`} style={{ color: '#1890ff', fontWeight: '500' }}>
            🧠 هوش مصنوعی
          </Link>
      ),
    },
    {
      key: 'cart',
      icon: <Badge count={cartCount} size="small">
        <ShoppingCartOutlined style={{ fontSize: '20px' }} />
      </Badge>,
      label: <Link href={`/${locale}/pharmacy/cart`}>{t('pharmacy.cart')}</Link>,
    },
  ];

  return (
      <nav className="navbar-modern">
        <div className="navbar-container">
          {/* منوی اصلی - وسط‌چین شده */}
          <div className="navbar-menu-wrapper">
            <Menu
                mode="horizontal"
                selectedKeys={[pathname.split('/')[2] || 'home']}
                items={menuItems}
                className="navbar-menu"
            />
          </div>

        </div>

        <style jsx>{`
        .navbar-modern {
          background: white;
          border-bottom: 1px solid #e2e8f0;
          position: sticky;
          top: 0;
          z-index: 999;
        }

        .navbar-container {
          max-width: 1440px;
          margin: 0 auto;
          padding: 0 24px;
          display: flex;
          align-items: center;
          justify-content: center;
          height: 60px;
          position: relative;
        }

        .navbar-menu-wrapper {
          flex: 1;
          display: flex;
          justify-content: center;
          align-items: center;
        }

        .navbar-menu {
          border: none !important;
          background: transparent !important;
          min-width: auto;
          display: flex;
          align-items: center;
          justify-content: center;
          width: auto;
        }

        .navbar-menu :global(.ant-menu-item) {
          padding: 0 16px;
          height: 44px;
          line-height: 44px;
          border-radius: 12px;
          margin: 0 2px;
          font-weight: 500;
          color: #475569;
          transition: all 0.25s ease;
        }

        .navbar-menu :global(.ant-menu-item:hover) {
          color: #2563eb !important;
          background: rgba(37, 99, 235, 0.06) !important;
        }

        .navbar-menu :global(.ant-menu-item-selected) {
          color: #2563eb !important;
          background: rgba(37, 99, 235, 0.08) !important;
        }

        .navbar-menu :global(.ant-menu-item-selected)::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 50%;
          transform: translateX(-50%);
          width: 60%;
          height: 3px;
          border-radius: 3px 3px 0 0;
          background: #2563eb;
        }

        .navbar-menu :global(.ant-menu-item) a {
          color: inherit;
          text-decoration: none;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .navbar-menu :global(.ant-menu-item-selected) a {
          color: #2563eb;
        }

        .navbar-right {
          display: flex;
          align-items: center;
          gap: 8px;
          flex-shrink: 0;
          position: absolute;
          right: 24px;
        }

        .lang-btn {
          display: flex;
          align-items: center;
          gap: 4px;
          height: 40px;
          border-radius: 12px;
          font-weight: 600;
          color: #475569;
          border: 1px solid transparent;
        }

        .lang-btn:hover {
          border-color: #e2e8f0;
          color: #2563eb;
        }

        @media (max-width: 768px) {
          .navbar-container {
            padding: 0 12px;
            height: 52px;
          }

          .navbar-menu :global(.ant-menu-item) {
            padding: 0 10px;
            font-size: 13px;
          }

          .navbar-menu :global(.ant-menu-item) span {
            display: none;
          }

          .navbar-menu :global(.ant-menu-item) .anticon {
            font-size: 18px;
          }

          .lang-btn span {
            display: none;
          }

          .navbar-right {
            right: 12px;
          }
        }

        @media (max-width: 480px) {
          .navbar-container {
            padding: 0 8px;
            height: 48px;
          }

          .navbar-menu :global(.ant-menu-item) {
            padding: 0 6px;
          }
        }
      `}</style>
      </nav>
  );
};

export default NavBar;