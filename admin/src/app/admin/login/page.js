'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Form, Input, Button, Typography, message, Alert, Row, Col, Divider } from 'antd';
import { MailOutlined, LockOutlined, HeartOutlined } from '@ant-design/icons';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

export default function AdminLoginPage() {
  const router = useRouter();
  const { loginWithEmail, isAuthenticated, loading } = useAuth();
  const { t } = useLanguage();
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    if (isAuthenticated) {
      router.push('/admin');
    }
  }, [isAuthenticated, router]);

  const handleLogin = async (values) => {
    setIsLoading(true);
    setErrorMessage('');
    try {
      await loginWithEmail(values.email, values.password);
      message.success(t('login_success', 'ورود با موفقیت انجام شد'));
      router.push('/admin');
    } catch (err) {
      setErrorMessage(err?.message || t('login_error', 'ایمیل یا رمز عبور اشتباه است'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        padding: '20px',
        direction: 'rtl',
        position: 'relative',
        overflow: 'hidden',
      }}
    >
      {/* ===== پس‌زمینه دکوراتیو ===== */}
      <div
        style={{
          position: 'absolute',
          top: -150,
          right: -150,
          width: 400,
          height: 400,
          borderRadius: '50%',
          background: 'rgba(255,255,255,0.05)',
          pointerEvents: 'none',
        }}
      />
      <div
        style={{
          position: 'absolute',
          bottom: -200,
          left: -200,
          width: 500,
          height: 500,
          borderRadius: '50%',
          background: 'rgba(255,255,255,0.05)',
          pointerEvents: 'none',
        }}
      />
      <div
        style={{
          position: 'absolute',
          top: '50%',
          left: '50%',
          transform: 'translate(-50%, -50%)',
          width: 800,
          height: 800,
          borderRadius: '50%',
          background: 'radial-gradient(circle, rgba(255,255,255,0.02) 0%, transparent 70%)',
          pointerEvents: 'none',
        }}
      />

      <Row
        gutter={[0, 0]}
        style={{
          width: '100%',
          maxWidth: 1000,
          background: 'white',
          borderRadius: 24,
          boxShadow: '0 40px 80px rgba(0,0,0,0.2)',
          overflow: 'hidden',
          position: 'relative',
          zIndex: 1,
        }}
      >
        {/* ===== سمت راست: فرم ===== */}
        <Col xs={24} md={14} style={{ padding: '48px 40px' }}>
          <div style={{ marginBottom: 32 }}>
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                marginBottom: 8,
              }}
            >
              <div
                style={{
                  width: 48,
                  height: 48,
                  background: 'linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)',
                  borderRadius: 14,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: 'white',
                  fontSize: 22,
                  boxShadow: '0 8px 24px rgba(37,99,235,0.3)',
                }}
              >
                <HeartOutlined />
              </div>
              <div>
                <div style={{ fontSize: 24, fontWeight: 800, color: '#0f172a' }}>
                  کلینیک<span style={{ color: '#2563eb' }}>یار</span>
                </div>
                <div style={{ fontSize: 13, color: '#64748b', marginTop: -2 }}>
                  {t('admin_panel', 'پنل مدیریت کلینیک')}
                </div>
              </div>
            </div>
          </div>

          <div style={{ marginBottom: 32 }}>
            <Title level={2} style={{ margin: 0, color: '#0f172a', fontSize: 28 }}>
              {t('welcome_back', 'خوش آمدید')}
            </Title>
            <Text type="secondary" style={{ fontSize: 15 }}>
              {t('login_subtitle', 'برای ورود به پنل مدیریت، ایمیل و رمز عبور خود را وارد کنید')}
            </Text>
          </div>

          {errorMessage && (
            <Alert
              title={errorMessage}
              type="error"
              showIcon
              style={{ marginBottom: 20, borderRadius: 12 }}
              closable
              onClose={() => setErrorMessage('')}
            />
          )}

          <Form
            name="login"
            onFinish={handleLogin}
            layout="vertical"
            size="large"
            style={{ maxWidth: 400 }}
          >
            <Form.Item
              label={<span style={{ fontWeight: 600 }}>{t('email', 'ایمیل')}</span>}
              name="email"
              rules={[
                { required: true, message: t('email_required', 'لطفاً ایمیل را وارد کنید') },
                { type: 'email', message: t('email_invalid', 'ایمیل نامعتبر است') },
              ]}
            >
              <Input
                prefix={<MailOutlined style={{ color: '#94a3b8' }} />}
                placeholder={t('email_placeholder', 'admin@clinic.com')}
                style={{ borderRadius: 12, height: 48, borderColor: '#e2e8f0' }}
                dir="ltr"
              />
            </Form.Item>

            <Form.Item
              label={<span style={{ fontWeight: 600 }}>{t('password', 'رمز عبور')}</span>}
              name="password"
              rules={[
                { required: true, message: t('password_required', 'لطفاً رمز عبور را وارد کنید') },
                { min: 6, message: t('password_min', 'رمز عبور باید حداقل ۶ کاراکتر باشد') },
              ]}
            >
              <Input.Password
                prefix={<LockOutlined style={{ color: '#94a3b8' }} />}
                placeholder={t('password_placeholder', '********')}
                style={{ borderRadius: 12, height: 48, borderColor: '#e2e8f0' }}
                dir="ltr"
              />
            </Form.Item>

            <Form.Item style={{ marginTop: 8 }}>
              <Button
                type="primary"
                htmlType="submit"
                loading={isLoading || loading}
                block
                style={{
                  height: 52,
                  background: 'linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)',
                  border: 'none',
                  borderRadius: 12,
                  fontWeight: 700,
                  fontSize: 16,
                  boxShadow: '0 8px 24px rgba(37,99,235,0.3)',
                  transition: 'all 0.3s',
                }}
                onMouseEnter={(e) => {
                  e.target.style.transform = 'translateY(-2px)';
                  e.target.style.boxShadow = '0 12px 32px rgba(37,99,235,0.4)';
                }}
                onMouseLeave={(e) => {
                  e.target.style.transform = 'translateY(0)';
                  e.target.style.boxShadow = '0 8px 24px rgba(37,99,235,0.3)';
                }}
              >
                {t('login', 'ورود به پنل مدیریت')}
              </Button>
            </Form.Item>
          </Form>

          <Divider style={{ margin: '20px 0 16px', borderColor: '#e8e8f0' }}>
            <Text type="secondary" style={{ fontSize: 12 }}>
              {t('login_info', 'سیستم مدیریت سلامت')}
            </Text>
          </Divider>

          <div style={{ display: 'flex', justifyContent: 'center', gap: 24, marginTop: 8 }}>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 20, fontWeight: 700, color: '#2563eb' }}>۱۲+</div>
              <div style={{ fontSize: 12, color: '#64748b' }}>{t('doctors', 'پزشکان')}</div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 20, fontWeight: 700, color: '#10b981' }}>۸۴+</div>
              <div style={{ fontSize: 12, color: '#64748b' }}>{t('patients', 'بیماران')}</div>
            </div>
            <div style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 20, fontWeight: 700, color: '#f59e0b' }}>۱۴۲+</div>
              <div style={{ fontSize: 12, color: '#64748b' }}>{t('appointments', 'نوبت‌ها')}</div>
            </div>
          </div>
        </Col>

        {/* ===== سمت چپ: تصویر ===== */}
        <Col
          xs={0}
          md={10}
          style={{
            background: 'linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '40px',
            position: 'relative',
            overflow: 'hidden',
          }}
        >
          <div
            style={{
              position: 'absolute',
              top: -100,
              right: -100,
              width: 300,
              height: 300,
              borderRadius: '50%',
              background: 'rgba(255,255,255,0.08)',
            }}
          />
          <div
            style={{
              position: 'absolute',
              bottom: -80,
              left: -80,
              width: 250,
              height: 250,
              borderRadius: '50%',
              background: 'rgba(255,255,255,0.08)',
            }}
          />

          <div
            style={{
              position: 'relative',
              zIndex: 1,
              textAlign: 'center',
              color: 'white',
            }}
          >
            <div
              style={{
                fontSize: 72,
                marginBottom: 24,
                display: 'block',
              }}
            >
              🏥
            </div>
            <h2
              style={{
                color: 'white',
                fontSize: 28,
                fontWeight: 700,
                marginBottom: 12,
              }}
            >
              {t('system_title', 'سیستم مدیریت کلینیک')}
            </h2>
            <p
              style={{
                color: 'rgba(255,255,255,0.8)',
                fontSize: 15,
                maxWidth: 320,
                margin: '0 auto',
                lineHeight: 1.8,
              }}
            >
              {t('system_desc', 'مدیریت هوشمند نوبت‌ها، بیماران، پزشکان و امور مالی کلینیک')}
            </p>

            <div
              style={{
                marginTop: 32,
                display: 'flex',
                justifyContent: 'center',
                gap: 16,
                flexWrap: 'wrap',
              }}
            >
              <div
                style={{
                  background: 'rgba(255,255,255,0.12)',
                  backdropFilter: 'blur(10px)',
                  borderRadius: 12,
                  padding: '12px 20px',
                  border: '1px solid rgba(255,255,255,0.1)',
                }}
              >
                <div style={{ fontSize: 24, fontWeight: 700 }}>💊</div>
                <div style={{ fontSize: 12, opacity: 0.8 }}>{t('prescriptions', 'نسخه‌ها')}</div>
              </div>
              <div
                style={{
                  background: 'rgba(255,255,255,0.12)',
                  backdropFilter: 'blur(10px)',
                  borderRadius: 12,
                  padding: '12px 20px',
                  border: '1px solid rgba(255,255,255,0.1)',
                }}
              >
                <div style={{ fontSize: 24, fontWeight: 700 }}>📋</div>
                <div style={{ fontSize: 12, opacity: 0.8 }}>{t('records', 'پرونده‌ها')}</div>
              </div>
              <div
                style={{
                  background: 'rgba(255,255,255,0.12)',
                  backdropFilter: 'blur(10px)',
                  borderRadius: 12,
                  padding: '12px 20px',
                  border: '1px solid rgba(255,255,255,0.1)',
                }}
              >
                <div style={{ fontSize: 24, fontWeight: 700 }}>📊</div>
                <div style={{ fontSize: 12, opacity: 0.8 }}>{t('reports', 'گزارشات')}</div>
              </div>
            </div>
          </div>
        </Col>
      </Row>
    </div>
  );
}
