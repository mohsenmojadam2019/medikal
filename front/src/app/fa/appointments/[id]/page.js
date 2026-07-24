'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card, Row, Col, Button, Typography, Spin, Tag,
  Space, Divider, Avatar, Descriptions, App, Alert,
  Modal, message, Badge
} from 'antd';
import {
  CalendarOutlined, ClockCircleOutlined,
  UserOutlined, LeftOutlined,
  DollarOutlined,
  CheckCircleOutlined, CloseCircleOutlined,
  InfoCircleOutlined, ReloadOutlined,
  NumberOutlined, MedicineBoxOutlined,
  QrcodeOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';
import QueueStatus from '@/components/QueueStatus/QueueStatus';

const { Title, Text } = Typography;

function toPersianDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  if (!date || isNaN(date.getTime())) return '';
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

function formatTime(timeStr) {
  if (!timeStr) return '';
  if (timeStr.includes('T')) {
    const date = new Date(timeStr);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
  }
  if (timeStr.includes(':')) {
    const parts = timeStr.split(':');
    return parts.length >= 2 ? `${parts[0]}:${parts[1]}` : timeStr;
  }
  return timeStr;
}

export default function AppointmentDetailPage() {
  const router = useRouter();
  const params = useParams();
  const { locale } = useLanguage();
  const { message: appMessage } = App.useApp();
  const id = params?.id;

  const [appointment, setAppointment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cancelling, setCancelling] = useState(false);
  const [addToQueueLoading, setAddToQueueLoading] = useState(false);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  const fetchAppointment = async () => {
    if (!id) {
      setError('شناسه نوبت یافت نشد');
      setLoading(false);
      return;
    }

    try {
      const token = getToken();
      if (!token) {
        router.push(`/${locale}/login`);
        return;
      }

      const res = await fetch(`${API_URL}/api/appointments/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();
      console.log('📋 Appointment detail:', data);

      if (data.success) {
        setAppointment(data.data);
      } else {
        setError(data.message || 'خطا در دریافت اطلاعات نوبت');
        appMessage.error(data.message || 'خطا در دریافت اطلاعات نوبت');
      }
    } catch (error) {
      console.error('Error fetching appointment:', error);
      setError('خطا در ارتباط با سرور');
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAppointment();
  }, [id, locale, router, appMessage]);

  const getStatusConfig = (status) => {
    const configs = {
      pending: { color: 'warning', text: 'در انتظار پرداخت', icon: <ClockCircleOutlined /> },
      confirmed: { color: 'success', text: 'پرداخت و تایید شده', icon: <CheckCircleOutlined /> },
      arrived: { color: 'primary', text: 'حاضر در مطب', icon: <UserOutlined /> },
      in_progress: { color: 'blue', text: 'در حال ویزیت', icon: <InfoCircleOutlined /> },
      completed: { color: 'green', text: 'انجام شده', icon: <CheckCircleOutlined /> },
      cancelled: { color: 'danger', text: 'لغو شده', icon: <CloseCircleOutlined /> },
      no_show: { color: 'secondary', text: 'حاضر نشده', icon: <CloseCircleOutlined /> },
    };
    return configs[status] || { color: 'default', text: status, icon: null };
  };

  const handleBack = () => {
    router.push(`/${locale}/profile/appointments`);
  };

  const handleCancel = async () => {
    if (!appointment) return;

    Modal.confirm({
      title: 'لغو نوبت',
      content: 'آیا از لغو این نوبت اطمینان دارید؟',
      okText: 'بله، لغو شود',
      cancelText: 'انصراف',
      okButtonProps: { danger: true },
      onOk: async () => {
        setCancelling(true);
        try {
          const token = getToken();
          const res = await fetch(`${API_URL}/api/appointments/${appointment.id}/cancel`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json',
            },
          });
          const data = await res.json();
          if (data.success) {
            appMessage.success('نوبت با موفقیت لغو شد');
            await fetchAppointment();
          } else {
            appMessage.error(data.message || 'خطا در لغو نوبت');
          }
        } catch (error) {
          console.error('Error cancelling appointment:', error);
          appMessage.error('خطا در ارتباط با سرور');
        } finally {
          setCancelling(false);
        }
      },
    });
  };

  const handlePay = () => {
    localStorage.setItem('appointmentData', JSON.stringify({
      doctorId: appointment.doctor_id,
      doctorName: appointment.doctor?.name || appointment.doctor?.full_name || 'پزشک',
      doctorSpecialty: appointment.doctor?.specialty?.name || 'عمومی',
      date: appointment.date,
      time: appointment.start_time,
      doctorFee: appointment.doctor?.consultation_fee || 0,
      appointmentId: appointment.id,
      status: appointment.status,
    }));
    router.push(`/${locale}/appointments/checkout`);
  };

  // ✅ اضافه کردن بیمار به صف انتظار
  const handleAddToQueue = async () => {
    if (!appointment) return;

    setAddToQueueLoading(true);
    try {
      const token = getToken();
      const res = await fetch(`${API_URL}/api/waiting/add`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          appointment_id: appointment.id,
          type: 'walk_in',
        }),
      });
      const data = await res.json();
      if (data.success) {
        appMessage.success(`شما با شماره ${data.data.queue_number} در صف قرار گرفتید`);
        await fetchAppointment();
      } else {
        appMessage.error(data.message || 'خطا در ثبت در صف');
      }
    } catch (error) {
      console.error('Error adding to queue:', error);
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setAddToQueueLoading(false);
    }
  };

  // ✅ تابع رفرش
  const handleRefresh = () => {
    fetchAppointment();
    appMessage.success('اطلاعات به‌روزرسانی شد');
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

  if (error) {
    return (
        <>
          <Header />
          <div style={{ maxWidth: '800px', margin: '0 auto', padding: '40px 20px', textAlign: 'center' }}>
            <Alert
                message="خطا"
                description={error}
                type="error"
                showIcon
                style={{ marginBottom: '20px' }}
            />
            <Button type="primary" onClick={handleBack}>
              بازگشت به لیست نوبت‌ها
            </Button>
          </div>
          <Footer />
        </>
    );
  }

  if (!appointment) {
    return (
        <>
          <Header />
          <div style={{ maxWidth: '800px', margin: '0 auto', padding: '40px 20px', textAlign: 'center' }}>
            <Title level={4}>نوبت یافت نشد</Title>
            <Button type="primary" onClick={handleBack}>
              بازگشت به لیست نوبت‌ها
            </Button>
          </div>
          <Footer />
        </>
    );
  }

  const statusConfig = getStatusConfig(appointment.status);
  const persianDate = toPersianDate(appointment.date);
  const time = formatTime(appointment.start_time);
  const fee = appointment.fee || appointment.doctor?.consultation_fee || 0;
  const doctorName = appointment.doctor?.name || appointment.doctor?.full_name || 'پزشک';
  const doctorSpecialty = appointment.doctor?.specialty?.name || 'عمومی';

  const isPending = appointment.status === 'pending';
  const isConfirmed = appointment.status === 'confirmed' || appointment.status === 'arrived';
  const isCompleted = appointment.status === 'completed';
  const isCancelled = appointment.status === 'cancelled';
  const isInProgress = appointment.status === 'in_progress';
  const isArrived = appointment.status === 'arrived';

  // ✅ نمایش وضعیت صف فقط برای نوبت‌های confirmed و arrived
  const showQueue = isConfirmed || isArrived || isInProgress;

  return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '900px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb />

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px', flexWrap: 'wrap', gap: '12px' }}>
              <Title level={2} style={{ marginBottom: '0' }}>📋 جزئیات نوبت</Title>
              <Space>
                <Button
                    icon={<ReloadOutlined />}
                    onClick={handleRefresh}
                    size="small"
                >
                  به‌روزرسانی
                </Button>
                <Button icon={<LeftOutlined />} onClick={handleBack}>
                  بازگشت
                </Button>
              </Space>
            </div>

            <Card style={{ borderRadius: '16px' }}>
              <Row gutter={[24, 24]}>
                <Col xs={24} md={8}>
                  <div style={{ textAlign: 'center' }}>
                    <Avatar
                        size={80}
                        src={appointment.doctor?.avatar}
                        style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}
                    >
                      {doctorName.charAt(0)}
                    </Avatar>
                    <Title level={4} style={{ marginTop: '12px', marginBottom: '4px' }}>
                      {doctorName}
                    </Title>
                    <Text type="secondary">{doctorSpecialty}</Text>
                    <div style={{ marginTop: '8px' }}>
                      <Tag color={statusConfig.color} style={{ fontSize: '14px', padding: '4px 12px' }}>
                        {statusConfig.icon} {statusConfig.text}
                      </Tag>
                    </div>

                    {/* ✅ شماره نوبت */}
                    {appointment.code && (
                        <div style={{ marginTop: '12px' }}>
                          <Tag color="blue" style={{ fontSize: '16px', padding: '6px 16px' }}>
                            <NumberOutlined /> کد: {appointment.code}
                          </Tag>
                        </div>
                    )}
                  </div>
                </Col>

                <Col xs={24} md={16}>
                  <Descriptions
                      bordered
                      column={1}
                      labelStyle={{ fontWeight: 'bold', width: '150px' }}
                  >
                    <Descriptions.Item label="کد نوبت">
                      <Text strong>{appointment.code || '—'}</Text>
                    </Descriptions.Item>
                    <Descriptions.Item label="تاریخ">
                      <Space>
                        <CalendarOutlined />
                        {persianDate}
                      </Space>
                    </Descriptions.Item>
                    <Descriptions.Item label="ساعت">
                      <Space>
                        <ClockCircleOutlined />
                        {time}
                      </Space>
                    </Descriptions.Item>
                    <Descriptions.Item label="هزینه">
                      <Space>
                        <DollarOutlined />
                        {parseFloat(fee).toLocaleString()} تومان
                      </Space>
                    </Descriptions.Item>
                    <Descriptions.Item label="وضعیت پرداخت">
                      <Tag color={isConfirmed || isCompleted ? 'success' : (isPending ? 'warning' : 'default')}>
                        {isConfirmed || isCompleted ? 'پرداخت شده' : (isPending ? 'در انتظار پرداخت' : '—')}
                      </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="نوع نوبت">
                      <Tag color="blue">
                        {appointment.type === 'online' ? 'ویزیت آنلاین' :
                            appointment.type === 'home_visit' ? 'ویزیت در منزل' : 'حضوری'}
                      </Tag>
                    </Descriptions.Item>
                    {appointment.notes && (
                        <Descriptions.Item label="توضیحات">
                          {appointment.notes}
                        </Descriptions.Item>
                    )}
                  </Descriptions>

                  {/* ✅ دکمه‌های اقدام */}
                  <div style={{ marginTop: '16px', display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
                    {isPending && (
                        <>
                          <Button
                              type="primary"
                              onClick={handlePay}
                              style={{ borderRadius: '8px' }}
                          >
                            پرداخت نوبت
                          </Button>
                          <Button
                              danger
                              onClick={handleCancel}
                              loading={cancelling}
                              style={{ borderRadius: '8px' }}
                          >
                            لغو نوبت
                          </Button>
                        </>
                    )}

                    {/* ✅ دکمه ثبت در صف - فقط برای نوبت‌های confirmed و arrived */}
                    {(isConfirmed || isArrived) && (
                        <Button
                            type="primary"
                            icon={<UserOutlined />}
                            onClick={handleAddToQueue}
                            loading={addToQueueLoading}
                            style={{ borderRadius: '8px' }}
                        >
                          ثبت در صف انتظار
                        </Button>
                    )}
                  </div>

                  {/* ✅ پیام‌های وضعیت */}
                  {isConfirmed && !isArrived && !isInProgress && (
                      <div style={{ marginTop: '16px' }}>
                        <Alert
                            title="✅ نوبت پرداخت و تایید شده"
                            description="پرداخت این نوبت با موفقیت انجام شده است. لطفاً در زمان مقرر در مطب حضور داشته باشید."
                            type="success"
                            showIcon
                        />
                      </div>
                  )}

                  {isCompleted && (
                      <div style={{ marginTop: '16px' }}>
                        <Alert
                            title="✅ ویزیت انجام شد"
                            description="ویزیت شما با موفقیت انجام شده است."
                            type="success"
                            showIcon
                        />
                      </div>
                  )}

                  {isCancelled && (
                      <div style={{ marginTop: '16px' }}>
                        <Alert
                            title="❌ نوبت لغو شده"
                            description="این نوبت لغو شده است."
                            type="error"
                            showIcon
                        />
                      </div>
                  )}

                  {isInProgress && (
                      <div style={{ marginTop: '16px' }}>
                        <Alert
                            title="🔄 در حال ویزیت"
                            description="پزشک در حال ویزیت شماست."
                            type="info"
                            showIcon
                            icon={<InfoCircleOutlined />}
                        />
                      </div>
                  )}

                  {isArrived && (
                      <div style={{ marginTop: '16px' }}>
                        <Alert
                            title="📍 حاضر در مطب"
                            description="شما در مطب حضور دارید. به زودی ویزیت شما شروع می‌شود."
                            type="info"
                            showIcon
                        />
                      </div>
                  )}
                </Col>
              </Row>
            </Card>

            {/* ✅ نمایش وضعیت صف انتظار */}
            {showQueue && (
                <div style={{ marginTop: '24px' }}>
                  <QueueStatus appointmentId={appointment.id} />
                </div>
            )}

            {/* ✅ QR Code نوبت (اختیاری) */}
            {isConfirmed && (
                <Card style={{ borderRadius: '16px', marginTop: '16px' }}>
                  <div style={{ textAlign: 'center' }}>
                    <QrcodeOutlined style={{ fontSize: '48px', color: '#2563eb' }} />
                    <Title level={5} style={{ marginTop: '8px' }}>کد QR نوبت</Title>
                    <Text type="secondary">این کد را در مطب نمایش دهید</Text>
                    <br />
                    <Tag color="blue" style={{ marginTop: '8px', fontSize: '12px' }}>
                      کد: {appointment.code}
                    </Tag>
                  </div>
                </Card>
            )}
          </div>
        </main>
        <Footer />
      </>
  );
}