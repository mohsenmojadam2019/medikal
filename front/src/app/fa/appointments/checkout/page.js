// /home/god/Videos/medikal/front/src/app/fa/appointments/checkout/page.js
'use client';

import { useState, useEffect } from 'react';
import {
  Card, Row, Col, Button, Typography, Spin, Tag,
  Space, Divider, Alert, Input, Radio, Avatar, Modal, Empty, App
} from 'antd';
import {
  CheckCircleOutlined, WalletOutlined, CreditCardOutlined,
  LeftOutlined, GiftOutlined,
  SafetyOutlined, UserOutlined,
  DollarOutlined,
  ClockCircleOutlined, CalendarOutlined,
  ReloadOutlined, LoginOutlined
} from '@ant-design/icons';
import { useRouter, useSearchParams } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

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

const getToken = () => {
  if (typeof window !== 'undefined') {
    return localStorage.getItem('token');
  }
  return null;
};

export default function CheckoutPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { locale } = useLanguage();
  const { message: appMessage } = App.useApp();

  // ✅ Stateها
  const [appointmentData, setAppointmentData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('wallet');
  const [walletBalance, setWalletBalance] = useState(0);
  const [discountCode, setDiscountCode] = useState('');
  const [discountApplied, setDiscountApplied] = useState(null);
  const [applyingDiscount, setApplyingDiscount] = useState(false);
  const [gateways, setGateways] = useState([]);
  const [selectedGateway, setSelectedGateway] = useState(null);
  const [gatewayModalVisible, setGatewayModalVisible] = useState(false);
  const [fetchingGateways, setFetchingGateways] = useState(false);
  const [invoice, setInvoice] = useState(null);
  const [fetchingInvoice, setFetchingInvoice] = useState(false);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  // ✅ بررسی لاگین و بازیابی اطلاعات نوبت
  useEffect(() => {
    const token = getToken();
    const isLoggedIn = !!token;
    setIsLoggedIn(isLoggedIn);

    // ✅ بازیابی اطلاعات نوبت از localStorage
    let storedData = localStorage.getItem('appointmentData');

    // ✅ اگر در appointmentData نبود، از tempAppointment استفاده کن
    if (!storedData) {
      const tempData = localStorage.getItem('tempAppointment');
      if (tempData) {
        try {
          const parsed = JSON.parse(tempData);
          // ✅ بررسی انقضا (حداکثر 30 دقیقه)
          if (Date.now() - parsed.timestamp < 30 * 60 * 1000) {
            storedData = tempData;
          } else {
            appMessage.warning('زمان رزرو منقضی شده است. لطفاً دوباره اقدام کنید.');
            localStorage.removeItem('tempAppointment');
            router.push(`/${locale}/doctors`);
            return;
          }
        } catch (error) {
          console.error('Error parsing tempAppointment:', error);
        }
      }
    }

    if (!storedData) {
      appMessage.warning('اطلاعات نوبت یافت نشد. لطفاً از صفحه انتخاب نوبت اقدام کنید.');
      router.push(`/${locale}/doctors`);
      return;
    }

    try {
      const data = JSON.parse(storedData);

      // ✅ اگر appointmentId ندارد و کاربر لاگین نیست، لاگین لازم است
      if (!data.appointmentId && !isLoggedIn) {
        setShowLoginModal(true);
        setLoading(false);
        return;
      }

      setAppointmentData(data);

      // ✅ اگر لاگین است، اطلاعات کامل را دریافت کن
      if (isLoggedIn && data.appointmentId) {
        fetchInvoice(data.appointmentId);
        fetchWalletBalance();
        fetchGateways();
      }

      setLoading(false);
    } catch (error) {
      console.error('Error parsing data:', error);
      appMessage.error('خطا در خواندن اطلاعات نوبت');
      router.push(`/${locale}/doctors`);
    }
  }, [locale, router, appMessage]);

  // ✅ دریافت فاکتور
  const fetchInvoice = async (appointmentId) => {
    if (!appointmentId) return null;

    setFetchingInvoice(true);
    try {
      const token = getToken();
      if (!token) {
        setShowLoginModal(true);
        return null;
      }

      const res = await fetch(`${API_URL}/api/invoices/appointment/${appointmentId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();

      if (data.success && data.data) {
        setInvoice(data.data);
        return data.data;
      } else {
        console.log('Invoice not found:', data.message);
        return null;
      }
    } catch (error) {
      console.error('Error fetching invoice:', error);
      return null;
    } finally {
      setFetchingInvoice(false);
    }
  };

  // ✅ دریافت موجودی کیف پول
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
        setWalletBalance(data.data?.balance || 0);
      }
    } catch (error) {
      console.error('Error fetching wallet balance:', error);
    }
  };

  // ✅ دریافت درگاه‌های پرداخت
  const fetchGateways = async () => {
    const token = getToken();
    if (!token) {
      setGateways([
        { name: 'local', title: 'درگاه تست (آفلاین)', icon: '🔄', is_default: true }
      ]);
      setSelectedGateway('local');
      return;
    }

    setFetchingGateways(true);
    try {
      const res = await fetch(`${API_URL}/api/payments/gateways`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();
      if (data.success) {
        const availableGateways = data.data?.available || [];
        setGateways(availableGateways);
        const defaultGateway = data.data?.default || availableGateways[0]?.name || 'local';
        setSelectedGateway(defaultGateway);
      } else {
        setGateways([
          { name: 'local', title: 'درگاه تست (آفلاین)', icon: '🔄', is_default: true }
        ]);
        setSelectedGateway('local');
      }
    } catch (error) {
      console.error('Error fetching gateways:', error);
      setGateways([
        { name: 'local', title: 'درگاه تست (آفلاین)', icon: '🔄', is_default: true }
      ]);
      setSelectedGateway('local');
    } finally {
      setFetchingGateways(false);
    }
  };

  // ✅ اعمال کد تخفیف
  const handleApplyDiscount = async () => {
    if (!discountCode.trim()) {
      appMessage.warning('لطفاً کد تخفیف را وارد کنید');
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
          amount: appointmentData?.doctorFee || 0,
        }),
      });
      const data = await res.json();
      if (data.success) {
        setDiscountApplied(data.data);
        appMessage.success(data.data?.message || 'کد تخفیف با موفقیت اعمال شد');
      } else {
        appMessage.error(data.message || 'کد تخفیف نامعتبر است');
        setDiscountApplied(null);
      }
    } catch (error) {
      console.error('Error validating discount:', error);
      appMessage.error('خطا در اعتبارسنجی کد تخفیف');
      setDiscountApplied(null);
    } finally {
      setApplyingDiscount(false);
    }
  };

  // ✅ محاسبه قیمت نهایی
  const calculateFinalPrice = () => {
    const basePrice = appointmentData?.doctorFee || 0;
    if (discountApplied) {
      if (discountApplied.type === 'percentage') {
        return Math.max(0, basePrice - (basePrice * discountApplied.value / 100));
      } else if (discountApplied.type === 'fixed') {
        return Math.max(0, basePrice - discountApplied.value);
      }
    }
    return basePrice;
  };

  const finalPrice = calculateFinalPrice();
  const isFree = finalPrice === 0;
  const canUseWallet = walletBalance >= finalPrice;

  // ✅ تابع هدایت به لاگین با ذخیره اطلاعات
  const handleGoToLogin = () => {
    setShowLoginModal(false);

    // ✅ ذخیره اطلاعات نوبت در tempAppointment
    const tempData = {
      doctorId: appointmentData?.doctorId || null,
      doctorName: appointmentData?.doctorName || '',
      doctorSpecialty: appointmentData?.doctorSpecialty || '',
      date: appointmentData?.date || '',
      time: appointmentData?.time || '',
      doctorFee: appointmentData?.doctorFee || 0,
      appointmentId: appointmentData?.appointmentId || null,
      timestamp: Date.now(),
      fromPage: 'checkout'
    };
    localStorage.setItem('tempAppointment', JSON.stringify(tempData));

    // ✅ هدایت به لاگین
    router.push(`/${locale}/login?redirect=/${locale}/appointments/checkout`);
  };

  // ✅ پرداخت با کیف پول
  const handleWalletPayment = async () => {
    if (!isLoggedIn) {
      setShowLoginModal(true);
      return;
    }

    if (!canUseWallet && !isFree) {
      appMessage.warning('موجودی کیف پول شما کافی نیست');
      return;
    }

    setSubmitting(true);
    const token = getToken();

    try {
      const appointmentId = appointmentData.appointmentId;
      if (!appointmentId) {
        appMessage.error('شناسه نوبت یافت نشد');
        setSubmitting(false);
        return;
      }

      let currentInvoice = invoice;
      if (!currentInvoice) {
        currentInvoice = await fetchInvoice(appointmentId);
        if (!currentInvoice) {
          appMessage.error('فاکتور یافت نشد. لطفاً دوباره تلاش کنید.');
          setSubmitting(false);
          return;
        }
      }

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
          appMessage.error(payData.message || 'خطا در پرداخت');
          setSubmitting(false);
          return;
        }
      }

      const confirmationData = {
        appointmentId,
        invoiceId: currentInvoice?.id || null,
        doctorName: appointmentData.doctorName,
        doctorSpecialty: appointmentData.doctorSpecialty,
        date: appointmentData.date,
        time: appointmentData.time,
        fee: finalPrice,
        discount: discountApplied ? (appointmentData.doctorFee - finalPrice) : 0,
        paymentMethod: isFree ? 'رایگان' : 'کیف پول',
        status: 'confirmed',
        invoiceNumber: currentInvoice?.invoice_number || null,
      };
      localStorage.setItem('appointmentConfirmation', JSON.stringify(confirmationData));
      localStorage.removeItem('appointmentData');
      localStorage.removeItem('tempAppointment');

      appMessage.success('✅ پرداخت با موفقیت انجام شد');
      router.push(`/${locale}/appointments/confirmation`);
    } catch (error) {
      console.error('Error in wallet payment:', error);
      appMessage.error('خطا در پردازش پرداخت');
    } finally {
      setSubmitting(false);
    }
  };

  // ✅ پرداخت با درگاه
  const handleGatewayPayment = async (gateway) => {
    if (!isLoggedIn) {
      setShowLoginModal(true);
      return;
    }

    setSubmitting(true);
    setGatewayModalVisible(false);
    const token = getToken();

    try {
      const appointmentId = appointmentData.appointmentId;
      if (!appointmentId) {
        appMessage.error('شناسه نوبت یافت نشد');
        setSubmitting(false);
        return;
      }

      let currentInvoice = invoice;
      if (!currentInvoice) {
        currentInvoice = await fetchInvoice(appointmentId);
        if (!currentInvoice) {
          appMessage.error('فاکتور یافت نشد. لطفاً دوباره تلاش کنید.');
          setSubmitting(false);
          return;
        }
      }

      const payRes = await fetch(`${API_URL}/api/payments/initiate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          invoice_id: currentInvoice.id,
          gateway: gateway,
          amount: finalPrice,
          discount_code: discountApplied?.code || null,
        }),
      });
      const payData = await payRes.json();

      if (payData.success) {
        if (payData.data?.redirect_url) {
          window.location.href = payData.data.redirect_url;
        } else {
          appMessage.success('پرداخت با موفقیت انجام شد');
          const confirmationData = {
            appointmentId,
            invoiceId: currentInvoice.id,
            doctorName: appointmentData.doctorName,
            doctorSpecialty: appointmentData.doctorSpecialty,
            date: appointmentData.date,
            time: appointmentData.time,
            fee: finalPrice,
            discount: discountApplied ? (appointmentData.doctorFee - finalPrice) : 0,
            paymentMethod: 'درگاه پرداخت',
            status: 'confirmed',
            invoiceNumber: currentInvoice.invoice_number,
          };
          localStorage.setItem('appointmentConfirmation', JSON.stringify(confirmationData));
          localStorage.removeItem('appointmentData');
          localStorage.removeItem('tempAppointment');
          router.push(`/${locale}/appointments/confirmation`);
        }
      } else {
        appMessage.error(payData.message || 'خطا در شروع پرداخت');
        setSubmitting(false);
      }
    } catch (error) {
      console.error('Error:', error);
      appMessage.error('خطا در ارتباط با درگاه پرداخت');
      setSubmitting(false);
    } finally {
      setSubmitting(false);
    }
  };

  // ✅ پرداخت اصلی
  const handlePayment = async () => {
    if (!isLoggedIn) {
      setShowLoginModal(true);
      return;
    }

    if (paymentMethod === 'wallet') {
      await handleWalletPayment();
    } else {
      if (gateways.length === 0) {
        appMessage.info('در حال بارگیری لیست درگاه‌ها...');
        await fetchGateways();
        if (gateways.length === 0) {
          appMessage.error('هیچ درگاه پرداختی در دسترس نیست');
          return;
        }
      }
      setGatewayModalVisible(true);
    }
  };

  // ✅ نمایش لودینگ
  if (loading) {
    return (
        <>
          <Header />
          <LoadingSpinner />
          <Footer />
        </>
    );
  }

  // ✅ اگر اطلاعات نوبت وجود نداشت
  if (!appointmentData) {
    return (
        <>
          <Header />
          <div style={{ textAlign: 'center', padding: '60px 20px' }}>
            <Title level={4}>اطلاعات نوبت یافت نشد</Title>
            <Button onClick={() => router.push(`/${locale}/doctors`)}>
              بازگشت به پزشکان
            </Button>
          </div>
          <Footer />
        </>
    );
  }

  const discountAmount = appointmentData.doctorFee - finalPrice;
  const persianDate = toPersianDate(appointmentData.date);
  const formattedTime = formatTime(appointmentData.time);
  const doctorName = appointmentData.doctorName || 'پزشک';
  const doctorSpecialty = appointmentData.doctorSpecialty || 'عمومی';

  return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '900px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb />

            <Title level={2} style={{ marginBottom: '4px' }}>💳 تایید و پرداخت</Title>
            <Text type="secondary">اطلاعات نوبت را بررسی و پرداخت را تکمیل کنید</Text>

            {/* ✅ هشدار لاگین */}
            {!isLoggedIn && (
                <Alert
                    message="برای تکمیل پرداخت نیاز به ورود دارید"
                    description="لطفاً وارد حساب کاربری خود شوید تا بتوانید پرداخت را تکمیل کنید."
                    type="warning"
                    showIcon
                    action={
                      <Button size="small" type="primary" icon={<LoginOutlined />} onClick={handleGoToLogin}>
                        ورود / ثبت‌نام
                      </Button>
                    }
                    style={{ marginTop: 16, borderRadius: '12px' }}
                />
            )}

            <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
              <Col xs={24} lg={10}>
                <Card
                    title="📋 خلاصه نوبت"
                    style={{ borderRadius: '16px' }}
                    styles={{ body: { padding: '20px' } }}
                >
                  <Space orientation="vertical" style={{ width: '100%' }} size="middle">
                    <div>
                      <Text type="secondary" style={{ fontSize: '12px' }}>پزشک</Text>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginTop: '4px' }}>
                        <Avatar size={32} style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}>
                          {doctorName.charAt(0)}
                        </Avatar>
                        <Text strong style={{ fontSize: '16px' }}>{doctorName}</Text>
                      </div>
                    </div>

                    <div>
                      <Text type="secondary" style={{ fontSize: '12px' }}>تخصص</Text>
                      <div style={{ marginTop: '4px' }}>
                        <Tag color="blue">{doctorSpecialty}</Tag>
                      </div>
                    </div>

                    <div>
                      <Text type="secondary" style={{ fontSize: '12px' }}>تاریخ و ساعت</Text>
                      <div style={{ marginTop: '4px' }}>
                        <Space>
                          <Tag icon={<CalendarOutlined />} color="blue">{persianDate}</Tag>
                          <Tag icon={<ClockCircleOutlined />} color="green">{formattedTime}</Tag>
                        </Space>
                      </div>
                    </div>

                    <Divider style={{ margin: '8px 0' }} />

                    <div>
                      <Text type="secondary" style={{ fontSize: '12px' }}>هزینه ویزیت</Text>
                      <div style={{ marginTop: '4px' }}>
                        <Text strong style={{ fontSize: '20px', color: '#2563eb' }}>
                          {appointmentData.doctorFee?.toLocaleString() || '۰'}
                        </Text>
                        <Text type="secondary"> تومان</Text>
                      </div>
                    </div>
                  </Space>
                </Card>

                <Card
                    style={{ marginTop: '16px', borderRadius: '16px', background: '#f0f5ff' }}
                    styles={{ body: { padding: '16px' } }}
                >
                  <Row gutter={[8, 8]}>
                    <Col span={12}>
                      <Text type="secondary">هزینه ویزیت</Text>
                    </Col>
                    <Col span={12} style={{ textAlign: 'left' }}>
                      <Text>{appointmentData.doctorFee?.toLocaleString() || '۰'} تومان</Text>
                    </Col>
                    {discountAmount > 0 && (
                        <>
                          <Col span={12}>
                            <Text type="secondary" style={{ color: '#10b981' }}>تخفیف</Text>
                          </Col>
                          <Col span={12} style={{ textAlign: 'left' }}>
                            <Text style={{ color: '#10b981' }}>- {discountAmount.toLocaleString()} تومان</Text>
                          </Col>
                        </>
                    )}
                    <Divider style={{ margin: '4px 0' }} />
                    <Col span={12}>
                      <Text strong>مبلغ قابل پرداخت</Text>
                    </Col>
                    <Col span={12} style={{ textAlign: 'left' }}>
                      <Text strong style={{ fontSize: '18px', color: '#2563eb' }}>
                        {finalPrice.toLocaleString()} تومان
                      </Text>
                    </Col>
                  </Row>
                </Card>
              </Col>

              <Col xs={24} lg={14}>
                <Card
                    title="💳 اطلاعات پرداخت"
                    style={{ borderRadius: '16px' }}
                    styles={{ body: { padding: '20px' } }}
                >
                  <div style={{ marginBottom: '20px' }}>
                    <Text strong><GiftOutlined /> کد تخفیف</Text>
                    <div style={{ display: 'flex', gap: '8px', marginTop: '8px' }}>
                      <Input
                          placeholder="کد تخفیف را وارد کنید"
                          value={discountCode}
                          onChange={(e) => setDiscountCode(e.target.value.toUpperCase())}
                          disabled={!!discountApplied || !isLoggedIn}
                          style={{ flex: 1 }}
                      />
                      <Button
                          type="primary"
                          onClick={handleApplyDiscount}
                          loading={applyingDiscount}
                          disabled={!!discountApplied || !discountCode.trim() || !isLoggedIn}
                      >
                        {discountApplied ? 'اعمال شده' : 'اعمال'}
                      </Button>
                    </div>
                    {discountApplied && (
                        <div style={{ marginTop: '8px', padding: '8px 12px', background: '#f0fdf4', borderRadius: '8px' }}>
                          <Text style={{ color: '#10b981' }}>
                            ✅ {discountApplied.message || 'کد تخفیف اعمال شد'}
                          </Text>
                        </div>
                    )}
                  </div>

                  <Divider style={{ margin: '12px 0' }} />

                  <div>
                    <Text strong>انتخاب روش پرداخت</Text>
                    <Radio.Group
                        value={paymentMethod}
                        onChange={(e) => setPaymentMethod(e.target.value)}
                        style={{ width: '100%', marginTop: '8px' }}
                        disabled={!isLoggedIn}
                    >
                      <Space orientation="vertical" style={{ width: '100%' }} size="middle">
                        <Radio value="wallet" disabled={isFree}>
                          <Space>
                            <WalletOutlined style={{ fontSize: '18px', color: '#2563eb' }} />
                            <div>
                              <div>کیف پول</div>
                              <Text type="secondary" style={{ fontSize: '12px' }}>
                                موجودی: {walletBalance?.toLocaleString() || '۰'} تومان
                              </Text>
                            </div>
                            {!canUseWallet && !isFree && (
                                <Tag color="red" style={{ marginRight: 0 }}>موجودی کافی نیست</Tag>
                            )}
                          </Space>
                        </Radio>
                        <Radio value="gateway">
                          <Space>
                            <CreditCardOutlined style={{ fontSize: '18px', color: '#10b981' }} />
                            <div>
                              <div>درگاه پرداخت</div>
                              <Text type="secondary" style={{ fontSize: '12px' }}>
                                {gateways.length > 0 ? `${gateways.length} درگاه موجود` : 'بارگیری درگاه‌ها...'}
                              </Text>
                            </div>
                            <Tag color="green" style={{ marginRight: 0 }}>امن</Tag>
                          </Space>
                        </Radio>
                      </Space>
                    </Radio.Group>
                  </div>

                  {!isLoggedIn && (
                      <Alert
                          title="برای پرداخت نیاز به ورود دارید"
                          description="لطفاً ابتدا وارد حساب کاربری خود شوید."
                          type="warning"
                          showIcon
                          style={{ marginTop: '16px' }}
                          action={
                            <Button size="small" type="primary" onClick={handleGoToLogin}>
                              ورود
                            </Button>
                          }
                      />
                  )}

                  {isFree && isLoggedIn && (
                      <Alert
                          title="این نوبت رایگان است"
                          type="success"
                          showIcon
                          style={{ marginTop: '16px' }}
                      />
                  )}

                  {paymentMethod === 'wallet' && !isFree && !canUseWallet && isLoggedIn && (
                      <Alert
                          title="موجودی کیف پول کافی نیست"
                          description="لطفاً روش پرداخت دیگری را انتخاب کنید یا کیف پول خود را شارژ کنید"
                          type="warning"
                          showIcon
                          style={{ marginTop: '16px' }}
                      />
                  )}

                  <div style={{ marginTop: '24px', display: 'flex', gap: '12px' }}>
                    <Button
                        onClick={() => router.push(`/${locale}/appointments/new?doctorId=${appointmentData.doctorId}`)}
                        icon={<LeftOutlined />}
                        size="large"
                        style={{ borderRadius: '12px' }}
                    >
                      بازگشت
                    </Button>
                    <Button
                        type="primary"
                        size="large"
                        onClick={handlePayment}
                        loading={submitting}
                        disabled={!isLoggedIn || (paymentMethod === 'wallet' && !canUseWallet && !isFree)}
                        style={{
                          flex: 1,
                          borderRadius: '12px',
                          height: '48px',
                          fontWeight: 'bold',
                        }}
                    >
                      {!isLoggedIn ? 'ابتدا وارد شوید' : isFree ? 'تایید نوبت رایگان' : 'پرداخت و تایید نوبت'}
                    </Button>
                  </div>

                  <div style={{ marginTop: '16px', textAlign: 'center' }}>
                    <Space>
                      <SafetyOutlined style={{ color: '#94a3b8' }} />
                      <Text type="secondary" style={{ fontSize: '12px' }}>
                        اطلاعات شما محفوظ است
                      </Text>
                    </Space>
                  </div>
                </Card>
              </Col>
            </Row>
          </div>
        </main>

        {/* ✅ مودال لاگین */}
        <Modal
            title="🔐 برای ادامه نیاز به ورود دارید"
            open={showLoginModal}
            onCancel={() => {
              setShowLoginModal(false);
              router.push(`/${locale}/doctors`);
            }}
            footer={[
              <Button key="cancel" onClick={() => {
                setShowLoginModal(false);
                router.push(`/${locale}/doctors`);
              }}>
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
                message="برای تکمیل رزرو نوبت، لطفاً وارد حساب کاربری خود شوید."
                description="اگر حساب کاربری ندارید، می‌توانید به راحتی ثبت‌نام کنید."
                type="info"
                showIcon
                style={{ marginBottom: 16 }}
            />
            <div style={{ background: '#f8fafc', padding: '16px', borderRadius: '12px' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text type="secondary">پزشک:</Text>
                <Text strong>{appointmentData?.doctorName || '---'}</Text>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text type="secondary">تاریخ:</Text>
                <Text strong>{persianDate || '---'}</Text>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text type="secondary">زمان:</Text>
                <Text strong>{formattedTime || '---'}</Text>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <Text type="secondary">هزینه:</Text>
                <Text strong style={{ color: '#2563eb' }}>
                  {appointmentData?.doctorFee?.toLocaleString() || '۰'} تومان
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

        {/* ✅ مودال انتخاب درگاه */}
        <Modal
            title="انتخاب درگاه پرداخت"
            open={gatewayModalVisible}
            onCancel={() => setGatewayModalVisible(false)}
            footer={null}
            width={500}
            centered
        >
          <div style={{ padding: '8px 0' }}>
            <Text type="secondary" style={{ display: 'block', marginBottom: '16px' }}>
              لطفاً یکی از درگاه‌های زیر را برای پرداخت انتخاب کنید:
            </Text>

            {fetchingGateways ? (
                <div style={{ textAlign: 'center', padding: '30px 0' }}>
                  <Spin size="large" />
                  <div style={{ marginTop: 12 }}>
                    <Text type="secondary">در حال بارگیری درگاه‌های پرداخت...</Text>
                  </div>
                </div>
            ) : gateways.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '20px 0' }}>
                  <Empty description="هیچ درگاه پرداختی در دسترس نیست" image={Empty.PRESENTED_IMAGE_SIMPLE} />
                  <Button type="primary" icon={<ReloadOutlined />} onClick={fetchGateways} style={{ marginTop: 16 }} loading={fetchingGateways}>
                    بارگیری مجدد
                  </Button>
                </div>
            ) : (
                <Space orientation="vertical" style={{ width: '100%' }} size="middle">
                  {gateways.map((gateway) => (
                      <Card
                          key={gateway.name}
                          size="small"
                          hoverable
                          onClick={() => handleGatewayPayment(gateway.name)}
                          style={{
                            borderRadius: '12px',
                            border: selectedGateway === gateway.name ? '2px solid #2563eb' : '1px solid #e2e8f0',
                            cursor: 'pointer',
                          }}
                          styles={{ body: { padding: '12px 16px' } }}
                      >
                        <Space>
                          <span style={{ fontSize: '24px' }}>{gateway.icon || '💳'}</span>
                          <div>
                            <Text strong>{gateway.title}</Text>
                            {gateway.is_default && <Tag color="blue" style={{ marginLeft: '8px' }}>پیش‌فرض</Tag>}
                          </div>
                        </Space>
                      </Card>
                  ))}
                </Space>
            )}

            <div style={{ marginTop: '20px', padding: '12px 16px', background: '#f8fafc', borderRadius: '8px' }}>
              <Text type="secondary" style={{ fontSize: '12px' }}>
                <SafetyOutlined /> پرداخت شما با امنیت کامل انجام می‌شود
              </Text>
            </div>
          </div>
        </Modal>

        <Footer />
      </>
  );
}