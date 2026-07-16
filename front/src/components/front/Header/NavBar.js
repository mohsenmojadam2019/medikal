// /home/god/Videos/medikal/front/src/components/front/Header/NavBar.jsx
'use client';

import { useState, useEffect } from 'react';
import { Drawer, Button, Avatar, Divider, Modal, Space } from 'antd';
import {
  UserOutlined, LogoutOutlined,
  MenuOutlined,
  PhoneOutlined,
  WhatsAppOutlined, MailOutlined,
  EnvironmentOutlined
} from '@ant-design/icons';
import Link from 'next/link';
import { useRouter, usePathname } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';

const NavBar = () => {
  const router = useRouter();
  const pathname = usePathname();
  const { locale } = useLanguage();
  const [mobileMenuVisible, setMobileMenuVisible] = useState(false);
  const [contactModalVisible, setContactModalVisible] = useState(false);
  const [user, setUser] = useState(null);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      fetchUser();
    }
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

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    router.push(`/${locale}/login`);
  };

  const showContactModal = () => {
    setContactModalVisible(true);
  };

  const navItems = [
    { key: 'home', label: 'صفحه اصلی', href: `/${locale}` },
    { key: 'appointments', label: 'نوبت‌دهی', href: `/${locale}/appointments/new` },
    { key: 'pharmacy', label: 'داروخانه', href: `/${locale}/pharmacy` },
    { key: 'ai-chat', label: 'هوش مصنوعی', href: `/${locale}/ai-chat` },
    { key: 'about', label: 'درباره ما', href: `/${locale}/about` },
    { key: 'contact', label: 'تماس با ما', isContact: true },
  ];

  const isActive = (href) => {
    if (href === `/${locale}`) {
      return pathname === href;
    }
    return pathname.startsWith(href);
  };

  return (
      <nav className="navbar-modern">
        <div className="navbar-container">
          <div className="nav-desktop">
            {navItems.map((item) => {
              if (item.isContact) {
                return (
                    <button
                        key={item.key}
                        className={`nav-link ${pathname === `/${locale}/contact` ? 'active' : ''}`}
                        onClick={showContactModal}
                    >
                      {item.label}
                    </button>
                );
              }
              return (
                  <Link
                      key={item.key}
                      href={item.href}
                      className={`nav-link ${isActive(item.href) ? 'active' : ''}`}
                  >
                    {item.label}
                  </Link>
              );
            })}
          </div>

          <button
              className="mobile-menu-btn"
              onClick={() => setMobileMenuVisible(true)}
          >
            <MenuOutlined style={{ fontSize: '24px' }} />
          </button>
        </div>

        <Drawer
            title="منو"
            placement="right"
            onClose={() => setMobileMenuVisible(false)}
            open={mobileMenuVisible}
            width={280}
            bodyStyle={{ padding: '16px 0' }}
        >
          {user ? (
              <div style={{ textAlign: 'center', padding: '0 16px 16px' }}>
                <Avatar size={64} icon={<UserOutlined />} />
                <div style={{ fontWeight: 600, marginTop: 8, color: '#000000' }}>
                  {user.name || user.full_name || 'کاربر'}
                </div>
                <div style={{ color: '#94a3b8', fontSize: 14 }}>
                  {user.role || 'کاربر'}
                </div>
              </div>
          ) : (
              <div style={{ padding: '0 16px 16px', display: 'flex', flexDirection: 'column', gap: 8 }}>
                <Link href={`/${locale}/login`}>
                  <Button type="primary" block>ورود</Button>
                </Link>
                <Link href={`/${locale}/register`}>
                  <Button block>ثبت نام</Button>
                </Link>
              </div>
          )}

          <Divider style={{ margin: 0 }} />

          <div style={{ display: 'flex', flexDirection: 'column', padding: '8px 0' }}>
            {navItems.map((item) => {
              if (item.isContact) {
                return (
                    <button
                        key={item.key}
                        className="mobile-nav-item"
                        onClick={() => {
                          setMobileMenuVisible(false);
                          showContactModal();
                        }}
                    >
                      {item.label}
                    </button>
                );
              }
              return (
                  <Link
                      key={item.key}
                      href={item.href}
                      className="mobile-nav-item"
                      onClick={() => setMobileMenuVisible(false)}
                  >
                    {item.label}
                  </Link>
              );
            })}
          </div>

          {user && (
              <>
                <Divider />
                <div style={{ display: 'flex', flexDirection: 'column', padding: '8px 0' }}>
                  <Link href={`/${locale}/profile`} className="mobile-nav-item">
                    پروفایل
                  </Link>
                  <button className="mobile-nav-item" onClick={handleLogout} style={{ color: '#ef4444' }}>
                    خروج
                  </button>
                </div>
              </>
          )}
        </Drawer>

        <Modal
            title={
              <Space>
                <PhoneOutlined style={{ color: '#2563eb' }} />
                <span>راه‌های ارتباط با ما</span>
              </Space>
            }
            open={contactModalVisible}
            onCancel={() => setContactModalVisible(false)}
            footer={null}
            width={480}
        >
          <div className="contact-modal-content">
            <div className="contact-item">
              <div className="contact-icon" style={{ background: '#dbeafe' }}>
                <PhoneOutlined style={{ color: '#2563eb', fontSize: 20 }} />
              </div>
              <div className="contact-info">
                <div className="contact-label">تلفن پشتیبانی</div>
                <a href="tel:02112345678" className="contact-value">۰۲۱-۱۲۳۴۵۶۷۸</a>
              </div>
            </div>

            <div className="contact-item">
              <div className="contact-icon" style={{ background: '#d1fae5' }}>
                <WhatsAppOutlined style={{ color: '#10b981', fontSize: 20 }} />
              </div>
              <div className="contact-info">
                <div className="contact-label">واتساپ</div>
                <a href="https://wa.me/989123456789" target="_blank" className="contact-value">
                  ۰۹۱۲-۳۴۵۶۷۸۹
                </a>
              </div>
            </div>

            <div className="contact-item">
              <div className="contact-icon" style={{ background: '#fce7f3' }}>
                <MailOutlined style={{ color: '#ec4899', fontSize: 20 }} />
              </div>
              <div className="contact-info">
                <div className="contact-label">ایمیل</div>
                <a href="mailto:info@clinic-yar.com" className="contact-value">
                  info@clinic-yar.com
                </a>
              </div>
            </div>

            <div className="contact-item">
              <div className="contact-icon" style={{ background: '#fef3c7' }}>
                <EnvironmentOutlined style={{ color: '#f59e0b', fontSize: 20 }} />
              </div>
              <div className="contact-info">
                <div className="contact-label">آدرس</div>
                <div className="contact-value">تهران، خیابان ولیعصر، پلاک ۱۲۳</div>
              </div>
            </div>

            <Divider />

            <div style={{ textAlign: 'center' }}>
              <div style={{ color: '#64748b', fontSize: 14, marginBottom: 12 }}>
                ما را در شبکه‌های اجتماعی دنبال کنید
              </div>
              <Space size="large">
                <a href="https://instagram.com" target="_blank" className="social-link" style={{ color: '#e4405f' }}>
                  <span style={{ fontSize: 28 }}>📸</span>
                </a>
                <a href="https://t.me" target="_blank" className="social-link" style={{ color: '#0088cc' }}>
                  <span style={{ fontSize: 28 }}>✈️</span>
                </a>
                <a href="https://wa.me/989123456789" target="_blank" className="social-link" style={{ color: '#25d366' }}>
                  <span style={{ fontSize: 28 }}>💬</span>
                </a>
              </Space>
            </div>

            <Divider />

            <Space style={{ width: '100%' }} direction="vertical">
              <Button
                  type="primary"
                  block
                  icon={<WhatsAppOutlined />}
                  onClick={() => window.open('https://wa.me/989123456789', '_blank')}
              >
                پیام در واتساپ
              </Button>
              <Button
                  block
                  icon={<PhoneOutlined />}
                  onClick={() => window.location.href = 'tel:02112345678'}
              >
                تماس تلفنی
              </Button>
            </Space>
          </div>
        </Modal>

        <style jsx global>{`
          /* زدن مستقیم به a های داخل منو */
          .navbar-modern a,
          .navbar-modern a:visited,
          .navbar-modern a:hover,
          .navbar-modern a:active,
          .navbar-modern a:focus {
            color: #000000 !important;
            text-decoration: none !important;
          }

          .navbar-modern .nav-link,
          .navbar-modern .nav-link:visited,
          .navbar-modern .nav-link:hover,
          .navbar-modern .nav-link:active,
          .navbar-modern .nav-link:focus {
            color: #000000 !important;
            text-decoration: none !important;
          }

          .navbar-modern .nav-link.active,
          .navbar-modern .nav-link.active:visited,
          .navbar-modern .nav-link.active:hover,
          .navbar-modern .nav-link.active:active {
            color: #000000 !important;
          }

          .navbar-modern .mobile-nav-item,
          .navbar-modern .mobile-nav-item:visited,
          .navbar-modern .mobile-nav-item:hover,
          .navbar-modern .mobile-nav-item:active {
            color: #000000 !important;
            text-decoration: none !important;
          }

          /* استایل اصلی منو */
          .navbar-modern {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
          }

          .navbar-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
          }

          .nav-desktop {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
          }

          .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            position: relative;
            color: #000000 !important;
          }

          .nav-link:hover {
            background: rgba(37, 99, 235, 0.05);
            color: #000000 !important;
          }

          .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            height: 3px;
            background: #2563eb;
            border-radius: 3px;
          }

          .nav-link:hover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            height: 3px;
            background: #2563eb;
            border-radius: 3px;
          }

          .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            color: #000000;
          }

          .mobile-menu-btn:hover {
            background: #f1f5f9;
          }

          .mobile-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: right;
            transition: background 0.2s ease;
            border-radius: 0;
            color: #000000 !important;
          }

          .mobile-nav-item:hover {
            background: #f1f5f9;
            color: #000000 !important;
          }

          .contact-modal-content {
            padding: 8px 0;
          }

          .contact-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
          }

          .contact-item:last-of-type {
            border-bottom: none;
          }

          .contact-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
          }

          .contact-info {
            flex: 1;
          }

          .contact-label {
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 2px;
          }

          .contact-value {
            color: #1e293b;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
          }

          .contact-value:hover {
            color: #2563eb;
          }

          .social-link {
            transition: transform 0.2s ease;
            display: inline-block;
          }

          .social-link:hover {
            transform: scale(1.1);
          }

          @media (max-width: 768px) {
            .navbar-container {
              padding: 0 12px;
              height: 52px;
            }

            .nav-desktop {
              display: none !important;
            }

            .mobile-menu-btn {
              display: flex !important;
              align-items: center;
              justify-content: center;
            }
          }

          @media (max-width: 1024px) and (min-width: 769px) {
            .nav-link {
              padding: 8px 14px;
              font-size: 16px;
            }

            .nav-desktop {
              gap: 4px;
            }
          }

          @media (min-width: 1025px) {
            .nav-link {
              padding: 10px 24px;
              font-size: 18px;
            }

            .nav-desktop {
              gap: 12px;
            }
          }
        `}</style>
      </nav>
  );
};

export default NavBar;