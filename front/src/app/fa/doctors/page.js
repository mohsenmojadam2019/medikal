// /home/god/Videos/medikal/front/src/app/fa/doctors/page.js
'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Button, Typography, Spin, Tag, Input, Select, Empty, App, Avatar } from 'antd';
import { SearchOutlined, CalendarOutlined, StarOutlined } from '@ant-design/icons';
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

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  useEffect(() => {
    fetchDoctors();
    fetchSpecialties();
  }, []);

  const fetchDoctors = async () => {
    setLoading(true);
    try {
      // ✅ استفاده از مسیر عمومی که در API وجود دارد
      const url = `${API_URL}/api/doctors/public`;

      console.log('🌐 Fetching doctors from:', url);

      const res = await fetch(url, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      console.log('📡 Response status:', res.status);

      const data = await res.json();
      console.log('📦 Response data:', data);

      if (data.success) {
        // ✅ داده داخل data.data.data است چون paginate شده
        const doctorsData = data.data?.data || [];
        console.log('👨‍⚕️ Doctors count:', doctorsData.length);
        setDoctors(doctorsData);
      } else {
        console.error('❌ API Error:', data.message);
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
  };

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

  const handleSearch = () => {
    fetchDoctors();
  };

  const handleSpecialtyChange = (value) => {
    setSpecialtyFilter(value);
    setTimeout(fetchDoctors, 100);
  };

  // ✅ تابع هدایت به صفحه رزرو با doctorId
  const handleBookAppointment = (doctorId) => {
    console.log('🩺 Booking appointment for doctor ID:', doctorId);

    if (!doctorId) {
      appMessage.error('شناسه پزشک نامعتبر است');
      return;
    }

    // ✅ ذخیره در localStorage
    localStorage.setItem('selectedDoctorId', String(doctorId));

    // ✅ ساخت URL با doctorId
    const url = `/${locale}/appointments/new?doctorId=${doctorId}`;
    console.log('🔗 Redirecting to:', url);

    // ✅ هدایت
    router.push(url);
  };

  if (loading) {
    return (
        <>
          <Header />
          <div style={{ textAlign: 'center', padding: '60px 20px' }}>
            <Spin size="large" />
          </div>
          <Footer />
        </>
    );
  }

  return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb />

            <Title level={2} style={{ marginBottom: '4px' }}>👨‍⚕️ پزشکان</Title>
            <Text type="secondary">لیست پزشکان متخصص را مشاهده و نوبت خود را رزرو کنید</Text>

            {/* فیلترها */}
            <div style={{ marginTop: '20px', display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
              <Search
                  placeholder="جستجوی نام پزشک..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onSearch={handleSearch}
                  style={{ width: 300 }}
                  enterButton
              />
              <Select
                  placeholder="انتخاب تخصص"
                  style={{ width: 200 }}
                  allowClear
                  onChange={handleSpecialtyChange}
              >
                {specialties.map((item) => (
                    <Select.Option key={item.id} value={item.id}>
                      {item.name}
                    </Select.Option>
                ))}
              </Select>
              <Button onClick={fetchDoctors}>بروزرسانی</Button>
            </div>

            {/* لیست پزشکان */}
            <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
              {doctors.length === 0 ? (
                  <Col span={24}>
                    <Empty description="هیچ پزشکی یافت نشد" />
                  </Col>
              ) : (
                  doctors.map((doctor) => (
                      <Col xs={24} md={12} lg={8} key={doctor.id}>
                        <Card
                            hoverable
                            style={{ borderRadius: '16px', height: '100%' }}
                            cover={
                              <div style={{
                                height: '100px',
                                background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: 'white',
                                fontSize: '48px',
                              }}>
                                👨‍⚕️
                              </div>
                            }
                            actions={[
                              <Button
                                  key="book"
                                  type="primary"
                                  icon={<CalendarOutlined />}
                                  onClick={() => {
                                    console.log('🖱️ Button clicked for doctor:', doctor.id, doctor.user?.name);
                                    handleBookAppointment(doctor.id);
                                  }}
                                  style={{ borderRadius: '20px' }}
                              >
                                رزرو نوبت
                              </Button>,
                              <Button
                                  key="profile"
                                  type="link"
                                  onClick={() => router.push(`/${locale}/doctors/${doctor.id}`)}
                              >
                                مشاهده پروفایل
                              </Button>
                            ]}
                        >
                          <Card.Meta
                              avatar={
                                <Avatar
                                    size={56}
                                    src={doctor.profile_image}
                                    style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}
                                >
                                  {doctor.user?.name?.charAt(0) || 'د'}
                                </Avatar>
                              }
                              title={
                                <div>
                                  <Text strong>{doctor.user?.name || 'پزشک'}</Text>
                                  <div>
                                    <Tag color="blue">{doctor.specialty?.name || 'عمومی'}</Tag>
                                    {doctor.rating > 0 && (
                                        <Tag color="gold">
                                          <StarOutlined /> {doctor.rating}
                                        </Tag>
                                    )}
                                  </div>
                                </div>
                              }
                              description={
                                <div>
                                  <Text type="secondary" style={{ fontSize: '12px' }}>
                                    💰 هزینه ویزیت: {parseFloat(doctor.consultation_fee || 0).toLocaleString()} تومان
                                  </Text>
                                  <br />
                                  <Text type="secondary" style={{ fontSize: '12px' }}>
                                    📊 {doctor.total_reviews || 0} نظر
                                  </Text>
                                  {doctor.is_available !== undefined && (
                                      <div>
                                        <br />
                                        <Tag color={doctor.is_available ? 'green' : 'red'}>
                                          {doctor.is_available ? 'فعال' : 'غیرفعال'}
                                        </Tag>
                                      </div>
                                  )}
                                </div>
                              }
                          />
                        </Card>
                      </Col>
                  ))
              )}
            </Row>
          </div>
        </main>
        <Footer />
      </>
  );
}