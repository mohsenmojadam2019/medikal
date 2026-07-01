'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Form, Input, Button, Card, Typography, message, Alert } from 'antd';
import { MobileOutlined, CheckCircleOutlined } from '@ant-design/icons';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

export default function AdminLoginPage() {
  const router = useRouter();
  const { login, verifyOtp, isAuthenticated } = useAuth();
  const { t } = useLanguage();

  const [step, setStep] = useState('mobile');
  const [mobile, setMobile] = useState('');
  const [countdown, setCountdown] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    if (isAuthenticated) {
      router.push('/admin');
    }
  }, [isAuthenticated, router]);

  useEffect(() => {
    if (countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [countdown]);

  const handleSendCode = async (values) => {
    setIsLoading(true);
    setErrorMessage('');
    try {
      await login(values.mobile);
      setMobile(values.mobile);
      setStep('otp');
      setCountdown(60);
      message.success(t('code_sent', 'کد تایید به شماره شما ارسال شد'));
    } catch (err) {
      setErrorMessage(err?.message || t('send_code_error', 'خطا در ارسال کد'));
    } finally {
      setIsLoading(false);
    }
  };

  const handleVerifyOtp = async (values) => {
    setIsLoading(true);
    setErrorMessage('');
    try {
      await verifyOtp(mobile, values.otp);
      message.success(t('login_success', 'ورود با موفقیت انجام شد'));
      router.push('/admin');
    } catch (err) {
      setErrorMessage(err?.message || t('invalid_code', 'کد تایید نامعتبر است'));
    } finally {
      setIsLoading(false);
    }
  };

  const handleResendCode = async () => {
    if (countdown > 0) return;
    try {
      await login(mobile);
      setCountdown(60);
      message.success(t('code_resent', 'کد جدید ارسال شد'));
    } catch (err) {
      setErrorMessage(t('resend_error', 'خطا در ارسال مجدد کد'));
    }
  };

  const handleBack = () => {
    setStep('mobile');
    setErrorMessage('');
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: '#f0f2f5',
        padding: '20px',
        direction: 'rtl',
      }}
    >
      <Card
        style={{
          width: '100%',
          maxWidth: 420,
          borderRadius: 16,
          boxShadow: '0 20px 60px rgba(0,0,0,0.08)',
          borderColor: '#e8e8f0',
        }}
        bodyStyle={{ padding: '32px 24px' }}
      >
        <div style={{ textAlign: 'center', marginBottom: 32 }}>
          <div
            style={{
              width: 64,
              height: 64,
              background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
              borderRadius: 16,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              marginBottom: 16,
              boxShadow: '0 8px 24px rgba(37,99,235,0.25)',
            }}
          >
            <span style={{ fontSize: 32, color: 'white' }}>🏥</span>
          </div>
          <Title level={3} style={{ margin: 0, color: '#0f172a' }}>
            کلینیک<span style={{ color: '#2563eb' }}>یار</span>
          </Title>
          <Text type="secondary" style={{ fontSize: 13 }}>
            {t('admin_panel', 'پنل مدیریت کلینیک')}
          </Text>
        </div>

        {errorMessage && (
          <Alert
            message={errorMessage}
            type="error"
            showIcon
            style={{ marginBottom: 16 }}
            closable
            onClose={() => setErrorMessage('')}
          />
        )}

        {step === 'mobile' && (
          <Form name="login" onFinish={handleSendCode} layout="vertical" size="large">
            <Form.Item
              label={t('mobile', 'شماره موبایل')}
              name="mobile"
              rules={[
                { required: true, message: t('mobile_required', 'لطفاً شماره موبایل را وارد کنید') },
                { pattern: /^09[0-9]{9}$/, message: t('mobile_invalid', 'شماره موبایل نامعتبر است') },
              ]}
            >
              <Input prefix={<MobileOutlined />} placeholder="۰۹۱۲۳۴۵۶۷۸۹" maxLength={11} dir="ltr" />
            </Form.Item>

            <Form.Item>
              <Button
                type="primary"
                htmlType="submit"
                loading={isLoading}
                block
                style={{
                  height: 44,
                  background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                  border: 'none',
                  borderRadius: 8,
                  fontWeight: 600,
                }}
              >
                {t('get_code', 'دریافت کد تایید')}
              </Button>
            </Form.Item>

            <div style={{ textAlign: 'center', marginTop: 8 }}>
              <Text type="secondary" style={{ fontSize: 12 }}>
                {t('code_send_info', 'کد تایید به شماره موبایل شما ارسال خواهد شد')}
              </Text>
            </div>
          </Form>
        )}

        {step === 'otp' && (
          <Form name="verify" onFinish={handleVerifyOtp} layout="vertical" size="large">
            <div style={{ textAlign: 'center', marginBottom: 16 }}>
              <Text type="secondary">
                {t('code_sent_to', 'کد تایید به شماره')} {mobile} {t('sent', 'ارسال شد')}
              </Text>
            </div>

            <Form.Item
              label={t('verification_code', 'کد تایید')}
              name="otp"
              rules={[
                { required: true, message: t('code_required', 'لطفاً کد تایید را وارد کنید') },
                { len: 6, message: t('code_length', 'کد تایید باید ۶ رقم باشد') },
              ]}
            >
              <Input.OTP length={6} style={{ direction: 'ltr', textAlign: 'center' }} />
            </Form.Item>

            <Form.Item>
              <Button
                type="primary"
                htmlType="submit"
                loading={isLoading}
                block
                style={{
                  height: 44,
                  background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                  border: 'none',
                  borderRadius: 8,
                  fontWeight: 600,
                }}
              >
                <CheckCircleOutlined /> {t('verify_login', 'تایید و ورود')}
              </Button>
            </Form.Item>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <Button type="link" onClick={handleBack} style={{ padding: 0, fontSize: 13 }}>
                ‹ {t('back', 'بازگشت')}
              </Button>
              <div>
                {countdown > 0 ? (
                  <Text type="secondary" style={{ fontSize: 13 }}>
                    {t('resend_after', 'ارسال مجدد پس از')} {countdown} {t('seconds', 'ثانیه')}
                  </Text>
                ) : (
                  <Button
                    type="link"
                    onClick={handleResendCode}
                    style={{ padding: 0, fontSize: 13 }}
                  >
                    {t('resend_code', 'ارسال مجدد کد')}
                  </Button>
                )}
              </div>
            </div>
          </Form>
        )}
      </Card>
    </div>
  );
}
