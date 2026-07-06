'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card, Row, Col, Typography, Spin, Tag, Button,
  Space, Divider, Avatar, Descriptions, App,
  Statistic, Badge, Tabs
} from 'antd';
import {
  UserOutlined, PhoneOutlined, MailOutlined,
  HomeOutlined, IdcardOutlined, EditOutlined,
  MedicineBoxOutlined, SafetyOutlined,
  CalendarOutlined, CheckCircleOutlined,
  WalletOutlined, ShoppingCartOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { TabPane } = Tabs;

export default function ProfilePage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const { message: appMessage } = App.useApp();
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [stats, setStats] = useState({
    appointments: 0,
    orders: 0,
    wallet: 0,
  });

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => localStorage.getItem('token');

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const token = getToken();
      const res = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setUser(data.data);
        fetchStats(data.data.id);
      } else {
        appMessage.error(data.message || 'خطا در دریافت اطلاعات');
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async (userId) => {
    try {
      const token = getToken();
      // دریافت آمار نوبت‌ها
      const appRes = await fetch(`${API_URL}/api/appointments/my/stats`, {
        headers: { 'Authorization': `Bearer ${token}` },
      });
      const appData = await appRes.json();

      // دریافت موجودی کیف پول
      const walletRes = await fetch(`${API_URL}/api/wallet/balance`, {
        headers: { 'Authorization': `Bearer ${token}` },
      });
      const walletData = await walletRes.json();

      setStats({
        appointments: appData.success ? appData.data?.total || 0 : 0,
        orders: 0,
        wallet: walletData.success ? walletData.data?.balance || 0 : 0,
      });
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
    return !!(user.name && user.mobile && user.national_code && user.address);
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
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb />

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
              <Title level={2} style={{ marginBottom: 0 }}>
                👤 پروفایل کاربری
              </Title>
              <Button
                  type="primary"
                  icon={<EditOutlined />}
                  onClick={() => router.push(`/${locale}/profile/edit`)}
                  size="large"
              >
                ویرایش اطلاعات
              </Button>
            </div>

            <Row gutter={[24, 24]}>
              <Col xs={24} lg={8}>
                <Card style={{ borderRadius: '16px', textAlign: 'center' }}>
                  <Avatar
                      size={100}
                      src={user?.avatar}
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
                          onClick={() => router.push(`/${locale}/profile/edit`)}
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
                      onClick={() => router.push(`/${locale}/wallet`)}
                  >
                    شارژ کیف پول
                  </Button>
                </Card>
              </Col>

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
                      {user?.national_code || '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="آدرس">
                      {user?.address || '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="نوع بیمه">
                      {getInsuranceLabel(user?.insurance_type)}
                    </Descriptions.Item>
                    <Descriptions.Item label="شماره بیمه">
                      {user?.insurance_number || '—'}
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
                      <Button
                          type="primary"
                          onClick={() => router.push(`/${locale}/appointments/new`)}
                      >
                        رزرو نوبت جدید
                      </Button>
                      <Button
                          style={{ marginRight: '8px' }}
                          onClick={() => router.push(`/${locale}/profile/appointments`)}
                      >
                        مشاهده همه نوبت‌ها
                      </Button>
                    </TabPane>
                    <TabPane tab="سفارشات داروخانه" key="orders">
                      <Button
                          type="primary"
                          onClick={() => router.push(`/${locale}/pharmacy`)}
                      >
                        خرید دارو
                      </Button>
                      <Button
                          style={{ marginRight: '8px' }}
                          onClick={() => router.push(`/${locale}/profile/pharmacy-orders`)}
                      >
                        مشاهده سفارشات
                      </Button>
                    </TabPane>
                  </Tabs>
                </Card>
              </Col>
            </Row>
          </div>
        </main>
        <Footer />
      </>
  );
}
