'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card, Row, Col, Typography, Spin, Tag, Button,
  Space, Divider, Avatar, Descriptions, App,
  Statistic, Tabs, Result, Empty
} from 'antd';
import {
  UserOutlined, PhoneOutlined, MailOutlined,
  HomeOutlined, IdcardOutlined, EditOutlined,
  CalendarOutlined, CheckCircleOutlined,
  WalletOutlined, ShoppingCartOutlined,
  ReloadOutlined
} from '@ant-design/icons';
import Breadcrumb from '@/components/shared/Breadcrumb';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text } = Typography;
const { TabPane } = Tabs;

export default function ProfilePage() {
  const router = useRouter();
  const { message: appMessage } = App.useApp();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [user, setUser] = useState(null);
  const [patient, setPatient] = useState(null);
  const [stats, setStats] = useState({
    appointments: 0,
    orders: 0,
    wallet: 0,
  });

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push('/fa/login');
      return;
    }
    fetchAllData();
  }, []);

  const fetchAllData = async () => {
    setLoading(true);
    await fetchProfile();
    await fetchStats();
    setLoading(false);
  };

  const refreshData = async () => {
    setRefreshing(true);
    await fetchProfile();
    await fetchStats();
    setRefreshing(false);
    appMessage.success('اطلاعات به‌روزرسانی شد');
  };

  const fetchProfile = async () => {
    try {
      const token = getToken();
      if (!token) return;

      // ۱. دریافت اطلاعات کاربر
      const userRes = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const userData = await userRes.json();
      console.log('📱 User data:', userData);

      if (userData.success && userData.data) {
        setUser(userData.data);
      }

      // ۲. دریافت اطلاعات بیمار
      try {
        const patientRes = await fetch(`${API_URL}/api/patients/me`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        });
        const patientData = await patientRes.json();
        console.log('🏥 Patient data:', patientData);

        if (patientData.success && patientData.data) {
          setPatient(patientData.data);
        } else {
          // اگر بیمار وجود نداشت، یک رکورد خالی بذار
          setPatient({
            national_code: '',
            address: '',
            insurance_type: '',
            insurance_number: '',
          });
        }
      } catch (patientError) {
        console.log('❌ Patient not found:', patientError);
        setPatient({
          national_code: '',
          address: '',
          insurance_type: '',
          insurance_number: '',
        });
      }

    } catch (error) {
      console.error('❌ Error fetching profile:', error);
      appMessage.error('خطا در دریافت اطلاعات');
    }
  };

  const fetchStats = async () => {
    try {
      const token = getToken();
      if (!token) return;

      // آمار نوبت‌ها
      try {
        const appRes = await fetch(`${API_URL}/api/appointments/my/stats`, {
          headers: { 'Authorization': `Bearer ${token}` },
        });
        const appData = await appRes.json();
        if (appData.success) {
          setStats(prev => ({ ...prev, appointments: appData.data?.total || 0 }));
        }
      } catch (e) {
        console.log('Error fetching appointment stats:', e);
      }

      // موجودی کیف پول
      try {
        const walletRes = await fetch(`${API_URL}/api/wallet/balance`, {
          headers: { 'Authorization': `Bearer ${token}` },
        });
        const walletData = await walletRes.json();
        if (walletData.success) {
          setStats(prev => ({ ...prev, wallet: walletData.data?.balance || 0 }));
        }
      } catch (e) {
        console.log('Error fetching wallet balance:', e);
      }

    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  const getInsuranceLabel = (type) => {
    const map = {
      'tamin_ejtemaei': 'تامین اجتماعی',
      'tamin_tekamili': 'بیمه تکمیلی',
      'asal': 'بیمه آسایش',
      'iran': 'بیمه ایران',
      'dana': 'بیمه دانا',
      'saman': 'بیمه سامان',
      'other': 'سایر',
    };
    return map[type] || type || 'ندارد';
  };

  const isProfileComplete = () => {
    if (!user) return false;
    const hasName = user?.name && user.name.length > 0;
    const hasMobile = user?.mobile && user.mobile.length > 0;
    const hasNationalCode = patient?.national_code && patient.national_code.length > 0;
    const hasAddress = patient?.address && patient.address.length > 0;
    return hasName && hasMobile && hasNationalCode && hasAddress;
  };

  if (loading) {
    return (
        <>
          <Header />
          <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '70vh' }}>
            <Spin size="large" />
          </div>
          <Footer />
        </>
    );
  }

  if (!user) {
    return (
        <>
          <Header />
          <div style={{ padding: '60px 20px', maxWidth: '600px', margin: '0 auto' }}>
            <Result
                status="error"
                title="خطا در دریافت اطلاعات"
                subTitle="لطفاً مجدداً وارد شوید"
                extra={
                  <Button type="primary" onClick={() => {
                    localStorage.removeItem('token');
                    router.push('/fa/login');
                  }}>
                    ورود مجدد
                  </Button>
                }
            />
          </div>
          <Footer />
        </>
    );
  }

  return (
      <>
        <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px', minHeight: 'calc(100vh - 200px)' }}>
          <Breadcrumb />

          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px', flexWrap: 'wrap', gap: '12px' }}>
            <Title level={2} style={{ marginBottom: 0 }}>
              👤 پروفایل کاربری
            </Title>
            <Space>
              <Button
                  icon={<ReloadOutlined />}
                  onClick={refreshData}
                  loading={refreshing}
              >
                به‌روزرسانی
              </Button>
              <Button
                  type="primary"
                  icon={<EditOutlined />}
                  onClick={() => router.push('/fa/profile/edit')}
                  size="large"
              >
                ویرایش اطلاعات
              </Button>
            </Space>
          </div>

          <Row gutter={[24, 24]}>
            {/* ستون سمت چپ */}
            <Col xs={24} lg={8}>
              <Card style={{ borderRadius: '16px', textAlign: 'center' }}>
                <Avatar
                    size={100}
                    src={user?.avatar_url}
                    style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}
                    icon={<UserOutlined />}
                />
                <Title level={3} style={{ marginTop: '12px', marginBottom: '4px' }}>
                  {user?.name || 'کاربر'}
                </Title>
                <Text type="secondary">{user?.mobile || 'شماره موبایل ثبت نشده'}</Text>
                <div style={{ marginTop: '8px' }}>
                  {isProfileComplete() ? (
                      <Tag color="green" icon={<CheckCircleOutlined />}>
                        اطلاعات کامل ✓
                      </Tag>
                  ) : (
                      <Tag color="orange" icon={<EditOutlined />}>
                        اطلاعات ناقص
                      </Tag>
                  )}
                </div>
                {!isProfileComplete() && (
                    <Button
                        type="link"
                        onClick={() => router.push('/fa/profile/edit')}
                        style={{ marginTop: '8px' }}
                    >
                      تکمیل اطلاعات
                    </Button>
                )}
              </Card>

              <Card style={{ borderRadius: '16px', marginTop: '16px' }}>
                <Statistic
                    title="موجودی کیف پول"
                    value={stats.wallet}
                    prefix={<WalletOutlined />}
                    suffix="تومان"
                    valueStyle={{ color: '#2563eb' }}
                />
                <Button
                    type="primary"
                    block
                    style={{ marginTop: '12px' }}
                    onClick={() => router.push('/fa/wallet')}
                >
                  شارژ کیف پول
                </Button>
              </Card>
            </Col>

            {/* ستون سمت راست */}
            <Col xs={24} lg={16}>
              <Card style={{ borderRadius: '16px' }}>
                <Descriptions
                    bordered
                    column={1}
                    labelStyle={{ fontWeight: 'bold', width: '150px' }}
                >
                  <Descriptions.Item label="نام و نام خانوادگی">
                    {user?.name || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label="شماره موبایل">
                    {user?.mobile || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label="ایمیل">
                    {user?.email || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label="کد ملی">
                    {patient?.national_code || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label="آدرس">
                    {patient?.address || '—'}
                  </Descriptions.Item>
                  <Descriptions.Item label="نوع بیمه">
                    {getInsuranceLabel(patient?.insurance_type)}
                  </Descriptions.Item>
                  <Descriptions.Item label="شماره بیمه">
                    {patient?.insurance_number || '—'}
                  </Descriptions.Item>
                </Descriptions>

                <Divider />

                <Row gutter={[16, 16]}>
                  <Col xs={12} sm={6}>
                    <Statistic
                        title="نوبت‌ها"
                        value={stats.appointments}
                        prefix={<CalendarOutlined />}
                    />
                  </Col>
                  <Col xs={12} sm={6}>
                    <Statistic
                        title="سفارشات داروخانه"
                        value={stats.orders}
                        prefix={<ShoppingCartOutlined />}
                    />
                  </Col>
                </Row>
              </Card>

              <Card style={{ borderRadius: '16px', marginTop: '16px' }}>
                <Tabs defaultActiveKey="appointments">
                  <TabPane tab="نوبت‌های من" key="appointments">
                    <Space wrap>
                      <Button
                          type="primary"
                          onClick={() => router.push('/fa/appointments/new')}
                      >
                        رزرو نوبت جدید
                      </Button>
                      <Button
                          onClick={() => router.push('/fa/profile/appointments')}
                      >
                        مشاهده همه نوبت‌ها
                      </Button>
                    </Space>
                  </TabPane>
                  <TabPane tab="سفارشات داروخانه" key="orders">
                    <Space wrap>
                      <Button
                          type="primary"
                          onClick={() => router.push('/fa/pharmacy')}
                      >
                        خرید دارو
                      </Button>
                      <Button
                          onClick={() => router.push('/fa/profile/pharmacy-orders')}
                      >
                        مشاهده سفارشات
                      </Button>
                    </Space>
                  </TabPane>
                </Tabs>
              </Card>
            </Col>
          </Row>
        </div>
      </>
  );
}