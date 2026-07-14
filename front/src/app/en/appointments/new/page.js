'use client';

import { useState, useEffect } from 'react';
import {
  Card, Row, Col, Button, Typography, Spin, Empty, Tag, message,
  Calendar, Space, Divider, Alert, Steps
} from 'antd';
import {
  CalendarOutlined, ClockCircleOutlined, UserOutlined,
  CheckCircleOutlined, LeftOutlined, RightOutlined,
  EnvironmentOutlined, DollarOutlined
} from '@ant-design/icons';
import { useRouter, useSearchParams } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import dayjs from 'dayjs';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function NewAppointmentPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t, locale } = useLanguage();
  const doctorId = searchParams.get('doctorId');

  const [doctor, setDoctor] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedDate, setSelectedDate] = useState(null);
  const [selectedTime, setSelectedTime] = useState(null);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [availableDates, setAvailableDates] = useState([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  // دریافت اطلاعات پزشک
  const fetchDoctor = async () => {
    if (!doctorId) {
      message.error('پزشک انتخاب نشده است');
      router.push(`/${locale}/doctors`);
      return;
    }

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

  // دریافت زمان‌های خالی برای یک تاریخ خاص
  const fetchAvailableSlots = async (date) => {
    if (!doctorId) return;

    setLoadingSlots(true);
    try {
      const formattedDate = date.format('YYYY-MM-DD');
      const res = await fetch(
          `${API_URL}/api/appointments/doctors/${doctorId}/available-slots?date=${formattedDate}`,
          {
            headers: {
              'Content-Type': 'application/json',
            },
          }
      );
      const data = await res.json();
      if (data.success) {
        setAvailableSlots(data.data.slots || []);
        setSelectedDate(date);
        setSelectedTime(null);
      } else {
        setAvailableSlots([]);
        message.warning(data.message || 'این روز زمان خالی ندارد');
      }
    } catch (error) {
      console.error('Error fetching slots:', error);
      setAvailableSlots([]);
      message.error('خطا در دریافت زمان‌های خالی');
    } finally {
      setLoadingSlots(false);
    }
  };

  // دریافت روزهای دارای نوبت خالی (برای تقویم)
  const fetchAvailableDates = async () => {
    // این قابلیت با API فعلی وجود ندارد، اما می‌توانیم ۷ روز آینده را چک کنیم
    const dates = [];
    const today = dayjs();
    for (let i = 0; i < 14; i++) {
      const date = today.add(i, 'day');
      dates.push(date);
    }
    setAvailableDates(dates);
  };

  useEffect(() => {
    if (!doctorId) {
      router.push(`/${locale}/doctors`);
      return;
    }
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    setLoading(true);
    Promise.all([
      fetchDoctor(),
      fetchAvailableDates(),
    ]).finally(() => setLoading(false));
  }, [doctorId]);

  const handleDateSelect = (date) => {
    fetchAvailableSlots(date);
  };

  const handleTimeSelect = (time) => {
    setSelectedTime(time);
  };

  const handleNext = () => {
    if (!selectedDate) {
      message.warning('لطفاً تاریخ نوبت را انتخاب کنید');
      return;
    }
    if (!selectedTime) {
      message.warning('لطفاً ساعت نوبت را انتخاب کنید');
      return;
    }

    // ذخیره اطلاعات در localStorage برای صفحه بعد
    const appointmentData = {
      doctorId: doctor.id,
      doctorName: doctor.full_name,
      doctorSpecialty: doctor.specialty?.name,
      doctorFee: doctor.consultation_fee || 0,
      date: selectedDate.format('YYYY-MM-DD'),
      time: selectedTime.time,
    };
    localStorage.setItem('appointmentData', JSON.stringify(appointmentData));

    router.push(`/${locale}/appointments/checkout`);
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

  if (!doctor) {
    return (
        <>
          <Header />
          <div className="container" style={{ padding: '40px 20px', textAlign: 'center' }}>
            <Title level={4}>پزشک یافت نشد</Title>
            <Button type="primary" onClick={() => router.push(`/${locale}/doctors`)}>
              بازگشت به لیست پزشکان
            </Button>
          </div>
          <Footer />
        </>
    );
  }

  // فیلتر زمان‌های خالی
  const availableTimes = availableSlots.filter(slot => slot.is_available);
  const hasAvailableSlots = availableTimes.length > 0;

  return (
      <>
        <Header />
        <main style={{ minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '1000px', margin: '40px auto', padding: '0 20px' }}>
            <Breadcrumb />

            <Title level={2}>📅 {t('appointments.newAppointment')}</Title>
            <Text type="secondary">انتخاب تاریخ و ساعت نوبت</Text>

            {/* اطلاعات پزشک */}
            <Card style={{ marginTop: '16px', borderRadius: '12px', background: '#f0f5ff' }}>
              <Row gutter={[16, 16]} align="middle">
                <Col xs={24} sm={6}>
                  <div style={{
                    width: '80px',
                    height: '80px',
                    borderRadius: '50%',
                    background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: '32px',
                    color: 'white',
                    margin: '0 auto'
                  }}>
                    {doctor.full_name?.charAt(0) || '👨‍⚕️'}
                  </div>
                </Col>
                <Col xs={24} sm={18}>
                  <Title level={4} style={{ margin: 0 }}>{doctor.full_name}</Title>
                  <Text type="secondary">{doctor.specialty?.name}</Text>
                  <br />
                  <Space>
                    <Text><EnvironmentOutlined /> {doctor.clinic_name || 'آدرس مطب'}</Text>
                    <Divider type="vertical" />
                    <Text><DollarOutlined /> {doctor.consultation_fee?.toLocaleString() || 0} تومان</Text>
                  </Space>
                </Col>
              </Row>
            </Card>

            <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
              {/* تقویم */}
              <Col xs={24} lg={12}>
                <Card title="تاریخ نوبت" style={{ borderRadius: '12px' }}>
                  <Calendar
                      fullscreen={false}
                      disabledDate={(current) => {
                        // غیرفعال کردن روزهای گذشته
                        return current && current < dayjs().startOf('day');
                      }}
                      onSelect={handleDateSelect}
                      onChange={handleDateSelect}
                      value={selectedDate}
                      style={{ width: '100%' }}
                  />
                  {selectedDate && (
                      <div style={{ marginTop: '12px', textAlign: 'center' }}>
                        <Tag color="blue">
                          {selectedDate.format('YYYY/MM/DD')} - {selectedDate.locale('fa').format('dddd')}
                        </Tag>
                      </div>
                  )}
                </Card>
              </Col>

              {/* زمان‌های خالی */}
              <Col xs={24} lg={12}>
                <Card
                    title={`ساعت‌های خالی ${selectedDate ? `(${selectedDate.format('YYYY/MM/DD')})` : ''}`}
                    style={{ borderRadius: '12px' }}
                >
                  {selectedDate ? (
                      loadingSlots ? (
                          <div style={{ textAlign: 'center', padding: '20px' }}>
                            <Spin />
                          </div>
                      ) : hasAvailableSlots ? (
                          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                            {availableTimes.map((slot) => (
                                <Button
                                    key={slot.time}
                                    type={selectedTime?.time === slot.time ? 'primary' : 'default'}
                                    onClick={() => handleTimeSelect(slot)}
                                    style={{
                                      minWidth: '70px',
                                      borderColor: selectedTime?.time === slot.time ? '#2563eb' : undefined
                                    }}
                                >
                                  {slot.time}
                                </Button>
                            ))}
                          </div>
                      ) : (
                          <Empty description="در این روز هیچ زمان خالی وجود ندارد" />
                      )
                  ) : (
                      <Empty description="لطفاً یک تاریخ را انتخاب کنید" />
                  )}

                  {selectedTime && (
                      <div style={{ marginTop: '16px', padding: '12px', background: '#f0fdf4', borderRadius: '8px' }}>
                        <Text strong>زمان انتخابی: </Text>
                        <Tag color="success">{selectedTime.time}</Tag>
                      </div>
                  )}
                </Card>
              </Col>
            </Row>

            {/* دکمه ادامه */}
            <div style={{ marginTop: '24px', display: 'flex', justifyContent: 'space-between' }}>
              <Button
                  onClick={() => router.push(`/${locale}/doctors`)}
                  icon={<LeftOutlined />}
              >
                بازگشت
              </Button>
              <Button
                  type="primary"
                  size="large"
                  onClick={handleNext}
                  disabled={!selectedDate || !selectedTime}
                  icon={<RightOutlined />}
              >
                ادامه به مرحله پرداخت
              </Button>
            </div>
          </div>
        </main>
        <Footer />
      </>
  );
}
