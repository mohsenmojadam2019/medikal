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
    PhoneOutlined, ShopOutlined, EnvironmentOutlined
} from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Step } = Steps;
const { Option } = Select;

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
    const [selectedPharmacy, setSelectedPharmacy] = useState(null);
    const [pharmacies, setPharmacies] = useState([]);
    const [loadingPharmacies, setLoadingPharmacies] = useState(false);
    const [patientId, setPatientId] = useState(null);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
    const getToken = () => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('token');
        }
        return null;
    };

    useEffect(() => {
        setIsMounted(true);
    }, []);

    const fetchPharmacies = async () => {
        setLoadingPharmacies(true);
        try {
            const res = await fetch(`${API_URL}/api/pharmacy/pharmacies?is_online=1&per_page=100`);
            const data = await res.json();
            if (data.success) {
                const list = data.data.data || data.data || [];
                setPharmacies(list);
                if (list.length > 0) {
                    setSelectedPharmacy(list[0]);
                }
            }
        } catch (error) {
            console.error('Error fetching pharmacies:', error);
        } finally {
            setLoadingPharmacies(false);
        }
    };

    const fetchUserProfile = async () => {
        try {
            const token = getToken();
            if (!token) {
                setUserLoading(false);
                return;
            }

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

                const patientRes = await fetch(`${API_URL}/api/patients/my-profile`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                });
                const patientData = await patientRes.json();
                if (patientData.success && patientData.data) {
                    setPatientId(patientData.data.id);
                    if (patientData.data.address) {
                        setDeliveryAddress(patientData.data.address);
                    }
                } else {
                    console.log('بیمار یافت نشد، کاربر ممکن است بیمار نباشد');
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
            setTimeout(() => router.push('/pharmacy'), 1500);
        }

        setCart(cartData);
        setLoading(false);

        fetchUserProfile();
        fetchWalletBalance();
        fetchGateways();
        fetchPharmacies();
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

    const handleSubmitOrder = async () => {
        if (cart.length === 0) {
            message.warning('سبد خرید شما خالی است');
            return;
        }

        if (!selectedPharmacy) {
            message.warning('لطفاً یک داروخانه را انتخاب کنید');
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
            if (!token) {
                message.warning('لطفاً ابتدا وارد شوید');
                router.push('/login');
                return;
            }

            if (!patientId) {
                message.warning('اطلاعات بیمار کامل نیست. لطفاً پروفایل خود را تکمیل کنید.');
                setSubmitting(false);
                return;
            }

            const orderData = {
                items: cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                    price: item.price,
                    name: item.name,
                })),
                pharmacy_id: selectedPharmacy.id,
                delivery_address: deliveryAddress,
                delivery_notes: deliveryNotes || '',
                recipient_name: recipientName,
                recipient_phone: recipientPhone,
                payment_method: 'wallet',
                subtotal: getSubtotal(),
                tax: getTax(),
                total_price: getTotal(),
            };

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

            if (!orderDataResponse.success) {
                message.error(orderDataResponse.message || 'خطا در ثبت سفارش');
                setSubmitting(false);
                return;
            }

            message.success('سفارش با موفقیت ثبت شد و از کیف پول شما کسر گردید');
            localStorage.removeItem('pharmacyCart');
            localStorage.removeItem('pharmacyCheckoutData');

            setTimeout(() => {
                router.push('/profile/pharmacy-orders');
            }, 1500);
        } catch (error) {
            console.error('❌ Network error:', error);
            message.error('خطا در ثبت سفارش');
            setSubmitting(false);
        }
    };

    const handleGatewayPayment = async () => {
        if (cart.length === 0) {
            message.warning('سبد خرید شما خالی است');
            return;
        }

        if (!selectedPharmacy) {
            message.warning('لطفاً یک داروخانه را انتخاب کنید');
            return;
        }

        if (!recipientName.trim() || !recipientPhone.trim() || !deliveryAddress.trim()) {
            message.warning('لطفاً ابتدا اطلاعات تحویل را کامل کنید');
            return;
        }

        setSubmitting(true);
        try {
            const token = getToken();
            if (!token) {
                message.warning('لطفاً ابتدا وارد شوید');
                router.push('/login');
                return;
            }

            if (!patientId) {
                message.warning('اطلاعات بیمار کامل نیست. لطفاً پروفایل خود را تکمیل کنید.');
                setSubmitting(false);
                return;
            }

            const orderData = {
                items: cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                    price: item.price,
                    name: item.name,
                })),
                pharmacy_id: selectedPharmacy.id,
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

            const orderRes = await fetch(`${API_URL}/api/pharmacy/orders`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData),
            });

            const orderDataResponse = await orderRes.json();

            if (!orderDataResponse.success) {
                message.error(orderDataResponse.message || 'خطا در ثبت سفارش');
                setSubmitting(false);
                return;
            }

            const paymentLink = orderDataResponse.data?.payment_link;

            if (!paymentLink) {
                message.error('لینک پرداخت یافت نشد');
                setSubmitting(false);
                return;
            }

            let cleanLink = paymentLink
                .replace(/\\/g, '')
                .replace(/"/g, '')
                .replace(/\s/g, '');

            localStorage.setItem('pendingOrder', JSON.stringify({
                orderNumber: orderDataResponse.data?.order_number,
                returnUrl: '/profile/pharmacy-orders',
            }));

            message.success('در حال انتقال به درگاه پرداخت...');

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
                <Header />
                <div style={{ maxWidth: '800px', margin: '0 auto', padding: '40px 20px', textAlign: 'center' }}>
                    <Empty description="سبد خرید شما خالی است" />
                    <Button type="primary" onClick={() => router.push('/pharmacy')}>
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
        selectedPharmacy !== null &&
        patientId !== null &&
        !(paymentMethod === 'wallet' && !canUseWallet);

    return (
        <>
            <Header />
            <main style={{ background: '#f0f2f5', minHeight: 'calc(100vh - 200px)', padding: '32px 20px' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
                    <div style={{ marginBottom: 24 }}>
                        <Button
                            icon={<LeftOutlined />}
                            onClick={() => router.back()}
                            style={{ marginBottom: 12 }}
                        >
                            بازگشت
                        </Button>
                        <Title level={2} style={{ marginBottom: 4 }}>💳 تسویه حساب</Title>
                        <Text type="secondary">اطلاعات سفارش را تکمیل کنید</Text>
                    </div>

                    <Steps current={currentStep} style={{ marginBottom: 32, maxWidth: 600 }}>
                        <Step title="بررسی سفارش" />
                        <Step title="پرداخت" />
                        <Step title="تایید" />
                    </Steps>

                    <Row gutter={[32, 32]}>
                        <Col xs={24} lg={16}>
                            <Card
                                title={<Text strong style={{ fontSize: 16 }}>📋 خلاصه سفارش</Text>}
                                style={{ borderRadius: 12, marginBottom: 24 }}
                            >
                                {cart.map((item, index) => (
                                    <div key={index} style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        padding: '12px 0',
                                        borderBottom: index < cart.length - 1 ? '1px solid #f0f0f0' : 'none',
                                    }}>
                                        <Space>
                                            <MedicineBoxOutlined style={{ fontSize: 20, color: '#2563eb' }} />
                                            <div>
                                                <Text strong>{item.name}</Text>
                                                <br />
                                                <Text type="secondary" style={{ fontSize: 12 }}>
                                                    {formatPrice(item.price)} × {toPersianNumber(item.quantity)}
                                                </Text>
                                            </div>
                                        </Space>
                                        <Text strong style={{ fontSize: 16, color: '#2563eb' }}>
                                            {formatPrice(item.price * item.quantity)}
                                        </Text>
                                    </div>
                                ))}
                            </Card>

                            <Card
                                title={<Text strong style={{ fontSize: 16 }}>🏥 انتخاب داروخانه</Text>}
                                style={{ borderRadius: 12, marginBottom: 24 }}
                            >
                                <Select
                                    style={{ width: '100%' }}
                                    placeholder="انتخاب داروخانه"
                                    value={selectedPharmacy?.id}
                                    onChange={(id) => {
                                        const ph = pharmacies.find(p => p.id === id);
                                        setSelectedPharmacy(ph);
                                    }}
                                    loading={loadingPharmacies}
                                    size="large"
                                >
                                    {pharmacies.map(p => (
                                        <Option key={p.id} value={p.id}>
                                            {p.name} - {p.city?.name || ''}, {p.province?.name || ''}
                                        </Option>
                                    ))}
                                </Select>
                                {selectedPharmacy && (
                                    <div style={{
                                        marginTop: 12,
                                        padding: 16,
                                        background: '#f0f7ff',
                                        borderRadius: 8,
                                        border: '1px solid #dbeafe'
                                    }}>
                                        <Text strong>{selectedPharmacy.name}</Text>
                                        <div style={{ marginTop: 4 }}>
                                            <Text type="secondary" style={{ fontSize: 13 }}>
                                                <EnvironmentOutlined /> {selectedPharmacy.full_address || selectedPharmacy.address}
                                            </Text>
                                        </div>
                                        {selectedPharmacy.clinic && (
                                            <Text type="secondary" style={{ fontSize: 13, display: 'block' }}>
                                                <ShopOutlined /> {selectedPharmacy.clinic.name}
                                            </Text>
                                        )}
                                        <Text type="secondary" style={{ fontSize: 13 }}>
                                            <PhoneOutlined /> {selectedPharmacy.phone || '—'}
                                        </Text>
                                    </div>
                                )}
                            </Card>

                            <Card
                                title={<Text strong style={{ fontSize: 16 }}>👤 اطلاعات گیرنده</Text>}
                                style={{ borderRadius: 12, marginBottom: 24 }}
                            >
                                <Row gutter={[24, 16]}>
                                    <Col xs={24} md={12}>
                                        <div style={{ marginBottom: 4 }}>
                                            <Text strong>نام گیرنده</Text>
                                        </div>
                                        <Input
                                            placeholder="نام گیرنده را وارد کنید"
                                            value={recipientName}
                                            onChange={(e) => setRecipientName(e.target.value)}
                                            prefix={<UserOutlined />}
                                            size="large"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <div style={{ marginBottom: 4 }}>
                                            <Text strong>شماره تماس</Text>
                                        </div>
                                        <Input
                                            placeholder="شماره تماس گیرنده"
                                            value={recipientPhone}
                                            onChange={(e) => setRecipientPhone(e.target.value)}
                                            prefix={<PhoneOutlined />}
                                            size="large"
                                        />
                                    </Col>
                                </Row>
                            </Card>

                            <Card
                                title={<Text strong style={{ fontSize: 16 }}>📍 آدرس تحویل</Text>}
                                style={{ borderRadius: 12, marginBottom: 24 }}
                            >
                                <Input.TextArea
                                    placeholder="آدرس کامل تحویل را وارد کنید..."
                                    value={deliveryAddress}
                                    onChange={(e) => setDeliveryAddress(e.target.value)}
                                    rows={3}
                                    size="large"
                                    style={{ borderRadius: 8 }}
                                />
                                {userProfile?.address && (
                                    <div style={{ marginTop: 8 }}>
                                        <Text type="secondary" style={{ fontSize: 13 }}>
                                            <HomeOutlined /> آدرس پیش‌فرض شما: {userProfile.address}
                                        </Text>
                                    </div>
                                )}
                                <div style={{ marginTop: 16 }}>
                                    <Text strong>توضیحات اضافی</Text>
                                    <Input.TextArea
                                        placeholder="توضیحات اضافی برای ارسال..."
                                        value={deliveryNotes}
                                        onChange={(e) => setDeliveryNotes(e.target.value)}
                                        rows={2}
                                        style={{ marginTop: 4, borderRadius: 8 }}
                                    />
                                </div>
                            </Card>

                            <Card
                                title={<Text strong style={{ fontSize: 16 }}>💳 روش پرداخت</Text>}
                                style={{ borderRadius: 12, marginBottom: 24 }}
                            >
                                <Radio.Group
                                    value={paymentMethod}
                                    onChange={(e) => setPaymentMethod(e.target.value)}
                                    style={{ width: '100%' }}
                                >
                                    <Row gutter={[16, 16]}>
                                        <Col xs={24}>
                                            <Radio value="wallet" style={{ display: 'block', padding: '12px 16px', border: paymentMethod === 'wallet' ? '2px solid #2563eb' : '1px solid #e8e8e8', borderRadius: 8, width: '100%' }}>
                                                <Space>
                                                    <WalletOutlined style={{ fontSize: 20, color: '#2563eb' }} />
                                                    <div>
                                                        <div><Text strong>کیف پول</Text></div>
                                                        <Text type="secondary" style={{ fontSize: 13 }}>
                                                            موجودی: {formatPrice(walletBalance)}
                                                        </Text>
                                                        {!canUseWallet && (
                                                            <Tag color="red" style={{ marginLeft: 8 }}>موجودی کافی نیست</Tag>
                                                        )}
                                                    </div>
                                                </Space>
                                            </Radio>
                                        </Col>
                                        <Col xs={24}>
                                            <Radio value="gateway" style={{ display: 'block', padding: '12px 16px', border: paymentMethod === 'gateway' ? '2px solid #2563eb' : '1px solid #e8e8e8', borderRadius: 8, width: '100%' }}>
                                                <Space>
                                                    <CreditCardOutlined style={{ fontSize: 20, color: '#2563eb' }} />
                                                    <div>
                                                        <div><Text strong>درگاه پرداخت</Text></div>
                                                        <Text type="secondary" style={{ fontSize: 13 }}>پرداخت امن از طریق درگاه</Text>
                                                    </div>
                                                </Space>
                                            </Radio>
                                        </Col>
                                    </Row>
                                </Radio.Group>

                                {paymentMethod === 'wallet' && !canUseWallet && (
                                    <Alert
                                        message="موجودی کافی نیست"
                                        description="لطفاً روش پرداخت دیگری را انتخاب کنید یا کیف پول خود را شارژ کنید"
                                        type="warning"
                                        showIcon
                                        style={{ marginTop: 16 }}
                                    />
                                )}

                                {paymentMethod === 'gateway' && (
                                    <div style={{ marginTop: 16 }}>
                                        <Text strong>انتخاب درگاه:</Text>
                                        <Radio.Group
                                            value={selectedGateway}
                                            onChange={(e) => setSelectedGateway(e.target.value)}
                                            style={{ marginTop: 8, display: 'block' }}
                                        >
                                            <Row gutter={[8, 8]}>
                                                {gateways.map((gateway) => (
                                                    <Col key={gateway.name} xs={24} sm={12}>
                                                        <Radio value={gateway.name} style={{ display: 'block', padding: '8px 12px', border: selectedGateway === gateway.name ? '2px solid #2563eb' : '1px solid #e8e8e8', borderRadius: 6 }}>
                                                            <Space>
                                                                <span>{gateway.icon || '💳'}</span>
                                                                <span>{gateway.title}</span>
                                                                {gateway.is_default && (
                                                                    <Tag color="blue" style={{ fontSize: 10 }}>پیش‌فرض</Tag>
                                                                )}
                                                            </Space>
                                                        </Radio>
                                                    </Col>
                                                ))}
                                            </Row>
                                        </Radio.Group>
                                    </div>
                                )}
                            </Card>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Card
                                title={<Text strong style={{ fontSize: 16 }}>💰 خلاصه پرداخت</Text>}
                                style={{ borderRadius: 12, position: 'sticky', top: 24 }}
                            >
                                <div style={{ marginBottom: 12 }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                                        <Text>جمع محصولات ({cart.length} مورد)</Text>
                                        <Text>{formatPrice(getSubtotal())}</Text>
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                                        <Text>هزینه ارسال</Text>
                                        <Text style={{ color: '#22c55e' }}>رایگان</Text>
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                                        <Text>مالیات (۹٪)</Text>
                                        <Text>{formatPrice(getTax())}</Text>
                                    </div>
                                </div>

                                <Divider style={{ margin: '8px 0' }} />

                                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 8, padding: '8px 0' }}>
                                    <Text strong style={{ fontSize: 18 }}>جمع کل</Text>
                                    <Text strong style={{ fontSize: 20, color: '#2563eb' }}>
                                        {formatPrice(getTotal())}
                                    </Text>
                                </div>

                                {getSubtotal() > 200000 && (
                                    <Alert
                                        message="🎉 ارسال رایگان"
                                        description="به دلیل خرید بالای ۲۰۰ هزار تومان، هزینه ارسال رایگان است"
                                        type="success"
                                        showIcon
                                        style={{ marginTop: 12 }}
                                    />
                                )}

                                {!patientId && (
                                    <Alert
                                        message="⚠️ اطلاعات بیمار کامل نیست"
                                        description={
                                            <div>
                                                <Text>لطفاً ابتدا پروفایل خود را تکمیل کنید</Text>
                                                <Button
                                                    type="primary"
                                                    size="small"
                                                    icon={<UserOutlined />}
                                                    onClick={() => router.push('/profile')}
                                                    style={{ marginTop: 8 }}
                                                >
                                                    تکمیل پروفایل
                                                </Button>
                                            </div>
                                        }
                                        type="warning"
                                        showIcon
                                        style={{ marginTop: 12 }}
                                    />
                                )}

                                <Button
                                    type="primary"
                                    size="large"
                                    block
                                    onClick={paymentMethod === 'wallet' ? handleSubmitOrder : handleGatewayPayment}
                                    loading={submitting}
                                    disabled={!isFormValid}
                                    style={{
                                        marginTop: 16,
                                        borderRadius: 8,
                                        height: 48,
                                        fontWeight: 'bold',
                                        fontSize: 16
                                    }}
                                >
                                    {paymentMethod === 'wallet'
                                        ? (canUseWallet ? 'پرداخت با کیف پول' : 'موجودی کافی نیست')
                                        : 'پرداخت با درگاه'
                                    }
                                </Button>

                                <div style={{ marginTop: 12, textAlign: 'center' }}>
                                    <Space>
                                        <SafetyOutlined style={{ color: '#94a3b8' }} />
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            اطلاعات شما محفوظ است
                                        </Text>
                                    </Space>
                                </div>
                            </Card>

                            <Card
                                title="🎁 پیشنهاد ویژه"
                                style={{ borderRadius: 12, marginTop: 16 }}
                                size="small"
                            >
                                <div style={{ textAlign: 'center' }}>
                                    <Text strong>تخفیف ۲۰٪ برای اولین خرید</Text>
                                    <div style={{ marginTop: 8 }}>
                                        <Tag color="green" style={{ fontSize: 14, padding: '4px 12px' }}>
                                            کد: WELCOME20
                                        </Tag>
                                    </div>
                                    <Text type="secondary" style={{ fontSize: 12 }}>
                                        کد تخفیف را در مرحله تسویه حساب وارد کنید
                                    </Text>
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