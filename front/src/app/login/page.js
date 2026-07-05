'use client';

import { Card, Typography, Divider, Button } from 'antd';
import Link from 'next/link';
import LoginForm from '@/components/auth/LoginForm';

const { Title, Text } = Typography;

export default function LoginPage() {
  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      background: 'linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)',
      padding: '20px',
    }}>
      <Card
        style={{
          maxWidth: '480px',
          width: '100%',
          borderRadius: '24px',
          boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: '32px' }}>
          <div style={{ 
            width: '64px', 
            height: '64px', 
            margin: '0 auto 16px',
            background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
            borderRadius: '16px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: '#fff',
            fontSize: '28px',
            boxShadow: '0 4px 12px rgba(37,99,235,0.3)',
          }}>
            <i className="fas fa-user-md" />
          </div>
          <Title level={2} style={{ margin: 0 }}>
            خوش آمدید
          </Title>
          <Text type="secondary">
            برای ادامه وارد حساب کاربری خود شوید
          </Text>
        </div>

        <LoginForm />

        <Divider plain>
          <Text type="secondary">حساب ندارید؟</Text>
        </Divider>

        <div style={{ textAlign: 'center' }}>
          <Link href="/register">
            <Button type="link" size="large">
              ثبت‌نام کنید
            </Button>
          </Link>
        </div>
      </Card>
    </div>
  );
}
