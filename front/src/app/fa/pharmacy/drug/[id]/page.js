'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { 
  Card, Row, Col, Button, Typography, Spin, Tag, 
  Space, Divider, Descriptions, App, Image,
  InputNumber, Alert, Rate, Skeleton,
  Tabs, List, Badge, Tooltip
} from 'antd';
import { 
  ShoppingCartOutlined, HeartOutlined, 
  SafetyOutlined, DollarOutlined,
  MedicineBoxOutlined, CheckCircleOutlined,
  CloseCircleOutlined, InfoCircleOutlined,
  LeftOutlined, ShareAltOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text, Paragraph } = Typography;
const { TabPane } = Tabs;

export default function DrugDetailPage() {
  const router = useRouter();
  const params = useParams();
  const { t, locale } = useLanguage();
  const { message: appMessage } = App.useApp();
  const id = params?.id;
  
  const [drug, setDrug] = useState(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [cart, setCart] = useState([]);
  const [relatedDrugs, setRelatedDrugs] = useState([]);
  
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => localStorage.getItem('token');

  useEffect(() => {
    const fetchDrug = async () => {
      if (!id) return;
      
      try {
        const token = getToken();
        const res = await fetch(`${API_URL}/api/drugs/${id}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        });
        const data = await res.json();
        if (data.success) {
          setDrug(data.data);
          // دریافت داروهای مرتبط
          fetchRelatedDrugs(data.data.category);
        } else {
          appMessage.error(data.message || t('productNotFound') || 'دارو یافت نشد');
          router.push(`/${locale}/pharmacy`);
        }
      } catch (error) {
        console.error('Error fetching drug:', error);
        appMessage.error(t('serverError') || 'خطا در دریافت اطلاعات');
      } finally {
        setLoading(false);
      }
    };

    const fetchRelatedDrugs = async (category) => {
      try {
        const token = getToken();
        const res = await fetch(
          `${API_URL}/api/drugs/active?category=${category}&limit=10`,
          {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json',
            },
          }
        );
        const data = await res.json();
        if (data.success) {
          setRelatedDrugs(data.data.data || data.data || []);
        }
      } catch (error) {
        console.error('Error fetching related drugs:', error);
      }
    };

    fetchDrug();
    
    const savedCart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');
    setCart(savedCart);
  }, [id]);

  const addToCart = () => {
    if (!drug) return;
    
    const existing = cart.find(item => item.id === drug.id);
    let newCart;
    if (existing) {
      newCart = cart.map(item =>
        item.id === drug.id ? { ...item, quantity: item.quantity + quantity } : item
      );
    } else {
      newCart = [...cart, { 
        ...drug, 
        quantity,
        price: drug.final_price || drug.price 
      }];
    }
    setCart(newCart);
    localStorage.setItem('pharmacyCart', JSON.stringify(newCart));
    appMessage.success(`${drug.name} ${t('addedToCart') || 'به سبد خرید اضافه شد'}`);
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

  if (!drug) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '40px 20px', textAlign: 'center' }}>
          <Title level={4}>{t('productNotFound') || 'دارو یافت نشد'}</Title>
          <Button type="primary" onClick={() => router.push(`/${locale}/pharmacy`)}>
            {t('backToPharmacy') || 'بازگشت به داروخانه'}
          </Button>
        </div>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
          <Breadcrumb 
            items={[
              { title: t('home') || 'خانه', href: `/${locale}` },
              { title: t('pharmacy') || 'داروخانه', href: `/${locale}/pharmacy` },
              { title: drug.name },
            ]}
          />

          <Row gutter={[24, 24]}>
            {/* تصویر دارو */}
            <Col xs={24} md={10}>
              <Card style={{ borderRadius: '16px' }}>
                <div style={{ 
                  height: 300, 
                  display: 'flex', 
                  alignItems: 'center', 
                  justifyContent: 'center',
                  background: '#f0f5ff',
                  borderRadius: '12px',
                }}>
                  <MedicineBoxOutlined style={{ fontSize: 100, color: '#2563eb' }} />
                </div>
                {drug.requires_prescription && (
                  <Alert
                    message={t('requiresPrescription') || 'نیاز به نسخه پزشک'}
                    description={t('prescriptionInfo') || 'برای خرید این دارو نیاز به ارائه نسخه پزشک معتبر دارید'}
                    type="warning"
                    showIcon
                    style={{ marginTop: '16px' }}
                  />
                )}
              </Card>
            </Col>

            {/* اطلاعات دارو */}
            <Col xs={24} md={14}>
              <Card style={{ borderRadius: '16px' }}>
                <div>
                  {drug.requires_prescription && (
                    <Tag color="orange" style={{ marginBottom: '8px', padding: '4px 12px' }}>
                      📋 {t('requiresPrescription') || 'نیاز به نسخه پزشک'}
                    </Tag>
                  )}
                  <Title level={2} style={{ marginBottom: '4px' }}>
                    {drug.name}
                  </Title>
                  <Space size="middle" wrap>
                    {drug.category && (
                      <Tag color="blue">{drug.category}</Tag>
                    )}
                    {drug.manufacturer && (
                      <Text type="secondary">
                        {t('manufacturer') || 'سازنده'}: {drug.manufacturer}
                      </Text>
                    )}
                    <Rate disabled defaultValue={4} style={{ fontSize: '14px' }} />
                  </Space>
                  {drug.generic_name && (
                    <div style={{ marginTop: '8px' }}>
                      <Text type="secondary">
                        {t('genericName') || 'نام ژنریک'}: {drug.generic_name}
                      </Text>
                    </div>
                  )}
                </div>

                <Divider />

                <Row gutter={[16, 16]}>
                  <Col xs={24} sm={12}>
                    <div>
                      <Text type="secondary">{t('price') || 'قیمت'}:</Text>
                      <div>
                        <Text strong style={{ color: '#2563eb', fontSize: '28px' }}>
                          {drug.price?.toLocaleString() || 0}
                        </Text>
                        <Text type="secondary" style={{ fontSize: '16px' }}> تومان</Text>
                      </div>
                    </div>
                  </Col>
                  <Col xs={24} sm={12}>
                    <div style={{ textAlign: 'left' }}>
                      <Text type="secondary">{t('stock') || 'موجودی'}:</Text>
                      <div>
                        <Badge 
                          status={drug.stock > 0 ? 'success' : 'error'} 
                          text={drug.stock > 0 ? `${drug.stock} ${t('inStock') || 'موجود'}` : t('outOfStock') || 'ناموجود'}
                        />
                      </div>
                    </div>
                  </Col>
                </Row>

                <Divider />

                <div>
                  <Text strong>{t('quantity') || 'تعداد'}:</Text>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginTop: '8px' }}>
                    <InputNumber
                      min={1}
                      max={drug.stock || 10}
                      value={quantity}
                      onChange={setQuantity}
                      style={{ width: 100 }}
                    />
                    <Button 
                      type="primary" 
                      size="large"
                      icon={<ShoppingCartOutlined />}
                      onClick={addToCart}
                      disabled={drug.stock === 0}
                      style={{ borderRadius: '12px', height: '48px', flex: 1 }}
                    >
                      {drug.stock > 0 ? t('addToCart') || 'افزودن به سبد خرید' : t('outOfStock') || 'ناموجود'}
                    </Button>
                  </div>
                </div>

                <Divider />

                <Space direction="vertical" style={{ width: '100%' }} size="small">
                  <div>
                    <Text type="secondary">
                      <SafetyOutlined /> {t('safeShopping') || 'خرید امن و تضمینی'}
                    </Text>
                  </div>
                  <div>
                    <Text type="secondary">
                      <InfoCircleOutlined /> {t('deliveryInfo') || 'ارسال به سراسر کشور'}
                    </Text>
                  </div>
                </Space>
              </Card>
            </Col>
          </Row>

          {/* توضیحات و مشخصات */}
          <Card style={{ marginTop: '24px', borderRadius: '16px' }}>
            <Tabs defaultActiveKey="description">
              <TabPane tab={t('description') || 'توضیحات'} key="description">
                <Paragraph>
                  {drug.description || t('noDescription') || 'توضیحاتی برای این دارو وارد نشده است.'}
                </Paragraph>
                {drug.strength && (
                  <div>
                    <Text strong>{t('strength') || 'قدرت دارو'}: </Text>
                    <Text>{drug.strength}</Text>
                  </div>
                )}
                {drug.form && (
                  <div>
                    <Text strong>{t('form') || 'شکل دارو'}: </Text>
                    <Text>{drug.form}</Text>
                  </div>
                )}
              </TabPane>
              <TabPane tab={t('specifications') || 'مشخصات'} key="specifications">
                <Descriptions bordered column={2}>
                  <Descriptions.Item label={t('brand') || 'برند'}>
                    {drug.brand || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('genericName') || 'نام ژنریک'}>
                    {drug.generic_name || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('strength') || 'قدرت دارو'}>
                    {drug.strength || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('form') || 'شکل دارو'}>
                    {drug.form || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('requiresPrescription') || 'نیاز به نسخه'}>
                    {drug.requires_prescription ? t('yes') || 'بله' : t('no') || 'خیر'}
                  </Descriptions.Item>
                  <Descriptions.Item label={t('stock') || 'موجودی'}>
                    {drug.stock} {t('items') || 'عدد'}
                  </Descriptions.Item>
                </Descriptions>
              </TabPane>
            </Tabs>
          </Card>

          {/* داروهای مشابه */}
          {relatedDrugs.length > 0 && (
            <div style={{ marginTop: '24px' }}>
              <Title level={4}>{t('relatedProducts') || 'داروهای مشابه'}</Title>
              <Row gutter={[16, 16]}>
                {relatedDrugs.filter(d => d.id !== drug.id).slice(0, 8).map((item) => (
                  <Col key={item.id} xs={12} sm={8} md={6} lg={4}>
                    <Card
                      hoverable
                      style={{ borderRadius: '12px' }}
                      onClick={() => router.push(`/${locale}/pharmacy/drug/${item.id}`)}
                      cover={
                        <div style={{ 
                          height: 80, 
                          background: '#f5f5f5', 
                          display: 'flex', 
                          alignItems: 'center', 
                          justifyContent: 'center',
                          borderRadius: '12px 12px 0 0',
                        }}>
                          <MedicineBoxOutlined style={{ fontSize: 32, color: '#999' }} />
                        </div>
                      }
                    >
                      <div style={{ minHeight: 60 }}>
                        <Text strong style={{ fontSize: '12px' }}>{item.name}</Text>
                        <div>
                          <Text strong style={{ color: '#2563eb', fontSize: '14px' }}>
                            {item.price?.toLocaleString() || 0} تومان
                          </Text>
                        </div>
                      </div>
                    </Card>
                  </Col>
                ))}
              </Row>
            </div>
          )}
        </div>
      </main>
      <Footer />
    </>
  );
}
