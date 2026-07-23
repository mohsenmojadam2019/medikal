'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card, Row, Col, Button, Typography, Spin, Tag,
  Space, Divider, Alert, Input, Radio, Avatar,
  Modal, Empty, App, Steps, Form, Select,
  InputNumber, Checkbox, Result, Skeleton, message
} from 'antd';
import {
  ShoppingCartOutlined, WalletOutlined, CreditCardOutlined,
  LeftOutlined, GiftOutlined, SafetyOutlined,
  TruckOutlined, HomeOutlined, UserOutlined,
  DollarOutlined, CheckCircleOutlined,
  ReloadOutlined, MedicineBoxOutlined,
  EditOutlined, UserAddOutlined, PlusOutlined,
  PhoneOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Step } = Steps;

function toPersianNumber(num) {
  if (!num && num !== 0) return '۰';
  const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  return num.toString().replace(/\d/g, d => persian[d]);
}

function formatPrice(price) {
  if (!price && price !== 0) return '۰ تومان';
  return toPersianNumber(price.toLocaleString()) + ' تومان';
}

export default function PharmacyCheckoutPage() {
  const router = useRouter();
  const { locale } = useLanguage();
  const [loading, setLoading] = useState(true);
  const [userLoading, setUserLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);
  const [cart, setCart] = useState([]);
  const [walletBalance, setWalletBalance] = useState(0);
  const [paymentMethod, setPaymentMethod] = useState('wallet');
  const [deliveryAddress, setDeliveryAddress] = useState('');
  const [deliveryNotes, setDeliveryNotes] = useState('');
  const [recipientName, setRecipientName] = useState('');
  const [recipientPhone, setRecipientPhone] = useState('');
  const [gateways, setGateways] = useState([]);
  const [selectedGateway, setSelectedGateway] = useState('local');
  const [userProfile, setUserProfile] = useState(null);
  const [useDifferentAddress, setUseDifferentAddress] = useState(false);
  const [isMounted, setIsMounted] = useState(false);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  // حل مشکل Hydration
  useEffect(() => {
    setIsMounted(true);
  }, []);

  const fetchUserProfile = async () => {
    try {
      const token = getToken();
      if (!token) return;

      const res = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setUserProfile(data.data);
        setRecipientName(data.data.name || '');
        setRecipientPhone(data.data.mobile || '');

        const patientRes = await fetch(`${API_URL}/api/patients/me`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        });
        const patientData = await patientRes.json();
        if (patientData.success && patientData.data?.address) {
          setDeliveryAddress(patientData.data.address);
        }
      }
    } catch (error) {
      console.error('Error fetching user profile:', error);
    } finally {
      setUserLoading(false);
    }
  };

  useEffect(() => {
    if (!isMounted) return;

    let cartData = [];
    const cartStorage = localStorage.getItem('pharmacyCart');
    const checkoutStorage = localStorage.getItem('pharmacyCheckoutData');

    if (checkoutStorage) {
      try {
        const data = JSON.parse(checkoutStorage);
        cartData = data.items || [];
      } catch (error) {
        console.error('Error parsing checkout data:', error);
      }
    } else if (cartStorage) {
      try {
        cartData = JSON.parse(cartStorage);
      } catch (error) {
        console.error('Error parsing cart data:', error);
      }
    }

    if (cartData.length === 0) {
      message.warning('سبد خرید شما خالی است');
      setTimeout(() => router.push(`/${locale}/pharmacy`), 1500);
    }

    setCart(cartData);
    setLoading(false);

    fetchUserProfile();
    fetchWalletBalance();
    fetchGateways();
  }, [isMounted]);

  const fetchWalletBalance = async () => {
    try {
      const token = getToken();
      if (!token) return;

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

  const fetchGateways = async () => {
    try {
      const token = getToken();
      const res = await fetch(`${API_URL}/api/payments/gateways`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setGateways(data.data?.available || []);
        const defaultGateway = data.data?.default || 'local';
        setSelectedGateway(defaultGateway);
      }
    } catch (error) {
      console.error('Error fetching gateways:', error);
    }
  };

  const getSubtotal = () => {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  };

  const getDeliveryFee = () => 0;
  const getTax = () => getSubtotal() * 0.09;
  const getTotal = () => getSubtotal() + getDeliveryFee() + getTax();
  const canUseWallet = walletBalance >= getTotal();

  const useUserInfo = () => {
    if (userProfile) {
      setRecipientName(userProfile.name || '');
      setRecipientPhone(userProfile.mobile || '');
      if (userProfile.address) {
        setDeliveryAddress(userProfile.address);
      }
      message.success('اطلاعات شما وارد شد');
    } else {
      message.warning('اطلاعات کاربر یافت نشد');
    }
  };

  const handleSubmitOrder = async () => {
    if (cart.length === 0) {
      message.warning('سبد خرید شما خالی است');
      return;
    }

    if (!recipientName.trim()) {
      message.warning('لطفاً نام گیرنده را وارد کنید');
      return;
    }

    if (!recipientPhone.trim()) {
      message.warning('لطفاً شماره تماس گیرنده را وارد کنید');
      return;
    }

    if (!deliveryAddress.trim()) {
      message.warning('لطفاً آدرس تحویل را وارد کنید');
      return;
    }

    if (!canUseWallet) {
      message.warning('موجودی کیف پول کافی نیست');
      return;
    }

    setSubmitting(true);
    try {
      const token = getToken();

      const orderData = {
        items: cart.map(item => ({
          drug_id: item.id,
          quantity: item.quantity,
          price: item.price,
          name: item.name,
        })),
        delivery_address: deliveryAddress,
        delivery_notes: deliveryNotes || '',
        recipient_name: recipientName,
        recipient_phone: recipientPhone,
        payment_method: 'wallet',
        subtotal: getSubtotal(),
        tax: getTax(),
        total_price: getTotal(),
      };

      console.log('📦 Creating order with wallet...', orderData);

      const orderRes = await fetch(`${API_URL}/api/pharmacy/orders`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData),
      });

      if (!orderRes.ok) {
        const errorData = await orderRes.text();
        console.error('❌ Order error:', errorData);
        message.error(`خطا در ثبت سفارش: ${orderRes.status}`);
        setSubmitting(false);
        return;
      }

      const orderDataResponse = await orderRes.json();
      console.log('📦 Order response:', orderDataResponse);

      if (!orderDataResponse.success) {
        message.error(orderDataResponse.message || 'خطا در ثبت سفارش');
        setSubmitting(false);
        return;
      }

      message.success('سفارش با موفقیت ثبت شد و از کیف پول شما کسر گردید');
      localStorage.removeItem('pharmacyCart');
      localStorage.removeItem('pharmacyCheckoutData');

      setTimeout(() => {
        router.push(`/${locale}/profile/pharmacy-orders`);
      }, 1500);
    } catch (error) {
      console.error('❌ Network error:', error);
      message.error('خطا در ثبت سفارش');
      setSubmitting(false);
    }
  };

  // ============================================================
  // ✅ اصلاح کامل لینک پرداخت
  // ============================================================
  const handleGatewayPayment = async () => {
    if (!recipientName.trim() || !recipientPhone.trim() || !deliveryAddress.trim()) {
      message.warning('لطفاً ابتدا اطلاعات تحویل را کامل کنید');
      return;
    }

    setSubmitting(true);
    try {
      const token = getToken();

      const orderData = {
        items: cart.map(item => ({
          drug_id: item.id,
          quantity: item.quantity,
          price: item.price,
          name: item.name,
        })),
        delivery_address: deliveryAddress,
        delivery_notes: deliveryNotes || '',
        recipient_name: recipientName,
        recipient_phone: recipientPhone,
        payment_method: 'gateway',
        gateway: selectedGateway,
        subtotal: getSubtotal(),
        tax: getTax(),
        total_price: getTotal(),
      };

      console.log('📦 Creating order...', orderData);

      const orderRes = await fetch(`${API_URL}/api/pharmacy/orders`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData),
      });

      if (!orderRes.ok) {
        const errorData = await orderRes.text();
        console.error('❌ Order error:', errorData);
        message.error(`خطا در ثبت سفارش: ${orderRes.status}`);
        setSubmitting(false);
        return;
      }

      const orderDataResponse = await orderRes.json();
      console.log('📦 Order response:', orderDataResponse);

      if (!orderDataResponse.success) {
        message.error(orderDataResponse.message || 'خطا در ثبت سفارش');
        setSubmitting(false);
        return;
      }

      let paymentLink = orderDataResponse.data?.payment_link;

      if (!paymentLink) {
        message.error('لینک پرداخت یافت نشد');
        setSubmitting(false);
        return;
      }

      console.log('🔗 Original payment link:', paymentLink);

      // ✅ مرحله 1: حذف کاراکترهای اضافی
      let cleanLink = paymentLink
          .replace(/\\/g, '')      // حذف backslash
          .replace(/"/g, '')       // حذف quotation marks
          .replace(/\s/g, '');     // حذف فاصله‌ها

      // ✅ مرحله 2: پیدا کردن موقعیت اولین ?
      const firstQIndex = cleanLink.indexOf('?');

      if (firstQIndex !== -1) {
        // ✅ مرحله 3: جدا کردن بخش قبل و بعد از اولین ?
        const baseUrl = cleanLink.substring(0, firstQIndex);
        let params = cleanLink.substring(firstQIndex + 1);

        // ✅ مرحله 4: تبدیل همه ? های بعدی به &
        params = params.replace(/\?/g, '&');

        // ✅ مرحله 5: ساخت لینک نهایی
        cleanLink = baseUrl + '?' + params;
      }

      // ✅ مرحله 6: اگر success=true نبود، اضافه کن
      if (!cleanLink.includes('success=true')) {
        cleanLink = cleanLink.includes('?')
            ? `${cleanLink}&success=true`
            : `${cleanLink}?success=true`;
      }

      console.log('✅ Final payment link:', cleanLink);

      // ✅ مرحله 7: ذخیره اطلاعات سفارش
      localStorage.setItem('pendingOrder', JSON.stringify({
        orderNumber: orderDataResponse.data?.order_number,
        returnUrl: `/${locale}/profile/pharmacy-orders`,
      }));

      message.success('در حال انتقال به درگاه پرداخت...');

      // ✅ مرحله 8: هدایت به لینک پرداخت
      setTimeout(() => {
        window.location.href = cleanLink;
      }, 500);

    } catch (error) {
      console.error('❌ Network error:', error);
      message.error('خطا در ارتباط با سرور');
      setSubmitting(false);
    }
  };

  if (loading || userLoading || !isMounted) {
    return (
        <>
          <Header />
          <LoadingSpinner />
          <Footer />
        </>
    );
  }

  if (!cart.length) {
    return (
        <>
          <div style={{ maxWidth: '800px', margin: '0 auto', padding: '40px 20px', textAlign: 'center' }}>
            <Empty description="سبد خرید شما خالی است" />
            <Button type="primary" onClick={() => router.push(`/${locale}/pharmacy`)}>
              ادامه خرید
            </Button>
          </div>
          <Footer />
        </>
    );
  }

  const isFormValid = recipientName.trim().length > 0 &&
      recipientPhone.trim().length > 0 &&
      deliveryAddress.trim().length > 0 &&
      !(paymentMethod === 'wallet' && !canUseWallet);

  return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '900px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb
                items={[
                  { title: 'خانه', href: `/${locale}` },
                  { title: 'داروخانه', href: `/${locale}/pharmacy` },
                  { title: 'تسویه حساب' },
                ]}
            />

            <Title level={2} style={{ marginBottom: '4px' }}>
              💳 تسویه حساب
            </Title>
            <Text type="secondary">اطلاعات سفارش را تکمیل کنید</Text>

            <Steps current={currentStep} style={{ marginTop: '24px' }}>
              <Step title="بررسی سفارش" />
              <Step title="پرداخت" />
              <Step title="تایید" />
            </Steps>

            <Row gutter={[24, 24]} style={{ marginTop: '24px' }}>
              <Col xs={24} lg={16}>
                <Card title="📋 خلاصه سفارش" style={{ borderRadius: '16px' }}>
                  <div style={{ marginBottom: '16px' }}>
                    <Text strong>محصولات:</Text>
                    {cart.map((item, index) => (
                        <div key={index} style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          padding: '8px 0',
                          borderBottom: index < cart.length - 1 ? '1px solid #f0f0f0' : 'none',
                        }}>
                          <Space>
                            <MedicineBoxOutlined />
                            <Text>{item.name}</Text>
                            <Text type="secondary">× {toPersianNumber(item.quantity)}</Text>
                          </Space>
                          <Text>{formatPrice(item.price * item.quantity)}</Text>
                        </div>
                    ))}
                  </div>

                  <Divider />

                  <div>
                    <Row gutter={[16, 16]}>
                      <Col xs={24} md={12}>
                        <Input
                            placeholder="نام گیرنده"
                            value={recipientName}
                            onChange={(e) => setRecipientName(e.target.value)}
                            prefix={<UserOutlined />}
                            size="large"
                        />
                      </Col>
                      <Col xs={24} md={12}>
                        <Input
                            placeholder="شماره تماس گیرنده"
                            value={recipientPhone}
                            onChange={(e) => setRecipientPhone(e.target.value)}
                            prefix={<PhoneOutlined />}
                            size="large"
                        />
                      </Col>
                    </Row>
                  </div>

                  <Divider />

                  <div>
                    <Text strong>آدرس تحویل:</Text>
                    <Input.TextArea
                        placeholder="آدرس کامل تحویل را وارد کنید..."
                        value={deliveryAddress}
                        onChange={(e) => setDeliveryAddress(e.target.value)}
                        rows={3}
                        style={{ marginTop: '8px', borderRadius: '8px' }}
                    />
                    {userProfile?.address && (
                        <Text type="secondary" style={{ fontSize: '12px', display: 'block', marginTop: '4px' }}>
                          آدرس پیش‌فرض شما: {userProfile.address}
                        </Text>
                    )}
                  </div>

                  <div style={{ marginTop: '16px' }}>
                    <Text strong>توضیحات:</Text>
                    <Input.TextArea
                        placeholder="توضیحات اضافی برای ارسال..."
                        value={deliveryNotes}
                        onChange={(e) => setDeliveryNotes(e.target.value)}
                        rows={2}
                        style={{ marginTop: '8px', borderRadius: '8px' }}
                    />
                  </div>
                </Card>

                <Card title="💳 روش پرداخت" style={{ borderRadius: '16px', marginTop: '16px' }}>
                  <Radio.Group
                      value={paymentMethod}
                      onChange={(e) => setPaymentMethod(e.target.value)}
                      style={{ width: '100%' }}
                  >
                    <Space direction="vertical" style={{ width: '100%' }} size="middle">
                      <Radio value="wallet">
                        <Space>
                          <WalletOutlined />
                          <div>
                            <div>کیف پول</div>
                            <Text type="secondary" style={{ fontSize: '12px' }}>
                              موجودی: {formatPrice(walletBalance)}
                            </Text>
                          </div>
                          {!canUseWallet && (
                              <Tag color="red">موجودی کافی نیست</Tag>
                          )}
                        </Space>
                      </Radio>
                      <Radio value="gateway">
                        <Space>
                          <CreditCardOutlined />
                          <div>
                            <div>درگاه پرداخت</div>
                            <Text type="secondary" style={{ fontSize: '12px' }}>
                              پرداخت امن از طریق درگاه
                            </Text>
                          </div>
                        </Space>
                      </Radio>
                    </Space>
                  </Radio.Group>

                  {paymentMethod === 'wallet' && !canUseWallet && (
                      <Alert
                          message="موجودی کافی نیست"
                          description="لطفاً روش پرداخت دیگری را انتخاب کنید یا کیف پول خود را شارژ کنید"
                          type="warning"
                          showIcon
                          style={{ marginTop: '12px' }}
                      />
                  )}

                  {paymentMethod === 'gateway' && (
                      <div style={{ marginTop: '16px' }}>
                        <Text strong>انتخاب درگاه:</Text>
                        <Radio.Group
                            value={selectedGateway}
                            onChange={(e) => setSelectedGateway(e.target.value)}
                            style={{ marginTop: '8px', display: 'block' }}
                        >
                          <Space direction="vertical">
                            {gateways.map((gateway) => (
                                <Radio key={gateway.name || gateway} value={gateway.name || gateway}>
                                  <Space>
                                    <span>{gateway.icon || '💳'}</span>
                                    <span>{gateway.title || gateway}</span>
                                    {gateway.is_default && (
                                        <Tag color="blue">پیش‌فرض</Tag>
                                    )}
                                  </Space>
                                </Radio>
                            ))}
                          </Space>
                        </Radio.Group>
                      </div>
                  )}
                </Card>
              </Col>

              <Col xs={24} lg={8}>
                <Card title="💰 خلاصه پرداخت" style={{ borderRadius: '16px' }}>
                  <div style={{ marginBottom: '8px', display: 'flex', justifyContent: 'space-between' }}>
                    <Text>جمع محصولات:</Text>
                    <Text>{formatPrice(getSubtotal())}</Text>
                  </div>
                  <div style={{ marginBottom: '8px', display: 'flex', justifyContent: 'space-between' }}>
                    <Text>هزینه ارسال:</Text>
                    <Text>{formatPrice(getDeliveryFee())}</Text>
                  </div>
                  <div style={{ marginBottom: '8px', display: 'flex', justifyContent: 'space-between' }}>
                    <Text>مالیات:</Text>
                    <Text>{formatPrice(getTax())}</Text>
                  </div>
                  <Divider style={{ margin: '8px 0' }} />
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '8px' }}>
                    <Text strong>جمع کل:</Text>
                    <Text strong style={{ color: '#2563eb', fontSize: '20px' }}>
                      {formatPrice(getTotal())}
                    </Text>
                  </div>

                  {paymentMethod === 'wallet' ? (
                      <Button
                          type="primary"
                          size="large"
                          block
                          onClick={handleSubmitOrder}
                          loading={submitting}
                          disabled={!isFormValid}
                          style={{ marginTop: '16px', borderRadius: '12px', height: '48px' }}
                      >
                        {canUseWallet ? 'پرداخت با کیف پول' : 'موجودی کافی نیست'}
                      </Button>
                  ) : (
                      <Button
                          type="primary"
                          size="large"
                          block
                          onClick={handleGatewayPayment}
                          loading={submitting}
                          disabled={!isFormValid}
                          style={{ marginTop: '16px', borderRadius: '12px', height: '48px' }}
                      >
                        پرداخت با درگاه
                      </Button>
                  )}

                  {!recipientName.trim() && (
                      <div style={{ marginTop: '8px' }}>
                        <Text type="danger" style={{ fontSize: '12px' }}>
                          ⚠️ لطفاً نام گیرنده را وارد کنید
                        </Text>
                      </div>
                  )}

                  {!recipientPhone.trim() && (
                      <div style={{ marginTop: '8px' }}>
                        <Text type="danger" style={{ fontSize: '12px' }}>
                          ⚠️ لطفاً شماره تماس گیرنده را وارد کنید
                        </Text>
                      </div>
                  )}

                  {!deliveryAddress.trim() && (
                      <div style={{ marginTop: '8px' }}>
                        <Text type="danger" style={{ fontSize: '12px' }}>
                          ⚠️ لطفاً آدرس تحویل را وارد کنید
                        </Text>
                      </div>
                  )}

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
        <Footer />
      </>
  );
}