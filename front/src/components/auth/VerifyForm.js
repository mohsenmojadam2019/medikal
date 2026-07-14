// /home/god/Videos/medikal/front/src/components/auth/VerifyForm.jsx
'use client';

import { useState } from 'react';
import { Button, message, Typography, App } from 'antd';
import { useRouter, usePathname } from 'next/navigation';
import OTPInput from './OTPInput';

const { Title, Text } = Typography;
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

export default function VerifyForm() {
  const router = useRouter();
  const pathname = usePathname();
  const { message: appMessage } = App.useApp();
  const [loading, setLoading] = useState(false);
  const [countdown, setCountdown] = useState(0);

  // ✅ تشخیص زبان از مسیر
  const getLocale = () => {
    const segments = pathname?.split('/') || [];
    const locale = segments[1] || 'fa';
    return ['fa', 'en', 'ar'].includes(locale) ? locale : 'fa';
  };

  const locale = getLocale();

  const handleVerify = async (code) => {
    setLoading(true);

    try {
      const token = localStorage.getItem('token');
      const res = await fetch(`${API_URL}/api/auth/verify-otp`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token && { 'Authorization': `Bearer ${token}` }),
        },
        body: JSON.stringify({ code: code }),
      });

      const data = await res.json();

      if (data.success) {
        if (data.data?.token) {
          localStorage.setItem('token', data.data.token);
        }
        if (data.data?.user) {
          localStorage.setItem('user', JSON.stringify(data.data.user));
        }

        appMessage.success(locale === 'fa' ? '✅ حساب کاربری شما با موفقیت تایید شد' :
            locale === 'en' ? '✅ Your account has been verified' :
                '✅ تم التحقق من حسابك بنجاح');
        router.push(`/${locale}`);
      } else {
        appMessage.error(data.message || (locale === 'fa' ? '❌ کد تایید نامعتبر است' :
            locale === 'en' ? '❌ Invalid verification code' :
                '❌ رمز التحقق غير صالح'));
      }
    } catch (error) {
      console.error('Error:', error);
      appMessage.error(locale === 'fa' ? '❌ خطا در تایید' :
          locale === 'en' ? '❌ Verification error' :
              '❌ خطأ في التحقق');
    } finally {
      setLoading(false);
    }
  };

  const handleResendCode = async () => {
    setCountdown(60);
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    try {
      const token = localStorage.getItem('token');
      const res = await fetch(`${API_URL}/api/auth/resend-otp`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token && { 'Authorization': `Bearer ${token}` }),
        },
      });

      const data = await res.json();

      if (data.success) {
        appMessage.success(locale === 'fa' ? '✅ کد مجدد ارسال شد' :
            locale === 'en' ? '✅ Code resent successfully' :
                '✅ تم إعادة إرسال الرمز');
      } else {
        appMessage.error(data.message || (locale === 'fa' ? '❌ خطا در ارسال مجدد کد' :
            locale === 'en' ? '❌ Error resending code' :
                '❌ خطأ في إعادة إرسال الرمز'));
      }
    } catch (error) {
      appMessage.error(locale === 'fa' ? '❌ خطا در ارتباط با سرور' :
          locale === 'en' ? '❌ Server connection error' :
              '❌ خطأ في الاتصال بالخادم');
    }
  };

  return (
      <div style={{ textAlign: 'center' }}>
        <Title level={3}>
          {locale === 'fa' ? 'تایید حساب کاربری' :
              locale === 'en' ? 'Verify Your Account' :
                  'تحقق من حسابك'}
        </Title>
        <Text type="secondary">
          {locale === 'fa' ? 'کد ۴ رقمی ارسال شده به موبایل خود را وارد کنید' :
              locale === 'en' ? 'Enter the 4-digit code sent to your phone' :
                  'أدخل الرمز المكون من 4 أرقام المرسل إلى هاتفك'}
        </Text>

        <div style={{ marginTop: '32px' }}>
          <OTPInput length={4} onComplete={handleVerify} disabled={loading} />
        </div>

        <div style={{ marginTop: '24px' }}>
          <Button
              type="link"
              onClick={handleResendCode}
              disabled={countdown > 0 || loading}
          >
            {locale === 'fa' ? `ارسال مجدد کد ${countdown > 0 ? `(${countdown}s)` : ''}` :
                locale === 'en' ? `Resend code ${countdown > 0 ? `(${countdown}s)` : ''}` :
                    `إعادة إرسال الرمز ${countdown > 0 ? `(${countdown}s)` : ''}`}
          </Button>
          <br />
          <Button
              type="link"
              onClick={() => router.push(`/${locale}/login`)}
          >
            {locale === 'fa' ? 'بازگشت به صفحه ورود' :
                locale === 'en' ? 'Back to login' :
                    'العودة إلى تسجيل الدخول'}
          </Button>
        </div>
      </div>
  );
}