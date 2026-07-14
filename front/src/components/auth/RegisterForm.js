// /home/god/Videos/medikal/front/src/components/auth/RegisterForm.jsx
'use client';

import { useState } from 'react';
import { Form, Input, Button, Checkbox, message, App } from 'antd';
import { UserOutlined, MailOutlined, PhoneOutlined, LockOutlined } from '@ant-design/icons';
import { useRouter, usePathname } from 'next/navigation';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

export default function RegisterForm() {
  const router = useRouter();
  const pathname = usePathname();
  const { message: appMessage } = App.useApp();
  const [loading, setLoading] = useState(false);
  const [form] = Form.useForm();

  // ✅ تشخیص زبان از مسیر
  const getLocale = () => {
    const segments = pathname?.split('/') || [];
    const locale = segments[1] || 'fa';
    return ['fa', 'en', 'ar'].includes(locale) ? locale : 'fa';
  };

  const locale = getLocale();

  const handleRegister = async (values) => {
    setLoading(true);

    try {
      const res = await fetch(`${API_URL}/api/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: values.fullName,
          email: values.email,
          mobile: values.phone,
          password: values.password,
        }),
      });

      const data = await res.json();

      if (data.success) {
        // ✅ ذخیره توکن اگر در پاسخ باشد
        if (data.data?.token) {
          localStorage.setItem('token', data.data.token);
        }
        if (data.data?.user) {
          localStorage.setItem('user', JSON.stringify(data.data.user));
        }

        appMessage.success(locale === 'fa' ? '✅ ثبت‌نام با موفقیت انجام شد' :
            locale === 'en' ? '✅ Registration successful' :
                '✅ تم التسجيل بنجاح');

        // ✅ هدایت به صفحه تأیید یا صفحه اصلی
        if (data.data?.requires_verification) {
          router.push(`/${locale}/verify`);
        } else {
          router.push(`/${locale}`);
        }
      } else {
        appMessage.error(data.message || (locale === 'fa' ? '❌ خطا در ثبت‌نام' :
            locale === 'en' ? '❌ Registration failed' :
                '❌ فشل التسجيل'));
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

  return (
      <Form
          form={form}
          onFinish={handleRegister}
          layout="vertical"
          size="large"
      >
        <Form.Item
            name="fullName"
            label={locale === 'fa' ? 'نام و نام خانوادگی' :
                locale === 'en' ? 'Full Name' :
                    'الاسم الكامل'}
            rules={[{ required: true, message: locale === 'fa' ? 'نام و نام خانوادگی را وارد کنید' :
                  locale === 'en' ? 'Please enter full name' :
                      'الرجاء إدخال الاسم الكامل' }]}
        >
          <Input prefix={<UserOutlined />} placeholder={locale === 'fa' ? 'محمد رضایی' : 'Mohamad Rezaei'} />
        </Form.Item>

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
          <Input prefix={<MailOutlined />} placeholder="example@email.com" dir="ltr" />
        </Form.Item>

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
          <Input prefix={<PhoneOutlined />} placeholder="09123456789" dir="ltr" />
        </Form.Item>

        <Form.Item
            name="password"
            label={locale === 'fa' ? 'رمز عبور' :
                locale === 'en' ? 'Password' :
                    'كلمة المرور'}
            rules={[
              { required: true, message: locale === 'fa' ? 'رمز عبور را وارد کنید' :
                    locale === 'en' ? 'Please enter password' :
                        'الرجاء إدخال كلمة المرور' },
              { min: 6, message: locale === 'fa' ? 'رمز عبور باید حداقل ۶ کاراکتر باشد' :
                    locale === 'en' ? 'Password must be at least 6 characters' :
                        'يجب أن تتكون كلمة المرور من 6 أحرف على الأقل' },
            ]}
        >
          <Input.Password prefix={<LockOutlined />} placeholder={locale === 'fa' ? 'حداقل ۶ کاراکتر' : 'Min 6 characters'} dir="ltr" />
        </Form.Item>

        <Form.Item
            name="confirmPassword"
            label={locale === 'fa' ? 'تکرار رمز عبور' :
                locale === 'en' ? 'Confirm Password' :
                    'تأكيد كلمة المرور'}
            dependencies={['password']}
            rules={[
              { required: true, message: locale === 'fa' ? 'تکرار رمز عبور را وارد کنید' :
                    locale === 'en' ? 'Please confirm password' :
                        'الرجاء تأكيد كلمة المرور' },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || getFieldValue('password') === value) {
                    return Promise.resolve();
                  }
                  return Promise.reject(new Error(locale === 'fa' ? 'رمز عبور با تکرار آن مطابقت ندارد' :
                      locale === 'en' ? 'Passwords do not match' :
                          'كلمات المرور غير متطابقة'));
                },
              }),
            ]}
        >
          <Input.Password prefix={<LockOutlined />} placeholder={locale === 'fa' ? 'تکرار رمز عبور' : 'Confirm password'} dir="ltr" />
        </Form.Item>

        <Form.Item
            name="terms"
            valuePropName="checked"
            rules={[
              { validator: (_, value) => value ? Promise.resolve() : Promise.reject(new Error(locale === 'fa' ? 'قوانین را بپذیرید' :
                    locale === 'en' ? 'Please accept terms' :
                        'الرجاء قبول الشروط')) },
            ]}
        >
          <Checkbox>
            {locale === 'fa' ? 'قوانین و مقررات را می‌پذیرم' :
                locale === 'en' ? 'I accept the terms and conditions' :
                    'أوافق على الشروط والأحكام'}
          </Checkbox>
        </Form.Item>

        <Form.Item>
          <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              size="large"
          >
            {locale === 'fa' ? 'ثبت‌نام' :
                locale === 'en' ? 'Register' :
                    'تسجيل'}
          </Button>
        </Form.Item>
      </Form>
  );
}