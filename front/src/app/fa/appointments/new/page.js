'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { 
  Card, Row, Col, Button, Typography, Spin, Tag, message, 
  Space, Divider, Alert, Calendar, Select, List, Avatar,
  Statistic, Timeline, Empty, Radio, Modal, Skeleton
} from 'antd';
import { 
  CalendarOutlined, ClockCircleOutlined, UserOutlined, 
  LeftOutlined, CheckCircleOutlined, CloseCircleOutlined,
  EnvironmentOutlined, StarOutlined, HeartOutlined,
  PhoneOutlined, MailOutlined, VideoCameraOutlined,
  DollarOutlined, SafetyOutlined, ReloadOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';
import dayjs from 'dayjs';
import 'dayjs/locale/fa';

dayjs.locale('fa');

const { Title, Text } = Typography;

// تبدیل تاریخ میلادی به شمسی
function toPersianDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  });
  return formatter.format(date);
}

function toPersianDateShort(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
  });
  return formatter.format(date);
}

export default function NewAppointmentPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t, locale } = useLanguage();
  const doctorId = searchParams.get('doctorId');
  
  const [doctor, setDoctor] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedDate, setSelectedDate] = useState(dayjs().format('YYYY-MM-DD'));
  const [availableSlots, setAvailableSlots] = useState([]);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [loadingBook, setLoadingBook] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [bookingResult, setBookingResult] = useState(null);
  
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => localStorage.getItem('token');

  // دریافت اطلاعات پزشک
  useEffect(() => {
    const fetchDoctor = async () => {
      if (!doctorId) {
        message.error('شناسه پزشک یافت نشد');
        router.push(`/${locale}/doctors`);
        return;
      }

      try {
        const token = getToken();
        const res = await fetch(`${API_URL}/api/doctors/${doctorId}/public`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        });
        const data = await res.json();
        console.log('👨‍⚕️ Doctor data:', data);
        if (data.success) {
          setDoctor(data.data);
        } else {
          message.error(data.message || 'خطا در دریافت اطلاعات پزشک');
        }
      } catch (error) {
        console.error('Error fetching doctor:', error);
        message.error('خطا در ارتباط با سرور');
      } finally {
        setLoading(false);
      }
    };

    fetchDoctor();
  }, [doctorId, locale, router]);

  // دریافت زمان‌های خالی
  const fetchAvailableSlots = async (date) => {
    if (!doctorId) return;
    
    setLoadingSlots(true);
    try {
      const token = getToken();
      console.log('📡 Fetching slots for doctor:', doctorId, 'date:', date);
      
      const res = await fetch(`${API_URL}/api/appointments/doctors/${doctorId}/available-slots?date=${date}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();
      console.log('📦 Slots response:', data);

      if (data.success) {
        const slots = data.data?.slots || [];
        console.log('📋 Available slots:', slots);
        setAvailableSlots(slots);
        
        if (slots.length === 0) {
          message.info('هیچ زمانی برای این تاریخ موجود نیست');
        } else {
          const availableCount = slots.filter(s => s.is_available !== false).length;
          message.success(`${availableCount} زمان موجود برای انتخاب`);
        }
      } else {
        message.error(data.message || 'خطا در دریافت زمان‌ها');
        setAvailableSlots([]);
      }
    } catch (error) {
      console.error('Error fetching slots:', error);
      message.error('خطا در ارتباط با سرور');
      setAvailableSlots([]);
    } finally {
      setLoadingSlots(false);
    }
  };

  // بارگیری اولیه زمان‌ها
  useEffect(() => {
    if (doctorId) {
      fetchAvailableSlots(selectedDate);
    }
  }, [doctorId, selectedDate]);

  const handleDateChange = (date) => {
    const formattedDate = date.format('YYYY-MM-DD');
    setSelectedDate(formattedDate);
    setSelectedSlot(null);
  };

  const handleSlotSelect = (slot) => {
    if (!slot.is_available) {
      message.warning('این زمان قبلاً رزرو شده است');
      return;
    }
    setSelectedSlot(slot);
  };

  const handleBook = async () => {
    if (!selectedSlot) {
      message.warning('لطفاً یک زمان را انتخاب کنید');
      return;
    }

    setLoadingBook(true);
    try {
      const token = getToken();
      const bookData = {
        doctor_id: parseInt(doctorId),
        date: selectedDate,
        start_time: selectedSlot.start_time || selectedSlot.time,
        notes: '',
      };

      console.log('📝 Booking appointment:', bookData);

      const res = await fetch(`${API_URL}/api/appointments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(bookData),
      });

      const data = await res.json();
      console.log('📦 Booking response:', data);

      if (data.success) {
        const appointment = data.data;
        setBookingResult({
          success: true,
          appointment: appointment,
          message: 'نوبت با موفقیت رزرو شد',
        });
        setShowSuccessModal(true);
        
        localStorage.setItem('appointmentData', JSON.stringify({
          doctorId: doctorId,
          doctorName: doctor?.name || doctor?.full_name || 'پزشک',
          doctorSpecialty: doctor?.specialty?.name || 'عمومی',
          date: selectedDate,
          time: selectedSlot.start_time || selectedSlot.time,
          doctorFee: parseFloat(doctor?.consultation_fee) || 0,
        }));
      } else {
        message.error(data.message || 'خطا در رزرو نوبت');
      }
    } catch (error) {
      console.error('Error booking:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoadingBook(false);
    }
  };

  const handleGoToPayment = () => {
    setShowSuccessModal(false);
    router.push(`/${locale}/appointments/checkout`);
  };

  const handleGoHome = () => {
    setShowSuccessModal(false);
    router.push(`/${locale}/doctors`);
  };

  const disabledDate = (current) => {
    return current && current < dayjs().startOf('day');
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
        <div style={{ textAlign: 'center', padding: '40px' }}>
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
      <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '900px', margin: '0 auto', padding: '24px 20px' }}>
          <Breadcrumb />

          <Title level={2} style={{ marginBottom: '4px' }}>📅 رزرو نوبت جدید</Title>
          <Text type="secondary">اطلاعات پزشک را بررسی و زمان مورد نظر را انتخاب کنید</Text>

          <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
            <Col xs={24} lg={8}>
              <Card 
                title="👨‍⚕️ اطلاعات پزشک"
                style={{ borderRadius: '16px' }}
                bodyStyle={{ padding: '16px' }}
              >
                <Space direction="vertical" style={{ width: '100%' }} size="middle">
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <Avatar 
                      size={64} 
                      src={doctor?.avatar}
                      style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}
                    >
                      {doctor?.name?.charAt(0) || doctor?.full_name?.charAt(0)}
                    </Avatar>
                    <div>
                      <Text strong style={{ fontSize: '16px' }}>
                        {doctor?.name || doctor?.full_name}
                      </Text>
                      <div>
                        <Tag color="blue">{doctor?.specialty?.name || 'عمومی'}</Tag>
                      </div>
                      {doctor?.consultation_fee > 0 && (
                        <Text type="secondary" style={{ fontSize: '12px' }}>
                          <DollarOutlined /> {parseFloat(doctor.consultation_fee).toLocaleString()} تومان
                        </Text>
                      )}
                    </div>
                  </div>

                  <Divider style={{ margin: '8px 0' }} />

                  <div>
                    <Text type="secondary">اطلاعات تماس</Text>
                    <div style={{ marginTop: '4px' }}>
                      {doctor?.phone && (
                        <div><PhoneOutlined /> {doctor.phone}</div>
                      )}
                      {doctor?.address && (
                        <div><EnvironmentOutlined /> {doctor.address}</div>
                      )}
                    </div>
                  </div>

                  {doctor?.bio && (
                    <>
                      <Divider style={{ margin: '8px 0' }} />
                      <div>
                        <Text type="secondary">درباره پزشک</Text>
                        <Text style={{ display: 'block', marginTop: '4px', fontSize: '13px' }}>
                          {doctor.bio}
                        </Text>
                      </div>
                    </>
                  )}
                </Space>
              </Card>
            </Col>

            <Col xs={24} lg={16}>
              <Card 
                title="🕐 انتخاب زمان"
                style={{ borderRadius: '16px' }}
                bodyStyle={{ padding: '20px' }}
              >
                <div style={{ marginBottom: '20px' }}>
                  <Text strong>تاریخ مورد نظر (شمسی)</Text>
                  <div style={{ marginTop: '8px' }}>
                    <Calendar 
                      fullscreen={false} 
                      value={dayjs(selectedDate)}
                      onChange={handleDateChange}
                      disabledDate={disabledDate}
                      style={{ borderRadius: '12px', border: '1px solid #e8e8e8' }}
                    />
                  </div>
                </div>

                <Divider style={{ margin: '12px 0' }} />

                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
                    <Text strong>
                      زمان‌های موجود برای {toPersianDate(selectedDate)}
                    </Text>
                    <Button 
                      size="small" 
                      icon={<ReloadOutlined />} 
                      onClick={() => fetchAvailableSlots(selectedDate)}
                      loading={loadingSlots}
                    >
                      بروزرسانی
                    </Button>
                  </div>

                  {loadingSlots ? (
                    <div style={{ padding: '20px', textAlign: 'center' }}>
                      <Spin size="large" />
                      <div style={{ marginTop: 12 }}>
                        <Text type="secondary">در حال دریافت زمان‌های موجود...</Text>
                      </div>
                    </div>
                  ) : availableSlots.length === 0 ? (
                    <Empty 
                      description="هیچ زمانی برای این تاریخ موجود نیست" 
                      image={Empty.PRESENTED_IMAGE_SIMPLE}
                    >
                      <Button type="primary" onClick={() => {
                        const nextDate = dayjs(selectedDate).add(1, 'day').format('YYYY-MM-DD');
                        setSelectedDate(nextDate);
                      }}>
                        مشاهده روز بعد
                      </Button>
                    </Empty>
                  ) : (
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(80px, 1fr))', gap: '8px' }}>
                      {availableSlots.map((slot, index) => {
                        const isAvailable = slot.is_available !== false;
                        const isSelected = selectedSlot === slot;
                        const timeLabel = slot.time || slot.start_time?.substring(0, 5) || '--:--';
                        
                        return (
                          <Button
                            key={index}
                            type={isSelected ? 'primary' : 'default'}
                            disabled={!isAvailable}
                            onClick={() => handleSlotSelect(slot)}
                            style={{
                              height: '56px',
                              borderRadius: '12px',
                              borderColor: isSelected ? '#2563eb' : (isAvailable ? '#d9d9d9' : '#f0f0f0'),
                              background: isSelected ? '#2563eb' : (isAvailable ? 'white' : '#f5f5f5'),
                              color: isSelected ? 'white' : (isAvailable ? 'inherit' : '#bfbfbf'),
                              fontWeight: isSelected ? 'bold' : 'normal',
                              transition: 'all 0.3s ease',
                            }}
                          >
                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                              <ClockCircleOutlined style={{ fontSize: '14px' }} />
                              <span style={{ fontSize: '14px' }}>{timeLabel}</span>
                              {!isAvailable && (
                                <span style={{ fontSize: '8px', color: '#ff4d4f' }}>رزرو شده</span>
                              )}
                            </div>
                          </Button>
                        );
                      })}
                    </div>
                  )}

                  {availableSlots.length > 0 && (
                    <div style={{ marginTop: '12px' }}>
                      <Text type="secondary" style={{ fontSize: '12px' }}>
                        {availableSlots.filter(s => s.is_available !== false).length} زمان موجود از {availableSlots.length} زمان
                      </Text>
                    </div>
                  )}
                </div>

                <Divider style={{ margin: '16px 0' }} />

                <div style={{ display: 'flex', gap: '12px' }}>
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
                    onClick={handleBook}
                    loading={loadingBook}
                    disabled={!selectedSlot}
                    style={{ 
                      flex: 1,
                      borderRadius: '12px',
                      height: '48px',
                      fontWeight: 'bold',
                    }}
                  >
                    {selectedSlot ? 
                      `رزرو نوبت ${selectedSlot.time || selectedSlot.start_time?.substring(0, 5)}` : 
                      'ابتدا یک زمان انتخاب کنید'
                    }
                  </Button>
                </div>

                {selectedSlot && doctor?.consultation_fee > 0 && (
                  <div style={{ marginTop: '12px', padding: '12px 16px', background: '#f0f5ff', borderRadius: '8px' }}>
                    <Space>
                      <DollarOutlined style={{ color: '#2563eb' }} />
                      <Text>
                        هزینه ویزیت: <strong>{parseFloat(doctor.consultation_fee).toLocaleString()} تومان</strong>
                      </Text>
                    </Space>
                  </div>
                )}
              </Card>
            </Col>
          </Row>
        </div>
      </main>

      <Modal
        title="✅ نوبت رزرو شد"
        open={showSuccessModal}
        onCancel={handleGoHome}
        footer={null}
        width={450}
        centered
        closable={false}
      >
        <div style={{ textAlign: 'center', padding: '20px 0' }}>
          <div style={{ fontSize: '64px', marginBottom: '16px' }}>🎉</div>
          <Title level={4}>نوبت شما با موفقیت رزرو شد</Title>
          
          {bookingResult?.appointment && (
            <div style={{ textAlign: 'right', marginTop: '16px', padding: '16px', background: '#f8fafc', borderRadius: '8px' }}>
              <div><strong>پزشک:</strong> {doctor?.name || doctor?.full_name}</div>
              <div><strong>تاریخ:</strong> {toPersianDate(selectedDate)}</div>
              <div><strong>ساعت:</strong> {selectedSlot?.time || selectedSlot?.start_time?.substring(0, 5)}</div>
              <div><strong>هزینه:</strong> {parseFloat(doctor?.consultation_fee || 0).toLocaleString()} تومان</div>
            </div>
          )}

          <div style={{ marginTop: '24px', display: 'flex', gap: '12px', justifyContent: 'center' }}>
            <Button 
              type="primary" 
              size="large"
              onClick={handleGoToPayment}
              style={{ borderRadius: '12px' }}
            >
              ادامه به پرداخت
            </Button>
            <Button 
              size="large"
              onClick={handleGoHome}
              style={{ borderRadius: '12px' }}
            >
              بازگشت به خانه
            </Button>
          </div>
        </div>
      </Modal>

      <Footer />
    </>
  );
}
