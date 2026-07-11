'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { 
  Card, Row, Col, Button, Typography, Space, Divider, 
  Empty, App, List, Tag, InputNumber, Popconfirm, 
  Checkbox, Alert, Badge, Skeleton, Image, message
} from 'antd';
import { 
  ShoppingCartOutlined, DeleteOutlined, PlusOutlined, 
  MinusOutlined, ArrowLeftOutlined, ShoppingOutlined,
  MedicineBoxOutlined, SafetyOutlined,
  WalletOutlined, CreditCardOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

function toPersianNumber(num) {
  if (!num && num !== 0) return '۰';
  const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  return num.toString().replace(/\d/g, d => persian[d]);
}

function formatPrice(price) {
  return toPersianNumber(price.toLocaleString()) + ' تومان';
}

export default function CartPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const { message: appMessage } = App.useApp();
  const [cart, setCart] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedItems, setSelectedItems] = useState([]);

  useEffect(() => {
    const savedCart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');
    setCart(savedCart);
    setSelectedItems(savedCart.map(item => item.id));
    setLoading(false);
  }, []);

  // ذخیره سبد خرید در localStorage
  useEffect(() => {
    if (!loading) {
      localStorage.setItem('pharmacyCart', JSON.stringify(cart));
    }
  }, [cart, loading]);

  const updateQuantity = (id, change) => {
    const newCart = cart.map(item =>
      item.id === id 
        ? { ...item, quantity: Math.max(1, item.quantity + change) } 
        : item
    );
    setCart(newCart);
  };

  const removeFromCart = (id) => {
    const newCart = cart.filter(item => item.id !== id);
    setCart(newCart);
    setSelectedItems(selectedItems.filter(itemId => itemId !== id));
    appMessage.success('✅ محصول از سبد خرید حذف شد');
  };

  const clearCart = () => {
    setCart([]);
    setSelectedItems([]);
    appMessage.success('✅ سبد خرید خالی شد');
  };

  const toggleSelectItem = (id) => {
    if (selectedItems.includes(id)) {
      setSelectedItems(selectedItems.filter(itemId => itemId !== id));
    } else {
      setSelectedItems([...selectedItems, id]);
    }
  };

  const toggleSelectAll = () => {
    if (selectedItems.length === cart.length) {
      setSelectedItems([]);
    } else {
      setSelectedItems(cart.map(item => item.id));
    }
  };

  const getSubtotal = () => {
    const selectedCart = cart.filter(item => selectedItems.includes(item.id));
    return selectedCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  };

  const getDeliveryFee = () => {
    const subtotal = getSubtotal();
    if (subtotal === 0) return 0;
    return subtotal > 200000 ? 0 : 25000; // بالای ۲۰۰ هزار تومان ارسال رایگان
  };

  const getTax = () => {
    return getSubtotal() * 0.09; // ۹٪ مالیات
  };

  const getTotal = () => {
    return getSubtotal() + getDeliveryFee() + getTax();
  };

  const goToCheckout = () => {
    if (selectedItems.length === 0) {
      appMessage.warning('لطفاً حداقل یک محصول را انتخاب کنید');
      return;
    }

    const selectedCart = cart.filter(item => selectedItems.includes(item.id));
    localStorage.setItem('pharmacyCheckoutData', JSON.stringify({
      items: selectedCart.map(item => ({
        id: item.id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
      })),
      total: getTotal(),
    }));
    router.push(`/${locale}/pharmacy/checkout`);
  };

  const continueShopping = () => {
    router.push(`/${locale}/pharmacy`);
  };

  if (loading) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '40px 20px' }}>
          <Skeleton active avatar paragraph={{ rows: 8 }} />
        </div>
        <Footer />
      </>
    );
  }

  if (cart.length === 0) {
    return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '900px', margin: '0 auto', padding: '40px 20px' }}>
            <Breadcrumb items={[
              { title: 'خانه', href: `/${locale}` },
              { title: 'داروخانه', href: `/${locale}/pharmacy` },
              { title: 'سبد خرید' },
            ]} />

            <Card style={{ borderRadius: '16px', textAlign: 'center', padding: '40px 0' }}>
              <Empty 
                description={
                  <div>
                    <Title level={3}>🛒 سبد خرید شما خالی است</Title>
                    <Text type="secondary">برای مشاهده محصولات به صفحه داروخانه بروید</Text>
                  </div>
                }
                image={Empty.PRESENTED_IMAGE_SIMPLE}
              >
                <Button 
                  type="primary" 
                  size="large"
                  icon={<ShoppingOutlined />}
                  onClick={continueShopping}
                  style={{ borderRadius: '12px', height: '48px' }}
                >
                  شروع خرید
                </Button>
              </Empty>
            </Card>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  const isAllSelected = selectedItems.length === cart.length && cart.length > 0;

  return (
    <>
      <Header />
      <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
          <Breadcrumb items={[
            { title: 'خانه', href: `/${locale}` },
            { title: 'داروخانه', href: `/${locale}/pharmacy` },
            { title: 'سبد خرید' },
          ]} />

          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
            <div>
              <Title level={2} style={{ marginBottom: '4px' }}>
                🛒 سبد خرید
              </Title>
              <Text type="secondary">
                {cart.length} محصول در سبد خرید شما موجود است
              </Text>
            </div>
            <Space>
              <Button 
                icon={<ArrowLeftOutlined />}
                onClick={continueShopping}
              >
                ادامه خرید
              </Button>
              <Popconfirm
                title="حذف همه محصولات"
                description="آیا از خالی کردن سبد خرید اطمینان دارید؟"
                onConfirm={clearCart}
                okText="بله، خالی شود"
                cancelText="انصراف"
              >
                <Button danger>
                  خالی کردن سبد
                </Button>
              </Popconfirm>
            </Space>
          </div>

          <Row gutter={[24, 24]}>
            {/* لیست محصولات */}
            <Col xs={24} lg={16}>
              <Card 
                title={
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <Checkbox
                      checked={isAllSelected}
                      onChange={toggleSelectAll}
                    />
                    <Text>انتخاب همه ({cart.length})</Text>
                  </div>
                }
                style={{ borderRadius: '16px' }}
                styles={{ body: { padding: '0' } }}
              >
                <List
                  dataSource={cart}
                  renderItem={(item, index) => {
                    const isSelected = selectedItems.includes(item.id);
                    return (
                      <List.Item
                        style={{
                          padding: '16px 24px',
                          background: isSelected ? '#f0f7ff' : 'transparent',
                          borderBottom: index < cart.length - 1 ? '1px solid #f0f0f0' : 'none',
                          transition: 'all 0.3s ease',
                        }}
                        actions={[
                          <Space key="actions">
                            <Button 
                              size="small" 
                              icon={<MinusOutlined />}
                              onClick={() => updateQuantity(item.id, -1)}
                            />
                            <Text strong>{toPersianNumber(item.quantity)}</Text>
                            <Button 
                              size="small" 
                              icon={<PlusOutlined />}
                              onClick={() => updateQuantity(item.id, 1)}
                            />
                            <Popconfirm
                              title="حذف محصول"
                              description={`آیا از حذف "${item.name}" اطمینان دارید؟`}
                              onConfirm={() => removeFromCart(item.id)}
                              okText="حذف"
                              cancelText="انصراف"
                            >
                              <Button 
                                type="text" 
                                danger 
                                icon={<DeleteOutlined />}
                              />
                            </Popconfirm>
                          </Space>,
                        ]}
                      >
                        <List.Item.Meta
                          avatar={
                            <Checkbox
                              checked={isSelected}
                              onChange={() => toggleSelectItem(item.id)}
                              style={{ alignSelf: 'center' }}
                            />
                          }
                          title={
                            <div>
                              <Text strong style={{ fontSize: '16px' }}>{item.name}</Text>
                              {item.requires_prescription && (
                                <Tag color="orange" style={{ marginLeft: '8px', fontSize: '10px' }}>
                                  📋 نیاز به نسخه
                                </Tag>
                              )}
                            </div>
                          }
                          description={
                            <div>
                              <Text type="secondary">
                                {item.brand || item.generic_name || ''}
                              </Text>
                              <div style={{ marginTop: '4px' }}>
                                <Text strong style={{ color: '#2563eb' }}>
                                  {formatPrice(item.price)}
                                </Text>
                                <Text type="secondary" style={{ fontSize: '12px' }}>
                                  × {toPersianNumber(item.quantity)}
                                </Text>
                              </div>
                            </div>
                          }
                        />
                        <div>
                          <Text strong style={{ fontSize: '18px', color: '#2563eb' }}>
                            {formatPrice(item.price * item.quantity)}
                          </Text>
                        </div>
                      </List.Item>
                    );
                  }}
                />
              </Card>
            </Col>

            {/* خلاصه سفارش */}
            <Col xs={24} lg={8}>
              <Card title="💰 خلاصه سفارش" style={{ borderRadius: '16px' }}>
                <div style={{ marginBottom: '12px' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                    <Text>جمع محصولات ({selectedItems.length} مورد)</Text>
                    <Text>{formatPrice(getSubtotal())}</Text>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                    <Text>هزینه ارسال</Text>
                    <Text>{getDeliveryFee() === 0 ? 'رایگان' : formatPrice(getDeliveryFee())}</Text>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                    <Text>مالیات (۹٪)</Text>
                    <Text>{formatPrice(getTax())}</Text>
                  </div>
                </div>

                <Divider style={{ margin: '8px 0' }} />

                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '8px' }}>
                  <Text strong style={{ fontSize: '18px' }}>جمع کل</Text>
                  <Text strong style={{ fontSize: '20px', color: '#2563eb' }}>
                    {formatPrice(getTotal())}
                  </Text>
                </div>

                {getSubtotal() > 200000 && (
                  <Alert
                    message="🎉 ارسال رایگان"
                    description="به دلیل خرید بالای ۲۰۰ هزار تومان، هزینه ارسال رایگان است"
                    type="success"
                    showIcon
                    style={{ marginTop: '12px' }}
                  />
                )}

                <Button
                  type="primary"
                  size="large"
                  block
                  onClick={goToCheckout}
                  disabled={selectedItems.length === 0}
                  style={{ 
                    marginTop: '16px', 
                    borderRadius: '12px', 
                    height: '48px',
                    fontWeight: 'bold'
                  }}
                >
                  {selectedItems.length === 0 
                    ? 'محصولی انتخاب نشده است' 
                    : `تسویه حساب (${toPersianNumber(selectedItems.length)} مورد)`
                  }
                </Button>

                <div style={{ marginTop: '12px', display: 'flex', justifyContent: 'center', gap: '8px' }}>
                  <SafetyOutlined style={{ color: '#94a3b8' }} />
                  <Text type="secondary" style={{ fontSize: '12px' }}>
                    پرداخت امن و تضمینی
                  </Text>
                </div>
              </Card>

              {/* پیشنهادات ویژه */}
              {cart.length > 0 && (
                <Card 
                  title="🎁 پیشنهاد ویژه" 
                  style={{ borderRadius: '16px', marginTop: '16px' }}
                  size="small"
                >
                  <div style={{ textAlign: 'center' }}>
                    <Text strong>تخفیف ۲۰٪ برای اولین خرید</Text>
                    <div style={{ marginTop: '8px' }}>
                      <Tag color="green" style={{ fontSize: '14px', padding: '4px 12px' }}>
                        کد: WELCOME20
                      </Tag>
                    </div>
                    <Text type="secondary" style={{ fontSize: '12px' }}>
                      کد تخفیف را در مرحله تسویه حساب وارد کنید
                    </Text>
                  </div>
                </Card>
              )}
            </Col>
          </Row>
        </div>
      </main>
      <Footer />
    </>
  );
}
