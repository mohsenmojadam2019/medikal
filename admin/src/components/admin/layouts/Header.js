'use client';

import { useMemo, useState } from 'react';
import { usePathname, useRouter } from 'next/navigation';
import {
    Avatar,
    Badge,
    Button,
    Dropdown,
    Form,
    Input,
    Layout,
    Modal,
    Select,
    Space,
    Tooltip,
} from 'antd';
import {
    GlobalOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    MessageOutlined,
    SearchOutlined,
    SettingOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';
import NotificationBell from '@/components/admin/NotificationBell';

const { Header: AntHeader } = Layout;

const PAGE_TITLES = [
    {
        path: '/admin/ai-chat/prompts',
        title: 'مدیریت پرامپت‌ها',
        subtitle: 'تنظیم و مدیریت پرامپت‌های هوش مصنوعی',
    },
    {
        path: '/admin/ai-chat',
        title: 'هوش مصنوعی',
        subtitle: 'مدیریت سرویس‌های هوشمند',
    },
    {
        path: '/admin/appointments',
        title: 'مدیریت نوبت‌ها',
        subtitle: 'بررسی و مدیریت نوبت‌های پزشکی',
    },
    {
        path: '/admin/doctors',
        title: 'مدیریت پزشکان',
        subtitle: 'مدیریت اطلاعات و وضعیت پزشکان',
    },
    {
        path: '/admin/patients',
        title: 'مدیریت بیماران',
        subtitle: 'مدیریت پرونده و اطلاعات بیماران',
    },
    {
        path: '/admin/schedules',
        title: 'زمان‌بندی',
        subtitle: 'مدیریت ساعات کاری پزشکان',
    },
    {
        path: '/admin/specialties',
        title: 'مدیریت تخصص‌ها',
        subtitle: 'تعریف و مدیریت تخصص‌های پزشکی',
    },
    {
        path: '/admin/prescriptions',
        title: 'مدیریت نسخه‌ها',
        subtitle: 'بررسی و مدیریت نسخه‌های پزشکی',
    },
    {
        path: '/admin/referrals',
        title: 'مدیریت ارجاعات',
        subtitle: 'مدیریت ارجاع بیماران بین پزشکان',
    },
    {
        path: '/admin/invoices',
        title: 'مدیریت فاکتورها',
        subtitle: 'مدیریت امور مالی و فاکتورها',
    },
    {
        path: '/admin/payments',
        title: 'مدیریت پرداخت‌ها',
        subtitle: 'مشاهده و مدیریت تراکنش‌ها',
    },
    {
        path: '/admin/reports',
        title: 'گزارشات',
        subtitle: 'مشاهده آمار و گزارش‌های مدیریتی',
    },
    {
        path: '/admin/notifications',
        title: 'اعلان‌ها',
        subtitle: 'مدیریت پیام‌ها و اعلان‌های سیستم',
    },
    {
        path: '/admin/blog',
        title: 'مدیریت محتوا',
        subtitle: 'مدیریت مقالات و محتوای سایت',
    },
    {
        path: '/admin/settings',
        title: 'تنظیمات',
        subtitle: 'تنظیمات عمومی پنل مدیریت',
    },
    {
        path: '/admin/profile',
        title: 'پروفایل',
        subtitle: 'مدیریت اطلاعات حساب کاربری',
    },
];

export default function Header({
                                   collapsed,
                                   isMobile,
                                   onToggle,
                               }) {
    const pathname = usePathname();
    const router = useRouter();

    const { user, logout } = useAuth();
    const {
        locale,
        languages = [],
        switchLanguage,
        t,
    } = useLanguage();

    const [searchValue, setSearchValue] = useState('');
    const [isLanguageModalOpen, setIsLanguageModalOpen] =
        useState(false);

    const pageInformation = useMemo(() => {
        const page = PAGE_TITLES.find(
            (item) =>
                pathname === item.path ||
                pathname.startsWith(`${item.path}/`)
        );

        return (
            page || {
                title: t('dashboard', 'داشبورد'),
                subtitle: t(
                    'admin_panel',
                    'پنل مدیریت کلینیک'
                ),
            }
        );
    }, [pathname, t]);

    const userName =
        user?.full_name ||
        user?.name ||
        user?.email ||
        t('user', 'کاربر');

    const avatarText =
        String(userName).trim().charAt(0).toUpperCase() || 'U';

    const currentLanguage =
        languages.find((language) => language.code === locale) || {
            code: 'fa',
            name: 'فارسی',
            nativeName: 'فارسی',
        };

    const userMenuItems = [
        {
            key: 'profile',
            icon: <UserOutlined />,
            label: t('profile', 'پروفایل من'),
            onClick: () => router.push('/admin/profile'),
        },
        {
            key: 'settings',
            icon: <SettingOutlined />,
            label: t('settings', 'تنظیمات'),
            onClick: () => router.push('/admin/settings'),
        },
        {
            type: 'divider',
        },
        {
            key: 'logout',
            danger: true,
            icon: <LogoutOutlined />,
            label: t('logout', 'خروج از حساب'),
            onClick: logout,
        },
    ];

    const handleLanguageChange = async ({ locale: language }) => {
        await switchLanguage(language);
        setIsLanguageModalOpen(false);
    };

    return (
        <>
            <AntHeader className="admin-header">
                <div className="admin-header-title-section">
                    <Button
                        type="text"
                        className="admin-header-menu-button"
                        icon={
                            collapsed ? (
                                <MenuUnfoldOutlined />
                            ) : (
                                <MenuFoldOutlined />
                            )
                        }
                        onClick={onToggle}
                        aria-label="باز و بسته کردن منو"
                    />

                    <div className="admin-header-page-information">
                        <h1>{pageInformation.title}</h1>
                        <p>{pageInformation.subtitle}</p>
                    </div>
                </div>

                {!isMobile && (
                    <div className="admin-header-search">
                        <Input
                            allowClear
                            value={searchValue}
                            prefix={<SearchOutlined />}
                            placeholder="جستجو در کل سیستم..."
                            onChange={(event) =>
                                setSearchValue(event.target.value)
                            }
                        />
                    </div>
                )}

                <div className="admin-header-actions">
                    {!isMobile && (
                        <Tooltip title="تغییر زبان">
                            <Button
                                type="text"
                                className="admin-header-action-button admin-language-button"
                                icon={<GlobalOutlined />}
                                onClick={() =>
                                    setIsLanguageModalOpen(true)
                                }
                            >
                                {currentLanguage.nativeName ||
                                    currentLanguage.name}
                            </Button>
                        </Tooltip>
                    )}

                    <NotificationBell />

                    {!isMobile && (
                        <Badge
                            count={3}
                            size="small"
                            offset={[-3, 3]}
                        >
                            <Tooltip title="پیام‌ها">
                                <Button
                                    type="text"
                                    className="admin-header-action-button"
                                    icon={<MessageOutlined />}
                                />
                            </Tooltip>
                        </Badge>
                    )}

                    <Dropdown
                        menu={{ items: userMenuItems }}
                        placement="bottomLeft"
                        trigger={['click']}
                    >
                        <button
                            type="button"
                            className="admin-user-button"
                        >
                            <Avatar
                                src={user?.avatar_url || user?.avatar}
                                className="admin-user-avatar"
                            >
                                {!user?.avatar_url &&
                                    !user?.avatar &&
                                    avatarText}
                            </Avatar>

                            {!isMobile && (
                                <div className="admin-user-information">
                                    <strong>{userName}</strong>
                                    <span>
                    {user?.roles?.[0]?.name ||
                        user?.roles?.[0] ||
                        'مدیر سیستم'}
                  </span>
                                </div>
                            )}
                        </button>
                    </Dropdown>
                </div>
            </AntHeader>

            <Modal
                title={t('select_language', 'انتخاب زبان')}
                open={isLanguageModalOpen}
                onCancel={() => setIsLanguageModalOpen(false)}
                footer={null}
                centered
                width={420}
                dir="rtl"
            >
                <Form
                    layout="vertical"
                    initialValues={{ locale }}
                    onFinish={handleLanguageChange}
                >
                    <Form.Item
                        name="locale"
                        label={t('language', 'زبان پنل')}
                        rules={[
                            {
                                required: true,
                                message: 'زبان را انتخاب کنید',
                            },
                        ]}
                    >
                        <Select
                            size="large"
                            placeholder="انتخاب زبان"
                            options={languages.map((language) => ({
                                value: language.code,
                                label:
                                    language.nativeName || language.name,
                            }))}
                        />
                    </Form.Item>

                    <Space
                        style={{
                            width: '100%',
                            justifyContent: 'flex-end',
                        }}
                    >
                        <Button
                            onClick={() =>
                                setIsLanguageModalOpen(false)
                            }
                        >
                            انصراف
                        </Button>

                        <Button
                            type="primary"
                            htmlType="submit"
                        >
                            اعمال تغییرات
                        </Button>
                    </Space>
                </Form>
            </Modal>
        </>
    );
}
