'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Input, Button, Space, message, Tabs, Table, Modal, Form, Select, DatePicker, Progress } from 'antd';
import { SearchOutlined, PlusOutlined, FileTextOutlined, ClockCircleOutlined, CheckCircleOutlined, CloseCircleOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { Search } = Input;
const { Option } = Select;
const { TabPane } = Tabs;

export default function LabPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [tests, setTests] = useState([]);
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchText, setSearchText] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [modalVisible, setModalVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [activeTab, setActiveTab] = useState('tests');
  const [form] = Form.useForm();
  const [selectedTests, setSelectedTests] = useState([]);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  // دریافت لیست آزمایش‌ها از API (عمومی - بدون لاگین)
  const fetchTests = async () => {
    try {
      const res = await fetch(`${API_URL}/api/lab/tests/active`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setTests(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching tests:', error);
    }
  };

  // دریافت سفارشات کاربر از API (نیاز به لاگین)
  const fetchOrders = async () => {
    try {
      const token = getToken();
      if (!token) return;

      const res = await fetch(`${API_URL}/api/lab/my/orders`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setOrders(data.data || []);
      }
    } catch (error) {
      console.error('Error fetching orders:', error);
    }
  };

  // ✅ بارگذاری اولیه - بدون نیاز به لاگین
  useEffect(() => {
    setLoading(true);
    Promise.all([
      fetchTests(),
      // فقط اگر لاگین هست، سفارشات رو بگیر
      getToken() ? fetchOrders() : Promise.resolve(),
    ]).finally(() => setLoading(false));
  }, []);

  // فیلتر آزمایش‌ها
  const filteredTests = tests.filter(test => {
    const matchSearch = test.name?.toLowerCase().includes(searchText.toLowerCase()) ||
        test.category?.toLowerCase().includes(searchText.toLowerCase());
    const matchCategory = selectedCategory === 'all' || test.category === selectedCategory;
    return matchSearch && matchCategory;
  });

  const getStatusTag = (status) => {
    const map = {
      pending: { color: 'warning', icon: <ClockCircleOutlined />, label: 'در انتظار' },
      processing: { color: 'processing', icon: <ClockCircleOutlined />, label: 'در حال پردازش' },
      completed: { color: 'success', icon: <CheckCircleOutlined />, label: 'تکمیل شده' },
      cancelled: { color: 'error', icon: <CloseCircleOutlined />, label: 'لغو شده' },
    };
    return map[status] || map.pending;
  };

  const getResultStatus = (result) => {
    if (!result) return null;
    const map = {
      normal: { color: 'success', label: 'نرمال' },
      abnormal: { color: 'warning', label: 'غیرطبیعی' },
      critical: { color: 'error', label: 'بحرانی' },
    };
    return map[result] || map.normal;
  };

  const handleSelectTest = (testId) => {
    setSelectedTests(prev =>
        prev.includes(testId)
            ? prev.filter(id => id !== testId)
            : [...prev, testId]
    );
  };

  // ✅ ثبت سفارش آزمایش - نیاز به لاگین
  const handleSubmitOrder = async (values) => {
    if (selectedTests.length === 0) {
      message.warning('لطفاً حداقل یک آزمایش را انتخاب کنید');
      return;
    }

    const token = getToken();
    if (!token) {
      message.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
      router.push(`/${locale}/login?redirect=/${locale}/lab`);
      return;
    }

    setSubmitting(true);

    try {
      const res = await fetch(`${API_URL}/api/lab/orders`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          test_ids: selectedTests,
          notes: values.notes || '',
        }),
      });

      const data = await res.json();
      if (data.success) {
        message.success('✅ سفارش آزمایش با موفقیت ثبت شد');
        setModalVisible(false);
        setSelectedTests([]);
        form.resetFields();
        await fetchOrders();
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
          <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
            <Spin size="large" />
            <p style={{ marginTop: '16px' }}>{t('common.loading')}</p>
          </div>
          <Footer />
        </>
    );
  }

  const orderColumns = [
    {
      title: 'شماره سفارش',
      dataIndex: 'order_number',
      key: 'order_number',
    },
    {
      title: 'تاریخ',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => dayjs(date).format('YYYY/MM/DD HH:mm'),
    },
    {
      title: 'تعداد آزمایش',
      key: 'tests_count',
      render: (_, record) => record.tests?.length || 0,
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const s = getStatusTag(status);
        return <Tag color={s.color}>{s.icon} {s.label}</Tag>;
      },
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
          <Button
              type="link"
              size="small"
              onClick={() => message.info(`جزئیات سفارش ${record.order_number}`)}
          >
            مشاهده
          </Button>
      ),
    },
  ];

  return (
      <>
        <Header />
        <main style={{ minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
            {/* هدر صفحه */}
            <div style={{ marginBottom: '32px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <Title level={2}>🧪 {t('nav.lab')}</Title>
                <Text type="secondary">انجام و پیگیری آزمایش‌های پزشکی</Text>
              </div>
              <Button
                  type="primary"
                  icon={<PlusOutlined />}
                  onClick={() => {
                    const token = getToken();
                    if (!token) {
                      message.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
                      router.push(`/${locale}/login?redirect=/${locale}/lab`);
                      return;
                    }
                    setModalVisible(true);
                  }}
                  size="large"
              >
                سفارش آزمایش جدید
              </Button>
            </div>

            {/* تب‌ها */}
            <Card style={{ borderRadius: '16px' }}>
              <Tabs activeKey={activeTab} onChange={setActiveTab}>
                <TabPane tab="📋 آزمایش‌ها" key="tests">
                  <div style={{ marginBottom: '16px', display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                    <Search
                        placeholder="جستجوی آزمایش..."
                        prefix={<SearchOutlined />}
                        value={searchText}
                        onChange={(e) => setSearchText(e.target.value)}
                        style={{ maxWidth: '400px' }}
                        enterButton
                    />
                    <Select
                        placeholder="دسته‌بندی"
                        style={{ width: '200px' }}
                        value={selectedCategory}
                        onChange={setSelectedCategory}
                    >
                      <Option value="all">همه دسته‌ها</Option>
                    </Select>
                  </div>

                  {filteredTests.length > 0 ? (
                      <Row gutter={[16, 16]}>
                        {filteredTests.map((test) => (
                            <Col xs={24} sm={12} lg={8} key={test.id}>
                              <Card
                                  style={{ borderRadius: '12px', height: '100%' }}
                                  hoverable
                                  onClick={() => {
                                    const token = getToken();
                                    if (!token) {
                                      message.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
                                      router.push(`/${locale}/login?redirect=/${locale}/lab`);
                                      return;
                                    }
                                    handleSelectTest(test.id);
                                  }}
                              >
                                <div style={{ textAlign: 'center', fontSize: '40px', marginBottom: '12px' }}>
                                  🔬
                                </div>
                                <Title level={4} style={{ textAlign: 'center' }}>
                                  {test.name}
                                </Title>
                                <div style={{ textAlign: 'center' }}>
                                  <Tag color="blue">{test.category}</Tag>
                                </div>
                                <div style={{ marginTop: '12px', textAlign: 'center' }}>
                                  <Text type="secondary">{test.description}</Text>
                                </div>
                                <div style={{ marginTop: '12px', textAlign: 'center' }}>
                                  <Text strong style={{ fontSize: '18px', color: '#2563eb' }}>
                                    {test.price?.toLocaleString() || 0}
                                  </Text>
                                  <Text type="secondary"> تومان</Text>
                                </div>
                                {test.requires_doctor && (
                                    <div style={{ textAlign: 'center', marginTop: '4px' }}>
                                      <Tag color="orange">نیاز به تجویز پزشک</Tag>
                                    </div>
                                )}
                                {selectedTests.includes(test.id) && (
                                    <div style={{ textAlign: 'center', marginTop: '8px' }}>
                                      <Tag color="success">انتخاب شده</Tag>
                                    </div>
                                )}
                              </Card>
                            </Col>
                        ))}
                      </Row>
                  ) : (
                      <Empty description="هیچ آزمایشی یافت نشد" />
                  )}
                </TabPane>

                <TabPane tab="📊 سفارشات من" key="orders">
                  {orders.length > 0 ? (
                      <Table
                          dataSource={orders}
                          columns={orderColumns}
                          rowKey="id"
                          pagination={{ pageSize: 10 }}
                      />
                  ) : (
                      <Empty description="هیچ سفارشی ثبت نشده است" />
                  )}
                </TabPane>
              </Tabs>
            </Card>
          </div>
        </main>

        {/* مودال سفارش آزمایش */}
        <Modal
            title="🧪 سفارش آزمایش جدید"
            open={modalVisible}
            onCancel={() => {
              setModalVisible(false);
              setSelectedTests([]);
              form.resetFields();
            }}
            footer={null}
            width={600}
        >
          <Form
              form={form}
              layout="vertical"
              onFinish={handleSubmitOrder}
              size="large"
          >
            <Form.Item
                name="tests"
                label="آزمایش‌های انتخاب شده"
            >
              <div style={{ marginBottom: '8px' }}>
                {selectedTests.length > 0 ? (
                    <Space wrap>
                      {selectedTests.map(id => {
                        const test = tests.find(t => t.id === id);
                        return test ? (
                            <Tag key={id} color="blue" closable onClose={() => handleSelectTest(id)}>
                              {test.name}
                            </Tag>
                        ) : null;
                      })}
                    </Space>
                ) : (
                    <Text type="secondary">هیچ آزمایشی انتخاب نشده است</Text>
                )}
              </div>
              <Text type="secondary" style={{ fontSize: '12px' }}>
                برای انتخاب آزمایش، از لیست آزمایش‌ها انتخاب کنید
              </Text>
            </Form.Item>

            <Form.Item
                name="notes"
                label="توضیحات"
            >
              <Input.TextArea rows={3} placeholder="توضیحات اضافی..." />
            </Form.Item>

            <Form.Item>
              <Button
                  type="primary"
                  htmlType="submit"
                  loading={submitting}
                  block
                  size="large"
              >
                ثبت سفارش
              </Button>
            </Form.Item>
          </Form>
        </Modal>

        <Footer />
      </>
  );
}