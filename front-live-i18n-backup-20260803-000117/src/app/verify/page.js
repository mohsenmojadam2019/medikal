'use client';

import { Card, Typography } from 'antd';
import VerifyForm from '@/components/auth/VerifyForm';

const { Title, Text } = Typography;

export default function VerifyPage() {
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
        <VerifyForm />
      </Card>
    </div>
  );
}
