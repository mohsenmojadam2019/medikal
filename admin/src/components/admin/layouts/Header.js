// src/components/admin/Header.js

'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Layout, Input, Avatar, Dropdown, Space, Button, Modal, Form, Select, Badge } from 'antd';
import {
    MenuUnfoldOutlined,
    MenuFoldOutlined,
    SearchOutlined,
    MessageOutlined,
    UserOutlined,
    LogoutOutlined,
    SettingOutlined,
    GlobalOutlined,
} from '@ant-design/icons';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';
import NotificationBell from '@/components/admin/NotificationBell'; // ✅ اضافه کن

const { Header: AntHeader } = Layout;

export default function Header({ collapsed, onToggle }) {
    const router = useRouter();
    const { user, logout } = useAuth();
    const { locale, languages, switchLanguage, t } = useLanguage();
    const [searchValue, setSearchValue] = useState('');
    const [isLanguageModalOpen, setIsLanguageModalOpen] = useState(false);

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
            icon: <LogoutOutlined />,
            label: t('logout', 'خروج'),
            onClick: logout,
        },
    ];

    const userName = user?.name || t('user', 'کاربر');
    const userAvatar = user?.avatar ? user.avatar : userName.charAt(0);

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

                    {/* ✅ زنگوله داینامیک */}
                    <NotificationBell />

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
                                src={user?.avatar_url || user?.avatar}
                                style={{
                                    background: !user?.avatar_url && !user?.avatar ? 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)' : undefined,
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
                                {!user?.avatar_url && !user?.avatar && userAvatar}
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