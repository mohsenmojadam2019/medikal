'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Button, Tabs, Table, message, Space } from 'antd';
import { CalendarOutlined, CheckCircleOutlined, ClockCircleOutlined, CloseCircleOutlined, PlusOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { TabPane } = Tabs;

export default function AppointmentsPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('upcoming');
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchAppointments = async () => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }

    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/appointments/my/appointments`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setAppointments(data.data || []);
      } else {
        message.error(data.message || 'خطا در دریافت نوبت‌ها');
      }
    } catch (error) {
      console.error('Error fetching appointments:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAppointments();
  }, []);

  const getStatus = (status) => {
    const map = {
      confirmed: { color: 'success', icon: <CheckCircleOutlined />, label: 'تایید شده' },
      pending: { color: 'warning', icon: <ClockCircleOutlined />, label: 'در انتظار' },
      completed: { color: 'blue', icon: <CheckCircleOutlined />, label: 'انجام شده' },
      cancelled: { color: 'error', icon: <CloseCircleOutlined />, label: 'لغو شده' },
      in_progress: { color: 'processing', icon: <ClockCircleOutlined />, label: 'در حال انجام' },
      arrived: { color: 'success', icon: <CheckCircleOutlined />, label: 'حاضر' },
      no_show: { color: 'error', icon: <CloseCircleOutlined />, label: 'حاضر نشده' },
    };
    return map[status] || map.pending;
  };

  const handleCancel = async (id) => {
    const token = getToken();
    try {
      const res = await fetch(`${API_URL}/api/appointments/${id}/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        message.success('نوبت با موفقیت لغو شد');
        fetchAppointments();
      } else {
        message.error(data.message || 'خطا در لغو نوبت');
      }
    } catch (error) {
      message.error('خطا در ارتباط با سرور');
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

  const upcomingAppointments = appointments.filter(a => 
    ['pending', 'confirmed', 'arrived', 'in_progress'].includes(a.status)
  );
  const pastAppointments = appointments.filter(a => 
    ['completed', 'cancelled', 'no_show'].includes(a.status)
  );

  const columns = [
    {
      title: 'پزشک',
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => doctor?.full_name || doctor?.name || 'نامشخص',
    },
    {
      title: 'تخصص',
      dataIndex: 'doctor',
      key: 'specialty',
      render: (doctor) => doctor?.specialty?.name || 'نامشخص',
    },
    {
      title: 'تاریخ',
      dataIndex: 'date',
      key: 'date',
    },
    {
      title: 'ساعت',
      dataIndex: 'time',
      key: 'time',
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const s = getStatus(status);
        return <Tag color={s.color}>{s.icon} {s.label}</Tag>;
      },
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Space>
          {(record.status === 'pending' || record.status === 'confirmed') && (
            <Button 
              type="link" 
              danger 
              size="small" 
              onClick={() => handleCancel(record.id)}
            >
              لغو
            </Button>
          )}
          <Button 
            type="link" 
            size="small" 
            onClick={() => router.push(`/${locale}/doctors/${record.doctor_id}`)}
          >
            مشاهده پزشک
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />
          
          <div style={{ marginBottom: '32px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div>
              <Title level={2}>📅 {t('nav.appointments')}</Title>
              <Text type="secondary">مدیریت نوبت‌های شما</Text>
            </div>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => router.push(`/${locale}/appointments/new`)}
              size="large"
            >
              نوبت جدید
            </Button>
          </div>

          <Card style={{ borderRadius: '16px' }}>
            <Tabs activeKey={activeTab} onChange={setActiveTab}>
              <TabPane tab={`نوبت‌های پیش رو (${upcomingAppointments.length})`} key="upcoming">
                {upcomingAppointments.length > 0 ? (
                  <Table
                    dataSource={upcomingAppointments}
                    columns={columns}
                    rowKey="id"
                    pagination={{ pageSize: 10 }}
                  />
                ) : (
                  <Empty description="هیچ نوبت پیش رویی ندارید" />
                )}
              </TabPane>
              <TabPane tab={`نوبت‌های گذشته (${pastAppointments.length})`} key="past">
                {pastAppointments.length > 0 ? (
                  <Table
                    dataSource={pastAppointments}
                    columns={columns}
                    rowKey="id"
                    pagination={{ pageSize: 10 }}
                  />
                ) : (
                  <Empty description="هیچ نوبت گذشته‌ای ندارید" />
                )}
              </TabPane>
            </Tabs>
          </Card>
        </div>
      </main>
      <Footer />
    </>
  );
}
