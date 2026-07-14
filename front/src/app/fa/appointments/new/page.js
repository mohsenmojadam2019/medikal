'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import {
  Card, Row, Col, Button, Typography, Spin, Tag,
  Space, Divider, Avatar, Empty, App
} from 'antd';
import {
  CalendarOutlined, ClockCircleOutlined,
  LeftOutlined, EnvironmentOutlined, PhoneOutlined,
  DollarOutlined, ReloadOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';
import PersianCalendar from '@/components/shared/PersianCalendar';

const { Title, Text } = Typography;

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
  const doctorId = searchParams.get('doctorId');

  const [doctor, setDoctor] = useState(null);
  const [loading, setLoading] = useState(true);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const [selectedDate, setSelectedDate] = useState(today);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [loadingBook, setLoadingBook] = useState(false);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  useEffect(() => {
    const fetchDoctor = async () => {
      if (!doctorId) {
        appMessage.error('شناسه پزشک یافت نشد');
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
        if (data.success) {
          setDoctor(data.data);
        } else {
          appMessage.error(data.message || 'خطا در دریافت اطلاعات پزشک');
        }
      } catch (error) {
        console.error('Error fetching doctor:', error);
        appMessage.error('خطا در ارتباط با سرور');
      } finally {
        setLoading(false);
      }
    };

    fetchDoctor();
  }, [doctorId, locale, router, appMessage]);

  const fetchAvailableSlots = useCallback(async (date) => {
    if (!doctorId) return;

    setLoadingSlots(true);
    try {
      const token = getToken();
      const dateStr = formatDateForAPI(date);

      if (!dateStr) {
        appMessage.error('تاریخ نامعتبر است');
        setLoadingSlots(false);
        return;
      }

      const res = await fetch(`${API_URL}/api/appointments/doctors/${doctorId}/available-slots?date=${dateStr}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();

      if (data.success) {
        const slots = data.data?.slots || [];
        setAvailableSlots(slots);

        // ✅ فقط اگر هیچ زمانی موجود نبود، پیام نمایش بده
        if (slots.length === 0) {
          appMessage.info('هیچ زمانی برای این تاریخ موجود نیست');
        }
        // ✅ حذف پیام موفقیت با تعداد زمان‌ها
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

  useEffect(() => {
    if (doctorId && selectedDate) {
      fetchAvailableSlots(selectedDate);
    }
  }, [doctorId, selectedDate, fetchAvailableSlots]);

  const handleDateChange = (date) => {
    if (date && date instanceof Date && !isNaN(date)) {
      setSelectedDate(date);
      setSelectedSlot(null);
    }
  };

  const handleSlotSelect = (slot) => {
    if (!slot.is_available) {
      appMessage.warning('این زمان قبلاً رزرو شده است');
      return;
    }
    setSelectedSlot(slot);
  };

  const handleBook = async () => {
    if (!selectedSlot) {
      appMessage.warning('لطفاً یک زمان را انتخاب کنید');
      return;
    }

    setLoadingBook(true);
    try {
      const token = getToken();
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
        doctor_id: parseInt(doctorId),
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
          doctorId: doctorId,
          doctorName: doctor?.name || doctor?.full_name || 'پزشک',
          doctorSpecialty: doctor?.specialty?.name || 'عمومی',
          date: dateStr,
          time: timeStr,
          doctorFee: parseFloat(doctor?.consultation_fee) || 0,
          appointmentId: appointmentId,
          status: appointment.status,
        };

        localStorage.setItem('appointmentData', JSON.stringify(appointmentData));

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

  const disabledDate = (date) => {
    if (!date || !(date instanceof Date) || isNaN(date)) return true;
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);
    return date < todayDate;
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
                    styles={{ body: { padding: '16px' } }}
                >
                  <Space orientation="vertical" style={{ width: '100%' }} size="middle">
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

        <Footer />
      </>
  );
}
