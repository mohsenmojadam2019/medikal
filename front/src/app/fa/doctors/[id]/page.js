'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Button, Typography, Rate, Tag, Spin, Descriptions, Divider, Tabs, List, Avatar, Space, message, Statistic } from 'antd';
import { 
  CalendarOutlined, EnvironmentOutlined, PhoneOutlined, 
  MailOutlined, UserOutlined,
  ArrowLeftOutlined, ClockCircleOutlined
} from '@ant-design/icons';
import { useRouter, useParams } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text, Paragraph } = Typography;
const { TabPane } = Tabs;

export default function DoctorDetailPage() {
  const router = useRouter();
  const params = useParams();
  const { t, locale } = useLanguage();
  const [doctor, setDoctor] = useState(null);
  const [loading, setLoading] = useState(true);
  const [reviews, setReviews] = useState([]);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const doctorId = params?.id;

  const getToken = () => localStorage.getItem('token');

  // دریافت اطلاعات پزشک از API
  const fetchDoctor = async () => {
    try {
      const res = await fetch(`${API_URL}/api/doctors/${doctorId}/public`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setDoctor(data.data);
      } else {
        message.error(data.message || 'خطا در دریافت اطلاعات پزشک');
      }
    } catch (error) {
      console.error('Error fetching doctor:', error);
      message.error('خطا در ارتباط با سرور');
    }
  };

  // دریافت نظرات پزشک از API
  const fetchReviews = async () => {
    try {
      const res = await fetch(`${API_URL}/api/ratings/doctors/${doctorId}`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setReviews(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching reviews:', error);
    }
  };

  useEffect(() => {
    if (doctorId) {
      setLoading(true);
      Promise.all([
        fetchDoctor(),
        fetchReviews(),
      ]).finally(() => setLoading(false));
    }
  }, [doctorId]);

  const handleBookAppointment = () => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    router.push(`/${locale}/appointments/new?doctorId=${doctorId}`);
  };

  if (loading) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
          <Spin size="large" />
          <p style={{ marginTop: '16px' }}>{t('common.loading')}</p>
        </div>
        <Footer />
      </>
    );
  }

  if (!doctor) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
          <Title level={4}>پزشک یافت نشد</Title>
          <Button type="primary" onClick={() => router.push(`/${locale}/doctors`)}>
            {t('common.backToHome')}
          </Button>
        </div>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Button 
            type="link" 
            icon={<ArrowLeftOutlined />} 
            onClick={() => router.push(`/${locale}/doctors`)}
            style={{ marginBottom: '16px' }}
          >
            بازگشت به لیست پزشکان
          </Button>

          <Row gutter={[24, 24]}>
            {/* اطلاعات اصلی پزشک */}
            <Col xs={24} lg={16}>
              <Card style={{ borderRadius: '16px' }}>
                <Row gutter={[24, 24]}>
                  <Col xs={24} sm={8} style={{ textAlign: 'center' }}>
                    <Avatar
                      size={120}
                      icon={<UserOutlined />}
                      style={{ 
                        background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                        fontSize: '48px'
                      }}
                    />
                    <div style={{ marginTop: '16px' }}>
                      <Tag color={doctor.is_available ? 'success' : 'error'}>
                        {doctor.is_available ? 'نوبت دارد' : 'نوبت محدود'}
                      </Tag>
                    </div>
                  </Col>
                  <Col xs={24} sm={16}>
                    <Title level={2}>{doctor.full_name}</Title>
                    <Text type="secondary" style={{ fontSize: '16px' }}>
                      {doctor.specialty?.name}
                    </Text>
                    <div style={{ marginTop: '8px' }}>
                      <Rate disabled defaultValue={doctor.rating || 0} allowHalf />
                      <Text style={{ marginLeft: '8px' }}>
                        ({doctor.reviews_count || 0} نظر)
                      </Text>
                    </div>
                    <div style={{ marginTop: '12px' }}>
                      <Space direction="vertical">
                        <Text><EnvironmentOutlined /> {doctor.clinic_name || 'آدرس مطب'}</Text>
                        <Text><PhoneOutlined /> {doctor.phone || '۰۲۱-۱۲۳۴۵۶۷۸'}</Text>
                        <Text><MailOutlined /> {doctor.email || 'info@clinic.com'}</Text>
                      </Space>
                    </div>
                  </Col>
                </Row>

                <Divider />

                <div>
                  <Title level={4}>درباره پزشک</Title>
                  <Paragraph>
                    {doctor.bio || 'توضیحاتی درباره پزشک در اینجا قرار می‌گیرد.'}
                  </Paragraph>
                </div>

                <div style={{ marginTop: '16px' }}>
                  <Row gutter={[16, 16]}>
                    <Col span={8}>
                      <Statistic title="سابقه کار" value={`${doctor.experience || 0} سال`} />
                    </Col>
                    <Col span={8}>
                      <Statistic title="تعداد بیماران" value={doctor.patients_count || 0} />
                    </Col>
                    <Col span={8}>
                      <Statistic title="تعداد نوبت‌ها" value={doctor.appointments_count || 0} />
                    </Col>
                  </Row>
                </div>
              </Card>
            </Col>

            {/* اطلاعات جانبی */}
            <Col xs={24} lg={8}>
              <Card style={{ borderRadius: '16px', marginBottom: '16px' }}>
                <div style={{ textAlign: 'center' }}>
                  <Title level={3}>
                    {doctor.consultation_fee?.toLocaleString() || 0}
                    <Text type="secondary" style={{ fontSize: '16px' }}> تومان</Text>
                  </Title>
                  <Text type="secondary">هزینه ویزیت</Text>
                </div>
                <Divider />
                <Button 
                  type="primary" 
                  size="large" 
                  block 
                  onClick={handleBookAppointment}
                  icon={<CalendarOutlined />}
                >
                  رزرو نوبت
                </Button>
              </Card>

              <Card style={{ borderRadius: '16px' }}>
                <Title level={4}>ساعات کاری</Title>
                {doctor.working_hours ? (
                  <Descriptions column={1}>
                    {Object.entries(doctor.working_hours).map(([day, hours]) => (
                      <Descriptions.Item key={day} label={day}>
                        {hours || 'تعطیل'}
                      </Descriptions.Item>
                    ))}
                  </Descriptions>
                ) : (
                  <Text type="secondary">ساعات کاری ثبت نشده است</Text>
                )}
              </Card>
            </Col>
          </Row>

          {/* نظرات بیماران */}
          <Card style={{ borderRadius: '16px', marginTop: '24px' }}>
            <Tabs defaultActiveKey="reviews">
              <TabPane tab={`نظرات (${reviews.length})`} key="reviews">
                {reviews.length > 0 ? (
                  <List
                    dataSource={reviews}
                    renderItem={(review) => (
                      <List.Item>
                        <List.Item.Meta
                          avatar={<Avatar>{review.user?.name?.charAt(0) || 'U'}</Avatar>}
                          title={
                            <Space>
                              <Text strong>{review.user?.name || 'کاربر'}</Text>
                              <Rate disabled defaultValue={review.rating} allowHalf style={{ fontSize: '14px' }} />
                            </Space>
                          }
                          description={
                            <>
                              <Text>{review.comment}</Text>
                              <br />
                              <Text type="secondary" style={{ fontSize: '12px' }}>
                                {new Date(review.created_at).toLocaleDateString('fa-IR')}
                              </Text>
                            </>
                          }
                        />
                      </List.Item>
                    )}
                  />
                ) : (
                  <Text type="secondary">هیچ نظری برای این پزشک ثبت نشده است.</Text>
                )}
              </TabPane>
            </Tabs>
          </Card>
        </div>
      </main>
      <Footer />
    </>
  );
}
