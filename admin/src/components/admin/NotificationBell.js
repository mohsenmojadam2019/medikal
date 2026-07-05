// src/components/admin/NotificationBell.js

'use client';

import { useState, useEffect, useRef } from 'react';
import {
    Badge,
    Button,
    Dropdown,
    List,
    Avatar,
    Typography,
    Space,
    Empty,
    Spin,
    Tag,
    Popconfirm,
    message,
    Modal,
    Tooltip,
} from 'antd';
import {
    BellOutlined,
    CheckOutlined,
    DeleteOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    UserOutlined,
    ClockCircleOutlined,
} from '@ant-design/icons';
import { notificationsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import { useAuth } from '@/context/AuthContext';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

const { Text } = Typography;

export default function NotificationBell() {
    const { t } = useLanguage();
    const { user } = useAuth();
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [markAllLoading, setMarkAllLoading] = useState(false);
    const intervalRef = useRef(null);

    // ===== دریافت اعلان‌های خوانده نشده =====
    const fetchUnreadNotifications = async () => {
        try {
            const response = await notificationsService.getUnread();
            if (response.data?.success) {
                setNotifications(response.data.data || []);
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    };

    // ===== دریافت تعداد اعلان‌های خوانده نشده =====
    const fetchUnreadCount = async () => {
        try {
            const response = await notificationsService.getUnreadCount();
            if (response.data?.success) {
                setUnreadCount(response.data.data?.count || 0);
            }
        } catch (error) {
            console.error('Error fetching unread count:', error);
        }
    };

    // ===== بارگذاری اولیه =====
    useEffect(() => {
        if (user) {
            fetchUnreadCount();
            fetchUnreadNotifications();
        }
    }, [user]);

    // ===== بررسی دوره‌ای اعلان‌ها (هر ۳۰ ثانیه) =====
    useEffect(() => {
        if (user) {
            intervalRef.current = setInterval(() => {
                fetchUnreadCount();
                if (open) {
                    fetchUnreadNotifications();
                }
            }, 30000);
        }

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [user, open]);

    // ===== علامت‌گذاری به عنوان خوانده شده =====
    const handleMarkAsRead = async (id) => {
        try {
            await notificationsService.markAsRead(id);
            setNotifications(notifications.filter((n) => n.id !== id));
            setUnreadCount(unreadCount - 1);
            message.success(t('marked_as_read', 'اعلان به عنوان خوانده شده علامت‌گذاری شد'));
        } catch (error) {
            message.error(t('error', 'خطا در علامت‌گذاری'));
        }
    };

    // ===== علامت‌گذاری همه به عنوان خوانده شده =====
    const handleMarkAllAsRead = async () => {
        setMarkAllLoading(true);
        try {
            await notificationsService.markAllAsRead();
            setNotifications([]);
            setUnreadCount(0);
            message.success(t('all_marked_as_read', 'همه اعلان‌ها به عنوان خوانده شده علامت‌گذاری شدند'));
        } catch (error) {
            message.error(t('error', 'خطا در علامت‌گذاری'));
        } finally {
            setMarkAllLoading(false);
        }
    };

    // ===== حذف اعلان =====
    const handleDelete = async (id) => {
        try {
            await notificationsService.delete(id);
            setNotifications(notifications.filter((n) => n.id !== id));
            const isUnread = notifications.find((n) => n.id === id)?.is_read === false;
            if (isUnread) {
                setUnreadCount(unreadCount - 1);
            }
            message.success(t('deleted', 'اعلان با موفقیت حذف شد'));
        } catch (error) {
            message.error(t('error', 'خطا در حذف اعلان'));
        }
    };

    // ===== کلیک روی اعلان =====
    const handleNotificationClick = (notification) => {
        if (!notification.is_read) {
            handleMarkAsRead(notification.id);
        }
        // اگر لینک داشت به آن صفحه برو
        if (notification.link) {
            window.location.href = notification.link;
        }
        setOpen(false);
    };

    // ===== فرمت زمان =====
    const formatTime = (date) => {
        if (!date) return '';
        try {
            const now = moment();
            const msgDate = moment(date);
            if (now.diff(msgDate, 'day') === 0) {
                return msgDate.format('HH:mm');
            } else if (now.diff(msgDate, 'day') === 1) {
                return t('yesterday', 'دیروز');
            } else {
                return msgDate.format('jYYYY/jMM/jDD');
            }
        } catch {
            return '';
        }
    };

    // ===== آیتم‌های لیست اعلان‌ها =====
    const notificationList = (
        <div style={{ width: 380, maxHeight: 450, overflow: 'auto' }}>
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    padding: '12px 16px',
                    borderBottom: '1px solid #f0f0f0',
                    position: 'sticky',
                    top: 0,
                    background: '#fff',
                    zIndex: 10,
                }}
            >
                <Text strong>{t('notifications', 'اعلان‌ها')}</Text>
                <Space size="small">
                    {notifications.length > 0 && (
                        <Button
                            type="text"
                            size="small"
                            icon={<CheckOutlined />}
                            loading={markAllLoading}
                            onClick={handleMarkAllAsRead}
                        >
                            {t('mark_all_read', 'خواندن همه')}
                        </Button>
                    )}
                </Space>
            </div>

            {loading ? (
                <div style={{ textAlign: 'center', padding: 40 }}>
                    <Spin />
                </div>
            ) : notifications.length === 0 ? (
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description={t('no_notifications', 'هیچ اعلانی وجود ندارد')}
                    style={{ padding: 40 }}
                />
            ) : (
                <List
                    dataSource={notifications}
                    renderItem={(item) => (
                        <List.Item
                            style={{
                                padding: '12px 16px',
                                cursor: 'pointer',
                                background: item.is_read ? 'transparent' : '#f0f7ff',
                                borderBottom: '1px solid #f5f5f5',
                                transition: 'background 0.2s',
                            }}
                            onClick={() => handleNotificationClick(item)}
                            onMouseEnter={(e) => {
                                e.currentTarget.style.background = '#fafafa';
                            }}
                            onMouseLeave={(e) => {
                                e.currentTarget.style.background = item.is_read ? 'transparent' : '#f0f7ff';
                            }}
                            actions={[
                                <Tooltip key="delete" title={t('delete', 'حذف')}>
                                    <Button
                                        type="text"
                                        size="small"
                                        icon={<DeleteOutlined />}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleDelete(item.id);
                                        }}
                                        danger
                                    />
                                </Tooltip>,
                            ]}
                        >
                            <List.Item.Meta
                                avatar={
                                    <Avatar
                                        icon={<UserOutlined />}
                                        style={{
                                            backgroundColor: item.is_read ? '#d9d9d9' : '#2563eb',
                                        }}
                                        size={36}
                                    />
                                }
                                title={
                                    <div
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                        }}
                                    >
                                        <Text
                                            strong={!item.is_read}
                                            style={{ fontSize: 13 }}
                                        >
                                            {item.title}
                                        </Text>
                                        {!item.is_read && (
                                            <Badge
                                                status="processing"
                                                color="#2563eb"
                                                size="small"
                                            />
                                        )}
                                    </div>
                                }
                                description={
                                    <div>
                                        <div
                                            style={{
                                                fontSize: 12,
                                                color: '#64748b',
                                                marginBottom: 4,
                                                display: '-webkit-box',
                                                WebkitLineClamp: 2,
                                                WebkitBoxOrient: 'vertical',
                                                overflow: 'hidden',
                                            }}
                                        >
                                            {item.body || item.message}
                                        </div>
                                        <div
                                            style={{
                                                fontSize: 11,
                                                color: '#94a3b8',
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 4,
                                            }}
                                        >
                                            <ClockCircleOutlined style={{ fontSize: 11 }} />
                                            {formatTime(item.created_at)}
                                            {item.priority && (
                                                <Tag
                                                    size="small"
                                                    color={
                                                        item.priority === 'high' ? 'red' :
                                                            item.priority === 'medium' ? 'orange' :
                                                                'default'
                                                    }
                                                    style={{ fontSize: 10, margin: 0 }}
                                                >
                                                    {item.priority}
                                                </Tag>
                                            )}
                                        </div>
                                    </div>
                                }
                            />
                        </List.Item>
                    )}
                />
            )}
        </div>
    );

    return (
        <Dropdown
            open={open}
            onOpenChange={(flag) => {
                setOpen(flag);
                if (flag) {
                    fetchUnreadNotifications();
                }
            }}
            dropdownRender={() => notificationList}
            trigger={['click']}
            placement="bottomRight"
        >
            <Badge
                count={unreadCount}
                size="small"
                offset={[-4, 4]}
                style={{
                    backgroundColor: '#2563eb',
                    boxShadow: '0 2px 8px rgba(37,99,235,0.3)',
                }}
            >
                <Button
                    type="text"
                    icon={<BellOutlined style={{ fontSize: 18 }} />}
                    style={{ width: 40, height: 40 }}
                />
            </Badge>
        </Dropdown>
    );
}