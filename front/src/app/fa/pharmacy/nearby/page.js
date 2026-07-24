'use client';

import { useState, useEffect } from 'react';
import {
    Card, Row, Col, Typography, Spin, Tag, Button,
    Space, App, Slider, Select
} from 'antd';
import { EnvironmentOutlined, PhoneOutlined, ShopOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function NearbyPharmaciesPage() {
    const router = useRouter();
    const { t, locale } = useLanguage();
    const { message: appMessage } = App.useApp();

    const [pharmacies, setPharmacies] = useState([]);
    const [loading, setLoading] = useState(true);
    const [location, setLocation] = useState(null);
    const [radius, setRadius] = useState(5);
    const [selectedProvince, setSelectedProvince] = useState('all');
    const [selectedCity, setSelectedCity] = useState('all');
    const [provinces, setProvinces] = useState([]);
    const [cities, setCities] = useState([]);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    useEffect(() => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => setLocation({ lat: position.coords.latitude, lng: position.coords.longitude }),
                () => { appMessage.warning(t('pharmacy.locationPermissionDenied')); setLocation({ lat: 35.6892, lng: 51.3890 }); }
            );
        } else {
            appMessage.warning(t('pharmacy.geolocationNotSupported'));
            setLocation({ lat: 35.6892, lng: 51.3890 });
        }
    }, []);

    useEffect(() => {
        fetch(`${API_URL}/api/clinics/provinces`)
            .then(res => res.json())
            .then(data => { if (data.success) setProvinces(data.data || []); })
            .catch(console.error);
    }, [API_URL]);

    useEffect(() => {
        if (selectedProvince !== 'all') {
            fetch(`${API_URL}/api/clinics/provinces/${selectedProvince}/cities`)
                .then(res => res.json())
                .then(data => { if (data.success) setCities(data.data || []); })
                .catch(console.error);
        } else setCities([]);
    }, [selectedProvince, API_URL]);

    useEffect(() => {
        if (location) fetchNearbyPharmacies();
    }, [location, radius, selectedProvince, selectedCity]);

    const fetchNearbyPharmacies = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ lat: location.lat, lng: location.lng, radius, per_page: 20 });
            if (selectedProvince !== 'all') params.append('province_id', selectedProvince);
            if (selectedCity !== 'all') params.append('city_id', selectedCity);
            const res = await fetch(`${API_URL}/api/pharmacy/nearby?${params}`);
            const data = await res.json();
            if (data.success) setPharmacies(data.data.data || data.data || []);
        } catch (error) {
            console.error('Error fetching nearby pharmacies:', error);
            appMessage.error(t('pharmacy.serverError'));
        } finally { setLoading(false); }
    };

    if (loading || !location) return <><Header /><LoadingSpinner /><Footer /></>;

    return (
        <>
            <Header />
            <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc', padding: '24px 20px' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
                    <Breadcrumb items={[
                        { title: t('pharmacy.title'), href: `/${locale}/pharmacy` },
                        { title: t('pharmacy.nearby') }
                    ]} />
                    <Title level={2}>{t('pharmacy.nearbyPharmacies')}</Title>

                    <Card style={{ borderRadius: 16, marginBottom: 24 }}>
                        <Row gutter={[16, 16]} align="middle">
                            <Col xs={24} md={6}>
                                <Text>{t('pharmacy.radius')}: {radius} km</Text>
                                <Slider min={1} max={20} value={radius} onChange={setRadius} />
                            </Col>
                            <Col xs={12} md={4}>
                                <Select placeholder={t('pharmacy.selectProvince')} style={{ width: '100%' }} value={selectedProvince} onChange={setSelectedProvince} allowClear>
                                    <Option value="all">{t('pharmacy.allProvinces')}</Option>
                                    {provinces.map(p => <Option key={p.id} value={p.id}>{p.name}</Option>)}
                                </Select>
                            </Col>
                            <Col xs={12} md={4}>
                                <Select placeholder={t('pharmacy.selectCity')} style={{ width: '100%' }} value={selectedCity} onChange={setSelectedCity} disabled={selectedProvince === 'all'} allowClear>
                                    <Option value="all">{t('pharmacy.allCities')}</Option>
                                    {cities.map(c => <Option key={c.id} value={c.id}>{c.name}</Option>)}
                                </Select>
                            </Col>
                            <Col xs={24} md={10}>
                                <Text type="secondary">{pharmacies.length} {t('pharmacy.pharmaciesFound')}</Text>
                            </Col>
                        </Row>
                    </Card>

                    {pharmacies.length > 0 ? (
                        <Row gutter={[16, 16]}>
                            {pharmacies.map((pharmacy) => (
                                <Col xs={24} sm={12} md={8} key={pharmacy.id}>
                                    <Card hoverable style={{ borderRadius: 12 }} onClick={() => router.push(`/${locale}/pharmacy/${pharmacy.id}`)}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                            {pharmacy.logo_thumb && <img src={pharmacy.logo_thumb} alt={pharmacy.name} style={{ width: 50, height: 50, borderRadius: '50%', objectFit: 'cover' }} />}
                                            <div>
                                                <Text strong>{pharmacy.name}</Text>
                                                <br />
                                                <Tag color="blue">{pharmacy.distance_text || `${pharmacy.distance?.toFixed(1) || 0} km`}</Tag>
                                            </div>
                                        </div>
                                        <div style={{ marginTop: 12 }}>
                                            <Text type="secondary" style={{ fontSize: 12 }}><EnvironmentOutlined /> {pharmacy.city?.name}, {pharmacy.province?.name}</Text>
                                            {pharmacy.clinic && <Text type="secondary" style={{ fontSize: 12, display: 'block' }}><ShopOutlined /> {pharmacy.clinic.name}</Text>}
                                            <Text type="secondary" style={{ fontSize: 12 }}><PhoneOutlined /> {pharmacy.phone}</Text>
                                        </div>
                                        <Tag color={pharmacy.is_online ? 'success' : 'default'}>{pharmacy.is_online ? t('pharmacy.online') : t('pharmacy.offline')}</Tag>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    ) : (
                        <Empty description={t('pharmacy.noNearbyPharmacies')} />
                    )}
                </div>
            </main>
            <Footer />
        </>
    );
}