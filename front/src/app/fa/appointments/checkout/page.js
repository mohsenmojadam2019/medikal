'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Button, Typography, Spin, Tag, message, 
  Space, Divider, Alert, Input, Radio, Statistic, Steps,
  Descriptions, Avatar, Modal, Empty
} from 'antd';
import { 
  CheckCircleOutlined, WalletOutlined, CreditCardOutlined, 
  LeftOutlined, GiftOutlined, 
  SafetyOutlined, UserOutlined,
  EnvironmentOutlined, DollarOutlined,
  ClockCircleOutlined, CalendarOutlined,
  BankOutlined, GlobalOutlined, ReloadOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

// تبدیل تاریخ میلادی به شمسی با استفاده از Intl.DateTimeFormat
function toPersianDate(dateStr) {
  if (!dateStr) return '';
  
  const date = new Date(dateStr);
  
  // تبدیل به شمسی با استفاده از Intl
  const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  });
  
  return formatter.format(date);
}

// تبدیل تاریخ به شمسی با فرمت کوتاه
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

// فرمت زمان (فقط ساعت و دقیقه)
function formatTime(timeStr) {
  if (!timeStr) return '';
  return timeStr.substring(0, 5); // فقط HH:MM
}

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
  const [gateways, setGateways] = useState([]);
  const [selectedGateway, setSelectedGateway] = useState(null);
  const [gatewayModalVisible, setGatewayModalVisible] = useState(false);
  const [fetchingGateways, setFetchingGateways] = useState(false);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  // دریافت درگاه‌های موجود
  const fetchGateways = async () => {
    const token = getToken();
    console.log('🔑 Token:', token ? 'Exists' : 'Not found');
    
    if (!token) {
      console.warn('⚠️ No token found for fetching gateways');
      setGateways([
        { 
          name: 'local', 
          title: 'درگاه تست (آفلاین)', 
          icon: '🔄', 
          is_default: true 
        }
      ]);
      setSelectedGateway('local');
      return;
    }

    setFetchingGateways(true);
    console.log('🌐 Fetching gateways from:', `${API_URL}/api/payments/gateways`);

    try {
      const res = await fetch(`${API_URL}/api/payments/gateways`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      console.log('📡 Response status:', res.status);

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      const data = await res.json();
      console.log('📦 Full response:', data);

      if (data.success) {
        const availableGateways = data.data?.available || [];
        console.log('✅ Available gateways count:', availableGateways.length);
        
        if (availableGateways.length === 0) {
          const fallbackGateway = [
            { 
              name: 'local', 
              title: 'درگاه تست (آفلاین)', 
              icon: '🔄', 
              is_default: true 
            }
          ];
          setGateways(fallbackGateway);
          setSelectedGateway('local');
          message.info('هیچ درگاه پرداختی فعال نیست. درگاه تست فعال شد.');
        } else {
          setGateways(availableGateways);
          const defaultGateway = data.data?.default || availableGateways[0]?.name || 'local';
          setSelectedGateway(defaultGateway);
          message.success(`${availableGateways.length} درگاه پرداخت بارگیری شد`);
        }
      } else {
        console.error('❌ API returned success=false:', data.message);
        setGateways([
          { 
            name: 'local', 
            title: 'درگاه تست (آفلاین)', 
            icon: '🔄', 
            is_default: true 
          }
        ]);
        setSelectedGateway('local');
        message.warning(data.message || 'خطا در بارگیری درگاه‌ها');
      }
    } catch (error) {
      console.error('❌ Error fetching gateways:', error);
      message.error('خطا در ارتباط با سرور');
      setGateways([
        { 
          name: 'local', 
          title: 'درگاه تست (آفلاین)', 
          icon: '🔄', 
          is_default: true 
        }
      ]);
      setSelectedGateway('local');
    } finally {
      setFetchingGateways(false);
    }
  };

  useEffect(() => {
    const stored = localStorage.getItem('appointmentData');
    console.log('📋 Stored appointment data:', stored);
    
    if (!stored) {
      router.push(`/${locale}/doctors`);
      return;
    }
    
    try {
      const data = JSON.parse(stored);
      console.log('✅ Parsed appointment data:', data);
      setAppointmentData(data);
    } catch (error) {
      console.error('❌ Error parsing appointment data:', error);
      router.push(`/${locale}/doctors`);
    }
  }, [locale, router]);

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

  useEffect(() => {
    if (appointmentData) {
      console.log('🔄 AppointmentData loaded, fetching wallet and gateways...');
      Promise.all([
        fetchWalletBalance(),
        fetchGateways(),
      ]).finally(() => {
        setLoading(false);
      });
    }
  }, [appointmentData]);

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
          amount: appointmentData?.doctorFee || 0,
        }),
      });
      const data = await res.json();
      if (data.success) {
        setDiscountApplied(data.data);
        message.success(data.data?.message || 'کد تخفیف با موفقیت اعمال شد');
      } else {
        message.error(data.message || 'کد تخفیف نامعتبر است');
        setDiscountApplied(null);
      }
    } catch (error) {
      console.error('Error validating discount:', error);
      message.error('خطا در اعتبارسنجی کد تخفیف');
      setDiscountApplied(null);
    } finally {
      setApplyingDiscount(false);
    }
  };

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

  const handlePayment = async () => {
    if (paymentMethod === 'wallet') {
      await handleWalletPayment();
    } else {
      console.log('🎯 Opening gateway modal, gateways count:', gateways.length);
      
      if (gateways.length === 0) {
        message.info('در حال بارگیری لیست درگاه‌ها...');
        await fetchGateways();
        if (gateways.length === 0) {
          message.error('هیچ درگاه پرداختی در دسترس نیست');
          return;
        }
      }
      
      setGatewayModalVisible(true);
    }
  };

  const handleWalletPayment = async () => {
    if (!canUseWallet && !isFree) {
      message.warning('موجودی کیف پول شما کافی نیست');
      return;
    }

    setSubmitting(true);
    const token = getToken();

    try {
      const bookRes = await fetch(`${API_URL}/api/appointments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          doctor_id: parseInt(appointmentData.doctorId),
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

      const invRes = await fetch(`${API_URL}/api/invoices/my`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const invData = await invRes.json();
      const invoice = invData.success ? invData.data?.find(i => i.appointment_id === appointmentId) : null;

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
      localStorage.removeItem('appointmentData');

      message.success('✅ نوبت با موفقیت رزرو شد');
      router.push(`/${locale}/appointments/confirmation`);
    } catch (error) {
      console.error('Error in wallet payment:', error);
      message.error('خطا در پردازش پرداخت');
    } finally {
      setSubmitting(false);
    }
  };

  const handleGatewayPayment = async (gateway) => {
    setSubmitting(true);
    setGatewayModalVisible(false);
    const token = getToken();

    try {
      console.log('🔄 Starting gateway payment with:', { gateway, token: token ? 'exists' : 'missing' });

      const bookRes = await fetch(`${API_URL}/api/appointments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          doctor_id: parseInt(appointmentData.doctorId),
          date: appointmentData.date,
          start_time: appointmentData.time,
          notes: '',
        }),
      });
      const bookData = await bookRes.json();

      console.log('📦 Booking response:', bookData);

      if (!bookData.success) {
        message.error(bookData.message || 'خطا در رزرو نوبت');
        setSubmitting(false);
        return;
      }

      const appointmentId = bookData.data.id;
      console.log('✅ Appointment created with ID:', appointmentId);

      const invRes = await fetch(`${API_URL}/api/invoices/my`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const invData = await invRes.json();
      console.log('📦 Invoices response:', invData);

      const invoice = invData.success ? invData.data?.find(i => i.appointment_id === appointmentId) : null;

      if (!invoice) {
        console.error('❌ No invoice found for appointment:', appointmentId);
        message.error('فاکتور یافت نشد. لطفاً دوباره تلاش کنید.');
        setSubmitting(false);
        return;
      }

      console.log('✅ Invoice found:', invoice.id, invoice.invoice_number);

      const payRes = await fetch(`${API_URL}/api/payments/initiate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          invoice_id: invoice.id,
          gateway: gateway,
          amount: finalPrice,
          discount_code: discountApplied?.code || null,
        }),
      });
      const payData = await payRes.json();
      console.log('📦 Payment initiation response:', payData);

      if (payData.success) {
        if (payData.data.redirect_url) {
          window.location.href = payData.data.redirect_url;
        } else if (payData.data.form) {
          const form = document.createElement('form');
          form.method = payData.data.form.method || 'POST';
          form.action = payData.data.form.action;
          
          if (payData.data.form.inputs) {
            Object.entries(payData.data.form.inputs).forEach(([key, value]) => {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = key;
              input.value = value;
              form.appendChild(input);
            });
          }
          
          document.body.appendChild(form);
          form.submit();
        } else {
          message.error('اطلاعات پرداخت ناقص است');
          setSubmitting(false);
        }
      } else {
        console.error('❌ Payment initiation failed:', payData);
        message.error(payData.message || 'خطا در شروع پرداخت');
        setSubmitting(false);
      }
    } catch (error) {
      console.error('❌ Error initiating payment:', error);
      message.error('خطا در ارتباط با درگاه پرداخت');
      setSubmitting(false);
    } finally {
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
        <div style={{ textAlign: 'center', padding: '40px' }}>
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
  
  // نمایش تاریخ شمسی با استفاده از Intl.DateTimeFormat
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

          <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
            <Col xs={24} lg={10}>
              <Card 
                title="📋 خلاصه نوبت"
                style={{ borderRadius: '16px' }}
                bodyStyle={{ padding: '20px' }}
              >
                <Space direction="vertical" style={{ width: '100%' }} size="middle">
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
                bodyStyle={{ padding: '16px' }}
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
                bodyStyle={{ padding: '20px' }}
              >
                <div style={{ marginBottom: '20px' }}>
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
                  >
                    <Space direction="vertical" style={{ width: '100%' }} size="middle">
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

                {isFree && (
                  <Alert
                    message="این نوبت رایگان است"
                    type="success"
                    showIcon
                    style={{ marginTop: '16px' }}
                  />
                )}

                {paymentMethod === 'wallet' && !isFree && !canUseWallet && (
                  <Alert
                    message="موجودی کیف پول کافی نیست"
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
                    disabled={paymentMethod === 'wallet' && !canUseWallet && !isFree}
                    style={{ 
                      flex: 1,
                      borderRadius: '12px',
                      height: '48px',
                      fontWeight: 'bold',
                    }}
                  >
                    {isFree ? 'تایید نوبت رایگان' : 'پرداخت و تایید نوبت'}
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

      {/* مودال انتخاب درگاه پرداخت */}
      <Modal
        title="انتخاب درگاه پرداخت"
        open={gatewayModalVisible}
        onCancel={() => setGatewayModalVisible(false)}
        footer={null}
        width={500}
        centered
        destroyOnClose
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
              <Empty 
                description="هیچ درگاه پرداختی در دسترس نیست" 
                image={Empty.PRESENTED_IMAGE_SIMPLE}
              />
              <Button 
                type="primary" 
                icon={<ReloadOutlined />}
                onClick={fetchGateways}
                style={{ marginTop: 16 }}
                loading={fetchingGateways}
              >
                بارگیری مجدد
              </Button>
            </div>
          ) : (
            <Space direction="vertical" style={{ width: '100%' }} size="middle">
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
                    transition: 'all 0.3s ease'
                  }}
                  bodyStyle={{ padding: '12px 16px' }}
                >
                  <Space>
                    <span style={{ fontSize: '24px' }}>{gateway.icon || '💳'}</span>
                    <div>
                      <Text strong>{gateway.title}</Text>
                      {gateway.is_default && (
                        <Tag color="blue" style={{ marginLeft: '8px' }}>پیش‌فرض</Tag>
                      )}
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
