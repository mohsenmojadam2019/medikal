'use client';

import { useState, useEffect, useCallback } from 'react';
import {
  Card, Row, Col, Typography, Spin, Empty, Tag,
  Button, Input, Space, App, Badge, Modal,
  Select, Pagination, Divider, Tooltip
} from 'antd';
import {
  SearchOutlined, ShoppingCartOutlined,
  PlusOutlined, MinusOutlined, DeleteOutlined,
  MedicineBoxOutlined, EyeOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function PharmacyPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const { message: appMessage } = App.useApp();

  const [drugs, setDrugs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(12);
  const [cart, setCart] = useState([]);
  const [cartVisible, setCartVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [categories, setCategories] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [requiresPrescription, setRequiresPrescription] = useState('all');
  const [totalItems, setTotalItems] = useState(0);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => localStorage.getItem('token');

  const fetchDrugs = useCallback(async () => {
    setLoading(true);
    try {
      const token = getToken();
      const params = new URLSearchParams();
      if (searchTerm) params.append('search', searchTerm);
      if (selectedCategory !== 'all') params.append('category', selectedCategory);
      if (requiresPrescription !== 'all') {
        params.append('requires_prescription', requiresPrescription === 'required' ? '1' : '0');
      }
      params.append('page', currentPage);
      params.append('per_page', pageSize);

      const res = await fetch(`${API_URL}/api/drugs/active?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();
      if (data.success) {
        const drugsData = data.data.data || data.data || [];
        setDrugs(drugsData);
        setTotalItems(data.data.total || data.data.length || 0);

        const uniqueCategories = [...new Set(drugsData.map(d => d.category).filter(Boolean))];
        setCategories(uniqueCategories);
      } else {
        appMessage.error(data.message || t('pharmacy.errorFetchingDrugs'));
      }
    } catch (error) {
      console.error('Error fetching drugs:', error);
      appMessage.error(t('pharmacy.serverError'));
    } finally {
      setLoading(false);
    }
  }, [searchTerm, selectedCategory, requiresPrescription, currentPage, pageSize, appMessage, t]);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    fetchDrugs();

    // ✅ بارگذاری سبد خرید از localStorage
    const savedCart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');
    console.log('🛒 Loaded cart:', savedCart);
    setCart(savedCart);
  }, [fetchDrugs, locale, router]);

  // ✅ ذخیره سبد خرید در localStorage
  useEffect(() => {
    localStorage.setItem('pharmacyCart', JSON.stringify(cart));
    console.log('💾 Cart saved:', cart);
  }, [cart]);

  const addToCart = (drug) => {
    const existing = cart.find(item => item.id === drug.id);
    let newCart;
    if (existing) {
      newCart = cart.map(item =>
          item.id === drug.id ? { ...item, quantity: item.quantity + 1 } : item
      );
    } else {
      newCart = [...cart, {
        ...drug,
        quantity: 1,
        price: drug.final_price || drug.price
      }];
    }
    setCart(newCart);
    appMessage.success(`${drug.name} ${t('pharmacy.addedToCart')}`);
  };

  const removeFromCart = (drugId) => {
    const newCart = cart.filter(item => item.id !== drugId);
    setCart(newCart);
  };

  const updateQuantity = (drugId, change) => {
    const newCart = cart.map(item =>
        item.id === drugId
            ? { ...item, quantity: Math.max(1, item.quantity + change) }
            : item
    );
    setCart(newCart);
  };

  const getTotalPrice = () => {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  };

  const getTotalItems = () => {
    return cart.reduce((sum, item) => sum + item.quantity, 0);
  };

  const handleSearch = () => {
    setCurrentPage(1);
    fetchDrugs();
  };

  // ✅ رفتن به صفحه تسویه حساب
  const goToCheckout = () => {
    if (cart.length === 0) {
      appMessage.warning(t('cartEmpty') || 'سبد خرید شما خالی است');
      return;
    }

    // ✅ ذخیره اطلاعات برای checkout
    const checkoutData = {
      items: cart.map(item => ({
        id: item.id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
      })),
      total: getTotalPrice(),
    };

    localStorage.setItem('pharmacyCheckoutData', JSON.stringify(checkoutData));
    console.log('📦 Checkout data saved:', checkoutData);

    setCartVisible(false);
    router.push(`/${locale}/pharmacy/checkout`);
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

  return (
      <>
        <Header />
        <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc' }}>
          <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb />

            <div style={{
              marginBottom: '24px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              flexWrap: 'wrap',
              gap: '16px'
            }}>
              <div>
                <Title level={2} style={{ marginBottom: '4px' }}>
                  💊 {t('pharmacy.title')}
                </Title>
                <Text type="secondary">
                  {t('pharmacy.subtitle')}
                </Text>
              </div>
              <Badge count={getTotalItems()} offset={[10, 0]} size="large">
                <Button
                    type="primary"
                    icon={<ShoppingCartOutlined />}
                    onClick={() => setCartVisible(true)}
                    size="large"
                    style={{ borderRadius: '12px', height: '48px' }}
                >
                  {t('pharmacy.cart')} ({getTotalItems()})
                </Button>
              </Badge>
            </div>

            <Card style={{ borderRadius: '16px', marginBottom: '24px' }}>
              <Row gutter={[16, 16]}>
                <Col xs={24} md={8}>
                  <Input
                      placeholder={t('pharmacy.searchProducts')}
                      prefix={<SearchOutlined />}
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      onPressEnter={handleSearch}
                      style={{ borderRadius: '8px' }}
                      allowClear
                  />
                </Col>
                <Col xs={12} md={6}>
                  <Select
                      placeholder={t('pharmacy.selectCategory')}
                      style={{ width: '100%' }}
                      value={selectedCategory}
                      onChange={setSelectedCategory}
                      allowClear
                  >
                    <Option value="all">{t('pharmacy.allCategories')}</Option>
                    {categories.map(cat => (
                        <Option key={cat} value={cat}>{cat}</Option>
                    ))}
                  </Select>
                </Col>
                <Col xs={12} md={6}>
                  <Select
                      placeholder={t('pharmacy.prescription')}
                      style={{ width: '100%' }}
                      value={requiresPrescription}
                      onChange={setRequiresPrescription}
                  >
                    <Option value="all">{t('pharmacy.all')}</Option>
                    <Option value="required">{t('pharmacy.withPrescription')}</Option>
                    <Option value="not_required">{t('pharmacy.withoutPrescription')}</Option>
                  </Select>
                </Col>
                <Col xs={24} md={4}>
                  <Button
                      type="primary"
                      block
                      onClick={handleSearch}
                      style={{ borderRadius: '8px' }}
                  >
                    {t('common.search') || 'جستجو'}
                  </Button>
                </Col>
              </Row>
            </Card>

            <div style={{ marginBottom: '16px' }}>
              <Text type="secondary">
                {totalItems} {t('pharmacy.products')} {t('pharmacy.found')}
              </Text>
            </div>

            {drugs.length > 0 ? (
                <>
                  <Row gutter={[16, 16]}>
                    {drugs.map((drug) => (
                        <Col xs={24} sm={12} md={8} lg={6} key={drug.id}>
                          <Card
                              hoverable
                              style={{
                                borderRadius: '12px',
                                height: '100%',
                                transition: 'all 0.3s ease',
                              }}
                              cover={
                                <div style={{
                                  height: 140,
                                  background: '#f0f5ff',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  borderRadius: '12px 12px 0 0',
                                  position: 'relative',
                                }}>
                                  <MedicineBoxOutlined style={{ fontSize: 56, color: '#2563eb' }} />
                                  {drug.requires_prescription && (
                                      <Tag
                                          color="orange"
                                          style={{
                                            position: 'absolute',
                                            top: 8,
                                            left: 8,
                                            fontSize: '10px',
                                          }}
                                      >
                                        {t('pharmacy.requiresPrescription')}
                                      </Tag>
                                  )}
                                  {drug.stock === 0 && (
                                      <Tag
                                          color="red"
                                          style={{
                                            position: 'absolute',
                                            top: 8,
                                            right: 8,
                                            fontSize: '10px',
                                          }}
                                      >
                                        {t('pharmacy.outOfStock')}
                                      </Tag>
                                  )}
                                </div>
                              }
                              actions={[
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<ShoppingCartOutlined />}
                                    onClick={() => addToCart(drug)}
                                    disabled={drug.stock === 0}
                                    block
                                    style={{ borderRadius: '8px' }}
                                >
                                  {drug.stock > 0 ? t('pharmacy.addToCart') : t('pharmacy.outOfStock')}
                                </Button>,
                                <Button
                                    type="text"
                                    size="small"
                                    icon={<EyeOutlined />}
                                    onClick={() => router.push(`/${locale}/pharmacy/drug/${drug.id}`)}
                                />,
                              ]}
                          >
                            <div style={{ minHeight: 80 }}>
                              <Tooltip title={drug.name}>
                                <Text strong style={{ fontSize: '14px', display: 'block' }}>
                                  {drug.name.length > 20 ? drug.name.substring(0, 20) + '...' : drug.name}
                                </Text>
                              </Tooltip>
                              {drug.generic_name && (
                                  <Text type="secondary" style={{ fontSize: '12px' }}>
                                    {drug.generic_name}
                                  </Text>
                              )}
                              <div style={{ marginTop: '4px' }}>
                                <Tag color="blue" style={{ fontSize: '10px' }}>
                                  {drug.category || 'عمومی'}
                                </Tag>
                              </div>
                              <div style={{ marginTop: '8px' }}>
                                <Text strong style={{ color: '#2563eb', fontSize: '16px' }}>
                                  {drug.price?.toLocaleString() || 0}
                                </Text>
                                <Text type="secondary" style={{ fontSize: '12px' }}> تومان</Text>
                              </div>
                              <div style={{ marginTop: '4px' }}>
                                <Text type="secondary" style={{ fontSize: '12px' }}>
                                  {t('pharmacy.stock')}: {drug.stock > 0 ? `${drug.stock} عدد` : t('pharmacy.outOfStock')}
                                </Text>
                              </div>
                            </div>
                          </Card>
                        </Col>
                    ))}
                  </Row>

                  <div style={{ marginTop: '32px', textAlign: 'center' }}>
                    <Pagination
                        current={currentPage}
                        total={totalItems}
                        pageSize={pageSize}
                        onChange={(page) => setCurrentPage(page)}
                        showSizeChanger
                        onShowSizeChange={(_, size) => {
                          setPageSize(size);
                          setCurrentPage(1);
                        }}
                        locale={{
                          items_per_page: `${t('pharmacy.perPage')}`,
                          jump_to: `${t('pharmacy.goTo')}`,
                        }}
                    />
                  </div>
                </>
            ) : (
                <Empty
                    description={t('pharmacy.noProducts')}
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                >
                  <Button type="primary" onClick={() => {
                    setSearchTerm('');
                    setSelectedCategory('all');
                    setRequiresPrescription('all');
                    setCurrentPage(1);
                    fetchDrugs();
                  }}>
                    {t('pharmacy.resetFilters')}
                  </Button>
                </Empty>
            )}
          </div>
        </main>

        {/* مودال سبد خرید */}
        <Modal
            title={
              <Space>
                <ShoppingCartOutlined />
                {t('pharmacy.cart')} ({getTotalItems()} {t('common.items')})
              </Space>
            }
            open={cartVisible}
            onCancel={() => setCartVisible(false)}
            footer={null}
            width={600}
        >
          {cart.length > 0 ? (
              <>
                {cart.map((item) => (
                    <Card key={item.id} size="small" style={{ marginBottom: '8px', borderRadius: '8px' }}>
                      <Row align="middle" gutter={[16, 16]}>
                        <Col flex="auto">
                          <Text strong>{item.name}</Text>
                          <br />
                          <Text type="secondary" style={{ fontSize: '12px' }}>
                            {item.price.toLocaleString()} تومان
                          </Text>
                          {item.requires_prescription && (
                              <Tag color="orange" style={{ fontSize: '10px', marginLeft: '8px' }}>
                                {t('pharmacy.requiresPrescription')}
                              </Tag>
                          )}
                        </Col>
                        <Col>
                          <Space>
                            <Button
                                icon={<MinusOutlined />}
                                size="small"
                                onClick={() => updateQuantity(item.id, -1)}
                            />
                            <Text strong>{item.quantity}</Text>
                            <Button
                                icon={<PlusOutlined />}
                                size="small"
                                onClick={() => updateQuantity(item.id, 1)}
                            />
                            <Button
                                icon={<DeleteOutlined />}
                                size="small"
                                danger
                                onClick={() => removeFromCart(item.id)}
                            />
                          </Space>
                        </Col>
                      </Row>
                    </Card>
                ))}
                <Divider />
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text strong style={{ fontSize: '18px' }}>
                    {t('common.total')}: {getTotalPrice().toLocaleString()} تومان
                  </Text>
                  <Button
                      type="primary"
                      size="large"
                      loading={submitting}
                      onClick={goToCheckout}
                      style={{ borderRadius: '8px' }}
                  >
                    {t('pharmacy.checkout')}
                  </Button>
                </div>
              </>
          ) : (
              <Empty description={t('pharmacy.cartEmpty')} />
          )}
        </Modal>

        <Footer />
      </>
  );
}
