'use client';

import { useEffect, useState } from 'react';
import { Layout } from 'antd';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';
import Sidebar from './Sidebar';
import Header from './Header';
import Loading from '../common/Loading';
import '@/styles/admin-layout.css';

const { Content } = Layout;

export default function AdminLayout({ children }) {
    const pathname = usePathname();
    const { loading } = useAuth();
    const { direction } = useLanguage();

    const [collapsed, setCollapsed] = useState(false);
    const [isMobile, setIsMobile] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        const mediaQuery = window.matchMedia('(max-width: 991px)');

        const handleScreenChange = (event) => {
            const mobile = event.matches;

            setIsMobile(mobile);
            setCollapsed(mobile);
        };

        handleScreenChange(mediaQuery);
        mediaQuery.addEventListener('change', handleScreenChange);
        setMounted(true);

        return () => {
            mediaQuery.removeEventListener('change', handleScreenChange);
        };
    }, []);

    useEffect(() => {
        if (isMobile) {
            setCollapsed(true);
        }
    }, [pathname, isMobile]);

    const handleToggleSidebar = () => {
        setCollapsed((current) => !current);
    };

    const handleCloseMobileSidebar = () => {
        if (isMobile) {
            setCollapsed(true);
        }
    };

    if (loading || !mounted) {
        return <Loading fullScreen />;
    }

    return (
        <Layout
            dir={direction || 'rtl'}
            className={[
                'admin-shell',
                collapsed ? 'admin-shell-collapsed' : 'admin-shell-expanded',
                isMobile ? 'admin-shell-mobile' : 'admin-shell-desktop',
            ].join(' ')}
        >
            <Sidebar
                collapsed={collapsed}
                onCollapse={setCollapsed}
            />

            {isMobile && !collapsed && (
                <button
                    type="button"
                    aria-label="بستن منو"
                    className="admin-sidebar-backdrop"
                    onClick={handleCloseMobileSidebar}
                />
            )}

            <Layout className="admin-main-layout">
                <Header
                    collapsed={collapsed}
                    isMobile={isMobile}
                    onToggle={handleToggleSidebar}
                />

                <Content className="admin-main-content">
                    <main className="admin-content-container">
                        {children}
                    </main>
                </Content>
            </Layout>
        </Layout>
    );
}
