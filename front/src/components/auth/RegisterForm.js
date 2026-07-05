'use client';

import { useState } from 'react';
import { Form, Input, Button, Checkbox, message } from 'antd';
import { UserOutlined, MailOutlined, PhoneOutlined, LockOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';

export default function RegisterForm() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [form] = Form.useForm();

  const handleRegister = async (values) => {
    setLoading(true);
    
    try {
      await new Promise(resolve => setTimeout(resolve, 1500));
      message.success('✅ ثبت‌نام با موفقیت انجام شد');
      message.info('📱 کد تایید به شماره شما ارسال شد');
      router.push('/verify');
    } catch (error) {
      message.error('❌ خطا در ثبت‌نام');
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
        label="نام و نام خانوادگی"
        rules={[{ required: true, message: 'نام و نام خانوادگی را وارد کنید' }]}
      >
        <Input prefix={<UserOutlined />} placeholder="محمد رضایی" />
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
        name="phone"
        label="شماره موبایل"
        rules={[
          { required: true, message: 'شماره موبایل را وارد کنید' },
          { pattern: /^09[0-9]{9}$/, message: 'شماره موبایل نامعتبر است' },
        ]}
      >
        <Input prefix={<PhoneOutlined />} placeholder="09123456789" />
      </Form.Item>

      <Form.Item
        name="password"
        label="رمز عبور"
        rules={[
          { required: true, message: 'رمز عبور را وارد کنید' },
          { min: 6, message: 'رمز عبور باید حداقل ۶ کاراکتر باشد' },
        ]}
      >
        <Input.Password prefix={<LockOutlined />} placeholder="حداقل ۶ کاراکتر" />
      </Form.Item>

      <Form.Item
        name="confirmPassword"
        label="تکرار رمز عبور"
        dependencies={['password']}
        rules={[
          { required: true, message: 'تکرار رمز عبور را وارد کنید' },
          ({ getFieldValue }) => ({
            validator(_, value) {
              if (!value || getFieldValue('password') === value) {
                return Promise.resolve();
              }
              return Promise.reject(new Error('رمز عبور با تکرار آن مطابقت ندارد'));
            },
          }),
        ]}
      >
        <Input.Password prefix={<LockOutlined />} placeholder="تکرار رمز عبور" />
      </Form.Item>

      <Form.Item
        name="terms"
        valuePropName="checked"
        rules={[
          { validator: (_, value) => value ? Promise.resolve() : Promise.reject(new Error('قوانین را بپذیرید')) },
        ]}
      >
        <Checkbox>
          <a href="#">قوانین و مقررات</a> را می‌پذیرم
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
          ثبت‌نام
        </Button>
      </Form.Item>
    </Form>
  );
}
