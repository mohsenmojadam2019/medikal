'use client';

import { useState, useEffect, useCallback } from 'react';
import {
  Card, Row, Col, Typography, Spin, Empty, Tag,
  Button, Input, Space, App, Select, Pagination,
  Image, Tooltip
} from 'antd';
import {
  SearchOutlined, EnvironmentOutlined,
  PhoneOutlined, ShopOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function PharmacyPage() {
  const router = useRouter();
  const { message: appMessage } = App.useApp();

  const [pharmacies, setPharmacies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(12);
  const [totalItems, setTotalItems] = useState(0);

  // فیلترها
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedProvince, setSelectedProvince] = useState('all');
  const [selectedCity, setSelectedCity] = useState('all');
  const [selectedClinic, setSelectedClinic] = useState('all');
  const [isOnline, setIsOnline] = useState('all');

  // داده‌های فیلتر
  const [provinces, setProvinces] = useState([]);
  const [cities, setCities] = useState([]);
  const [clinics, setClinics] = useState([]);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const fetchPharmacies = useCallback(async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);
      if (selectedProvince !== 'all') params.append('province_id', selectedProvince);
      if (selectedCity !== 'all') params.append('city_id', selectedCity);
      if (selectedClinic !== 'all') params.append('clinic_id', selectedClinic);
      if (isOnline !== 'all') params.append('is_online', isOnline === 'true' ? '1' : '0');
      params.append('page', currentPage);
      params.append('per_page', pageSize);

      const res = await fetch(`${API_URL}/api/pharmacy/pharmacies?${params}`);
      const data = await res.json();
      if (data.success) {
        const pharmaciesData = data.data.data || data.data || [];
        setPharmacies(pharmaciesData);
        setTotalItems(data.data.total || pharmaciesData.length || 0);
      } else {
        appMessage.error(data.message || 'خطا در دریافت اطلاعات');
      }
    } catch (error) {
      console.error('Error fetching pharmacies:', error);
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  }, [searchTerm, selectedProvince, selectedCity, selectedClinic, isOnline, currentPage, pageSize, appMessage]);

  const fetchFilterData = useCallback(async () => {
    try {
      const [provincesRes, clinicsRes] = await Promise.all([
        fetch(`${API_URL}/api/clinics/provinces`),
        fetch(`${API_URL}/api/clinics`)
      ]);

      const provincesData = await provincesRes.json();
      if (provincesData.success) setProvinces(provincesData.data || []);

      const clinicsData = await clinicsRes.json();
      if (clinicsData.success) setClinics(clinicsData.data.data || clinicsData.data || []);
    } catch (error) {
      console.error('Error fetching filter data:', error);
    }
  }, [API_URL]);

  const fetchCities = useCallback(async (provinceId) => {
    if (provinceId === 'all') { setCities([]); return; }
    try {
      const res = await fetch(`${API_URL}/api/clinics/provinces/${provinceId}/cities`);
      const data = await res.json();
      if (data.success) setCities(data.data || []);
    } catch (error) {
      console.error('Error fetching cities:', error);
    }
  }, [API_URL]);

  useEffect(() => {
    fetchPharmacies();
    fetchFilterData();
  }, [fetchPharmacies, fetchFilterData]);

  useEffect(() => {
    fetchCities(selectedProvince);
  }, [selectedProvince, fetchCities]);

  const handleProvinceChange = (value) => {
    setSelectedProvince(value);
    setSelectedCity('all');
  };

  const handleSearch = () => {
    setCurrentPage(1);
    fetchPharmacies();
  };

  if (loading && pharmacies.length === 0) {
    return <><Header /><LoadingSpinner /><Footer /></>;
  }

  return (
      <>
        <Header />
        <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc', padding: '24px 20px' }}>
          <div style={{ maxWidth: '1200px', margin: '0 auto' }}>

            {/* مسیر راهنما - دستی بدون Breadcrumb */}
            <div style={{ marginBottom: 8 }}>
              <Text type="secondary">
                <a href="" style={{ color: '#2563eb' }}>خانه</a>
                <span> / داروخانه</span>
              </Text>
            </div>

            <div style={{ marginBottom: '24px' }}>
              <Title level={2}>🏥 داروخانه</Title>
              <Text type="secondary">خرید آنلاین دارو با نسخه الکترونیک</Text>
            </div>

            {/* فیلترها */}
            <Card style={{ borderRadius: '16px', marginBottom: '24px' }}>
              <Row gutter={[16, 16]}>
                <Col xs={24} md={6}>
                  <Input
                      placeholder="جستجوی داروخانه..."
                      prefix={<SearchOutlined />}
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      onPressEnter={handleSearch}
                      allowClear
                  />
                </Col>
                <Col xs={12} md={4}>
                  <Select
                      placeholder="انتخاب استان"
                      style={{ width: '100%' }}
                      value={selectedProvince}
                      onChange={handleProvinceChange}
                      allowClear
                  >
                    <Option value="all">همه استان‌ها</Option>
                    {provinces.map(p => <Option key={p.id} value={p.id}>{p.name}</Option>)}
                  </Select>
                </Col>
                <Col xs={12} md={4}>
                  <Select
                      placeholder="انتخاب شهر"
                      style={{ width: '100%' }}
                      value={selectedCity}
                      onChange={setSelectedCity}
                      disabled={selectedProvince === 'all'}
                      allowClear
                  >
                    <Option value="all">همه شهرها</Option>
                    {cities.map(c => <Option key={c.id} value={c.id}>{c.name}</Option>)}
                  </Select>
                </Col>
                <Col xs={12} md={4}>
                  <Select
                      placeholder="انتخاب کلینیک"
                      style={{ width: '100%' }}
                      value={selectedClinic}
                      onChange={setSelectedClinic}
                      allowClear
                  >
                    <Option value="all">همه کلینیک‌ها</Option>
                    {clinics.map(c => <Option key={c.id} value={c.id}>{c.name}</Option>)}
                  </Select>
                </Col>
                <Col xs={12} md={4}>
                  <Select
                      placeholder="وضعیت"
                      style={{ width: '100%' }}
                      value={isOnline}
                      onChange={setIsOnline}
                  >
                    <Option value="all">همه وضعیت‌ها</Option>
                    <Option value="true">آنلاین</Option>
                    <Option value="false">آفلاین</Option>
                  </Select>
                </Col>
                <Col xs={24} md={2}>
                  <Button type="primary" block onClick={handleSearch}>جستجو</Button>
                </Col>
              </Row>
            </Card>

            <div style={{ marginBottom: '16px' }}>
              <Text type="secondary">{totalItems} داروخانه یافت شد</Text>
            </div>

            {pharmacies.length > 0 ? (
                <>
                  <Row gutter={[16, 16]}>
                    {pharmacies.map((pharmacy) => (
                        <Col xs={24} sm={12} md={8} lg={6} key={pharmacy.id}>
                          <Card
                              hoverable
                              style={{ borderRadius: '12px', height: '100%' }}
                              onClick={() => router.push(`/pharmacy/${pharmacy.id}`)}
                              cover={
                                <div style={{
                                  height: 140,
                                  background: 'linear-gradient(135deg, #f0f5ff, #e0e7ff)',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  position: 'relative',
                                  borderRadius: '12px 12px 0 0'
                                }}>
                                  {pharmacy.logo_url ? (
                                      <Image src={pharmacy.logo_url} alt={pharmacy.name} width={80} height={80} preview={false} style={{ borderRadius: '50%', objectFit: 'cover' }} />
                                  ) : (
                                      <ShopOutlined style={{ fontSize: 56, color: '#2563eb' }} />
                                  )}
                                  <div style={{
                                    position: 'absolute', top: 8, right: 8,
                                    width: 12, height: 12, borderRadius: '50%',
                                    background: pharmacy.is_online ? '#22c55e' : '#9ca3af',
                                    border: '2px solid white'
                                  }} />
                                </div>
                              }
                          >
                            <Tooltip title={pharmacy.name}>
                              <Text strong style={{ fontSize: '15px', display: 'block' }}>
                                {pharmacy.name.length > 22 ? pharmacy.name.substring(0, 22) + '...' : pharmacy.name}
                              </Text>
                            </Tooltip>
                            <Text type="secondary" style={{ fontSize: '12px' }}>
                              <EnvironmentOutlined /> {pharmacy.city?.name}, {pharmacy.province?.name}
                            </Text>
                            {pharmacy.clinic && (
                                <div><Text type="secondary" style={{ fontSize: '12px' }}><ShopOutlined /> {pharmacy.clinic.name}</Text></div>
                            )}
                            <div><Text type="secondary" style={{ fontSize: '12px' }}><PhoneOutlined /> {pharmacy.phone || '—'}</Text></div>
                            <div style={{ marginTop: 8 }}>
                              <Tag color={pharmacy.is_online ? 'success' : 'default'}>{pharmacy.is_online ? 'آنلاین' : 'آفلاین'}</Tag>
                              <Tag color="green">فعال</Tag>
                            </div>
                          </Card>
                        </Col>
                    ))}
                  </Row>
                  <div style={{ marginTop: 32, textAlign: 'center' }}>
                    <Pagination
                        current={currentPage}
                        total={totalItems}
                        pageSize={pageSize}
                        onChange={(page) => setCurrentPage(page)}
                        showSizeChanger
                        onShowSizeChange={(_, size) => { setPageSize(size); setCurrentPage(1); }}
                    />
                  </div>
                </>
            ) : (
                <Empty description="هیچ داروخانه‌ای یافت نشد">
                  <Button type="primary" onClick={() => {
                    setSearchTerm('');
                    setSelectedProvince('all');
                    setSelectedCity('all');
                    setSelectedClinic('all');
                    setIsOnline('all');
                    setCurrentPage(1);
                    fetchPharmacies();
                  }}>بازنشانی فیلترها</Button>
                </Empty>
            )}
          </div>
        </main>
        <Footer />
      </>
  );
}