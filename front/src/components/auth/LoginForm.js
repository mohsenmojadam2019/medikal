// /home/god/Videos/medikal/front/src/components/auth/LoginForm.jsx
'use client';

import { useState } from 'react';
import { Form, Input, Button, Tabs, message, App } from 'antd';
import { PhoneOutlined, MailOutlined, LockOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import { useRouter, usePathname } from 'next/navigation';
import OTPInput from './OTPInput';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

export default function LoginForm() {
  const router = useRouter();
  const pathname = usePathname();
  const { message: appMessage } = App.useApp();
  const [loading, setLoading] = useState(false);
  const [phoneForm] = Form.useForm();
  const [emailForm] = Form.useForm();
  const [showOTP, setShowOTP] = useState(false);
  const [phoneNumber, setPhoneNumber] = useState('');
  const [countdown, setCountdown] = useState(0);
  const [activeTab, setActiveTab] = useState('phone');

  // ✅ تشخیص زبان از مسیر
  const getLocale = () => {
    const segments = pathname?.split('/') || [];
    const locale = segments[1] || 'fa';
    return ['fa', 'en', 'ar'].includes(locale) ? locale : 'fa';
  };

  const locale = getLocale();

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
        appMessage.success(locale === 'fa' ? '✅ کد تایید به شماره شما ارسال شد' :
            locale === 'en' ? '✅ Verification code sent to your number' :
                '✅ تم إرسال رمز التحقق إلى رقمك');
        setShowOTP(true);
        setCountdown(60);
        startCountdown();
      } else {
        appMessage.error(data.message || (locale === 'fa' ? '❌ خطا در ارسال کد' :
            locale === 'en' ? '❌ Error sending code' :
                '❌ خطأ في إرسال الرمز'));
      }
    } catch (error) {
      console.error('Error:', error);
      appMessage.error(locale === 'fa' ? '❌ خطا در ارتباط با سرور' :
          locale === 'en' ? '❌ Server connection error' :
              '❌ خطأ في الاتصال بالخادم');
    } finally {
      setLoading(false);
    }
  };

  // ===== تایمر برای ارسال مجدد =====
  const startCountdown = () => {
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
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
        if (data.data.user) {
          localStorage.setItem('user', JSON.stringify(data.data.user));
        }

        appMessage.success(locale === 'fa' ? '✅ ورود با موفقیت انجام شد' :
            locale === 'en' ? '✅ Login successful' :
                '✅ تم تسجيل الدخول بنجاح');

        const redirect = new URLSearchParams(window.location.search).get('redirect') || `/${locale}`;
        router.push(redirect);
      } else {
        appMessage.error(data.message || (locale === 'fa' ? '❌ کد تایید نامعتبر است' :
            locale === 'en' ? '❌ Invalid verification code' :
                '❌ رمز التحقق غير صالح'));
      }
    } catch (error) {
      console.error('Error:', error);
      appMessage.error(locale === 'fa' ? '❌ خطا در تایید کد' :
          locale === 'en' ? '❌ Error verifying code' :
              '❌ خطأ في التحقق من الرمز');
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
        if (data.data.user) {
          localStorage.setItem('user', JSON.stringify(data.data.user));
        }

        appMessage.success(locale === 'fa' ? '✅ ورود با موفقیت انجام شد' :
            locale === 'en' ? '✅ Login successful' :
                '✅ تم تسجيل الدخول بنجاح');

        const redirect = new URLSearchParams(window.location.search).get('redirect') || `/${locale}`;
        router.push(redirect);
      } else {
        appMessage.error(data.message || (locale === 'fa' ? '❌ ایمیل یا رمز عبور اشتباه است' :
            locale === 'en' ? '❌ Invalid email or password' :
                '❌ البريد الإلكتروني أو كلمة المرور غير صحيحة'));
      }
    } catch (error) {
      console.error('Error:', error);
      appMessage.error(locale === 'fa' ? '❌ خطا در ارتباط با سرور' :
          locale === 'en' ? '❌ Server connection error' :
              '❌ خطأ في الاتصال بالخادم');
    } finally {
      setLoading(false);
    }
  };

  // ===== محتوای تب ورود با موبایل =====
  const renderPhoneTab = () => (
      <Form
          form={phoneForm}
          onFinish={handleSendCode}
          layout="vertical"
      >
        <Form.Item
            name="phone"
            label={locale === 'fa' ? 'شماره موبایل' :
                locale === 'en' ? 'Phone Number' :
                    'رقم الهاتف'}
            rules={[
              { required: true, message: locale === 'fa' ? 'شماره موبایل را وارد کنید' :
                    locale === 'en' ? 'Please enter phone number' :
                        'الرجاء إدخال رقم الهاتف' },
              { pattern: /^09[0-9]{9}$/, message: locale === 'fa' ? 'شماره موبایل نامعتبر است' :
                    locale === 'en' ? 'Invalid phone number' :
                        'رقم الهاتف غير صالح' },
            ]}
        >
          <Input
              prefix={<PhoneOutlined />}
              placeholder="09123456789"
              size="large"
              disabled={loading}
              dir="ltr"
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
            {locale === 'fa' ? 'ارسال کد تایید' :
                locale === 'en' ? 'Send Verification Code' :
                    'إرسال رمز التحقق'}
          </Button>
        </Form.Item>
      </Form>
  );

  // ===== محتوای تب ورود با ایمیل =====
  const renderEmailTab = () => (
      <Form
          form={emailForm}
          onFinish={handleEmailLogin}
          layout="vertical"
      >
        <Form.Item
            name="email"
            label={locale === 'fa' ? 'ایمیل' :
                locale === 'en' ? 'Email' :
                    'البريد الإلكتروني'}
            rules={[
              { required: true, message: locale === 'fa' ? 'ایمیل را وارد کنید' :
                    locale === 'en' ? 'Please enter email' :
                        'الرجاء إدخال البريد الإلكتروني' },
              { type: 'email', message: locale === 'fa' ? 'ایمیل نامعتبر است' :
                    locale === 'en' ? 'Invalid email' :
                        'البريد الإلكتروني غير صالح' },
            ]}
        >
          <Input prefix={<MailOutlined />} placeholder="example@email.com" size="large" dir="ltr" />
        </Form.Item>

        <Form.Item
            name="password"
            label={locale === 'fa' ? 'رمز عبور' :
                locale === 'en' ? 'Password' :
                    'كلمة المرور'}
            rules={[{ required: true, message: locale === 'fa' ? 'رمز عبور را وارد کنید' :
                  locale === 'en' ? 'Please enter password' :
                      'الرجاء إدخال كلمة المرور' }]}
        >
          <Input.Password prefix={<LockOutlined />} size="large" dir="ltr" />
        </Form.Item>

        <Form.Item>
          <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              size="large"
          >
            {locale === 'fa' ? 'ورود' :
                locale === 'en' ? 'Login' :
                    'تسجيل الدخول'}
          </Button>
        </Form.Item>
      </Form>
  );

  // ===== آیتم‌های تب =====
  const tabItems = [
    {
      key: 'phone',
      label: locale === 'fa' ? '📱 ورود با موبایل' :
          locale === 'en' ? '📱 Phone Login' :
              '📱 تسجيل الدخول بالهاتف',
      children: renderPhoneTab(),
    },
    {
      key: 'email',
      label: locale === 'fa' ? '✉️ ورود با ایمیل' :
          locale === 'en' ? '✉️ Email Login' :
              '✉️ تسجيل الدخول بالبريد',
      children: renderEmailTab(),
    },
  ];

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
            {locale === 'fa' ? 'بازگشت' : locale === 'en' ? 'Back' : 'رجوع'}
          </Button>

          <h3 style={{ marginBottom: '8px' }}>
            {locale === 'fa' ? 'کد تایید را وارد کنید' :
                locale === 'en' ? 'Enter verification code' :
                    'أدخل رمز التحقق'}
          </h3>
          <p style={{ color: 'var(--gray-500)', marginBottom: '24px' }}>
            {locale === 'fa' ? `کد ۴ رقمی برای شماره ${phoneNumber} ارسال شد` :
                locale === 'en' ? `A 4-digit code was sent to ${phoneNumber}` :
                    `تم إرسال رمز مكون من 4 أرقام إلى ${phoneNumber}`}
          </p>

          <OTPInput length={4} onComplete={handleVerifyOTP} />

          <div style={{ marginTop: '16px' }}>
            <Button
                type="link"
                onClick={() => {
                  setShowOTP(false);
                  setTimeout(() => handleSendCode({ phone: phoneNumber }), 500);
                }}
                disabled={countdown > 0}
            >
              {locale === 'fa' ? `ارسال مجدد کد ${countdown > 0 ? `(${countdown}s)` : ''}` :
                  locale === 'en' ? `Resend code ${countdown > 0 ? `(${countdown}s)` : ''}` :
                      `إعادة إرسال الرمز ${countdown > 0 ? `(${countdown}s)` : ''}`}
            </Button>
          </div>
        </div>
    );
  }

  // ===== صفحه اصلی ورود با Tabs جدید =====
  return (
      <Tabs
          activeKey={activeTab}
          onChange={setActiveTab}
          items={tabItems}
          className="login-tabs"
      />
  );
}