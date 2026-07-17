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
  WalletOutlined,
  CreditCardOutlined,
  MessageOutlined,
  BellOutlined,
  ReadOutlined,
  SearchOutlined,
  HomeOutlined,
  ShopOutlined,
  UserSwitchOutlined,
  BarChartOutlined,
  ApiOutlined,
  SettingOutlined,
  HeartFilled,
  RobotOutlined,
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
    // ===== بخش هوش مصنوعی =====
    {
      key: 'ai-chat',
      icon: <RobotOutlined />,
      label: 'هوش مصنوعی',
      children: [
        {
          key: 'ai-chat',
          icon: <DashboardOutlined />,
          label: <Link href="/admin/ai-chat">داشبورد</Link>,
        },
        {
          key: 'ai-chat-prompts',
          icon: <FileTextOutlined />,
          label: <Link href="/admin/ai-chat/prompts">مدیریت پرامپت‌ها</Link>,
        },
        {
          key: 'ai-chat-models',
          icon: <RobotOutlined />,
          label: <Link href="/admin/ai-chat/models">مدیریت مدل‌ها</Link>,
        },
        {
          key: 'ai-chat-sessions',
          icon: <MessageOutlined />,
          label: <Link href="/admin/ai-chat/sessions">جلسات چت</Link>,
        },
        {
          key: 'ai-chat-analytics',
          icon: <BarChartOutlined />,
          label: <Link href="/admin/ai-chat/analytics">گزارشات</Link>,
        },
        {
          key: 'ai-chat-settings',
          icon: <SettingOutlined />,
          label: <Link href="/admin/ai-chat/settings">تنظیمات</Link>,
        },
      ],
    },
    // ===== ادامه منوهای قبلی =====
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
          icon: <FileTextOutlined />,
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
          icon: <ShopOutlined />,
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
    if (path.startsWith('/admin/ai-chat')) {
      if (path === '/admin/ai-chat') return ['ai-chat'];
      if (path.includes('/prompts')) return ['ai-chat-prompts'];
      if (path.includes('/models')) return ['ai-chat-models'];
      if (path.includes('/sessions')) return ['ai-chat-sessions'];
      if (path.includes('/analytics')) return ['ai-chat-analytics'];
      if (path.includes('/settings')) return ['ai-chat-settings'];
    }
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
                  دکتر<span style={{ color: '#2563eb' }}>وب</span>
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
            defaultOpenKeys={['ai-chat']}
            items={menuItems}
            style={{ border: 'none', background: 'transparent' }}
        />
      </Sider>
  );
}
