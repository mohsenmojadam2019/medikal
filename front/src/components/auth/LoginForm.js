'use client';

import { useState } from 'react';
import { Form, Input, Button, Tabs, message } from 'antd';
import { PhoneOutlined, MailOutlined, LockOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import OTPInput from './OTPInput';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

export default function LoginForm() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [phoneForm] = Form.useForm();
  const [emailForm] = Form.useForm();
  const [showOTP, setShowOTP] = useState(false);
  const [phoneNumber, setPhoneNumber] = useState('');

  // ===== ارسال OTP =====
  const handleSendCode = async (values) => {
    setLoading(true);
    setPhoneNumber(values.phone);
    
    try {
      const res = await fetch(`${API_URL}/api/auth/login/mobile`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mobile: values.phone }),
      });
      
      const data = await res.json();
      
      if (data.success) {
        message.success('✅ کد تایید به شماره شما ارسال شد');
        setShowOTP(true);
      } else {
        message.error(data.message || '❌ خطا در ارسال کد');
      }
    } catch (error) {
      console.error('Error:', error);
      message.error('❌ خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  // ===== تایید OTP =====
  const handleVerifyOTP = async (code) => {
    setLoading(true);
    
    try {
      const res = await fetch(`${API_URL}/api/auth/login/mobile/verify`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mobile: phoneNumber, code: code }),
      });
      
      const data = await res.json();
      
      if (data.success) {
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
        
        message.success('✅ ورود با موفقیت انجام شد');
        router.push('/');
      } else {
        message.error(data.message || '❌ کد تایید نامعتبر است');
      }
    } catch (error) {
      console.error('Error:', error);
      message.error('❌ خطا در تایید کد');
    } finally {
      setLoading(false);
    }
  };

  // ===== ورود با ایمیل =====
  const handleEmailLogin = async (values) => {
    setLoading(true);
    
    try {
      const res = await fetch(`${API_URL}/api/auth/login/email`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: values.email, password: values.password }),
      });
      
      const data = await res.json();
      
      if (data.success) {
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
        
        message.success('✅ ورود با موفقیت انجام شد');
        router.push('/');
      } else {
        message.error(data.message || '❌ ایمیل یا رمز عبور اشتباه است');
      }
    } catch (error) {
      console.error('Error:', error);
      message.error('❌ خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  // ===== صفحه OTP =====
  if (showOTP) {
    return (
      <div style={{ textAlign: 'center' }}>
        <Button 
          type="link" 
          onClick={() => setShowOTP(false)}
          icon={<ArrowLeftOutlined />}
          style={{ marginBottom: '24px' }}
        >
          بازگشت
        </Button>
        
        <h3 style={{ marginBottom: '8px' }}>کد تایید را وارد کنید</h3>
        <p style={{ color: 'var(--gray-500)', marginBottom: '24px' }}>
          کد ۴ رقمی برای شماره {phoneNumber} ارسال شد
        </p>
        
        <OTPInput length={4} onComplete={handleVerifyOTP} />
        
        <Button 
          type="link" 
          onClick={() => {
            setShowOTP(false);
            setTimeout(() => handleSendCode({ phone: phoneNumber }), 500);
          }}
          style={{ marginTop: '16px' }}
        >
          ارسال مجدد کد
        </Button>
      </div>
    );
  }

  // ===== صفحه اصلی ورود =====
  return (
    <Tabs defaultActiveKey="phone" className="login-tabs">
      <Tabs.TabPane tab="📱 ورود با موبایل" key="phone">
        <Form
          form={phoneForm}
          onFinish={handleSendCode}
          layout="vertical"
        >
          <Form.Item
            name="phone"
            label="شماره موبایل"
            rules={[
              { required: true, message: 'شماره موبایل را وارد کنید' },
              { pattern: /^09[0-9]{9}$/, message: 'شماره موبایل نامعتبر است' },
            ]}
          >
            <Input
              prefix={<PhoneOutlined />}
              placeholder="09123456789"
              size="large"
              disabled={loading}
            />
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              size="large"
            >
              ارسال کد تایید
            </Button>
          </Form.Item>
        </Form>
      </Tabs.TabPane>

      <Tabs.TabPane tab="✉️ ورود با ایمیل" key="email">
        <Form
          form={emailForm}
          onFinish={handleEmailLogin}
          layout="vertical"
        >
          <Form.Item
            name="email"
            label="ایمیل"
            rules={[
              { required: true, message: 'ایمیل را وارد کنید' },
              { type: 'email', message: 'ایمیل نامعتبر است' },
            ]}
          >
            <Input prefix={<MailOutlined />} placeholder="example@email.com" size="large" />
          </Form.Item>

          <Form.Item
            name="password"
            label="رمز عبور"
            rules={[{ required: true, message: 'رمز عبور را وارد کنید' }]}
          >
            <Input.Password prefix={<LockOutlined />} size="large" />
          </Form.Item>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              size="large"
            >
              ورود
            </Button>
          </Form.Item>
        </Form>
      </Tabs.TabPane>
    </Tabs>
  );
}
