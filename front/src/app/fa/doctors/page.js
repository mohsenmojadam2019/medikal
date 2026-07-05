'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Button, Typography, Spin, Empty, Tag, Rate, Pagination, 
  message, Space, Input
} from 'antd';
import { EnvironmentOutlined, SearchOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function DoctorsPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filteredDoctors, setFilteredDoctors] = useState([]);
  const [searchText, setSearchText] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(9);
  const [totalDoctors, setTotalDoctors] = useState(0);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  // دریافت لیست پزشکان از API
  const fetchDoctors = async (page = 1) => {
    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/doctors/public?page=${page}&per_page=${pageSize}`, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });
      
      const data = await res.json();
      
      if (data.success) {
        const doctorsData = data.data.data || [];
        setDoctors(doctorsData);
        setFilteredDoctors(doctorsData);
        setTotalDoctors(data.data.total || 0);
      } else {
        message.error(data.message || 'خطا در دریافت لیست پزشکان');
      }
    } catch (error) {
      console.error('Error fetching doctors:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDoctors();
  }, []);

  // فیلتر کردن پزشکان
  useEffect(() => {
    let filtered = doctors;
    
    if (searchText) {
      filtered = filtered.filter(doc => 
        doc.full_name?.toLowerCase().includes(searchText.toLowerCase()) ||
        doc.specialty?.name?.toLowerCase().includes(searchText.toLowerCase()) ||
        doc.clinic_name?.toLowerCase().includes(searchText.toLowerCase())
      );
    }
    
    setFilteredDoctors(filtered);
    setCurrentPage(1);
  }, [searchText, doctors]);

  const handleBookAppointment = (doctorId) => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    router.push(`/${locale}/appointments/new?doctorId=${doctorId}`);
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    fetchDoctors(page);
  };

  if (loading) {
    return (
      <>
        <Header />
        <LoadingSpinner />
        <Footer />
      </>
    );
  }

  const paginatedDoctors = filteredDoctors;

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />
          
          <div style={{ marginBottom: '32px' }}>
            <Title level={2}>👨‍⚕️ {t('doctors.title')}</Title>
            <Text type="secondary">{t('doctors.subtitle')}</Text>
          </div>

          {/* جستجو */}
          <div style={{ marginBottom: '24px' }}>
            <Input.Search
              placeholder={t('doctors.search')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              enterButton
              size="large"
            />
          </div>

          <div style={{ marginTop: '16px' }}>
            <Text type="secondary">
              {filteredDoctors.length} پزشک یافت شد
            </Text>
          </div>

          {paginatedDoctors.length > 0 ? (
            <>
              <Row gutter={[16, 16]} style={{ marginTop: '16px' }}>
                {paginatedDoctors.map((doctor) => (
                  <Col xs={24} sm={12} lg={8} key={doctor.id}>
                    <Card
                      hoverable
                      style={{ borderRadius: '12px', height: '100%' }}
                      cover={
                        <div style={{ 
                          height: '160px', 
                          background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontSize: '64px',
                          color: 'white'
                        }}>
                          {doctor.full_name?.charAt(0) || '👨‍⚕️'}
                        </div>
                      }
                      actions={[
                        <Button 
                          type="primary" 
                          onClick={() => handleBookAppointment(doctor.id)}
                          key="book"
                        >
                          {t('doctors.bookAppointment')}
                        </Button>,
                        <Button 
                          type="link" 
                          onClick={() => router.push(`/${locale}/doctors/${doctor.id}`)}
                          key="profile"
                        >
                          {t('doctors.viewProfile')}
                        </Button>,
                      ]}
                    >
                      <Card.Meta
                        title={
                          <Space>
                            <Text strong>{doctor.full_name || 'پزشک'}</Text>
                            {doctor.is_available && <Tag color="success">{t('doctors.hasAppointment')}</Tag>}
                          </Space>
                        }
                        description={
                          <div>
                            <Text type="secondary">{doctor.specialty?.name || 'تخصص'}</Text>
                            <br />
                            <Text type="secondary" style={{ fontSize: '12px' }}>
                              <EnvironmentOutlined /> {doctor.clinic_name || 'آدرس مطب'}
                            </Text>
                            <div style={{ marginTop: '8px' }}>
                              <Rate disabled defaultValue={doctor.rating || 0} allowHalf style={{ fontSize: '14px' }} />
                              <span style={{ marginLeft: '8px', fontSize: '12px', color: '#94a3b8' }}>
                                ({doctor.total_reviews || 0} {t('doctors.reviews')})
                              </span>
                            </div>
                            <div style={{ marginTop: '8px' }}>
                              <Text strong style={{ fontSize: '16px', color: '#2563eb' }}>
                                {parseInt(doctor.consultation_fee || 0).toLocaleString()}
                              </Text>
                              <Text type="secondary" style={{ fontSize: '12px' }}> {t('doctors.fee')}</Text>
                            </div>
                          </div>
                        }
                      />
                    </Card>
                  </Col>
                ))}
              </Row>

              <Pagination
                current={currentPage}
                total={totalDoctors}
                pageSize={pageSize}
                onChange={handlePageChange}
                showSizeChanger
                onShowSizeChange={(current, size) => {
                  setPageSize(size);
                  setCurrentPage(1);
                  fetchDoctors(1);
                }}
                style={{ marginTop: '32px', textAlign: 'center' }}
              />
            </>
          ) : (
            <Empty description={t('doctors.noDoctors')} />
          )}
        </div>
      </main>
      <Footer />
    </>
  );
}
