'use client';

import { useState, useEffect } from 'react';
import { Card, Typography, Spin, Alert, Row, Col } from 'antd';
import { RobotOutlined, WarningOutlined } from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import ChatWidget from '@/components/ai/ChatWidget';
import { useParams } from 'next/navigation';

const { Title, Paragraph } = Typography;

export default function AiChatPage() {
    const { locale } = useParams();
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const timer = setTimeout(() => setLoading(false), 300);
        return () => clearTimeout(timer);
    }, []);

    if (loading) {
        return (
            <>
                <Header />
                <div className="container" style={{ padding: '60px 0', textAlign: 'center' }}>
                    <Spin size="large" tip="در حال بارگذاری..." />
                </div>
                <Footer />
            </>
        );
    }

    return (
        <>
            <Header />
            <main style={{ background: '#f0f7ff', minHeight: 'calc(100vh - 200px)' }}>
                <div className="container" style={{ padding: '40px 0' }}>
                    {/* Header Section */}
                    <div style={{ textAlign: 'center', marginBottom: '40px' }}>
                        <div style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            width: '80px',
                            height: '80px',
                            borderRadius: '50%',
                            background: 'linear-gradient(135deg, #1890ff, #096dd9)',
                            marginBottom: '20px',
                            boxShadow: '0 8px 30px rgba(24,144,255,0.3)',
                        }}>
                            <RobotOutlined style={{ fontSize: '40px', color: 'white' }} />
                        </div>
                        <Title level={2} style={{ marginBottom: '8px' }}>
                            🧠 دکتر آنلاین - هوش مصنوعی پزشکی
                        </Title>
                        <Paragraph style={{ fontSize: '18px', color: '#666', maxWidth: '600px', margin: '0 auto' }}>
                            سوالات پزشکی خود را بپرسید و از پاسخ‌های هوشمند و دقیق بهره‌مند شوید.
                        </Paragraph>

                        <Row gutter={[16, 16]} justify="center" style={{ marginTop: '20px' }}>
                            <Col>
                                <Alert
                                    message="⚠️ نکته مهم"
                                    description="این سیستم فقط جنبه اطلاع‌رسانی دارد و جایگزین تشخیص پزشک نیست."
                                    type="warning"
                                    showIcon
                                    icon={<WarningOutlined />}
                                    style={{ maxWidth: '400px' }}
                                />
                            </Col>
                            <Col>
                                <Alert
                                    message="🆘 اورژانس"
                                    description="در موارد اورژانسی با شماره 115 تماس بگیرید."
                                    type="error"
                                    showIcon
                                    style={{ maxWidth: '400px' }}
                                />
                            </Col>
                        </Row>
                    </div>

                    {/* Chat Section */}
                    <Card
                        style={{
                            borderRadius: '20px',
                            boxShadow: '0 8px 40px rgba(0,0,0,0.08)',
                            overflow: 'hidden',
                            border: 'none',
                            maxWidth: '900px',
                            margin: '0 auto',
                        }}
                    >
                        <ChatWidget />
                    </Card>

                    {/* Features Section */}
                    <Row gutter={[24, 24]} style={{ marginTop: '40px' }} justify="center">
                        <Col xs={24} sm={8}>
                            <div style={{
                                textAlign: 'center',
                                padding: '24px',
                                background: 'white',
                                borderRadius: '16px',
                                boxShadow: '0 4px 16px rgba(0,0,0,0.06)',
                            }}>
                                <div style={{ fontSize: '32px', marginBottom: '8px' }}>🔒</div>
                                <strong>حریم خصوصی شما محفوظ است</strong>
                                <div style={{ fontSize: '14px', color: '#999', marginTop: '4px' }}>
                                    اطلاعات شما رمزنگاری و امن است
                                </div>
                            </div>
                        </Col>
                        <Col xs={24} sm={8}>
                            <div style={{
                                textAlign: 'center',
                                padding: '24px',
                                background: 'white',
                                borderRadius: '16px',
                                boxShadow: '0 4px 16px rgba(0,0,0,0.06)',
                            }}>
                                <div style={{ fontSize: '32px', marginBottom: '8px' }}>⚡</div>
                                <strong>پاسخ‌دهی سریع و هوشمند</strong>
                                <div style={{ fontSize: '14px', color: '#999', marginTop: '4px' }}>
                                    پاسخ‌ها در کسری از ثانیه
                                </div>
                            </div>
                        </Col>
                        <Col xs={24} sm={8}>
                            <div style={{
                                textAlign: 'center',
                                padding: '24px',
                                background: 'white',
                                borderRadius: '16px',
                                boxShadow: '0 4px 16px rgba(0,0,0,0.06)',
                            }}>
                                <div style={{ fontSize: '32px', marginBottom: '8px' }}>🩺</div>
                                <strong>اطلاعات دقیق پزشکی</strong>
                                <div style={{ fontSize: '14px', color: '#999', marginTop: '4px' }}>
                                    بر اساس منابع معتبر پزشکی
                                </div>
                            </div>
                        </Col>
                    </Row>
                </div>
            </main>
            <Footer />
        </>
    );
}
