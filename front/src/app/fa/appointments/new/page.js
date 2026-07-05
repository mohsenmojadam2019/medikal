'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Button, Typography, Spin, Empty, Tag, message, 
  Space, Divider, Alert, Skeleton, Avatar, Calendar, Badge, Tooltip
} from 'antd';
import { 
  CalendarOutlined, ClockCircleOutlined, 
  LeftOutlined, RightOutlined,
  EnvironmentOutlined, DollarOutlined,
  CheckCircleOutlined, UserOutlined,
  StarOutlined, ThunderboltOutlined
} from '@ant-design/icons';
import { useRouter, useSearchParams } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import dayjs from 'dayjs';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';

const { Title, Text } = Typography;

function toJalali(date) {
  const d = new Date(date);
  const calendar = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    weekday: 'long',
  });
  const parts = calendar.formatToParts(d);
  const year = parts.find(p => p.type === 'year').value;
  const month = parts.find(p => p.type === 'month').value;
  const day = parts.find(p => p.type === 'day').value;
  const weekday = parts.find(p => p.type === 'weekday').value;
  return { year, month, day, weekday, jalali: `${year}/${month}/${day}` };
}

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
  const [selectedDateIndex, setSelectedDateIndex] = useState(null);
  const [isBooking, setIsBooking] = useState(false);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

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

  const fetchAvailableSlots = async (date) => {
    const formattedDate = date.format('YYYY-MM-DD');
    
    try {
      const res = await fetch(
        `${API_URL}/api/appointments/doctors/${doctorId}/available-slots?date=${formattedDate}`,
        {
          headers: {
            'Content-Type': 'application/json',
          },
        }
      );
      const data = await res.json();
      
      if (data.success && data.data) {
        const slots = data.data.slots || [];
        return slots;
      }
      return [];
    } catch (error) {
      console.error('Error fetching slots:', error);
      return [];
    }
  };

  const fetchAvailableDates = async () => {
    const dates = [];
    const today = dayjs();
    setLoading(true);
    
    for (let i = 0; i < 14; i++) {
      const date = today.add(i, 'day');
      const slots = await fetchAvailableSlots(date);
      
      if (slots.length > 0) {
        const jalali = toJalali(date.toDate());
        dates.push({
          date: date,
          jalali: jalali.jalali,
          weekday: jalali.weekday,
          day: jalali.day,
          slots: slots,
        });
      }
    }
    
    setAvailableDates(dates);
    setLoading(false);
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
    fetchDoctor();
    fetchAvailableDates();
  }, [doctorId]);

  const handleDateSelect = (index) => {
    const dateData = availableDates[index];
    setSelectedDateIndex(index);
    setSelectedDate(dateData.date);
    setAvailableSlots(dateData.slots);
    setSelectedTime(null);
  };

  const handleTimeSelect = (time) => {
    setSelectedTime(time);
  };

  const handleNext = async () => {
    if (!selectedDate || !selectedTime) {
      message.warning('لطفاً تاریخ و ساعت را انتخاب کنید');
      return;
    }

    setIsBooking(true);

    try {
      const slots = await fetchAvailableSlots(selectedDate);
      const stillAvailable = slots.some(slot => 
        slot.start_time === selectedTime.start_time && slot.is_available === true
      );

      if (!stillAvailable) {
        message.error('متأسفانه این زمان توسط شخص دیگری رزرو شده است. لطفاً زمان دیگری را انتخاب کنید.');
        const newSlots = await fetchAvailableSlots(selectedDate);
        setAvailableSlots(newSlots);
        setSelectedTime(null);
        setIsBooking(false);
        return;
      }

      const appointmentData = {
        doctorId: doctor.id,
        doctorName: doctor.full_name,
        doctorSpecialty: doctor.specialty?.name,
        doctorFee: parseInt(doctor.consultation_fee || 0),
        date: selectedDate.format('YYYY-MM-DD'),
        time: selectedTime.start_time,
      };
      localStorage.setItem('appointmentData', JSON.stringify(appointmentData));
      
      router.push(`/${locale}/appointments/checkout`);
    } catch (error) {
      console.error('Error checking availability:', error);
      message.error('خطا در بررسی زمان نوبت');
    } finally {
      setIsBooking(false);
    }
  };

  if (loading) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1000px', margin: '40px auto', padding: '0 20px' }}>
          <Skeleton active avatar paragraph={{ rows: 8 }} />
        </div>
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

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc' }}>
        <div style={{ maxWidth: '1000px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />

          <Title level={2} style={{ marginBottom: '4px' }}>📅 {t('appointments.newAppointment')}</Title>
          <Text type="secondary" style={{ fontSize: '16px' }}>انتخاب تاریخ و ساعت نوبت</Text>

          {/* اطلاعات پزشک */}
          <Card style={{ marginTop: '24px', borderRadius: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.05)' }}>
            <Row gutter={[24, 24]} align="middle">
              <Col xs={24} sm={6} style={{ textAlign: 'center' }}>
                <Avatar
                  size={80}
                  style={{ 
                    background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                    boxShadow: '0 4px 12px rgba(37,99,235,0.3)'
                  }}
                >
                  {doctor.full_name?.charAt(0) || '👨‍⚕️'}
                </Avatar>
              </Col>
              <Col xs={24} sm={18}>
                <Title level={3} style={{ margin: 0, marginBottom: '4px' }}>{doctor.full_name}</Title>
                <Space size="middle" wrap>
                  <Text style={{ color: '#2563eb', fontWeight: 500 }}>{doctor.specialty?.name}</Text>
                  <span style={{ color: '#e2e8f0' }}>|</span>
                  <Text><EnvironmentOutlined style={{ color: '#64748b' }} /> {doctor.clinic_name || 'آدرس مطب'}</Text>
                  <span style={{ color: '#e2e8f0' }}>|</span>
                  <Text><DollarOutlined style={{ color: '#10b981' }} /> <strong>{parseInt(doctor.consultation_fee || 0).toLocaleString()}</strong> تومان</Text>
                </Space>
                <div style={{ marginTop: '8px' }}>
                  <Space>
                    <StarOutlined style={{ color: '#f59e0b' }} />
                    <Text>{doctor.rating || 4.9}</Text>
                    <Text type="secondary" style={{ fontSize: '12px' }}>({doctor.total_reviews || 0} نظر)</Text>
                  </Space>
                </div>
              </Col>
            </Row>
          </Card>

          <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
            {/* لیست روزهای دارای نوبت */}
            <Col xs={24} lg={14}>
              <Card 
                title="📅 روزهای دارای نوبت خالی"
                style={{ borderRadius: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.05)', height: '100%' }}
              >
                {availableDates.length > 0 ? (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                    {availableDates.map((item, index) => (
                      <div
                        key={index}
                        onClick={() => handleDateSelect(index)}
                        style={{
                          padding: '14px 18px',
                          background: selectedDateIndex === index ? '#eff6ff' : '#ffffff',
                          borderRadius: '12px',
                          cursor: 'pointer',
                          border: selectedDateIndex === index ? '2px solid #2563eb' : '1px solid #e2e8f0',
                          transition: 'all 0.3s ease',
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          boxShadow: selectedDateIndex === index ? '0 4px 12px rgba(37,99,235,0.1)' : 'none',
                        }}
                        onMouseEnter={(e) => {
                          if (selectedDateIndex !== index) {
                            e.currentTarget.style.borderColor = '#2563eb';
                            e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
                          }
                        }}
                        onMouseLeave={(e) => {
                          if (selectedDateIndex !== index) {
                            e.currentTarget.style.borderColor = '#e2e8f0';
                            e.currentTarget.style.boxShadow = 'none';
                          }
                        }}
                      >
                        <Space size="middle">
                          <div style={{ 
                            width: '48px', 
                            height: '48px', 
                            borderRadius: '12px',
                            background: selectedDateIndex === index ? '#2563eb' : '#f1f5f9',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: selectedDateIndex === index ? 'white' : '#475569',
                          }}>
                            <span style={{ fontSize: '18px', fontWeight: 'bold' }}>{item.day}</span>
                            <span style={{ fontSize: '10px' }}>{item.jalali.split('/')[1]}</span>
                          </div>
                          <div>
                            <div style={{ fontWeight: '600', fontSize: '16px', color: selectedDateIndex === index ? '#1e293b' : '#334155' }}>
                              {item.weekday}
                            </div>
                            <div style={{ fontSize: '13px', color: selectedDateIndex === index ? '#64748b' : '#94a3b8' }}>
                              {item.jalali}
                            </div>
                          </div>
                        </Space>
                        <Tag color={selectedDateIndex === index ? 'blue' : 'default'} style={{ borderRadius: '20px', padding: '2px 14px' }}>
                          {item.slots.length} زمان خالی
                        </Tag>
                      </div>
                    ))}
                  </div>
                ) : (
                  <Alert 
                    message="هیچ روزی با نوبت خالی یافت نشد" 
                    description="برای امروز و روزهای آینده زمان خالی موجود نیست" 
                    type="warning" 
                    showIcon 
                  />
                )}
              </Card>
            </Col>

            {/* ساعت‌های خالی */}
            <Col xs={24} lg={10}>
              <Card 
                title={
                  <Space>
                    <ClockCircleOutlined style={{ color: '#2563eb' }} />
                    <span>ساعت‌های خالی</span>
                    {selectedDate && (
                      <Tag color="blue" style={{ borderRadius: '20px' }}>
                        {toJalali(selectedDate.toDate()).jalali}
                      </Tag>
                    )}
                  </Space>
                }
                style={{ borderRadius: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.05)', height: '100%' }}
              >
                {selectedDate ? (
                  availableSlots.length > 0 ? (
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '10px' }}>
                      {availableSlots.map((slot) => {
                        const isAvailable = slot.is_available === true;
                        return (
                          <Button
                            key={slot.start_time}
                            type={selectedTime?.start_time === slot.start_time ? 'primary' : 'default'}
                            onClick={() => isAvailable && handleTimeSelect(slot)}
                            disabled={!isAvailable}
                            style={{ 
                              minWidth: '80px',
                              height: '48px',
                              borderRadius: '12px',
                              fontWeight: selectedTime?.start_time === slot.start_time ? 'bold' : 'normal',
                              borderColor: !isAvailable ? '#ef4444' : (
                                selectedTime?.start_time === slot.start_time ? '#2563eb' : '#22c55e'
                              ),
                              background: !isAvailable ? '#fef2f2' : (
                                selectedTime?.start_time === slot.start_time ? '#2563eb' : '#f0fdf4'
                              ),
                              color: !isAvailable ? '#ef4444' : (
                                selectedTime?.start_time === slot.start_time ? 'white' : '#16a34a'
                              ),
                              boxShadow: selectedTime?.start_time === slot.start_time ? '0 4px 12px rgba(37,99,235,0.2)' : 'none',
                              cursor: !isAvailable ? 'not-allowed' : 'pointer',
                              opacity: !isAvailable ? 0.6 : 1,
                            }}
                          >
                            {slot.time}
                            {!isAvailable && (
                              <span style={{ fontSize: '10px', display: 'block', color: '#ef4444' }}>
                                ✕ رزرو
                              </span>
                            )}
                            {isAvailable && (
                              <span style={{ fontSize: '10px', display: 'block', color: '#16a34a' }}>
                                ✓ خالی
                              </span>
                            )}
                          </Button>
                        );
                      })}
                    </div>
                  ) : (
                    <Empty description="در این روز هیچ زمان خالی وجود ندارد" />
                  )
                ) : (
                  <div style={{ textAlign: 'center', padding: '40px 0' }}>
                    <CalendarOutlined style={{ fontSize: '48px', color: '#cbd5e1' }} />
                    <p style={{ marginTop: '16px', color: '#94a3b8' }}>
                      لطفاً یک روز را انتخاب کنید
                    </p>
                  </div>
                )}

                {selectedTime && selectedTime.is_available && (
                  <div style={{ 
                    marginTop: '20px', 
                    padding: '16px', 
                    background: '#f0fdf4', 
                    borderRadius: '12px',
                    border: '1px solid #bbf7d0',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center'
                  }}>
                    <Space>
                      <CheckCircleOutlined style={{ color: '#10b981' }} />
                      <Text strong>زمان انتخابی: </Text>
                      <Tag color="success" style={{ fontSize: '16px', padding: '4px 16px', borderRadius: '20px' }}>{selectedTime.time}</Tag>
                    </Space>
                  </div>
                )}

                {selectedTime && !selectedTime.is_available && (
                  <div style={{ 
                    marginTop: '20px', 
                    padding: '16px', 
                    background: '#fef2f2', 
                    borderRadius: '12px',
                    border: '1px solid #fecaca',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center'
                  }}>
                    <Space>
                      <CloseCircleOutlined style={{ color: '#ef4444' }} />
                      <Text strong style={{ color: '#ef4444' }}>این زمان رزرو شده است</Text>
                    </Space>
                  </div>
                )}
              </Card>
            </Col>
          </Row>

          {/* دکمه ادامه */}
          <div style={{ marginTop: '32px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Button 
              onClick={() => router.push(`/${locale}/doctors`)}
              icon={<LeftOutlined />}
              size="large"
              style={{ borderRadius: '12px' }}
            >
              بازگشت
            </Button>
            <Button 
              type="primary" 
              size="large"
              onClick={handleNext}
              loading={isBooking}
              disabled={!selectedDate || !selectedTime || !selectedTime?.is_available || isBooking}
              icon={<RightOutlined />}
              style={{ 
                borderRadius: '12px',
                height: '48px',
                padding: '0 32px',
                fontWeight: 'bold',
                boxShadow: (!selectedDate || !selectedTime || !selectedTime?.is_available || isBooking) ? 'none' : '0 4px 16px rgba(37,99,235,0.3)',
              }}
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
