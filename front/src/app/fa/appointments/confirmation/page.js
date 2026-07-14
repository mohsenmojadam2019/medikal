'use client';

import { useState, useEffect } from 'react';
import {
  Card, Row, Col, Button, Typography, Spin, Tag,
  Divider, Space, QRCode, message, Alert, Statistic
} from 'antd';
import {
  CheckCircleOutlined, FilePdfOutlined,
  PrinterOutlined, DownloadOutlined,
  WhatsAppOutlined, MailOutlined, CalendarOutlined,
  ClockCircleOutlined, UserOutlined, DollarOutlined
} from '@ant-design/icons';
import { useRouter, useSearchParams } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import dayjs from 'dayjs';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function ConfirmationPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t, locale } = useLanguage();
  const [appointment, setAppointment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [invoice, setInvoice] = useState(null);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  useEffect(() => {
    // بررسی وضعیت پرداخت از URL (برای درگاه)
    const status = searchParams.get('status');
    const paymentId = searchParams.get('payment_id');

    // دریافت اطلاعات از localStorage
    const stored = localStorage.getItem('appointmentConfirmation');
    if (stored) {
      try {
        const data = JSON.parse(stored);
        setAppointment(data);
        if (data.invoiceId) {
          fetchInvoice(data.invoiceId);
        }
        // پاک کردن از localStorage
        localStorage.removeItem('appointmentConfirmation');
        setLoading(false);
        return;
      } catch {
        // ignore
      }
    }

    // اگر اطلاعاتی در localStorage نبود
    router.push(`/${locale}/profile/appointments`);
  }, []);

  const fetchInvoice = async (invoiceId) => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/invoices/${invoiceId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setInvoice(data.data);
      }
    } catch (error) {
      console.error('Error fetching invoice:', error);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const handleDownloadPDF = async () => {
    if (!invoice) {
      message.warning('فاکتوری برای دانلود وجود ندارد');
      return;
    }
    // این قابلیت نیاز به API جدید دارد
    message.info('قابلیت دانلود PDF به زودی اضافه می‌شود');
  };

  const handleShareWhatsApp = () => {
    const text = `نوبت من در کلینیک‌یار\nپزشک: ${appointment?.doctorName}\nتاریخ: ${dayjs(appointment?.date).format('YYYY/MM/DD')}\nساعت: ${appointment?.time}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
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

  if (!appointment) {
    return (
        <>
          <Header />
          <div className="container" style={{ padding: '40px 20px', textAlign: 'center' }}>
            <Title level={4}>اطلاعات نوبت یافت نشد</Title>
            <Button type="primary" onClick={() => router.push(`/${locale}/profile/appointments`)}>
              مشاهده نوبت‌های من
            </Button>
          </div>
          <Footer />
        </>
    );
  }

  const isSuccess = appointment.status === 'confirmed';

  return (
      <>
        <Header />
        <main style={{ minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '800px', margin: '40px auto', padding: '0 20px' }}>
            <Breadcrumb />

            {/* وضعیت نوبت */}
            <Alert
                message={
                  isSuccess ? (
                      <Space>
                        <CheckCircleOutlined style={{ color: '#10b981', fontSize: '20px' }} />
                        <span style={{ fontSize: '16px', fontWeight: 'bold' }}>
                    ✅ نوبت با موفقیت رزرو شد
                  </span>
                      </Space>
                  ) : (
                      <Space>
                  <span style={{ fontSize: '16px', fontWeight: 'bold' }}>
                    ⏳ در حال پردازش پرداخت
                  </span>
                      </Space>
                  )
                }
                description={
                  isSuccess
                      ? 'نوبت شما با موفقیت ثبت و پرداخت شد. لطفاً در زمان مقرر به مطب مراجعه فرمایید.'
                      : 'پرداخت شما در حال بررسی است. به زودی نتیجه به شما اعلام می‌شود.'
                }
                type={isSuccess ? 'success' : 'info'}
                showIcon
                style={{ marginBottom: '24px', borderRadius: '12px' }}
            />

            <Row gutter={[24, 24]}>
              {/* اطلاعات نوبت */}
              <Col xs={24} lg={16}>
                <Card title="📋 اطلاعات نوبت" style={{ borderRadius: '12px' }}>
                  <Space direction="vertical" style={{ width: '100%' }} size="middle">
                    <Row gutter={[16, 16]}>
                      <Col span={12}>
                        <div>
                          <Text type="secondary">پزشک</Text>
                          <br />
                          <Text strong>{appointment.doctorName}</Text>
                        </div>
                      </Col>
                      <Col span={12}>
                        <div>
                          <Text type="secondary">تخصص</Text>
                          <br />
                          <Text>{appointment.doctorSpecialty}</Text>
                        </div>
                      </Col>
                    </Row>

                    <Row gutter={[16, 16]}>
                      <Col span={12}>
                        <div>
                          <Text type="secondary">تاریخ</Text>
                          <br />
                          <Text strong>{dayjs(appointment.date).format('YYYY/MM/DD')}</Text>
                        </div>
                      </Col>
                      <Col span={12}>
                        <div>
                          <Text type="secondary">ساعت</Text>
                          <br />
                          <Tag color="blue" style={{ fontSize: '14px', padding: '4px 12px' }}>
                            {appointment.time}
                          </Tag>
                        </div>
                      </Col>
                    </Row>

                    <Divider />

                    <Row gutter={[16, 16]}>
                      <Col span={12}>
                        <div>
                          <Text type="secondary">هزینه پرداختی</Text>
                          <br />
                          <Text strong style={{ fontSize: '18px', color: '#2563eb' }}>
                            {appointment.fee?.toLocaleString() || 0} تومان
                          </Text>
                        </div>
                      </Col>
                      <Col span={12}>
                        <div>
                          <Text type="secondary">روش پرداخت</Text>
                          <br />
                          <Tag color="green">{appointment.paymentMethod || 'کیف پول'}</Tag>
                        </div>
                      </Col>
                    </Row>

                    {appointment.discount > 0 && (
                        <div>
                          <Text type="secondary">تخفیف</Text>
                          <br />
                          <Tag color="gold">{appointment.discount.toLocaleString()} تومان</Tag>
                        </div>
                    )}

                    {appointment.invoiceNumber && (
                        <div>
                          <Text type="secondary">شماره فاکتور</Text>
                          <br />
                          <Text strong>{appointment.invoiceNumber}</Text>
                        </div>
                    )}
                  </Space>
                </Card>
              </Col>

              {/* QR Code و اقدامات */}
              <Col xs={24} lg={8}>
                <Card title="📱 کارت نوبت" style={{ borderRadius: '12px', textAlign: 'center' }}>
                  <div style={{
                    padding: '16px',
                    background: '#f8fafc',
                    borderRadius: '12px',
                    marginBottom: '16px'
                  }}>
                    <QRCode
                        value={JSON.stringify({
                          appointmentId: appointment.appointmentId,
                          date: appointment.date,
                          time: appointment.time,
                          doctor: appointment.doctorName,
                        })}
                        size={150}
                        style={{ margin: '0 auto' }}
                    />
                  </div>
                  <Text type="secondary" style={{ fontSize: '12px' }}>
                    کد QR را در مطب نمایش دهید
                  </Text>

                  <Divider />

                  <Space direction="vertical" style={{ width: '100%' }} size="middle">
                    <Button
                        icon={<PrinterOutlined />}
                        onClick={handlePrint}
                        block
                    >
                      پرینت فاکتور
                    </Button>
                    <Button
                        icon={<DownloadOutlined />}
                        onClick={handleDownloadPDF}
                        block
                    >
                      دانلود PDF
                    </Button>
                    <Button
                        icon={<WhatsAppOutlined />}
                        onClick={handleShareWhatsApp}
                        block
                        style={{ color: '#25d366', borderColor: '#25d366' }}
                    >
                      اشتراک در واتساپ
                    </Button>
                  </Space>
                </Card>

                <Card style={{ borderRadius: '12px', marginTop: '16px' }}>
                  <Space direction="vertical" style={{ width: '100%' }}>
                    <Button
                        type="primary"
                        onClick={() => router.push(`/${locale}/profile/appointments`)}
                        block
                    >
                      📋 مشاهده نوبت‌های من
                    </Button>
                    <Button
                        onClick={() => router.push(`/${locale}/doctors`)}
                        block
                    >
                      👨‍⚕️ نوبت جدید
                    </Button>
                  </Space>
                </Card>
              </Col>
            </Row>
          </div>
        </main>
        <Footer />
      </>
  );
}
