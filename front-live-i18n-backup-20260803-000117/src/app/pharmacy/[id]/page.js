'use client';

import * as React from 'react';
import { useState, useEffect } from 'react';
import {
    Card, Row, Col, Typography, Spin, Tag, Button,
    Descriptions, Image, Space, Carousel, Divider
} from 'antd';
import {
    EnvironmentOutlined, PhoneOutlined, MailOutlined,
    ShopOutlined, LeftOutlined, ClockCircleOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function PharmacyDetailPage({ params }) {
    const router = useRouter();
    const [pharmacy, setPharmacy] = useState(null);
    const [loading, setLoading] = useState(true);
    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
    const { id } = React.use(params);

    useEffect(() => {
        fetchPharmacyDetail();
    }, [id]);

    const fetchPharmacyDetail = async () => {
        setLoading(true);
        try {
            const res = await fetch(`${API_URL}/api/pharmacy/pharmacies/${id}`);
            const data = await res.json();
            if (data.success) {
                setPharmacy(data.data.pharmacy);
            }
        } catch (error) {
            console.error('Error fetching pharmacy detail:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <><Header /><LoadingSpinner /><Footer /></>;

    if (!pharmacy) {
        return (
            <>
                <Header />
                <div style={{ textAlign: 'center', padding: 60 }}>
                    <Title level={3}>داروخانه یافت نشد</Title>
                    <Button onClick={() => router.push('/pharmacy')}>
                        بازگشت به لیست
                    </Button>
                </div>
                <Footer />
            </>
        );
    }

    return (
        <>
            <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc', padding: '24px 20px' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
                    <Breadcrumb items={[
                        { title: 'داروخانه', href: '/pharmacy' },
                        { title: pharmacy.name }
                    ]} />

                    <Button
                        icon={<LeftOutlined />}
                        onClick={() => router.back()}
                        style={{ marginBottom: 16 }}
                    >
                        بازگشت
                    </Button>

                    <Row gutter={[24, 24]}>
                        {/* ستون تصاویر */}
                        <Col xs={24} md={10}>
                            <Card style={{ borderRadius: 16, overflow: 'hidden' }}>
                                {pharmacy.images_urls?.length > 0 ? (
                                    <Carousel autoplay>
                                        {pharmacy.images_urls.map((img, index) => (
                                            <div
                                                key={index}
                                                style={{
                                                    height: 300,
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    background: '#f0f2f5'
                                                }}
                                            >
                                                <Image
                                                    src={img.url}
                                                    alt={pharmacy.name}
                                                    style={{ maxHeight: 280, objectFit: 'contain' }}
                                                    preview={false}
                                                />
                                            </div>
                                        ))}
                                    </Carousel>
                                ) : (
                                    <div style={{
                                        height: 300,
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        background: '#e0e7ff'
                                    }}>
                                        <ShopOutlined style={{ fontSize: 80, color: '#2563eb' }} />
                                    </div>
                                )}
                            </Card>
                        </Col>

                        {/* ستون اطلاعات */}
                        <Col xs={24} md={14}>
                            <Card style={{ borderRadius: 16 }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
                                    {pharmacy.logo_url && (
                                        <Image
                                            src={pharmacy.logo_url}
                                            alt={pharmacy.name}
                                            width={80}
                                            height={80}
                                            style={{ borderRadius: '50%', objectFit: 'cover' }}
                                            preview={false}
                                        />
                                    )}
                                    <div>
                                        <Title level={2} style={{ marginBottom: 4 }}>{pharmacy.name}</Title>
                                        <Space>
                                            <Tag color={pharmacy.is_online ? 'success' : 'default'}>
                                                {pharmacy.is_online ? 'آنلاین' : 'آفلاین'}
                                            </Tag>
                                            {pharmacy.clinic && <Tag color="blue">{pharmacy.clinic.name}</Tag>}
                                        </Space>
                                    </div>
                                </div>

                                <Descriptions column={1} bordered size="small">
                                    <Descriptions.Item label="آدرس">
                                        <EnvironmentOutlined /> {pharmacy.full_address || pharmacy.address || '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="استان">
                                        {pharmacy.province?.name || '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="شهر">
                                        {pharmacy.city?.name || '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="کلینیک">
                                        {pharmacy.clinic ? (
                                            <Space><ShopOutlined /><span>{pharmacy.clinic.name}</span></Space>
                                        ) : '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="تلفن">
                                        <PhoneOutlined /> {pharmacy.phone || '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="ایمیل">
                                        <MailOutlined /> {pharmacy.email || '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="شماره پروانه">
                                        {pharmacy.license_number || '—'}
                                    </Descriptions.Item>
                                    {pharmacy.working_hours && (
                                        <Descriptions.Item label="ساعات کاری">
                                            <ClockCircleOutlined /> {typeof pharmacy.working_hours === 'object'
                                            ? JSON.stringify(pharmacy.working_hours)
                                            : pharmacy.working_hours}
                                        </Descriptions.Item>
                                    )}
                                </Descriptions>

                                <Divider />

                                <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                                    <Button
                                        type="primary"
                                        icon={<PhoneOutlined />}
                                        href={`tel:${pharmacy.phone}`}
                                        disabled={!pharmacy.phone}
                                    >
                                        تماس
                                    </Button>
                                    <Button
                                        icon={<EnvironmentOutlined />}
                                        onClick={() => {
                                            if (pharmacy.latitude && pharmacy.longitude) {
                                                window.open(`https://www.google.com/maps?q=${pharmacy.latitude},${pharmacy.longitude}`);
                                            }
                                        }}
                                        disabled={!pharmacy.latitude || !pharmacy.longitude}
                                    >
                                        مشاهده در نقشه
                                    </Button>
                                    <Button
                                        onClick={() => router.push(`/pharmacy/products?pharmacy_id=${pharmacy.id}`)}
                                    >
                                        مشاهده داروها
                                    </Button>
                                    <Button
                                        type="primary"
                                        ghost
                                        onClick={() => router.push(`/pharmacy/order?pharmacy_id=${pharmacy.id}`)}
                                        disabled={!pharmacy.is_online}
                                    >
                                        سفارش دارو
                                    </Button>
                                </div>
                            </Card>
                        </Col>
                    </Row>
                </div>
            </main>
            <Footer />
        </>
    );
}