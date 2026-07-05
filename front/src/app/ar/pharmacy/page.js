'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Input, Button, Space, message, Badge, Modal, Form, Select, Pagination } from 'antd';
import { SearchOutlined, ShoppingCartOutlined, PlusOutlined, MinusOutlined, DeleteOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import AdvancedSearch from '@/components/shared/AdvancedSearch';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function PharmacyPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [drugs, setDrugs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filteredDrugs, setFilteredDrugs] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(12);
  const [cart, setCart] = useState([]);
  const [cartVisible, setCartVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [categories, setCategories] = useState([]);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchDrugs = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/drugs/active`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setDrugs(data.data || []);
        setFilteredDrugs(data.data || []);
        const uniqueCategories = [...new Set(data.data.map(d => d.category))];
        setCategories(uniqueCategories);
      } else {
        message.error(data.message || 'خطا در دریافت لیست داروها');
      }
    } catch (error) {
      console.error('Error fetching drugs:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    fetchDrugs();
  }, []);

  const handleSearch = ({ text, filters }) => {
    let filtered = drugs;
    
    if (text) {
      filtered = filtered.filter(drug =>
        drug.name?.toLowerCase().includes(text.toLowerCase()) ||
        drug.category?.toLowerCase().includes(text.toLowerCase())
      );
    }
    
    if (filters?.category && filters.category !== 'all') {
      filtered = filtered.filter(drug => drug.category === filters.category);
    }
    
    if (filters?.prescription) {
      filtered = filtered.filter(drug =>
        drug.requires_prescription === (filters.prescription === 'required')
      );
    }
    
    if (filters?.priceRange) {
      const [min, max] = filters.priceRange.split('-').map(Number);
      filtered = filtered.filter(drug =>
        drug.price >= min && drug.price <= max
      );
    }
    
    setFilteredDrugs(filtered);
    setCurrentPage(1);
  };

  const filterOptions = [
    {
      name: 'category',
      label: 'دسته‌بندی',
      type: 'select',
      placeholder: 'انتخاب دسته‌بندی',
      options: [
        { value: 'all', label: 'همه دسته‌ها' },
        ...categories.map(c => ({ value: c, label: c })),
      ],
    },
    {
      name: 'prescription',
      label: 'نیاز به نسخه',
      type: 'select',
      placeholder: 'نیاز به نسخه',
      options: [
        { value: 'all', label: 'همه' },
        { value: 'required', label: 'نیاز به نسخه' },
        { value: 'not_required', label: 'بدون نسخه' },
      ],
    },
    {
      name: 'priceRange',
      label: 'بازه قیمتی',
      type: 'select',
      placeholder: 'بازه قیمتی',
      options: [
        { value: 'all', label: 'همه' },
        { value: '0-50000', label: 'تا ۵۰,۰۰۰ تومان' },
        { value: '50000-100000', label: '۵۰,۰۰۰ تا ۱۰۰,۰۰۰' },
        { value: '100000-200000', label: '۱۰۰,۰۰۰ تا ۲۰۰,۰۰۰' },
        { value: '200000-500000', label: '۲۰۰,۰۰۰ تا ۵۰۰,۰۰۰' },
        { value: '500000-999999999', label: 'بیش از ۵۰۰,۰۰۰' },
      ],
    },
  ];

  const addToCart = (drug) => {
    const existing = cart.find(item => item.id === drug.id);
    if (existing) {
      setCart(cart.map(item =>
        item.id === drug.id ? { ...item, quantity: item.quantity + 1 } : item
      ));
    } else {
      setCart([...cart, { ...drug, quantity: 1 }]);
    }
    message.success(`${drug.name} به سبد خرید اضافه شد`);
  };

  const removeFromCart = (drugId) => {
    setCart(cart.filter(item => item.id !== drugId));
  };

  const updateQuantity = (drugId, change) => {
    setCart(cart.map(item =>
      item.id === drugId
        ? { ...item, quantity: Math.max(1, item.quantity + change) }
        : item
    ));
  };

  const getTotalPrice = () => {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  };

  const handleSubmitOrder = async () => {
    if (cart.length === 0) {
      message.warning('سبد خرید خالی است');
      return;
    }

    setSubmitting(true);
    const token = getToken();

    try {
      const res = await fetch(`${API_URL}/api/pharmacy/orders`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          items: cart.map(item => ({
            drug_id: item.id,
            quantity: item.quantity,
          })),
          total: getTotalPrice(),
        }),
      });

      const data = await res.json();
      if (data.success) {
        message.success('✅ سفارش با موفقیت ثبت شد');
        setCart([]);
        setCartVisible(false);
        router.push(`/${locale}/profile`);
      } else {
        message.error(data.message || '❌ خطا در ثبت سفارش');
      }
    } catch (error) {
      console.error('Error submitting order:', error);
      message.error('❌ خطا در ارتباط با سرور');
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

  const paginatedDrugs = filteredDrugs.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  );

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />
          
          <div style={{ marginBottom: '32px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '16px' }}>
            <div>
              <Title level={2}>💊 {t('nav.pharmacy')}</Title>
              <Text type="secondary">خرید آنلاین دارو و مکمل‌ها</Text>
            </div>
            <Badge count={cart.length} offset={[10, 0]}>
              <Button
                type="primary"
                icon={<ShoppingCartOutlined />}
                onClick={() => setCartVisible(true)}
                size="large"
              >
                سبد خرید ({cart.length})
              </Button>
            </Badge>
          </div>

          <AdvancedSearch
            onSearch={handleSearch}
            filters={filterOptions}
            placeholder="جستجوی دارو..."
          />

          <div style={{ marginTop: '16px' }}>
            <Text type="secondary">
              {filteredDrugs.length} دارو یافت شد
            </Text>
          </div>

          {paginatedDrugs.length > 0 ? (
            <>
              <Row gutter={[16, 16]} style={{ marginTop: '16px' }}>
                {paginatedDrugs.map((drug) => (
                  <Col xs={24} sm={12} lg={6} key={drug.id}>
                    <Card
                      style={{ borderRadius: '12px', height: '100%' }}
                      actions={[
                        <Button
                          type="primary"
                          onClick={() => addToCart(drug)}
                          disabled={drug.stock === 0}
                          block
                        >
                          {drug.stock > 0 ? 'افزودن به سبد' : 'ناموجود'}
                        </Button>
                      ]}
                    >
                      <div style={{ textAlign: 'center', fontSize: '48px', marginBottom: '12px' }}>
                        💊
                      </div>
                      <Title level={4} style={{ textAlign: 'center' }}>
                        {drug.name}
                      </Title>
                      <div style={{ textAlign: 'center' }}>
                        <Tag color="blue">{drug.category}</Tag>
                        {drug.requires_prescription && (
                          <Tag color="orange">نیاز به نسخه</Tag>
                        )}
                      </div>
                      <div style={{ marginTop: '12px', textAlign: 'center' }}>
                        <Text strong style={{ fontSize: '20px', color: '#2563eb' }}>
                          {drug.price?.toLocaleString() || 0}
                        </Text>
                        <Text type="secondary"> تومان</Text>
                      </div>
                      <div style={{ marginTop: '8px', textAlign: 'center' }}>
                        <Text type="secondary">
                          موجودی: {drug.stock > 0 ? `${drug.stock} عدد` : 'ناموجود'}
                        </Text>
                      </div>
                    </Card>
                  </Col>
                ))}
              </Row>

              <Pagination
                current={currentPage}
                total={filteredDrugs.length}
                pageSize={pageSize}
                onChange={(page) => setCurrentPage(page)}
                showSizeChanger
                onShowSizeChange={(current, size) => {
                  setPageSize(size);
                  setCurrentPage(1);
                }}
                style={{ marginTop: '32px', textAlign: 'center' }}
              />
            </>
          ) : (
            <Empty description="هیچ دارویی یافت نشد" />
          )}
        </div>
      </main>

      <Modal
        title="🛒 سبد خرید"
        open={cartVisible}
        onCancel={() => setCartVisible(false)}
        footer={null}
        width={600}
      >
        {cart.length > 0 ? (
          <>
            {cart.map((item) => (
              <Card key={item.id} size="small" style={{ marginBottom: '8px' }}>
                <Row align="middle" gutter={[16, 16]}>
                  <Col flex="auto">
                    <Text strong>{item.name}</Text>
                    <br />
                    <Text type="secondary">{item.price.toLocaleString()} تومان</Text>
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
            <div style={{ marginTop: '16px', textAlign: 'left' }}>
              <Text strong style={{ fontSize: '18px' }}>
                مجموع: {getTotalPrice().toLocaleString()} تومان
              </Text>
            </div>
            <div style={{ marginTop: '16px' }}>
              <Button
                type="primary"
                block
                size="large"
                loading={submitting}
                onClick={handleSubmitOrder}
              >
                ثبت سفارش
              </Button>
            </div>
          </>
        ) : (
          <Empty description="سبد خرید خالی است" />
        )}
      </Modal>

      <Footer />
    </>
  );
}
