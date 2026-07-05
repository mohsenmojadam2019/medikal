'use client';

import { useState } from 'react';
import { Button, message, Typography } from 'antd';
import { useRouter } from 'next/navigation';
import OTPInput from './OTPInput';

const { Title, Text } = Typography;

export default function VerifyForm() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  const handleVerify = async (code) => {
    setLoading(true);
    
    try {
      const res = await fetch('http://localhost:8210/api/auth/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code }),
      });
      
      const data = await res.json();
      
      if (data.success) {
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
        message.success('✅ حساب کاربری شما با موفقیت تایید شد');
        router.push('/');
      } else {
        message.error(data.message || '❌ کد تایید نامعتبر است');
      }
    } catch (error) {
      message.error('❌ خطا در تایید');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ textAlign: 'center' }}>
      <Title level={3}>تایید حساب کاربری</Title>
      <Text type="secondary">
        کد ۴ رقمی ارسال شده به موبایل خود را وارد کنید
      </Text>
      
      <div style={{ marginTop: '32px' }}>
        <OTPInput length={4} onComplete={handleVerify} />
      </div>
      
      <div style={{ marginTop: '24px' }}>
        <Button 
          type="link" 
          onClick={() => message.info('کد مجدد ارسال شد')}
        >
          ارسال مجدد کد
        </Button>
        <br />
        <Button 
          type="link" 
          onClick={() => router.push('/login')}
        >
          بازگشت به صفحه ورود
        </Button>
      </div>
    </div>
  );
}
