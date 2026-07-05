'use client';

import { useState, useEffect } from 'react';
import { Badge, Popover, List, Button, Empty, Spin, message } from 'antd';
import { BellOutlined, CheckOutlined, DeleteOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import dayjs from 'dayjs';

export default function NotificationBell() {
  const router = useRouter();
  const { locale } = useLanguage();
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchNotifications = async () => {
    const token = getToken();
    if (!token) return;

    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/notifications`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setNotifications(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching notifications:', error);
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = async (id) => {
    const token = getToken();
    try {
      await fetch(`${API_URL}/api/notifications/${id}/read`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      setNotifications(notifications.map(n => 
        n.id === id ? { ...n, is_read: true } : n
      ));
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const markAllAsRead = async () => {
    const token = getToken();
    try {
      await fetch(`${API_URL}/api/notifications/read-all`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      setNotifications(notifications.map(n => ({ ...n, is_read: true })));
      message.success('همه اعلان‌ها خوانده شد');
    } catch (error) {
      console.error('Error marking all as read:', error);
    }
  };

  const deleteNotification = async (id) => {
    const token = getToken();
    try {
      await fetch(`${API_URL}/api/notifications/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      setNotifications(notifications.filter(n => n.id !== id));
    } catch (error) {
      console.error('Error deleting notification:', error);
    }
  };

  useEffect(() => {
    if (open) {
      fetchNotifications();
    }
  }, [open]);

  const unreadCount = notifications.filter(n => !n.is_read).length;

  const content = (
    <div style={{ width: '360px' }}>
      <div style={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center',
        padding: '12px 16px',
        borderBottom: '1px solid #e2e8f0'
      }}>
        <span style={{ fontWeight: 'bold' }}>اعلان‌ها</span>
        {unreadCount > 0 && (
          <Button type="link" size="small" onClick={markAllAsRead}>
            <CheckOutlined /> همه را خوانده شد
          </Button>
        )}
      </div>
      
      {loading ? (
        <div style={{ padding: '20px', textAlign: 'center' }}>
          <Spin />
        </div>
      ) : notifications.length > 0 ? (
        <List
          style={{ maxHeight: '400px', overflowY: 'auto' }}
          dataSource={notifications}
          renderItem={(item) => (
            <List.Item
              style={{
                background: item.is_read ? 'transparent' : '#f0f5ff',
                cursor: 'pointer',
                padding: '12px 16px',
              }}
              onClick={() => {
                if (!item.is_read) markAsRead(item.id);
                if (item.link) {
                  router.push(`/${locale}${item.link}`);
                  setOpen(false);
                }
              }}
              actions={[
                <Button
                  key="delete"
                  type="text"
                  size="small"
                  icon={<DeleteOutlined />}
                  onClick={(e) => {
                    e.stopPropagation();
                    deleteNotification(item.id);
                  }}
                />
              ]}
            >
              <List.Item.Meta
                title={item.title}
                description={
                  <div>
                    <div style={{ fontSize: '12px', color: '#94a3b8' }}>
                      {item.message}
                    </div>
                    <div style={{ fontSize: '10px', color: '#cbd5e1' }}>
                      {dayjs(item.created_at).fromNow()}
                    </div>
                  </div>
                }
              />
            </List.Item>
          )}
        />
      ) : (
        <Empty description="هیچ اعلانی وجود ندارد" />
      )}
    </div>
  );

  return (
    <Popover
      content={content}
      trigger="click"
      open={open}
      onOpenChange={setOpen}
      placement="bottomRight"
    >
      <Badge count={unreadCount} size="small">
        <Button type="text" icon={<BellOutlined />} />
      </Badge>
    </Popover>
  );
}
