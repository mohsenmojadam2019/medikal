'use client';

import { useState, useEffect } from 'react';
import { Card, Upload, Button, message, Space, Avatar, Typography, Spin } from 'antd';
import { UserOutlined, ArrowLeftOutlined, UploadOutlined, CameraOutlined, DeleteOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

const { Title, Text } = Typography;

export default function UploadAvatarPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [imageUrl, setImageUrl] = useState(null);
  const [user, setUser] = useState(null);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

    // دریافت اطلاعات کاربر
    const userData = localStorage.getItem('user');
    if (userData) {
      try {
        const parsed = JSON.parse(userData);
        setUser(parsed);
        if (parsed.avatar_url) {
          setImageUrl(parsed.avatar_url);
        }
      } catch {
        setUser(null);
      }
    }
  }, [router]);

  const handleUpload = async (file) => {
    setLoading(true);
    const token = getToken();

    const formData = new FormData();
    formData.append('avatar', file);

    try {
      const res = await fetch(`${API_URL}/api/profile/avatar`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      });

      const data = await res.json();

      if (data.success) {
        setImageUrl(data.data.avatar_url);
        
        // به‌روزرسانی localStorage
        const userData = localStorage.getItem('user');
        if (userData) {
          const parsed = JSON.parse(userData);
          parsed.avatar_url = data.data.avatar_url;
          localStorage.setItem('user', JSON.stringify(parsed));
        }
        
        message.success('✅ عکس با موفقیت آپلود شد');
        setTimeout(() => router.push('/profile'), 1500);
      } else {
        message.error(data.message || '❌ خطا در آپلود عکس');
      }
    } catch (error) {
      console.error('Upload error:', error);
      message.error('❌ خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }

    return false;
  };

  const handleDelete = async () => {
    setDeleting(true);
    const token = getToken();

    try {
      const res = await fetch(`${API_URL}/api/profile/avatar`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();

      if (data.success) {
        setImageUrl(null);
        
        // به‌روزرسانی localStorage
        const userData = localStorage.getItem('user');
        if (userData) {
          const parsed = JSON.parse(userData);
          parsed.avatar_url = null;
          localStorage.setItem('user', JSON.stringify(parsed));
        }
        
        message.success('✅ عکس با موفقیت حذف شد');
      } else {
        message.error(data.message || '❌ خطا در حذف عکس');
      }
    } catch (error) {
      console.error('Delete error:', error);
      message.error('❌ خطا در ارتباط با سرور');
    } finally {
      setDeleting(false);
    }
  };

  if (!user) {
    return (
      <div style={{ maxWidth: '500px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Spin size="large" />
        <p style={{ marginTop: '16px' }}>در حال بارگذاری...</p>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '500px', margin: '40px auto', padding: '0 20px' }}>
      <Card
        title={
          <Space>
            <Link href="/profile">
              <Button type="text" icon={<ArrowLeftOutlined />} />
            </Link>
            <span>آپلود عکس پروفایل</span>
          </Space>
        }
        style={{ borderRadius: '16px', textAlign: 'center' }}
      >
        <div style={{ marginBottom: '24px' }}>
          <Avatar
            size={120}
            src={imageUrl}
            icon={<UserOutlined style={{ fontSize: '48px' }} />}
            style={{ 
              background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
              boxShadow: '0 4px 16px rgba(37,99,235,0.3)'
            }}
          />
          <Title level={4} style={{ marginTop: '16px' }}>
            {imageUrl ? 'عکس پروفایل' : 'بدون عکس'}
          </Title>
          <Text type="secondary">حداکثر حجم ۲ مگابایت - فرمت‌های مجاز: JPG, PNG, GIF</Text>
        </div>

        <Space direction="vertical" style={{ width: '100%' }} size="middle">
          <Upload
            accept="image/*"
            beforeUpload={handleUpload}
            showUploadList={false}
            maxCount={1}
          >
            <Button type="primary" icon={<UploadOutlined />} loading={loading} block size="large">
              {imageUrl ? 'تغییر عکس' : 'انتخاب و آپلود عکس'}
            </Button>
          </Upload>

          {imageUrl && (
            <Button 
              danger 
              icon={<DeleteOutlined />} 
              loading={deleting} 
              onClick={handleDelete}
              block 
              size="large"
            >
              حذف عکس
            </Button>
          )}
        </Space>
      </Card>
    </div>
  );
}
