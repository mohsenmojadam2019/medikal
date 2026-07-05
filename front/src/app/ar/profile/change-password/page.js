'use client';

import { useState } from 'react';
import { Card, Form, Input, Button, message, Space } from 'antd';
import { LockOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

export default function ChangePasswordPage() {
  const router = useRouter();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const handleSubmit = async (values) => {
    setLoading(true);
    const token = localStorage.getItem('token');

    try {
      const res = await fetch(`${API_URL}/api/profile/change-password`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          current_password: values.current_password,
          new_password: values.new_password,
          new_password_confirmation: values.confirm_password,
        }),
      });

      const data = await res.json();

      if (data.success) {
        message.success('✅ رمز عبور با موفقیت تغییر کرد');
        form.resetFields();
        router.push('/profile');
      } else {
        message.error(data.message || '❌ خطا در تغییر رمز عبور');
      }
    } catch (error) {
      message.error('❌ خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: '500px', margin: '40px auto', padding: '0 20px' }}>
      <Card
        title={
          <Space>
            <Link href="/profile">
              <Button type="text" icon={<ArrowLeftOutlined />} />
            </Link>
            <span>تغییر رمز عبور</span>
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
            name="current_password"
            label="رمز عبور فعلی"
            rules={[{ required: true, message: 'رمز عبور فعلی را وارد کنید' }]}
          >
            <Input.Password prefix={<LockOutlined />} placeholder="رمز عبور فعلی" />
          </Form.Item>

          <Form.Item
            name="new_password"
            label="رمز عبور جدید"
            rules={[
              { required: true, message: 'رمز عبور جدید را وارد کنید' },
              { min: 6, message: 'رمز عبور باید حداقل ۶ کاراکتر باشد' },
            ]}
          >
            <Input.Password prefix={<LockOutlined />} placeholder="حداقل ۶ کاراکتر" />
          </Form.Item>

          <Form.Item
            name="confirm_password"
            label="تکرار رمز عبور جدید"
            dependencies={['new_password']}
            rules={[
              { required: true, message: 'تکرار رمز عبور را وارد کنید' },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || getFieldValue('new_password') === value) {
                    return Promise.resolve();
                  }
                  return Promise.reject(new Error('رمز عبور با تکرار آن مطابقت ندارد'));
                },
              }),
            ]}
          >
            <Input.Password prefix={<LockOutlined />} placeholder="تکرار رمز عبور جدید" />
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              size="large"
            >
              تغییر رمز عبور
            </Button>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
}
