// src/components/notifications/NotificationBell.js
'use client';

import { useState, useEffect } from 'react';
import { Badge, Button, Dropdown, List, Typography, Spin, Empty, Space } from 'antd';
import { BellOutlined, CheckCircleOutlined, DeleteOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';

const { Text } = Typography;

export default function NotificationBell() {
  const router = useRouter();
  const { locale } = useLanguage();
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [unreadCount, setUnreadCount] = useState(0);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    setLoading(true);
    try {
      const token = getToken();
      if (!token) {
        setNotifications([]);
        setUnreadCount(0);
        return;
      }

      const res = await fetch(`${API_URL}/api/notifications`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      const data = await res.json();

      // ✅ بررسی اینکه داده آرایه هست
      let notificationList = [];
      if (data.success && Array.isArray(data.data)) {
        notificationList = data.data;
      } else if (data.success && Array.isArray(data.data?.data)) {
        notificationList = data.data.data;
      } else if (Array.isArray(data)) {
        notificationList = data;
      } else {
        notificationList = [];
      }

      setNotifications(notificationList);

      // ✅ محاسبه تعداد خوانده نشده‌ها
      const unread = notificationList.filter(n => !n.is_read).length;
      setUnreadCount(unread);

    } catch (error) {
      console.error('Error fetching notifications:', error);
      setNotifications([]);
      setUnreadCount(0);
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = async (id) => {
    try {
      const token = getToken();
      if (!token) return;

      const res = await fetch(`${API_URL}/api/notifications/${id}/read`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (res.ok) {
        // ✅ بروزرسانی لیست
        setNotifications(prev =>
            prev.map(n =>
                n.id === id ? { ...n, is_read: true } : n
            )
        );
        setUnreadCount(prev => Math.max(0, prev - 1));
      }
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const markAllAsRead = async () => {
    try {
      const token = getToken();
      if (!token) return;

      const res = await fetch(`${API_URL}/api/notifications/read-all`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (res.ok) {
        setNotifications(prev =>
            prev.map(n => ({ ...n, is_read: true }))
        );
        setUnreadCount(0);
      }
    } catch (error) {
      console.error('Error marking all as read:', error);
    }
  };

  const deleteNotification = async (id) => {
    try {
      const token = getToken();
      if (!token) return;

      const res = await fetch(`${API_URL}/api/notifications/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (res.ok) {
        setNotifications(prev => prev.filter(n => n.id !== id));
        // بروزرسانی تعداد خوانده نشده
        const unread = notifications.filter(n => n.id !== id && !n.is_read).length;
        setUnreadCount(unread);
      }
    } catch (error) {
      console.error('Error deleting notification:', error);
    }
  };

  const getNotificationContent = (notification) => {
    // ✅ بررسی وجود data
    const data = notification.data || {};
    return (
        <div style={{ padding: '4px 0' }}>
          <Text strong>{notification.title || 'اطلاعیه'}</Text>
          <br />
          <Text type="secondary" style={{ fontSize: '12px' }}>
            {notification.message || notification.content || 'بدون پیام'}
          </Text>
          <br />
          <Text type="secondary" style={{ fontSize: '11px' }}>
            {notification.created_at ? new Date(notification.created_at).toLocaleDateString('fa-IR') : ''}
          </Text>
        </div>
    );
  };

  const notificationMenu = {
    items: [
      {
        key: 'header',
        label: (
            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              padding: '8px 0',
              borderBottom: '1px solid #f0f0f0'
            }}>
              <Text strong>اعلان‌ها</Text>
              {unreadCount > 0 && (
                  <Button
                      type="link"
                      size="small"
                      onClick={markAllAsRead}
                  >
                    همه را خوانده شده
                  </Button>
              )}
            </div>
        ),
        disabled: true,
      },
      ...(loading ? [{
        key: 'loading',
        label: (
            <div style={{ textAlign: 'center', padding: '20px' }}>
              <Spin size="small" />
            </div>
        ),
        disabled: true,
      }] : []),
      ...(!loading && notifications.length === 0 ? [{
        key: 'empty',
        label: (
            <Empty
                description="هیچ اعلانی وجود ندارد"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
                style={{ padding: '20px 0' }}
            />
        ),
        disabled: true,
      }] : []),
      ...(!loading && notifications.length > 0 ? notifications.map((notification, index) => ({
        key: notification.id || `notif-${index}`,
        label: (
            <div
                style={{
                  padding: '8px 12px',
                  background: notification.is_read ? 'transparent' : '#f0f7ff',
                  borderRadius: '4px',
                  margin: '2px 0',
                  cursor: 'pointer',
                  transition: 'all 0.2s',
                  borderRight: notification.is_read ? 'none' : '3px solid #1890ff'
                }}
                onClick={() => {
                  if (!notification.is_read) {
                    markAsRead(notification.id);
                  }
                  if (notification.data?.order_id || notification.data?.appointment_id) {
                    const path = notification.data?.order_id
                        ? `/${locale}/profile/pharmacy-orders`
                        : `/${locale}/appointments`;
                    router.push(path);
                  }
                }}
            >
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <div style={{ flex: 1 }}>
                  {getNotificationContent(notification)}
                </div>
                <Button
                    type="text"
                    size="small"
                    icon={<DeleteOutlined />}
                    onClick={(e) => {
                      e.stopPropagation();
                      deleteNotification(notification.id);
                    }}
                />
              </div>
            </div>
        ),
      })) : []),
      ...(!loading && notifications.length > 0 ? [{
        key: 'footer',
        label: (
            <div style={{
              textAlign: 'center',
              padding: '8px 0',
              borderTop: '1px solid #f0f0f0'
            }}>
              <Button
                  type="link"
                  size="small"
                  onClick={() => router.push(`/${locale}/notifications`)}
              >
                مشاهده همه اعلان‌ها
              </Button>
            </div>
        ),
        disabled: true,
      }] : []),
    ],
  };

  return (
      <Dropdown
          menu={notificationMenu}
          placement="bottomRight"
          trigger={['click']}
          overlayStyle={{ width: 400, maxHeight: 500 }}
      >
        <Badge count={unreadCount} size="small">
          <Button type="text" icon={<BellOutlined />} />
        </Badge>
      </Dropdown>
  );
}