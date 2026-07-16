// /home/god/Videos/medikal/front/src/app/fa/appointments/new/page.js
'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import {
  Card, Row, Col, Button, Typography, Spin, Tag,
  Space, Divider, Avatar, Empty, App, Alert, Modal
} from 'antd';
import {
  CalendarOutlined, ClockCircleOutlined,
  LeftOutlined, EnvironmentOutlined, PhoneOutlined,
  DollarOutlined, ReloadOutlined, UserOutlined, LoginOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';
import PersianCalendar from '@/components/shared/PersianCalendar';

const { Title, Text } = Typography;

// تبدیل تاریخ میلادی به شمسی
function toPersianDate(date) {
  if (!date || !(date instanceof Date) || isNaN(date)) return '';
  try {
    const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      weekday: 'long',
    });
    return formatter.format(date);
  } catch {
    return '';
  }
}

// فرمت تاریخ برای API (YYYY-MM-DD)
function formatDateForAPI(date) {
  if (!date || !(date instanceof Date) || isNaN(date)) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export default function NewAppointmentPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { locale } = useLanguage();
  const { message: appMessage } = App.useApp();

  // دریافت doctorId از query string
  const doctorId = searchParams.get('doctorId');
  console.log('🔍 doctorId from URL:', doctorId);

  const [doctor, setDoctor] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [redirecting, setRedirecting] = useState(false);

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const [selectedDate, setSelectedDate] = useState(today);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [loadingBook, setLoadingBook] = useState(false);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  // بررسی doctorId از چند منبع
  useEffect(() => {
    let finalDoctorId = doctorId;

    if (!finalDoctorId && typeof window !== 'undefined') {
      finalDoctorId = localStorage.getItem('selectedDoctorId');
      console.log('📦 doctorId from localStorage:', finalDoctorId);
    }

    if (!finalDoctorId) {
      setError('شناسه پزشک یافت نشد');
      setLoading(false);
      appMessage.error('شناسه پزشک یافت نشد. لطفاً از صفحه پزشکان اقدام کنید.');
      setRedirecting(true);
      const timer = setTimeout(() => {
        router.push(`/${locale}/doctors`);
      }, 3000);
      return () => clearTimeout(timer);
    }

    if (!doctorId && finalDoctorId) {
      console.log('🔄 Adding doctorId to URL:', finalDoctorId);
      router.replace(`/${locale}/appointments/new?doctorId=${finalDoctorId}`);
    }
  }, [doctorId, locale, router, appMessage]);

  // بررسی وضعیت لاگین
  useEffect(() => {
    const token = localStorage.getItem('token');
    setIsLoggedIn(!!token);
  }, []);

  // دریافت اطلاعات پزشک
  useEffect(() => {
    const fetchDoctor = async () => {
      const finalDoctorId = doctorId || localStorage.getItem('selectedDoctorId');
      if (!finalDoctorId) return;

      try {
        setError(null);
        console.log('🌐 Fetching doctor:', `${API_URL}/api/doctors/${finalDoctorId}/public`);

        const res = await fetch(`${API_URL}/api/doctors/${finalDoctorId}/public`, {
          headers: {
            'Content-Type': 'application/json',
          },
        });

        console.log('📡 Response status:', res.status);

        if (!res.ok) {
          if (res.status === 404) {
            throw new Error('پزشک مورد نظر یافت نشد');
          }
          throw new Error(`خطا در دریافت اطلاعات پزشک (${res.status})`);
        }

        const data = await res.json();
        console.log('📦 Doctor data:', data);

        if (data.success && data.data) {
          setDoctor(data.data);
        } else {
          throw new Error(data.message || 'اطلاعات پزشک نامعتبر است');
        }
      } catch (error) {
        console.error('Error fetching doctor:', error);
        setError(error.message || 'خطا در دریافت اطلاعات پزشک');
        appMessage.error(error.message || 'خطا در دریافت اطلاعات پزشک');
      } finally {
        setLoading(false);
      }
    };

    fetchDoctor();
  }, [doctorId, appMessage, API_URL]);

  // دریافت زمان‌های موجود
  const fetchAvailableSlots = useCallback(async (date) => {
    const finalDoctorId = doctorId || localStorage.getItem('selectedDoctorId');
    if (!finalDoctorId) return;

    setLoadingSlots(true);
    try {
      const dateStr = formatDateForAPI(date);
      console.log('🌐 Fetching slots:', `${API_URL}/api/appointments/doctors/${finalDoctorId}/available-slots?date=${dateStr}`);

      const res = await fetch(`${API_URL}/api/appointments/doctors/${finalDoctorId}/available-slots?date=${dateStr}`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();
      console.log('📦 Slots data:', data);

      if (data.success) {
        const slots = data.data?.slots || [];
        setAvailableSlots(slots);

        if (slots.length === 0) {
          // appMessage.info('هیچ زمانی برای این تاریخ موجود نیست');
        }
      } else {
        appMessage.error(data.message || 'خطا در دریافت زمان‌ها');
        setAvailableSlots([]);
      }
    } catch (error) {
      console.error('Error fetching slots:', error);
      appMessage.error('خطا در ارتباط با سرور');
      setAvailableSlots([]);
    } finally {
      setLoadingSlots(false);
    }
  }, [doctorId, API_URL, appMessage]);

  // بارگذاری زمان‌ها با تغییر تاریخ
  useEffect(() => {
    const finalDoctorId = doctorId || localStorage.getItem('selectedDoctorId');
    if (finalDoctorId && selectedDate && doctor) {
      fetchAvailableSlots(selectedDate);
    }
  }, [doctorId, selectedDate, fetchAvailableSlots, doctor]);

  // تغییر تاریخ
  const handleDateChange = (date) => {
    if (date && date instanceof Date && !isNaN(date)) {
      setSelectedDate(date);
      setSelectedSlot(null);
    }
  };

  // انتخاب زمان
  const handleSlotSelect = (slot) => {
    if (!slot.is_available) {
      appMessage.warning('این زمان قبلاً رزرو شده است');
      return;
    }
    setSelectedSlot(slot);
  };

  // رزرو نوبت
  const handleBook = async () => {
    if (!isLoggedIn) {
      setShowLoginModal(true);
      return;
    }

    if (!selectedSlot) {
      appMessage.warning('لطفاً یک زمان را انتخاب کنید');
      return;
    }

    setLoadingBook(true);
    try {
      const token = localStorage.getItem('token');
      const finalDoctorId = doctorId || localStorage.getItem('selectedDoctorId');
      const dateStr = formatDateForAPI(selectedDate);

      let timeStr = selectedSlot.start_time || selectedSlot.time || '';
      if (timeStr.includes(':')) {
        const parts = timeStr.split(':');
        timeStr = parts.length >= 2 ? `${parts[0]}:${parts[1]}` : timeStr;
      }

      if (!dateStr || !timeStr) {
        appMessage.error('تاریخ یا زمان نامعتبر است');
        setLoadingBook(false);
        return;
      }

      const bookData = {
        doctor_id: parseInt(finalDoctorId),
        date: dateStr,
        start_time: timeStr,
        notes: '',
      };

      console.log('📝 Booking data:', bookData);

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
        const appointmentId = appointment.id;

        const appointmentData = {
          doctorId: finalDoctorId,
          doctorName: doctor?.user?.name || doctor?.name || 'پزشک',
          doctorSpecialty: doctor?.specialty?.name || 'عمومی',
          date: dateStr,
          time: timeStr,
          doctorFee: parseFloat(doctor?.consultation_fee) || 0,
          appointmentId: appointmentId,
          status: appointment.status,
        };

        localStorage.setItem('appointmentData', JSON.stringify(appointmentData));
        localStorage.removeItem('selectedDoctorId');

        appMessage.success('نوبت با موفقیت رزرو شد');
        router.push(`/${locale}/appointments/checkout`);
      } else {
        let errorMsg = data.message || 'خطا در رزرو نوبت';
        if (data.errors) {
          const errors = Object.values(data.errors).flat().join('، ');
          errorMsg = errors || errorMsg;
        }
        appMessage.error(errorMsg);
      }
    } catch (error) {
      console.error('Error booking:', error);
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setLoadingBook(false);
    }
  };

  // هدایت به صفحه لاگین
  const handleGoToLogin = () => {
    setShowLoginModal(false);
    const finalDoctorId = doctorId || localStorage.getItem('selectedDoctorId');
    const tempAppointmentData = {
      doctorId: finalDoctorId,
      doctorName: doctor?.user?.name || doctor?.name || 'پزشک',
      doctorSpecialty: doctor?.specialty?.name || 'عمومی',
      date: formatDateForAPI(selectedDate),
      time: selectedSlot?.time || selectedSlot?.start_time || '',
      doctorFee: parseFloat(doctor?.consultation_fee) || 0,
      timestamp: Date.now(),
      fromPage: 'new-appointment'
    };
    localStorage.setItem('tempAppointment', JSON.stringify(tempAppointmentData));
    router.push(`/${locale}/login?redirect=/${locale}/appointments/checkout`);
  };

  // غیرفعال کردن تاریخ‌های گذشته
  const disabledDate = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return true;
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);
    return date < todayDate;
  };

  // نمایش لودینگ
  if (loading) {
    return (
        <>
          <Header />
          <LoadingSpinner />
          <Footer />
        </>
    );
  }

  // نمایش خطا
  if (error) {
    return (
        <>
          <Header />
          <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
            <div style={{ maxWidth: '900px', margin: '0 auto', padding: '40px 20px' }}>
              <Card style={{ borderRadius: '16px', textAlign: 'center', padding: '40px 20px' }}>
                <Alert
                    message="خطا"
                    description={error}
                    type="error"
                    showIcon
                    style={{ marginBottom: '24px' }}
                />
                {redirecting ? (
                    <div>
                      <Spin />
                      <div style={{ marginTop: 12 }}>
                        <Text type="secondary">در حال انتقال به صفحه پزشکان...</Text>
                      </div>
                    </div>
                ) : (
                    <Button
                        type="primary"
                        onClick={() => router.push(`/${locale}/doctors`)}
                        size="large"
                    >
                      بازگشت به لیست پزشکان
                    </Button>
                )}
              </Card>
            </div>
          </main>
          <Footer />
        </>
    );
  }

  // اگر پزشک وجود نداشت
  if (!doctor) {
    return (
        <>
          <Header />
          <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
            <div style={{ maxWidth: '900px', margin: '0 auto', padding: '40px 20px' }}>
              <Card style={{ borderRadius: '16px', textAlign: 'center', padding: '40px 20px' }}>
                <Empty
                    image={<UserOutlined style={{ fontSize: 64, color: '#d9d9d9' }} />}
                    description="پزشک مورد نظر یافت نشد"
                />
                <Button
                    type="primary"
                    onClick={() => router.push(`/${locale}/doctors`)}
                    size="large"
                    style={{ marginTop: 16 }}
                >
                  بازگشت به لیست پزشکان
                </Button>
              </Card>
            </div>
          </main>
          <Footer />
        </>
    );
  }

  // نمایش اصلی صفحه
  return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '900px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb />

            <Title level={2} style={{ marginBottom: '4px' }}>📅 رزرو نوبت جدید</Title>
            <Text type="secondary">اطلاعات پزشک را بررسی و زمان مورد نظر را انتخاب کنید</Text>

            <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
              {/* ستون اطلاعات پزشک */}
              <Col xs={24} lg={8}>
                <Card
                    title="👨‍⚕️ اطلاعات پزشک"
                    style={{ borderRadius: '16px', height: '100%' }}
                    styles={{ body: { padding: '16px' } }}
                >
                  <Space orientation="vertical" style={{ width: '100%' }} size="middle">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <Avatar
                          size={64}
                          src={doctor?.profile_image}
                          style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}
                      >
                        {doctor?.user?.name?.charAt(0) || 'د'}
                      </Avatar>
                      <div>
                        <Text strong style={{ fontSize: '16px' }}>
                          {doctor?.user?.name || 'پزشک'}
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
                        {doctor?.clinic_phone && (
                            <div><PhoneOutlined /> {doctor.clinic_phone}</div>
                        )}
                        {doctor?.clinic_address && (
                            <div><EnvironmentOutlined /> {doctor.clinic_address}</div>
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

                    <Divider style={{ margin: '8px 0' }} />
                    <div style={{ background: isLoggedIn ? '#f6ffed' : '#fffbe6', padding: '8px 12px', borderRadius: '8px' }}>
                      <Text type="secondary" style={{ fontSize: '12px' }}>
                        {isLoggedIn ? (
                            <>✅ شما وارد حساب کاربری خود شده‌اید</>
                        ) : (
                            <>🔑 برای رزرو نهایی نیاز به ورود دارید</>
                        )}
                      </Text>
                    </div>
                  </Space>
                </Card>
              </Col>

              {/* ستون انتخاب زمان */}
              <Col xs={24} lg={16}>
                <Card
                    title="🕐 انتخاب زمان"
                    style={{ borderRadius: '16px' }}
                    styles={{ body: { padding: '20px' } }}
                >
                  <div style={{ marginBottom: '20px' }}>
                    <Text strong>تاریخ مورد نظر (شمسی)</Text>
                    <div style={{ marginTop: '8px' }}>
                      <PersianCalendar
                          value={selectedDate}
                          onChange={handleDateChange}
                          disabledDate={disabledDate}
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
                        />
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
                      {selectedSlot ? `رزرو و پرداخت` : 'ابتدا یک زمان انتخاب کنید'}
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

        {/* مودال لاگین */}
        <Modal
            title="🔐 برای ادامه نیاز به ورود دارید"
            open={showLoginModal}
            onCancel={() => setShowLoginModal(false)}
            footer={[
              <Button key="cancel" onClick={() => setShowLoginModal(false)}>
                انصراف
              </Button>,
              <Button
                  key="login"
                  type="primary"
                  icon={<LoginOutlined />}
                  onClick={handleGoToLogin}
                  size="large"
              >
                ورود / ثبت‌نام
              </Button>,
            ]}
            width={480}
            centered
        >
          <div style={{ padding: '20px 0' }}>
            <Alert
                message="برای تکمیل رزرو نوبت، لطفاً ابتدا وارد حساب کاربری خود شوید."
                description="اگر حساب کاربری ندارید، می‌توانید به راحتی ثبت‌نام کنید."
                type="info"
                showIcon
                style={{ marginBottom: 16 }}
            />
            <div style={{ background: '#f8fafc', padding: '16px', borderRadius: '12px' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text type="secondary">پزشک:</Text>
                <Text strong>{doctor?.user?.name || doctor?.name}</Text>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text type="secondary">تاریخ:</Text>
                <Text strong>{toPersianDate(selectedDate)}</Text>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text type="secondary">زمان:</Text>
                <Text strong>{selectedSlot?.time || selectedSlot?.start_time?.substring(0, 5) || '---'}</Text>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <Text type="secondary">هزینه:</Text>
                <Text strong style={{ color: '#2563eb' }}>
                  {parseFloat(doctor?.consultation_fee || 0).toLocaleString()} تومان
                </Text>
              </div>
            </div>
            <div style={{ marginTop: 16, textAlign: 'center' }}>
              <Text type="secondary" style={{ fontSize: '12px' }}>
                پس از ورود، اطلاعات نوبت شما ذخیره خواهد شد.
              </Text>
            </div>
          </div>
        </Modal>

        <Footer />
      </>
  );
}