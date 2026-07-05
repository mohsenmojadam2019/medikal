'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Statistic, Button, Space, Avatar, Typography, 
  Tabs, List, Tag, Badge, Skeleton, message, Divider, Progress,
  Descriptions, Empty, Spin
} from 'antd';
import { 
  UserOutlined, EditOutlined, LockOutlined, 
  CalendarOutlined, MedicineBoxOutlined, 
  WalletOutlined, FileTextOutlined,
  CreditCardOutlined, CameraOutlined,
  CheckCircleOutlined, ClockCircleOutlined,
  CloseCircleOutlined
} from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import Breadcrumb from '@/components/shared/Breadcrumb';
import { useLanguage } from '@/lib/context/LanguageContext';

const { Title, Text } = Typography;

export default function ProfilePage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [appointments, setAppointments] = useState([]);
  const [prescriptions, setPrescriptions] = useState([]);
  const [transactions, setTransactions] = useState([]);
  const [medicalRecords, setMedicalRecords] = useState([]);
  const [walletBalance, setWalletBalance] = useState(0);
  const [stats, setStats] = useState({});
  const [activeTab, setActiveTab] = useState('overview');
  const [avatarUrl, setAvatarUrl] = useState(null);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('token');
  };

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }
  }, [router]);

  const fetchUserData = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setUser(data.data.user);
        localStorage.setItem('user', JSON.stringify(data.data.user));
      }
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const fetchAvatar = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/profile/avatar`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      
      if (data.success && data.data) {
        const url = data.data.avatar_url || data.data.avatar_original_url;
        if (url) {
          setAvatarUrl(url);
        }
      }
    } catch (error) {
      console.error('Error fetching avatar:', error);
    }
  };

  const fetchAppointments = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/appointments/my/appointments`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setAppointments(Array.isArray(data.data) ? data.data : []);
      }
    } catch (error) {
      console.error('Error fetching appointments:', error);
      setAppointments([]);
    }
  };

  const fetchPrescriptions = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/prescriptions/my`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setPrescriptions(Array.isArray(data.data) ? data.data : []);
      }
    } catch (error) {
      console.error('Error fetching prescriptions:', error);
      setPrescriptions([]);
    }
  };

  const fetchTransactions = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/wallet/transactions`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setTransactions(Array.isArray(data.data) ? data.data : []);
      }
    } catch (error) {
      console.error('Error fetching transactions:', error);
      setTransactions([]);
    }
  };

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
        setWalletBalance(data.data?.balance || 0);
      }
    } catch (error) {
      console.error('Error fetching wallet balance:', error);
    }
  };

  const fetchMedicalRecords = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/ehr/records`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setMedicalRecords(Array.isArray(data.data) ? data.data : []);
      }
    } catch (error) {
      console.error('Error fetching medical records:', error);
      setMedicalRecords([]);
    }
  };

  const fetchStats = async () => {
    const token = getToken();
    if (!token) return;

    try {
      const res = await fetch(`${API_URL}/api/dashboard/patient`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setStats(data.data || {});
      }
    } catch (error) {
      console.error('Error fetching stats:', error);
      setStats({});
    }
  };

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

    const loadData = async () => {
      setLoading(true);
      await Promise.all([
        fetchUserData(),
        fetchAvatar(),
        fetchAppointments(),
        fetchPrescriptions(),
        fetchTransactions(),
        fetchWalletBalance(),
        fetchMedicalRecords(),
        fetchStats(),
      ]);
      setLoading(false);
    };

    loadData();
  }, [router]);

  const getAppointmentStatus = (status) => {
    const statusMap = {
      confirmed: { color: 'success', icon: <CheckCircleOutlined />, label: t('profile.statusConfirmed') },
      pending: { color: 'warning', icon: <ClockCircleOutlined />, label: t('profile.statusPending') },
      completed: { color: 'blue', icon: <CheckCircleOutlined />, label: t('profile.statusCompleted') },
      cancelled: { color: 'error', icon: <CloseCircleOutlined />, label: t('profile.statusCancelled') },
      in_progress: { color: 'processing', icon: <ClockCircleOutlined />, label: t('profile.statusInProgress') },
      arrived: { color: 'success', icon: <CheckCircleOutlined />, label: t('profile.statusArrived') },
      no_show: { color: 'error', icon: <CloseCircleOutlined />, label: t('profile.statusNoShow') },
    };
    return statusMap[status] || statusMap.pending;
  };

  const getPrescriptionStatus = (status) => {
    const statusMap = {
      active: { color: 'success', label: t('profile.statusConfirmed') },
      completed: { color: 'blue', label: t('profile.statusCompleted') },
      pending: { color: 'warning', label: t('profile.statusPending') },
      cancelled: { color: 'error', label: t('profile.statusCancelled') },
      expired: { color: 'error', label: 'منقضی شده' },
    };
    return statusMap[status] || statusMap.pending;
  };

  const getTransactionStatus = (status) => {
    const statusMap = {
      success: { color: 'success', label: t('profile.statusSuccess') },
      pending: { color: 'warning', label: t('profile.statusPending') },
      failed: { color: 'error', label: t('profile.statusFailed') },
      refunded: { color: 'blue', label: t('profile.statusRefunded') },
    };
    return statusMap[status] || statusMap.pending;
  };

  if (loading) {
    return (
      <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Spin size="large" />
        <p style={{ marginTop: '16px' }}>در حال بارگذاری اطلاعات...</p>
      </div>
    );
  }

  if (!user) {
    return (
      <div style={{ maxWidth: '800px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Title level={4}>کاربری یافت نشد</Title>
        <Button type="primary" onClick={() => router.push('/login')}>
          ورود به حساب
        </Button>
      </div>
    );
  }

  const safeTransactions = Array.isArray(transactions) ? transactions : [];
  const paymentTransactions = safeTransactions.filter(t => t.type === 'payment' || t.type === 'withdraw');

  const tabItems = [
    {
      key: 'overview',
      label: t('profile.overview'),
      children: (
        <Row gutter={[16, 16]}>
          <Col xs={24} lg={12}>
            <Card title={t('profile.personalInfo')} size="small">
              <Descriptions column={1}>
                <Descriptions.Item label={t('profile.name')}>{user.name || user.full_name || 'ثبت نشده'}</Descriptions.Item>
                <Descriptions.Item label={t('profile.mobile')}>{user.mobile || 'ثبت نشده'}</Descriptions.Item>
                <Descriptions.Item label={t('profile.email')}>{user.email || 'ثبت نشده'}</Descriptions.Item>
                <Descriptions.Item label={t('profile.status')}>
                  <Tag color={user.is_active ? 'success' : 'error'}>
                    {user.is_active ? t('profile.active') : t('profile.inactive')}
                  </Tag>
                </Descriptions.Item>
                <Descriptions.Item label={t('profile.memberSince')}>
                  {user.created_at ? new Date(user.created_at).toLocaleDateString('fa-IR') : 'ثبت نشده'}
                </Descriptions.Item>
              </Descriptions>
            </Card>
          </Col>
          
          <Col xs={24} lg={12}>
            <Card title={t('profile.quickStats')} size="small">
              <Progress 
                percent={user.profile_completion || 0} 
                status="active" 
                format={() => `${user.profile_completion || 0}٪ ${t('profile.profileCompletion')}`} 
              />
              <Divider />
              <Row gutter={[8, 8]}>
                <Col span={12}>
                  <Statistic title={t('profile.completedAppointments')} value={stats.completed_appointments || 0} />
                </Col>
                <Col span={12}>
                  <Statistic title={t('profile.pendingAppointments')} value={stats.pending_appointments || 0} />
                </Col>
                <Col span={12}>
                  <Statistic title={t('profile.activePrescriptions')} value={stats.active_prescriptions || 0} />
                </Col>
                <Col span={12}>
                  <Statistic title={t('profile.totalPayments')} value={stats.total_payments || 0} suffix="تومان" />
                </Col>
              </Row>
            </Card>
          </Col>
        </Row>
      )
    },
    {
      key: 'appointments',
      label: <><CalendarOutlined /> {t('profile.appointmentsList')} ({appointments.length})</>,
      children: (
        <>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <Text strong>{t('profile.appointmentsList')}</Text>
            <Link href="/profile/appointments">
              <Button type="primary" size="small">{t('profile.viewAll')}</Button>
            </Link>
          </div>
          
          {appointments.length > 0 ? (
            <List
              dataSource={appointments.slice(0, 5)}
              renderItem={(item) => {
                const status = getAppointmentStatus(item.status);
                return (
                  <List.Item
                    actions={[
                      <Tag key="status" color={status.color}>
                        {status.icon} {status.label}
                      </Tag>
                    ]}
                  >
                    <List.Item.Meta
                      title={<Text strong>{item.doctor?.full_name || item.doctor_name || 'پزشک'}</Text>}
                      description={
                        <>
                          <Text>{item.doctor?.specialty?.name || item.specialty || 'تخصص'}</Text>
                          <br />
                          <Text type="secondary">{item.date} - {item.time}</Text>
                          <br />
                          <Text type="secondary">هزینه: {(item.fee || 0).toLocaleString()} تومان</Text>
                        </>
                      }
                    />
                  </List.Item>
                );
              }}
            />
          ) : (
            <Empty description={t('profile.noAppointments')} />
          )}
        </>
      )
    },
    {
      key: 'prescriptions',
      label: <><MedicineBoxOutlined /> {t('profile.prescriptionsList')} ({prescriptions.length})</>,
      children: (
        <>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <Text strong>{t('profile.prescriptionsList')}</Text>
            <Link href="/profile/prescriptions">
              <Button type="primary" size="small">{t('profile.viewAll')}</Button>
            </Link>
          </div>
          
          {prescriptions.length > 0 ? (
            <List
              dataSource={prescriptions.slice(0, 5)}
              renderItem={(item) => {
                const status = getPrescriptionStatus(item.status);
                return (
                  <List.Item>
                    <List.Item.Meta
                      title={<Text strong>{item.doctor?.full_name || item.doctor_name || 'پزشک'}</Text>}
                      description={
                        <>
                          <Text>{item.medicines?.join(' - ') || item.medicines_text || 'داروها'}</Text>
                          <br />
                          <Text type="secondary">تاریخ: {item.date}</Text>
                          <br />
                          <Tag color={status.color}>{status.label}</Tag>
                        </>
                      }
                    />
                  </List.Item>
                );
              }}
            />
          ) : (
            <Empty description={t('profile.noPrescriptions')} />
          )}
        </>
      )
    },
    {
      key: 'wallet',
      label: <><WalletOutlined /> {t('profile.walletTitle')}</>,
      children: (
        <>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <Text strong>{t('profile.walletTitle')}</Text>
            <Link href="/profile/wallet">
              <Button type="primary" size="small">{t('profile.viewAll')}</Button>
            </Link>
          </div>
          
          <Card size="small" style={{ marginBottom: '16px', background: '#f0f5ff' }}>
            <Row gutter={[16, 16]} align="middle">
              <Col>
                <WalletOutlined style={{ fontSize: '32px', color: '#2563eb' }} />
              </Col>
              <Col flex="auto">
                <Text type="secondary">{t('profile.currentBalance')}</Text>
                <div>
                  <Text strong style={{ fontSize: '24px', color: '#2563eb' }}>
                    {walletBalance.toLocaleString()}
                  </Text>
                  <Text> تومان</Text>
                </div>
              </Col>
              <Col>
                <Button type="primary">{t('wallet.deposit')}</Button>
              </Col>
            </Row>
          </Card>

          {safeTransactions.length > 0 ? (
            <List
              dataSource={safeTransactions.slice(0, 5)}
              renderItem={(item) => {
                const status = getTransactionStatus(item.status);
                return (
                  <List.Item>
                    <List.Item.Meta
                      title={
                        <Space>
                          <Text>{item.description}</Text>
                          <Tag color={status.color}>{status.label}</Tag>
                        </Space>
                      }
                      description={
                        <>
                          <Text type="secondary">{item.date}</Text>
                          <br />
                          <Text style={{ color: item.type === 'deposit' || item.type === 'credit' ? '#10b981' : '#ef4444' }}>
                            {item.type === 'deposit' || item.type === 'credit' ? '+' : '-'} {item.amount.toLocaleString()} تومان
                          </Text>
                        </>
                      }
                    />
                  </List.Item>
                );
              }}
            />
          ) : (
            <Empty description={t('profile.noTransactions')} />
          )}
        </>
      )
    },
    {
      key: 'records',
      label: <><FileTextOutlined /> {t('profile.medicalRecords')} ({medicalRecords.length})</>,
      children: (
        <>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <Text strong>{t('profile.medicalRecords')}</Text>
            <Link href="/profile/medical-records">
              <Button type="primary" size="small">{t('profile.viewAll')}</Button>
            </Link>
          </div>
          
          {medicalRecords.length > 0 ? (
            <List
              dataSource={medicalRecords.slice(0, 5)}
              renderItem={(item) => (
                <List.Item>
                  <List.Item.Meta
                    title={<Text strong>{item.type || item.title || 'پرونده'}</Text>}
                    description={
                      <>
                        <Text>نتیجه: {item.result || item.description || 'ثبت نشده'}</Text>
                        <br />
                        <Text type="secondary">پزشک: {item.doctor?.full_name || item.doctor_name || 'نامشخص'}</Text>
                        <br />
                        <Text type="secondary">تاریخ: {item.date}</Text>
                      </>
                    }
                  />
                </List.Item>
              )}
            />
          ) : (
            <Empty description={t('profile.noRecords')} />
          )}
        </>
      )
    },
    {
      key: 'payments',
      label: <><CreditCardOutlined /> {t('profile.paymentsReport')}</>,
      children: (
        <>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <Text strong>{t('profile.paymentsReport')}</Text>
            <Link href="/profile/payments">
              <Button type="primary" size="small">{t('profile.viewAll')}</Button>
            </Link>
          </div>
          
          {paymentTransactions.length > 0 ? (
            <List
              dataSource={paymentTransactions.slice(0, 5)}
              renderItem={(item) => {
                const status = getTransactionStatus(item.status);
                return (
                  <List.Item>
                    <List.Item.Meta
                      title={
                        <Space>
                          <Text>{item.description}</Text>
                          <Tag color={status.color}>{status.label}</Tag>
                        </Space>
                      }
                      description={
                        <>
                          <Text type="secondary">{item.date}</Text>
                          <br />
                          <Text style={{ color: '#ef4444' }}>
                            - {item.amount.toLocaleString()} تومان
                          </Text>
                        </>
                      }
                    />
                  </List.Item>
                );
              }}
            />
          ) : (
            <Empty description={t('profile.noPayments')} />
          )}
        </>
      )
    }
  ];

  return (
    <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
      <Breadcrumb />
      
      <Card style={{ borderRadius: '16px', marginBottom: '24px' }}>
        <Row gutter={[24, 24]} align="middle">
          <Col xs={24} sm={6} style={{ textAlign: 'center' }}>
            <Badge
              count={
                <Link href="/profile/upload-avatar">
                  <CameraOutlined style={{ 
                    background: '#2563eb', 
                    color: 'white', 
                    borderRadius: '50%', 
                    padding: '4px',
                    cursor: 'pointer',
                    fontSize: '14px'
                  }} />
                </Link>
              }
              offset={[-10, 80]}
            >
              <Avatar
                size={100}
                src={avatarUrl || user?.avatar_url}
                icon={<UserOutlined style={{ fontSize: '40px' }} />}
                style={{ 
                  background: (!avatarUrl && !user?.avatar_url) ? 'linear-gradient(135deg, #2563eb, #7c3aed)' : 'none',
                  boxShadow: '0 4px 16px rgba(37,99,235,0.3)'
                }}
              />
            </Badge>
            <div style={{ marginTop: '12px' }}>
              <Title level={4} style={{ margin: 0 }}>
                {user.name || user.full_name || user.mobile || 'کاربر گرامی'}
              </Title>
              <Text type="secondary">{user.mobile || 'شماره موبایل ثبت نشده'}</Text>
            </div>
          </Col>
          
          <Col xs={24} sm={18}>
            <Row gutter={[16, 16]}>
              <Col xs={12} sm={6}>
                <Link href="/profile/appointments">
                  <Statistic 
                    title={t('profile.appointmentsList')} 
                    value={stats.total_appointments || appointments.length || 0} 
                    prefix={<CalendarOutlined />}
                    style={{ cursor: 'pointer' }}
                  />
                </Link>
              </Col>
              <Col xs={12} sm={6}>
                <Link href="/profile/prescriptions">
                  <Statistic 
                    title={t('profile.prescriptionsList')} 
                    value={stats.total_prescriptions || prescriptions.length || 0} 
                    prefix={<MedicineBoxOutlined />}
                    style={{ cursor: 'pointer' }}
                  />
                </Link>
              </Col>
              <Col xs={12} sm={6}>
                <Link href="/profile/wallet">
                  <Statistic 
                    title={t('profile.walletTitle')} 
                    value={walletBalance.toLocaleString()} 
                    prefix={<WalletOutlined />}
                    suffix="تومان"
                    style={{ cursor: 'pointer' }}
                  />
                </Link>
              </Col>
              <Col xs={12} sm={6}>
                <Link href="/profile/medical-records">
                  <Statistic 
                    title={t('profile.medicalRecords')} 
                    value={stats.total_records || medicalRecords.length || 0} 
                    prefix={<FileTextOutlined />}
                    style={{ cursor: 'pointer' }}
                  />
                </Link>
              </Col>
            </Row>
          </Col>
        </Row>
        
        <Divider />
        
        <Row gutter={[16, 16]}>
          <Col>
            <Space wrap>
              <Link href="/profile/edit">
                <Button icon={<EditOutlined />}>{t('profile.edit')}</Button>
              </Link>
              <Link href="/profile/change-password">
                <Button icon={<LockOutlined />}>{t('profile.changePassword')}</Button>
              </Link>
              <Link href="/profile/upload-avatar">
                <Button icon={<CameraOutlined />}>{t('profile.changeAvatar')}</Button>
              </Link>
            </Space>
          </Col>
        </Row>
      </Card>

      <Card style={{ borderRadius: '16px' }}>
        <Tabs 
          activeKey={activeTab} 
          onChange={setActiveTab}
          items={tabItems}
        />
      </Card>
    </div>
  );
}
