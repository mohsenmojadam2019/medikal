'use client';

import { useState, useEffect } from 'react';
import { Layout, Row, Col, Badge, Button, Space, Dropdown, message } from 'antd';
import { BellOutlined, MailOutlined, UserOutlined, LogoutOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import TopBar from './TopBar';
import NavBar from './NavBar';
import SearchBox from './SearchBox';

const { Header: AntHeader } = Layout;

export default function Header() {
  const router = useRouter();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    
    if (token && userData) {
      try {
        setUser(JSON.parse(userData));
      } catch {
        setUser(null);
      }
    }
    setLoading(false);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    message.success('✅ با موفقیت خارج شدید');
    router.push('/login');
  };

  const userMenu = {
    items: [
      {
        key: 'profile',
        label: <Link href="/profile">👤 پروفایل</Link>,
      },
      {
        key: 'appointments',
        label: <Link href="/appointments">📅 نوبت‌های من</Link>,
      },
      { type: 'divider' },
      {
        key: 'logout',
        label: '🚪 خروج',
        danger: true,
        onClick: handleLogout,
        icon: <LogoutOutlined />,
      },
    ],
  };

  // نمایش نام کاربر
  const getUserName = () => {
    if (!user) return 'کاربر';
    return user.name || user.full_name || user.mobile || 'کاربر';
  };

  return (
    <>
      <TopBar />
      <AntHeader
        className="header-main"
        style={{ background: '#fff', padding: '12px 0', height: 'auto', borderBottom: '1px solid #e2e8f0' }}
      >
        <div className="container">
          <Row align="middle" gutter={[16, 16]}>
            <Col xs={6} sm={6} md={4}>
              <Link href="/" className="logo">
                <div className="logo-icon">
                  <i className="fas fa-user-md" />
                </div>
                <div>
                  <span className="logo-text">
                    کلینیک<span>‌یار</span>
                  </span>
                  <span className="logo-sub">سیستم مدیریت جامع سلامت</span>
                </div>
              </Link>
            </Col>

            <Col xs={12} sm={10} md={14}>
              <SearchBox />
            </Col>

            <Col xs={6} sm={8} md={6}>
              <Space size="middle" className="header-actions">
                <Badge count={3}>
                  <Button type="text" icon={<BellOutlined />} />
                </Badge>
                <Badge count={5}>
                  <Button type="text" icon={<MailOutlined />} />
                </Badge>

                {!loading && user ? (
                  <Dropdown menu={userMenu} placement="bottomLeft">
                    <Button type="primary" icon={<UserOutlined />}>
                      {getUserName()}
                    </Button>
                  </Dropdown>
                ) : (
                  <Space>
                    <Link href="/login">
                      <Button>ورود</Button>
                    </Link>
                    <Link href="/register">
                      <Button type="primary">ثبت‌نام</Button>
                    </Link>
                  </Space>
                )}
              </Space>
            </Col>
          </Row>
        </div>
      </AntHeader>
      <NavBar />
    </>
  );
}
