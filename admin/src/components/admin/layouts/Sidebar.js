'use client';

import { Layout, Menu } from 'antd';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  DashboardOutlined,
  UserOutlined,
  TeamOutlined,
  CalendarOutlined,
  HeartOutlined,
  ClockCircleOutlined,
  FileTextOutlined,
  MedicineBoxOutlined,
  ArrowRightOutlined,
  StarOutlined,
  FileInvoiceOutlined,
  WalletOutlined,
  CreditCardOutlined,
  MessageOutlined,
  BellOutlined,
  ReadOutlined,
  SearchOutlined,
  HomeOutlined,
  HospitalOutlined,
  UserSwitchOutlined,
  BarChartOutlined,
  ApiOutlined,
  SettingOutlined,
  HeartFilled,
} from '@ant-design/icons';
import { useLanguage } from '@/context/LanguageContext';

const { Sider } = Layout;

export default function Sidebar({ collapsed, onCollapse }) {
  const pathname = usePathname();
  const { t } = useLanguage();

  const menuItems = [
    {
      key: 'dashboard',
      icon: <DashboardOutlined />,
      label: <Link href="/admin">{t('dashboard', 'نمای کلی')}</Link>,
    },
    {
      type: 'group',
      label: t('management', 'مدیریت'),
      children: [
        {
          key: 'doctors',
          icon: <UserOutlined />,
          label: <Link href="/admin/doctors">{t('doctors', 'پزشکان')}</Link>,
        },
        {
          key: 'patients',
          icon: <TeamOutlined />,
          label: <Link href="/admin/patients">{t('patients', 'بیماران')}</Link>,
        },
        {
          key: 'appointments',
          icon: <CalendarOutlined />,
          label: <Link href="/admin/appointments">{t('appointments', 'نوبت‌ها')}</Link>,
        },
        {
          key: 'specialties',
          icon: <HeartOutlined />,
          label: <Link href="/admin/specialties">{t('specialties', 'تخصص‌ها')}</Link>,
        },
        {
          key: 'schedules',
          icon: <ClockCircleOutlined />,
          label: <Link href="/admin/schedules">{t('schedules', 'زمان‌بندی')}</Link>,
        },
      ],
    },
    {
      type: 'group',
      label: t('medical', 'پزشکی'),
      children: [
        {
          key: 'prescriptions',
          icon: <FileTextOutlined />,
          label: <Link href="/admin/prescriptions">{t('prescriptions', 'نسخه‌ها')}</Link>,
        },
        {
          key: 'drugs',
          icon: <MedicineBoxOutlined />,
          label: <Link href="/admin/drugs">{t('drugs', 'داروها')}</Link>,
        },
        {
          key: 'referrals',
          icon: <ArrowRightOutlined />,
          label: <Link href="/admin/referrals">{t('referrals', 'ارجاعات')}</Link>,
        },
        {
          key: 'ratings',
          icon: <StarOutlined />,
          label: <Link href="/admin/ratings">{t('ratings', 'نظرات و امتیازات')}</Link>,
        },
      ],
    },
    {
      type: 'group',
      label: t('financial', 'مالی'),
      children: [
        {
          key: 'invoices',
          icon: <FileInvoiceOutlined />,
          label: <Link href="/admin/invoices">{t('invoices', 'فاکتورها')}</Link>,
        },
        {
          key: 'wallet',
          icon: <WalletOutlined />,
          label: <Link href="/admin/wallet">{t('wallet', 'کیف پول')}</Link>,
        },
        {
          key: 'payments',
          icon: <CreditCardOutlined />,
          label: <Link href="/admin/payments">{t('payments', 'پرداخت‌ها')}</Link>,
        },
      ],
    },
    {
      type: 'group',
      label: t('communication', 'ارتباطات'),
      children: [
        {
          key: 'chat',
          icon: <MessageOutlined />,
          label: <Link href="/admin/chat">{t('chat', 'پیام‌ها')}</Link>,
        },
        {
          key: 'notifications',
          icon: <BellOutlined />,
          label: <Link href="/admin/notifications">{t('notifications', 'اعلان‌ها')}</Link>,
        },
        {
          key: 'reminders',
          icon: <ClockCircleOutlined />,
          label: <Link href="/admin/reminders">{t('reminders', 'یادآوری‌ها')}</Link>,
        },
      ],
    },
    {
      type: 'group',
      label: t('content', 'محتوا'),
      children: [
        {
          key: 'blog',
          icon: <ReadOutlined />,
          label: <Link href="/admin/blog">{t('blog', 'وبلاگ')}</Link>,
        },
        {
          key: 'seo',
          icon: <SearchOutlined />,
          label: <Link href="/admin/seo">{t('seo', 'سئو')}</Link>,
        },
        {
          key: 'landing',
          icon: <HomeOutlined />,
          label: <Link href="/admin/landing">{t('landing', 'صفحه اصلی')}</Link>,
        },
      ],
    },
    {
      type: 'group',
      label: t('system', 'سیستم'),
      children: [
        {
          key: 'clinic',
          icon: <HospitalOutlined />,
          label: <Link href="/admin/clinic">{t('clinic', 'کلینیک')}</Link>,
        },
        {
          key: 'users',
          icon: <UserSwitchOutlined />,
          label: <Link href="/admin/users">{t('users', 'کاربران')}</Link>,
        },
        {
          key: 'reports',
          icon: <BarChartOutlined />,
          label: <Link href="/admin/reports">{t('reports', 'گزارشات')}</Link>,
        },
        {
          key: 'webhook',
          icon: <ApiOutlined />,
          label: <Link href="/admin/webhook">{t('webhook', 'وب‌هوک')}</Link>,
        },
        {
          key: 'settings',
          icon: <SettingOutlined />,
          label: <Link href="/admin/settings">{t('settings', 'تنظیمات')}</Link>,
        },
      ],
    },
  ];

  const getSelectedKey = () => {
    const path = pathname || '/admin';
    const key = path.replace('/admin/', '').replace('/', '') || 'dashboard';
    return [key];
  };

  return (
    <Sider
      collapsible
      collapsed={collapsed}
      onCollapse={onCollapse}
      width={280}
      theme="light"
      style={{
        height: '100vh',
        position: 'fixed',
        right: 0,
        top: 0,
        bottom: 0,
        zIndex: 100,
        borderLeft: '1px solid #e8e8f0',
        overflow: 'auto',
        background: '#ffffff',
      }}
    >
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: '12px',
          padding: '16px 20px',
          borderBottom: '1px solid #e8e8f0',
          marginBottom: '8px',
        }}
      >
        <div
          style={{
            width: 46,
            height: 46,
            background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
            borderRadius: 12,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: '#ffffff',
            fontSize: 22,
            flexShrink: 0,
            boxShadow: '0 4px 12px rgba(37,99,235,0.3)',
          }}
        >
          <HeartFilled />
        </div>
        {!collapsed && (
          <div>
            <div style={{ fontSize: 20, fontWeight: 800, color: '#0f172a' }}>
              کلینیک<span style={{ color: '#2563eb' }}>یار</span>
            </div>
            <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400, marginTop: -2 }}>
              {t('system_management', 'سیستم مدیریت سلامت')}
            </div>
          </div>
        )}
      </div>

      <Menu
        mode="inline"
        selectedKeys={getSelectedKey()}
        items={menuItems}
        style={{ border: 'none', background: 'transparent' }}
      />
    </Sider>
  );
}
EOF```

### ۳.۲. `src/components/admin/layouts/Header.js`

```bash
cat > src/components/admin/layouts/Header.js << 'EOF'
'use client';

