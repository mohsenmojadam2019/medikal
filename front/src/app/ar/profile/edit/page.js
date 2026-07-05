'use client';

import { useState, useEffect } from 'react';
import { Card, Form, Input, Button, message, Skeleton, Space } from 'antd';
import { UserOutlined, PhoneOutlined, MailOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

export default function EditProfilePage() {
  const router = useRouter();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [user, setUser] = useState(null);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const userData = localStorage.getItem('user');
    if (userData) {
      try {
        const parsed = JSON.parse(userData);
        setUser(parsed);
        form.setFieldsValue({
          name: parsed.name || parsed.full_name || '',
          email: parsed.email || '',
          mobile: parsed.mobile || '',
        });
      } catch {
        setUser(null);
      }
    }
    setLoading(false);
  }, [router, form]);

  const handleSubmit = async (values) => {
    setSubmitting(true);
    const token = localStorage.getItem('token');

    try {
      const res = await fetch(`${API_URL}/api/profile`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(values),
      });

      const data = await res.json();

      if (data.success) {
        const updatedUser = { ...user, ...values };
        localStorage.setItem('user', JSON.stringify(updatedUser));
        message.success('✅ اطلاعات با موفقیت به‌روزرسانی شد');
        router.push('/profile');
      } else {
        message.error(data.message || '❌ خطا در به‌روزرسانی');
      }
    } catch (error) {
      message.error('❌ خطا در ارتباط با سرور');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div style={{ maxWidth: '600px', margin: '40px auto', padding: '0 20px' }}>
        <Skeleton active paragraph={{ rows: 6 }} />
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '600px', margin: '40px auto', padding: '0 20px' }}>
      <Card
        title={
          <Space>
            <Link href="/profile">
              <Button type="text" icon={<ArrowLeftOutlined />} />
            </Link>
            <span>ویرایش اطلاعات</span>
          </Space>
        }
        style={{ borderRadius: '16px', boxShadow: '0 4px 24px rgba(0,0,0,0.06)' }}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          size="large"
        >
          <Form.Item
            name="name"
            label="نام و نام خانوادگی"
            rules={[{ required: true, message: 'نام را وارد کنید' }]}
          >
            <Input prefix={<UserOutlined />} placeholder="نام و نام خانوادگی" />
          </Form.Item>

          <Form.Item
            name="email"
            label="ایمیل"
            rules={[
              { required: true, message: 'ایمیل را وارد کنید' },
              { type: 'email', message: 'ایمیل نامعتبر است' },
            ]}
          >
            <Input prefix={<MailOutlined />} placeholder="example@email.com" />
          </Form.Item>

          <Form.Item
            name="mobile"
            label="شماره موبایل"
            rules={[
              { required: true, message: 'شماره موبایل را وارد کنید' },
              { pattern: /^09[0-9]{9}$/, message: 'شماره موبایل نامعتبر است' },
            ]}
          >
            <Input prefix={<PhoneOutlined />} placeholder="09123456789" disabled />
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={submitting}
              block
              size="large"
            >
              ذخیره تغییرات
            </Button>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
}
