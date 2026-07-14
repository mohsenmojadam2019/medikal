// /home/god/Videos/medikal/front/src/app/en/login/page.js
'use client';

import { Card, Typography, Divider, Button, App } from 'antd';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useState } from 'react';
import LoginForm from '@/components/auth/LoginForm';

const { Title, Text } = Typography;

export default function LoginPage() {
    const router = useRouter();
    const { message: appMessage } = App.useApp();
    const [loading, setLoading] = useState(false);

    const handleLogin = async (values) => {
        setLoading(true);
        try {
            const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
            const res = await fetch(`${API_URL}/api/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(values),
            });

            const data = await res.json();

            if (data.success) {
                localStorage.setItem('token', data.data.token);
                appMessage.success('Login successful');

                const redirect = new URLSearchParams(window.location.search).get('redirect') || '/en';
                router.push(redirect);
            } else {
                appMessage.error(data.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            appMessage.error('Server connection error');
        } finally {
            setLoading(false);
        }
    };

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
                        Welcome Back
                    </Title>
                    <Text type="secondary">
                        Please sign in to continue
                    </Text>
                </div>

                <LoginForm onSubmit={handleLogin} loading={loading} />

                <Divider plain>
                    <Text type="secondary">Don't have an account?</Text>
                </Divider>

                <div style={{ textAlign: 'center' }}>
                    <Link href="/en/register">
                        <Button type="link" size="large">
                            Sign up
                        </Button>
                    </Link>
                </div>
            </Card>
        </div>
    );
}