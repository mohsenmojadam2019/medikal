'use client';

import { useState } from 'react';
import {
    Card, Row, Col, Typography, Input, Button, Form,
    Space, Divider, message, Alert
} from 'antd';
import {
    PhoneOutlined, MailOutlined, EnvironmentOutlined,
    WhatsAppOutlined, SendOutlined, UserOutlined
} from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function ContactPage() {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);

    const onFinish = async (values) => {
        setLoading(true);
        try {
            console.log('Form values:', values);
            await new Promise(resolve => setTimeout(resolve, 1500));
            message.success('پیام شما با موفقیت ارسال شد!');
            form.resetFields();
        } catch (error) {
            message.error('خطا در ارسال پیام');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Header />
            <main style={{ minHeight: '100vh', padding: '40px 0', background: '#f8fafc' }}>
                <div className="container">
                    <div style={{ textAlign: 'center', marginBottom: 40 }}>
                        <Title level={1}>
                            <PhoneOutlined style={{ color: '#2563eb', marginLeft: 12 }} />
                            تماس با ما
                        </Title>
                        <Text type="secondary" style={{ fontSize: 16 }}>
                            ما همیشه آماده پاسخگویی به شما هستیم
                        </Text>
                    </div>

                    <Row gutter={[32, 32]}>
                        <Col xs={24} lg={10}>
                            <Card
                                title="اطلاعات تماس"
                                bordered={false}
                                style={{ borderRadius: 16, height: '100%' }}
                            >
                                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                                    <div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 4 }}>
                                            <PhoneOutlined style={{ color: '#2563eb', fontSize: 20 }} />
                                            <Text strong>تلفن پشتیبانی</Text>
                                        </div>
                                        <a href="tel:02112345678" style={{ fontSize: 16, color: '#1e293b' }}>
                                            ۰۲۱-۱۲۳۴۵۶۷۸
                                        </a>
                                    </div>

                                    <div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 4 }}>
                                            <WhatsAppOutlined style={{ color: '#25d366', fontSize: 20 }} />
                                            <Text strong>واتساپ</Text>
                                        </div>
                                        <a href="https://wa.me/989123456789" target="_blank" style={{ fontSize: 16, color: '#1e293b' }}>
                                            ۰۹۱۲-۳۴۵۶۷۸۹
                                        </a>
                                    </div>

                                    <div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 4 }}>
                                            <MailOutlined style={{ color: '#ec4899', fontSize: 20 }} />
                                            <Text strong>ایمیل</Text>
                                        </div>
                                        <a href="mailto:info@clinic-yar.com" style={{ fontSize: 16, color: '#1e293b' }}>
                                            info@clinic-yar.com
                                        </a>
                                    </div>

                                    <div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 4 }}>
                                            <EnvironmentOutlined style={{ color: '#f59e0b', fontSize: 20 }} />
                                            <Text strong>آدرس</Text>
                                        </div>
                                        <Text style={{ fontSize: 16, color: '#1e293b' }}>
                                            تهران، خیابان ولیعصر، پلاک ۱۲۳
                                        </Text>
                                    </div>

                                    <Divider />

                                    <Alert
                                        message="ساعت پاسخگویی"
                                        description="شنبه تا پنجشنبه ۸ صبح تا ۸ شب"
                                        type="info"
                                        showIcon
                                    />
                                </Space>
                            </Card>
                        </Col>

                        <Col xs={24} lg={14}>
                            <Card
                                title="ارسال پیام"
                                bordered={false}
                                style={{ borderRadius: 16 }}
                            >
                                <Form
                                    form={form}
                                    layout="vertical"
                                    onFinish={onFinish}
                                    size="large"
                                >
                                    <Row gutter={16}>
                                        <Col xs={24} sm={12}>
                                            <Form.Item
                                                name="name"
                                                label="نام و نام خانوادگی"
                                                rules={[{ required: true, message: 'لطفاً نام خود را وارد کنید' }]}
                                            >
                                                <Input prefix={<UserOutlined />} placeholder="نام خود را وارد کنید" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} sm={12}>
                                            <Form.Item
                                                name="email"
                                                label="ایمیل"
                                                rules={[
                                                    { required: true, message: 'لطفاً ایمیل خود را وارد کنید' },
                                                    { type: 'email', message: 'لطفاً ایمیل معتبر وارد کنید' }
                                                ]}
                                            >
                                                <Input prefix={<MailOutlined />} placeholder="ایمیل خود را وارد کنید" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Form.Item
                                        name="subject"
                                        label="موضوع"
                                        rules={[{ required: true, message: 'لطفاً موضوع را وارد کنید' }]}
                                    >
                                        <Input placeholder="موضوع پیام را وارد کنید" />
                                    </Form.Item>

                                    <Form.Item
                                        name="message"
                                        label="متن پیام"
                                        rules={[
                                            { required: true, message: 'لطفاً متن پیام را وارد کنید' },
                                            { min: 10, message: 'متن پیام باید حداقل ۱۰ کاراکتر باشد' }
                                        ]}
                                    >
                                        <TextArea
                                            rows={6}
                                            placeholder="متن پیام خود را وارد کنید..."
                                            showCount
                                            maxLength={500}
                                        />
                                    </Form.Item>

                                    <Form.Item>
                                        <Button
                                            type="primary"
                                            htmlType="submit"
                                            loading={loading}
                                            icon={<SendOutlined />}
                                            size="large"
                                            block
                                        >
                                            ارسال پیام
                                        </Button>
                                    </Form.Item>
                                </Form>
                            </Card>
                        </Col>
                    </Row>

                    <Card
                        style={{ marginTop: 32, borderRadius: 16, overflow: 'hidden' }}
                        bordered={false}
                    >
                        <div style={{
                            background: '#e2e8f0',
                            height: 300,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            borderRadius: 8
                        }}>
                            <div style={{ textAlign: 'center' }}>
                                <EnvironmentOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                                <div style={{ color: '#64748b', marginTop: 8, fontSize: 18 }}>
                                    📍 نقشه تعاملی - تهران، خیابان ولیعصر، پلاک ۱۲۳
                                </div>
                                <Text type="secondary">
                                    (برای نمایش نقشه کامل، کلید API نقشه را تنظیم کنید)
                                </Text>
                            </div>
                        </div>
                    </Card>
                </div>
            </main>
            <Footer />

            <style jsx>{`
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 0 24px;
                }

                @media (max-width: 768px) {
                    main {
                        padding: 20px 0;
                    }
                    .container {
                        padding: 0 16px;
                    }
                }
            `}</style>
        </>
    );
}