import { useState } from 'react';
import { Layout, Input, Badge, Avatar, Dropdown, Space, Button, Modal, Form, Select } from 'antd';
import {
  MenuUnfoldOutlined,
  MenuFoldOutlined,
  SearchOutlined,
  BellOutlined,
  MessageOutlined,
  UserOutlined,
  LogoutOutlined,
  SettingOutlined,
  GlobalOutlined,
} from '@ant-design/icons';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';

const { Header: AntHeader } = Layout;

export default function Header({ collapsed, onToggle }) {
  const { user, logout } = useAuth();
  const { locale, languages, switchLanguage, t } = useLanguage();
  const [searchValue, setSearchValue] = useState('');
  const [isLanguageModalOpen, setIsLanguageModalOpen] = useState(false);

  const userMenuItems = [
    {
      key: 'profile',
      icon: <UserOutlined />,
      label: t('profile', 'پروفایل'),
    },
    {
      key: 'settings',
      icon: <SettingOutlined />,
      label: t('settings', 'تنظیمات'),
    },
    {
      type: 'divider',
    },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: t('logout', 'خروج'),
      onClick: logout,
    },
  ];

  const userName = user?.name || t('user', 'کاربر');
  const userAvatar = userName.charAt(0);

  const currentLanguage = languages.find((lang) => lang.code === locale) || {
    code: 'fa',
    name: 'فارسی',
    nativeName: 'فارسی',
  };

  const handleLanguageChange = async (values) => {
    await switchLanguage(values.locale);
    setIsLanguageModalOpen(false);
  };

  return (
    <>
      <AntHeader
        style={{
          background: '#ffffff',
          padding: '0 24px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          borderBottom: '1px solid #e8e8f0',
          height: 72,
          position: 'sticky',
          top: 0,
          zIndex: 99,
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
          <Button
            type="text"
            icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
            onClick={onToggle}
            style={{ fontSize: 18 }}
          />
          <div>
            <h1 style={{ fontSize: 20, fontWeight: 800, margin: 0, color: '#0f172a' }}>
              {t('dashboard', 'داشبورد')}
              <span
                style={{
                  fontSize: 13,
                  fontWeight: 400,
                  color: '#64748b',
                  display: 'block',
                }}
              >
                {t('admin_panel', 'پنل مدیریت کلینیک')}
              </span>
            </h1>
          </div>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <Input
            prefix={<SearchOutlined style={{ color: '#94a3b8' }} />}
            placeholder={t('search_placeholder', 'جستجو در کل سیستم...')}
            value={searchValue}
            onChange={(e) => setSearchValue(e.target.value)}
            style={{
              width: 220,
              borderRadius: 8,
              borderColor: '#e2e8f0',
            }}
          />

          <Button
            type="text"
            icon={<GlobalOutlined />}
            onClick={() => setIsLanguageModalOpen(true)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 4,
              fontSize: 13,
            }}
          >
            {currentLanguage.nativeName || currentLanguage.name}
          </Button>

          <Badge count={5} size="small" offset={[-4, 4]}>
            <Button
              type="text"
              icon={<BellOutlined style={{ fontSize: 18 }} />}
              style={{ width: 40, height: 40 }}
            />
          </Badge>

          <Badge count={3} size="small" offset={[-4, 4]}>
            <Button
              type="text"
              icon={<MessageOutlined style={{ fontSize: 18 }} />}
              style={{ width: 40, height: 40 }}
            />
          </Badge>

          <Dropdown
            menu={{ items: userMenuItems }}
            placement="bottomLeft"
            trigger={['click']}
          >
            <Space style={{ cursor: 'pointer', marginRight: 8 }}>
              <Avatar
                style={{
                  background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                  boxShadow: '0 2px 8px rgba(37,99,235,0.2)',
                  width: 40,
                  height: 40,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontWeight: 700,
                  fontSize: 16,
                  color: '#ffffff',
                }}
              >
                {userAvatar}
              </Avatar>
              <span style={{ fontWeight: 600, color: '#0f172a' }}>{userName}</span>
            </Space>
          </Dropdown>
        </div>
      </AntHeader>

      <Modal
        title={t('select_language', 'انتخاب زبان')}
        open={isLanguageModalOpen}
        onCancel={() => setIsLanguageModalOpen(false)}
        footer={null}
        centered
        dir="rtl"
      >
        <Form
          layout="vertical"
          onFinish={handleLanguageChange}
          initialValues={{ locale }}
        >
          <Form.Item
            name="locale"
            label={t('language', 'زبان')}
            rules={[{ required: true, message: 'لطفاً زبان را انتخاب کنید' }]}
          >
            <Select
              size="large"
              placeholder={t('select_language', 'انتخاب زبان')}
              showSearch
              optionFilterProp="children"
            >
              {languages.map((lang) => (
                <Select.Option key={lang.code} value={lang.code}>
                  {lang.nativeName || lang.name}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              block
              size="large"
              style={{
                height: 44,
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
                borderRadius: 8,
                fontWeight: 600,
              }}
            >
              {t('apply', 'اعمال')}
            </Button>
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}
