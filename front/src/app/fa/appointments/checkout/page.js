'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Button, Typography, Spin, Tag, message, 
  Space, Divider, Alert, Input, Radio, Statistic, Steps,
  Descriptions, Avatar, Modal
} from 'antd';
import { 
  CheckCircleOutlined, WalletOutlined, CreditCardOutlined, 
  LeftOutlined, GiftOutlined, 
  SafetyOutlined, UserOutlined,
  EnvironmentOutlined, DollarOutlined,
  ClockCircleOutlined, CalendarOutlined,
  BankOutlined, GlobalOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

function toJalali(dateStr) {
  const d = new Date(dateStr);
  const calendar = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  });
  return calendar.format(d);
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
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  // دریافت درگاه‌های موجود
  const fetchGateways = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/payment/gateways`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setGateways(data.data.available || []);
        const defaultGateway = data.data.default;
        if (defaultGateway) {
          setSelectedGateway(defaultGateway);
        }
      }
    } catch (error) {
      console.error('Error fetching gateways:', error);
    }
  };

  useEffect(() => {
    const stored = localStorage.getItem('appointmentData');
    if (!stored) {
      router.push(`/${locale}/doctors`);
      return;
    }
    try {
      const data = JSON.parse(stored);
      setAppointmentData(data);
    } catch {
      router.push(`/${locale}/doctors`);
    }
  }, []);

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
      Promise.all([
        fetchWalletBalance(),
        fetchGateways(),
      ]);
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
        message.success(data.data.message || 'کد تخفیف با موفقیت اعمال شد');
      } else {
        message.error(data.message || 'کد تخفیف نامعتبر است');
        setDiscountApplied(null);
      }
    } catch (error) {
      message.error('خطا در اعتبارسنجی کد تخفیف');
    } finally {
      setApplyingDiscount(false);
    }
  };

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

  const handlePayment = async () => {
    if (paymentMethod === 'wallet') {
      await handleWalletPayment();
    } else {
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

      // 2. پرداخت با کیف پول
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
      console.error('Error:', error);
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
          gateway: gateway,
          amount: finalPrice,
          discount_code: discountApplied?.code || null,
        }),
      });
      const payData = await payRes.json();

      if (payData.success) {
        if (payData.data.redirect_url) {
          // هدایت به درگاه
          window.location.href = payData.data.redirect_url;
        } else if (payData.data.form) {
          // ارسال فرم به درگاه
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
        }
      } else {
        message.error(payData.message || 'خطا در شروع پرداخت');
      }
    } catch (error) {
      console.error('Error initiating payment:', error);
      message.error('خطا در ارتباط با درگاه پرداخت');
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
  const jalaliDate = toJalali(appointmentData.date);

  return (
    <>
      <Header />
      <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '900px', margin: '0 auto', padding: '24px 20px' }}>
          <Breadcrumb />

          <Title level={2} style={{ marginBottom: '4px' }}>💳 تایید و پرداخت</Title>
          <Text type="secondary">اطلاعات نوبت را بررسی و پرداخت را تکمیل کنید</Text>

          <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
            {/* ستون چپ: خلاصه نوبت */}
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
                        {appointmentData.doctorName?.charAt(0)}
                      </Avatar>
                      <Text strong style={{ fontSize: '16px' }}>{appointmentData.doctorName}</Text>
                    </div>
                  </div>

                  <div>
                    <Text type="secondary" style={{ fontSize: '12px' }}>تخصص</Text>
                    <div style={{ marginTop: '4px' }}>
                      <Tag color="blue">{appointmentData.doctorSpecialty}</Tag>
                    </div>
                  </div>

                  <div>
                    <Text type="secondary" style={{ fontSize: '12px' }}>تاریخ و ساعت</Text>
                    <div style={{ marginTop: '4px' }}>
                      <Space>
                        <Tag icon={<CalendarOutlined />} color="blue">{jalaliDate}</Tag>
                        <Tag icon={<ClockCircleOutlined />} color="green">{appointmentData.time}</Tag>
                      </Space>
                    </div>
                  </div>

                  <Divider style={{ margin: '8px 0' }} />

                  <div>
                    <Text type="secondary" style={{ fontSize: '12px' }}>هزینه ویزیت</Text>
                    <div style={{ marginTop: '4px' }}>
                      <Text strong style={{ fontSize: '20px', color: '#2563eb' }}>
                        {appointmentData.doctorFee.toLocaleString()}
                      </Text>
                      <Text type="secondary"> تومان</Text>
                    </div>
                  </div>
                </Space>
              </Card>

              {/* خلاصه مبلغ */}
              <Card 
                style={{ marginTop: '16px', borderRadius: '16px', background: '#f0f5ff' }}
                bodyStyle={{ padding: '16px' }}
              >
                <Row gutter={[8, 8]}>
                  <Col span={12}>
                    <Text type="secondary">هزینه ویزیت</Text>
                  </Col>
                  <Col span={12} style={{ textAlign: 'left' }}>
                    <Text>{appointmentData.doctorFee.toLocaleString()} تومان</Text>
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

            {/* ستون راست: پرداخت */}
            <Col xs={24} lg={14}>
              <Card 
                title="💳 اطلاعات پرداخت"
                style={{ borderRadius: '16px' }}
                bodyStyle={{ padding: '20px' }}
              >
                {/* کد تخفیف */}
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

                {/* روش پرداخت */}
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
                              موجودی: {walletBalance.toLocaleString()} تومان
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
                              انتخاب از بین درگاه‌های موجود
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
      >
        <div style={{ padding: '8px 0' }}>
          <Text type="secondary" style={{ display: 'block', marginBottom: '16px' }}>
            لطفاً یکی از درگاه‌های زیر را برای پرداخت انتخاب کنید:
          </Text>
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
                  cursor: 'pointer'
                }}
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
        </div>
      </Modal>

      <Footer />
    </>
  );
}
