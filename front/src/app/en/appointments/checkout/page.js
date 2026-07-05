'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Button, Typography, Spin, Empty, Tag, message, 
  Divider, Alert, Space, Input, Radio, Statistic, Modal
} from 'antd';
import { 
  CheckCircleOutlined, WalletOutlined, CreditCardOutlined, 
  LeftOutlined, FilePdfOutlined, GiftOutlined, 
  QuestionCircleOutlined, SafetyOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import dayjs from 'dayjs';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function CheckoutPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [appointmentData, setAppointmentData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('wallet');
  const [walletBalance, setWalletBalance] = useState(0);
  const [discountCode, setDiscountCode] = useState('');
  const [discountApplied, setDiscountApplied] = useState(null);
  const [applyingDiscount, setApplyingDiscount] = useState(false);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  // دریافت اطلاعات از localStorage
  useEffect(() => {
    const stored = localStorage.getItem('appointmentData');
    if (!stored) {
      router.push(`/${locale}/doctors`);
      return;
    }
    try {
      setAppointmentData(JSON.parse(stored));
    } catch {
      router.push(`/${locale}/doctors`);
    }
  }, []);

  // دریافت موجودی کیف پول
  const fetchWalletBalance = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/wallet/balance`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setWalletBalance(data.data.balance || 0);
      }
    } catch (error) {
      console.error('Error fetching wallet balance:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (appointmentData) {
      fetchWalletBalance();
    }
  }, [appointmentData]);

  // اعمال کد تخفیف
  const handleApplyDiscount = async () => {
    if (!discountCode.trim()) {
      message.warning('لطفاً کد تخفیف را وارد کنید');
      return;
    }

    setApplyingDiscount(true);
    const token = getToken();

    try {
      const res = await fetch(`${API_URL}/api/discounts/validate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          code: discountCode,
          doctor_id: appointmentData?.doctorId,
          amount: appointmentData?.doctorFee || 0,
        }),
      });
      const data = await res.json();
      if (data.success) {
        setDiscountApplied(data.data);
        message.success(data.data.message || 'کد تخفیف با موفقیت اعمال شد');
      } else {
        message.error(data.message || 'کد تخفیف نامعتبر است');
        setDiscountApplied(null);
      }
    } catch (error) {
      console.error('Error applying discount:', error);
      message.error('خطا در اعتبارسنجی کد تخفیف');
    } finally {
      setApplyingDiscount(false);
    }
  };

  // محاسبه مبلغ نهایی
  const calculateFinalPrice = () => {
    const basePrice = appointmentData?.doctorFee || 0;
    if (discountApplied) {
      if (discountApplied.type === 'percentage') {
        return basePrice - (basePrice * discountApplied.value / 100);
      } else if (discountApplied.type === 'fixed') {
        return Math.max(0, basePrice - discountApplied.value);
      }
    }
    return basePrice;
  };

  const finalPrice = calculateFinalPrice();
  const isFree = finalPrice === 0;
  const canUseWallet = walletBalance >= finalPrice;

  // پرداخت با کیف پول
  const handleWalletPayment = async () => {
    if (!canUseWallet && !isFree) {
      message.warning('موجودی کیف پول شما کافی نیست');
      return;
    }

    setSubmitting(true);
    const token = getToken();

    try {
      // 1. رزرو نوبت
      const bookRes = await fetch(`${API_URL}/api/appointments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          doctor_id: appointmentData.doctorId,
          date: appointmentData.date,
          start_time: appointmentData.time,
          notes: '',
        }),
      });
      const bookData = await bookRes.json();

      if (!bookData.success) {
        message.error(bookData.message || 'خطا در رزرو نوبت');
        setSubmitting(false);
        return;
      }

      const appointmentId = bookData.data.id;

      // 2. پرداخت با کیف پول (اگر نوبت پولی بود)
      if (!isFree) {
        const payRes = await fetch(`${API_URL}/api/wallet/pay-appointment`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            appointment_id: appointmentId,
            discount_code: discountApplied?.code || null,
          }),
        });
        const payData = await payRes.json();

        if (!payData.success) {
          message.error(payData.message || 'خطا در پرداخت');
          setSubmitting(false);
          return;
        }
      }

      // 3. دریافت فاکتور
      const invoiceRes = await fetch(`${API_URL}/api/invoices/my`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const invoiceData = await invoiceRes.json();
      
      const invoice = invoiceData.success ? invoiceData.data?.find(i => i.appointment_id === appointmentId) : null;

      // ذخیره اطلاعات نوبت برای صفحه تایید
      const confirmationData = {
        appointmentId,
        invoiceId: invoice?.id || null,
        doctorName: appointmentData.doctorName,
        doctorSpecialty: appointmentData.doctorSpecialty,
        date: appointmentData.date,
        time: appointmentData.time,
        fee: finalPrice,
        discount: discountApplied ? (appointmentData.doctorFee - finalPrice) : 0,
        paymentMethod: isFree ? 'رایگان' : 'کیف پول',
        status: 'confirmed',
        invoiceNumber: invoice?.invoice_number || null,
      };
      localStorage.setItem('appointmentConfirmation', JSON.stringify(confirmationData));

      // حذف داده‌های موقت
      localStorage.removeItem('appointmentData');

      message.success('✅ نوبت با موفقیت رزرو شد');
      router.push(`/${locale}/appointments/confirmation`);
    } catch (error) {
      console.error('Error processing payment:', error);
      message.error('خطا در پردازش پرداخت');
    } finally {
      setSubmitting(false);
    }
  };

  // پرداخت با درگاه
  const handleGatewayPayment = async () => {
    setSubmitting(true);
    const token = getToken();

    try {
      // 1. رزرو نوبت
      const bookRes = await fetch(`${API_URL}/api/appointments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          doctor_id: appointmentData.doctorId,
          date: appointmentData.date,
          start_time: appointmentData.time,
          notes: '',
        }),
      });
      const bookData = await bookRes.json();

      if (!bookData.success) {
        message.error(bookData.message || 'خطا در رزرو نوبت');
        setSubmitting(false);
        return;
      }

      const appointmentId = bookData.data.id;

      // 2. شروع پرداخت با درگاه
      const payRes = await fetch(`${API_URL}/api/payments/initiate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          appointment_id: appointmentId,
          amount: finalPrice,
          gateway: 'zarinpal',
          discount_code: discountApplied?.code || null,
          callback_url: `${window.location.origin}/${locale}/appointments/confirmation?status=success`,
        }),
      });
      const payData = await payRes.json();

      if (payData.success && payData.data.payment_url) {
        // ذخیره اطلاعات موقت
        localStorage.setItem('pendingAppointment', JSON.stringify({
          appointmentId,
          appointmentData,
          discountApplied,
        }));
        // هدایت به درگاه پرداخت
        window.location.href = payData.data.payment_url;
      } else {
        message.error(payData.message || 'خطا در شروع پرداخت');
        setSubmitting(false);
      }
    } catch (error) {
      console.error('Error initiating payment:', error);
      message.error('خطا در ارتباط با درگاه پرداخت');
      setSubmitting(false);
    }
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

  if (!appointmentData) {
    return (
      <>
        <Header />
        <div className="container" style={{ padding: '40px 20px', textAlign: 'center' }}>
          <Title level={4}>اطلاعات نوبت یافت نشد</Title>
          <Button type="primary" onClick={() => router.push(`/${locale}/doctors`)}>
            انتخاب نوبت جدید
          </Button>
        </div>
        <Footer />
      </>
    );
  }

  const discountAmount = appointmentData.doctorFee - finalPrice;

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '800px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />

          <Title level={2}>💳 {t('appointments.checkout')}</Title>
          <Text type="secondary">تایید و پرداخت نوبت</Text>

          <Row gutter={[24, 24]} style={{ marginTop: '16px' }}>
            {/* خلاصه نوبت */}
            <Col xs={24} lg={8}>
              <Card title="📋 خلاصه نوبت" style={{ borderRadius: '12px' }}>
                <Space direction="vertical" style={{ width: '100%' }} size="small">
                  <div>
                    <Text type="secondary">پزشک</Text>
                    <br />
                    <Text strong>{appointmentData.doctorName}</Text>
                  </div>
                  <div>
                    <Text type="secondary">تخصص</Text>
                    <br />
                    <Text>{appointmentData.doctorSpecialty}</Text>
                  </div>
                  <div>
                    <Text type="secondary">تاریخ</Text>
                    <br />
                    <Text>{dayjs(appointmentData.date).format('YYYY/MM/DD')}</Text>
                  </div>
                  <div>
                    <Text type="secondary">ساعت</Text>
                    <br />
                    <Tag color="blue">{appointmentData.time}</Tag>
                  </div>
                  <Divider />
                  <div>
                    <Text type="secondary">هزینه ویزیت</Text>
                    <br />
                    <Text strong style={{ fontSize: '18px' }}>
                      {appointmentData.doctorFee.toLocaleString()} تومان
                    </Text>
                  </div>
                </Space>
              </Card>
            </Col>

            {/* چک‌اوت */}
            <Col xs={24} lg={16}>
              <Card title="💳 پرداخت" style={{ borderRadius: '12px' }}>
                {/* کد تخفیف */}
                <div style={{ marginBottom: '16px' }}>
                  <Text strong><GiftOutlined /> کد تخفیف</Text>
                  <div style={{ display: 'flex', gap: '8px', marginTop: '8px' }}>
                    <Input
                      placeholder="کد تخفیف را وارد کنید"
                      value={discountCode}
                      onChange={(e) => setDiscountCode(e.target.value.toUpperCase())}
                      disabled={!!discountApplied}
                      style={{ flex: 1 }}
                    />
                    <Button
                      type="primary"
                      onClick={handleApplyDiscount}
                      loading={applyingDiscount}
                      disabled={!!discountApplied || !discountCode.trim()}
                    >
                      {discountApplied ? 'اعمال شده' : 'اعمال'}
                    </Button>
                  </div>
                  {discountApplied && (
                    <div style={{ marginTop: '8px', padding: '8px', background: '#f0fdf4', borderRadius: '8px' }}>
                      <Text style={{ color: '#10b981' }}>
                        ✅ {discountApplied.message || 'کد تخفیف اعمال شد'}
                      </Text>
                      <br />
                      <Text type="secondary">
                        تخفیف: {discountAmount.toLocaleString()} تومان
                      </Text>
                    </div>
                  )}
                </div>

                <Divider />

                {/* روش پرداخت */}
                <div>
                  <Text strong>انتخاب روش پرداخت</Text>
                  <Radio.Group 
                    value={paymentMethod} 
                    onChange={(e) => setPaymentMethod(e.target.value)}
                    style={{ width: '100%', marginTop: '8px' }}
                  >
                    <Space direction="vertical" style={{ width: '100%' }}>
                      <Radio value="wallet" disabled={isFree}>
                        <Space>
                          <WalletOutlined />
                          <span>کیف پول</span>
                          <Tag color="blue">{walletBalance.toLocaleString()} تومان</Tag>
                          {!canUseWallet && !isFree && (
                            <Text type="danger" style={{ fontSize: '12px' }}>
                              (موجودی کافی نیست)
                            </Text>
                          )}
                        </Space>
                      </Radio>
                      <Radio value="gateway">
                        <Space>
                          <CreditCardOutlined />
                          <span>درگاه پرداخت (زرین‌پال)</span>
                          <Tag color="green">امن</Tag>
                        </Space>
                      </Radio>
                      {isFree && (
                        <Alert
                          message="این نوبت رایگان است"
                          type="success"
                          showIcon
                          style={{ marginTop: '8px' }}
                        />
                      )}
                    </Space>
                  </Radio.Group>
                </div>

                <Divider />

                {/* مبلغ نهایی */}
                <div style={{ 
                  padding: '16px', 
                  background: '#f0f5ff', 
                  borderRadius: '8px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center'
                }}>
                  <div>
                    <Text type="secondary">مبلغ قابل پرداخت</Text>
                    <Title level={3} style={{ margin: 0, color: '#2563eb' }}>
                      {finalPrice.toLocaleString()} تومان
                    </Title>
                    {discountApplied && (
                      <Text type="secondary" style={{ fontSize: '12px' }}>
                        (تخفیف: {discountAmount.toLocaleString()} تومان)
                      </Text>
                    )}
                  </div>
                  <SafetyOutlined style={{ fontSize: '32px', color: '#2563eb' }} />
                </div>

                <div style={{ marginTop: '16px', display: 'flex', gap: '8px' }}>
                  <Button 
                    onClick={() => router.push(`/${locale}/appointments/new?doctorId=${appointmentData.doctorId}`)}
                    icon={<LeftOutlined />}
                    size="large"
                  >
                    بازگشت
                  </Button>
                  <Button
                    type="primary"
                    size="large"
                    onClick={paymentMethod === 'wallet' ? handleWalletPayment : handleGatewayPayment}
                    loading={submitting}
                    disabled={!canUseWallet && paymentMethod === 'wallet' && !isFree}
                    style={{ flex: 1 }}
                  >
                    {isFree ? 'تایید نوبت رایگان' : 'پرداخت و تایید نوبت'}
                  </Button>
                </div>
              </Card>
            </Col>
          </Row>
        </div>
      </main>
      <Footer />
    </>
  );
}
