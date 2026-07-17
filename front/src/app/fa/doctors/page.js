// /home/god/Videos/medikal/front/src/app/fa/doctors/page.js
'use client';

import { useState, useEffect, useCallback } from 'react';
import { Card, Row, Col, Button, Typography, Spin, Tag, Input, Select, Empty, App, Avatar, Rate, Space, Divider } from 'antd';
import { SearchOutlined, CalendarOutlined, StarOutlined, EnvironmentOutlined, PhoneOutlined, HeartOutlined, HeartFilled, FilterOutlined, SortAscendingOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';

const { Title, Text } = Typography;
const { Search } = Input;

export default function DoctorsPage() {
  const router = useRouter();
  const { locale } = useLanguage();
  const { message: appMessage } = App.useApp();

  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [specialtyFilter, setSpecialtyFilter] = useState(null);
  const [specialties, setSpecialties] = useState([]);
  const [favorites, setFavorites] = useState([]);
  const [sortBy, setSortBy] = useState('rating');

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const fetchDoctors = useCallback(async () => {
    setLoading(true);
    try {
      const url = `${API_URL}/api/doctors/public`;
      const res = await fetch(url, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();

      if (data.success) {
        let doctorsData = data.data?.data || [];
        
        if (specialtyFilter) {
          doctorsData = doctorsData.filter(d => d.specialty_id === specialtyFilter);
        }
        
        if (searchTerm) {
          doctorsData = doctorsData.filter(d => 
            d.user?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            d.specialty?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            d.clinic_name?.toLowerCase().includes(searchTerm.toLowerCase())
          );
        }
        
        doctorsData.sort((a, b) => {
          if (sortBy === 'rating') return (parseFloat(b.rating) || 0) - (parseFloat(a.rating) || 0);
          if (sortBy === 'fee') return (parseFloat(a.consultation_fee) || 0) - (parseFloat(b.consultation_fee) || 0);
          if (sortBy === 'experience') return (b.experience || 0) - (a.experience || 0);
          return 0;
        });
        
        setDoctors(doctorsData);
      } else {
        appMessage.error(data.message || 'خطا در دریافت لیست پزشکان');
        setDoctors([]);
      }
    } catch (error) {
      console.error('❌ Fetch error:', error);
      appMessage.error('خطا در ارتباط با سرور');
      setDoctors([]);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, specialtyFilter, sortBy, API_URL, appMessage]);

  const fetchSpecialties = async () => {
    try {
      const res = await fetch(`${API_URL}/api/specialties`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setSpecialties(Array.isArray(data.data) ? data.data : []);
      }
    } catch (error) {
      console.error('Error fetching specialties:', error);
    }
  };

  useEffect(() => {
    fetchDoctors();
    fetchSpecialties();
    const savedFavorites = JSON.parse(localStorage.getItem('favoriteDoctors') || '[]');
    setFavorites(savedFavorites);
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchDoctors();
    }, 300);
    return () => clearTimeout(timer);
  }, [searchTerm, specialtyFilter, sortBy, fetchDoctors]);

  const toggleFavorite = (doctorId) => {
    let newFavorites;
    if (favorites.includes(doctorId)) {
      newFavorites = favorites.filter(id => id !== doctorId);
    } else {
      newFavorites = [...favorites, doctorId];
    }
    setFavorites(newFavorites);
    localStorage.setItem('favoriteDoctors', JSON.stringify(newFavorites));
  };

  const handleSearch = (value) => {
    setSearchTerm(value);
  };

  const handleSpecialtyChange = (value) => {
    setSpecialtyFilter(value);
  };

  const handleSortChange = (value) => {
    setSortBy(value);
  };

  const handleBookAppointment = (doctorId) => {
    if (!doctorId) {
      appMessage.error('شناسه پزشک نامعتبر است');
      return;
    }
    localStorage.setItem('selectedDoctorId', String(doctorId));
    router.push(`/${locale}/appointments/new?doctorId=${doctorId}`);
  };

  if (loading) {
    return (
      <>
        <Header />
        <div style={{ textAlign: 'center', padding: '60px 20px' }}>
          <Spin size="large" />
          <p style={{ marginTop: '16px', color: '#94a3b8' }}>در حال بارگذاری پزشکان...</p>
        </div>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main style={{ 
        backgroundImage: "url('/image/bac-1.png')",
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        backgroundAttachment: 'fixed',
        minHeight: 'calc(100vh - 200px)',
        position: 'relative'
      }}>
        {/* اوورلی نیمه شفاف برای خوانایی بهتر */}
        <div style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(255,255,255,0.85)',
          zIndex: 0
        }} />
        
        <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px', position: 'relative', zIndex: 1 }}>
          <Breadcrumb />

          <div style={{ textAlign: 'center', marginBottom: '32px' }}>
            <Title level={2} style={{ marginBottom: '4px', fontSize: '32px' }}>
              👨‍⚕️ پزشکان متخصص
            </Title>
            <Text type="secondary" style={{ fontSize: '16px' }}>
              بهترین پزشکان را بر اساس تخصص و امتیاز انتخاب کنید
            </Text>
          </div>

          {/* فیلترها */}
          <Card style={{ 
            borderRadius: '16px', 
            marginBottom: '24px', 
            border: 'none', 
            boxShadow: '0 4px 20px rgba(0,0,0,0.04)',
            background: 'rgba(255,255,255,0.92)',
            backdropFilter: 'blur(10px)'
          }}>
            <Row gutter={[16, 16]} align="middle">
              <Col xs={24} md={8}>
                <Search
                  placeholder="جستجوی پزشک، تخصص، مطب..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onSearch={handleSearch}
                  size="large"
                  enterButton={<Button type="primary" icon={<SearchOutlined />}>جستجو</Button>}
                  style={{ borderRadius: '12px' }}
                  allowClear
                />
              </Col>
              <Col xs={12} md={5}>
                <Select
                  placeholder="انتخاب تخصص"
                  style={{ width: '100%' }}
                  allowClear
                  value={specialtyFilter}
                  onChange={handleSpecialtyChange}
                  size="large"
                  suffixIcon={<FilterOutlined />}
                >
                  <Select.Option value={null}>همه تخصص‌ها</Select.Option>
                  {specialties.map((item) => (
                    <Select.Option key={item.id} value={item.id}>
                      {item.name}
                    </Select.Option>
                  ))}
                </Select>
              </Col>
              <Col xs={12} md={5}>
                <Select
                  placeholder="مرتب‌سازی"
                  style={{ width: '100%' }}
                  value={sortBy}
                  onChange={handleSortChange}
                  size="large"
                  suffixIcon={<SortAscendingOutlined />}
                >
                  <Select.Option value="rating">⭐ بیشترین امتیاز</Select.Option>
                  <Select.Option value="fee">💰 کمترین هزینه</Select.Option>
                  <Select.Option value="experience">👨‍⚕️ بیشترین سابقه</Select.Option>
                </Select>
              </Col>
              <Col xs={24} md={6}>
                <Button 
                  onClick={fetchDoctors} 
                  size="large" 
                  block
                  style={{ borderRadius: '12px', height: '44px' }}
                >
                  بروزرسانی لیست
                </Button>
              </Col>
            </Row>
          </Card>

          {/* تعداد نتایج */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <Text type="secondary">
              <strong style={{ color: '#0f172a', fontSize: '18px' }}>{doctors.length}</strong> پزشک یافت شد
            </Text>
          </div>

          {/* لیست پزشکان */}
          <Row gutter={[24, 24]}>
            {doctors.length === 0 ? (
              <Col span={24}>
                <Card style={{ borderRadius: '16px', background: 'rgba(255,255,255,0.92)' }}>
                  <Empty 
                    description="هیچ پزشکی یافت نشد" 
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                  >
                    <Button type="primary" onClick={() => {
                      setSearchTerm('');
                      setSpecialtyFilter(null);
                      fetchDoctors();
                    }}>
                      بازنشانی فیلترها
                    </Button>
                  </Empty>
                </Card>
              </Col>
            ) : (
              doctors.map((doctor) => {
                const isFavorite = favorites.includes(doctor.id);
                const rating = parseFloat(doctor.rating) || 0;
                const fee = parseFloat(doctor.consultation_fee) || 0;
                const isAvailable = doctor.is_available !== false;

                return (
                  <Col xs={24} md={12} lg={8} xl={6} key={doctor.id}>
                    <Card
                      hoverable
                      className="doctor-card-modern"
                      style={{
                        borderRadius: '20px',
                        height: '100%',
                        border: 'none',
                        boxShadow: '0 4px 20px rgba(0,0,0,0.04)',
                        transition: 'all 0.3s ease',
                        overflow: 'hidden',
                        position: 'relative',
                        background: 'rgba(255,255,255,0.95)',
                        backdropFilter: 'blur(10px)'
                      }}
                      bodyStyle={{ padding: '20px' }}
                    >
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          toggleFavorite(doctor.id);
                        }}
                        style={{
                          position: 'absolute',
                          top: '12px',
                          right: '12px',
                          zIndex: 10,
                          background: 'rgba(255,255,255,0.9)',
                          border: 'none',
                          borderRadius: '50%',
                          width: '36px',
                          height: '36px',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          cursor: 'pointer',
                          backdropFilter: 'blur(10px)',
                          boxShadow: '0 2px 8px rgba(0,0,0,0.06)'
                        }}
                      >
                        {isFavorite ? (
                          <HeartFilled style={{ color: '#ef4444', fontSize: '18px' }} />
                        ) : (
                          <HeartOutlined style={{ color: '#94a3b8', fontSize: '18px' }} />
                        )}
                      </button>

                      {rating >= 4.8 && (
                        <div style={{
                          position: 'absolute',
                          top: '12px',
                          left: '12px',
                          zIndex: 10,
                          background: 'linear-gradient(135deg, #f59e0b, #fbbf24)',
                          color: '#78350f',
                          padding: '3px 12px',
                          borderRadius: '50px',
                          fontSize: '10px',
                          fontWeight: '700',
                          boxShadow: '0 4px 12px rgba(245,158,11,0.3)'
                        }}>
                          ⭐ ویژه
                        </div>
                      )}

                      <div style={{ textAlign: 'center', marginBottom: '16px' }}>
                        <div style={{
                          position: 'relative',
                          display: 'inline-block'
                        }}>
                          <Avatar
                            size={80}
                            src={doctor.profile_image}
                            style={{
                              background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                              fontSize: '32px',
                              border: '3px solid #fff',
                              boxShadow: '0 4px 16px rgba(37,99,235,0.15)'
                            }}
                          >
                            {doctor.user?.name?.charAt(0) || 'د'}
                          </Avatar>
                          <div style={{
                            position: 'absolute',
                            bottom: '2px',
                            right: '2px',
                            width: '16px',
                            height: '16px',
                            borderRadius: '50%',
                            background: isAvailable ? '#10b981' : '#ef4444',
                            border: '2px solid #fff',
                            boxShadow: isAvailable ? '0 0 0 3px rgba(16,185,129,0.2)' : '0 0 0 3px rgba(239,68,68,0.2)'
                          }} />
                        </div>
                      </div>

                      <div style={{ textAlign: 'center' }}>
                        <h3 style={{
                          fontSize: '18px',
                          fontWeight: '700',
                          color: '#0f172a',
                          margin: '0 0 4px 0'
                        }}>
                          {doctor.user?.name || 'پزشک'}
                        </h3>
                        <Text type="secondary" style={{ fontSize: '14px', color: '#2563eb', fontWeight: '500' }}>
                          {doctor.specialty?.name || 'عمومی'}
                        </Text>
                        
                        {doctor.clinic_name && (
                          <div style={{ marginTop: '4px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '4px' }}>
                            <EnvironmentOutlined style={{ fontSize: '12px', color: '#94a3b8' }} />
                            <Text type="secondary" style={{ fontSize: '13px' }}>
                              {doctor.clinic_name}
                            </Text>
                          </div>
                        )}
                      </div>

                      <Divider style={{ margin: '12px 0' }} />

                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px' }}>
                        <Space size="small">
                          <Rate disabled defaultValue={rating} allowHalf style={{ fontSize: '14px', color: '#f59e0b' }} />
                          <Text strong style={{ fontSize: '14px', color: '#0f172a' }}>{rating.toFixed(1)}</Text>
                        </Space>
                        <Text type="secondary" style={{ fontSize: '13px' }}>
                          <i className="fas fa-comment" style={{ marginLeft: '4px' }} />
                          {doctor.total_reviews || 0} نظر
                        </Text>
                      </div>

                      <div style={{
                        background: '#f8fafc',
                        borderRadius: '12px',
                        padding: '8px 16px',
                        textAlign: 'center',
                        marginBottom: '12px'
                      }}>
                        <Text strong style={{ fontSize: '20px', color: '#2563eb' }}>
                          {fee.toLocaleString()}
                        </Text>
                        <Text type="secondary" style={{ fontSize: '14px' }}> تومان</Text>
                      </div>

                      <Space direction="vertical" style={{ width: '100%' }} size="small">
                        <Button
                          type="primary"
                          size="large"
                          icon={<CalendarOutlined />}
                          onClick={() => handleBookAppointment(doctor.id)}
                          style={{
                            borderRadius: '12px',
                            height: '44px',
                            fontWeight: '600',
                            background: 'linear-gradient(135deg, #2563eb, #3b82f6)',
                            border: 'none',
                            width: '100%'
                          }}
                        >
                          رزرو نوبت
                        </Button>
                        <Button
                          size="large"
                          onClick={() => router.push(`/${locale}/doctors/${doctor.id}`)}
                          style={{
                            borderRadius: '12px',
                            height: '40px',
                            borderColor: '#e2e8f0',
                            color: '#475569',
                            width: '100%'
                          }}
                        >
                          مشاهده پروفایل
                        </Button>
                      </Space>

                      <div style={{ marginTop: '8px', display: 'flex', justifyContent: 'center', gap: '4px', flexWrap: 'wrap' }}>
                        {doctor.is_available !== undefined && (
                          <Tag color={isAvailable ? 'green' : 'red'} style={{ borderRadius: '50px', fontSize: '11px' }}>
                            {isAvailable ? '🟢 فعال' : '🔴 غیرفعال'}
                          </Tag>
                        )}
                        {doctor.experience && doctor.experience > 10 && (
                          <Tag color="blue" style={{ borderRadius: '50px', fontSize: '11px' }}>
                            👨‍⚕️ {doctor.experience} سال سابقه
                          </Tag>
                        )}
                      </div>
                    </Card>
                  </Col>
                );
              })
            )}
          </Row>

          {doctors.length > 0 && (
            <div style={{ textAlign: 'center', marginTop: '48px', padding: '20px' }}>
              <Text type="secondary">
                {doctors.length} پزشک متخصص آماده ارائه خدمت به شما هستند
              </Text>
            </div>
          )}
        </div>
      </main>
      <Footer />

      <style jsx>{`
    .doctor-card-modern {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.doctor-card-modern:hover {
  transform: translateY(-8px) scale(1.01) !important;
  box-shadow: 0 20px 60px rgba(37, 99, 235, 0.12) !important;
}

.doctor-card-modern .ant-card-body {
  padding: 20px !important;
}

.doctor-card-modern .ant-btn-primary {
  background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
  border: none !important;
}

.doctor-card-modern .ant-btn-primary:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3) !important;
}

@media (max-width: 768px) {
.doctor-card-modern .ant-card-body {
    padding: 16px !important;
  }
}
`}</style>
    </>
  );
}
