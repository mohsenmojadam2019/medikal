
'use client';

import { useState, useEffect } from 'react';
import { Card, Typography, Spin, Empty, Tag, Button, Tabs, Table, App, Space } from 'antd';
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
  const { message: appMessage } = App.useApp();
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
        let appointmentsData = [];
        if (Array.isArray(data.data)) {
          appointmentsData = data.data;
        } else if (data.data && Array.isArray(data.data.data)) {
          appointmentsData = data.data.data;
        } else {
          appointmentsData = [];
        }
        setAppointments(appointmentsData);
      } else {
        appMessage.error(data.message || 'خطا در دریافت نوبت‌ها');
      }
    } catch (error) {
      console.error('Error fetching appointments:', error);
      appMessage.error('خطا در ارتباط با سرور');
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
        appMessage.success('نوبت با موفقیت لغو شد');
        fetchAppointments();
      } else {
        appMessage.error(data.message || 'خطا در لغو نوبت');
      }
    } catch (error) {
      appMessage.error('خطا در ارتباط با سرور');
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

  const appointmentsList = Array.isArray(appointments) ? appointments : [];

  const upcomingAppointments = appointmentsList.filter(a =>
      a.status && ['pending', 'confirmed', 'arrived', 'in_progress'].includes(a.status)
  );
  const pastAppointments = appointmentsList.filter(a =>
      a.status && ['completed', 'cancelled', 'no_show'].includes(a.status)
  );

  const columns = [
    {
      title: 'پزشک',
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => {
        if (!doctor) return '—';
        return doctor.full_name || doctor.name || '—';
      },
    },
    {
      title: 'تخصص',
      dataIndex: 'doctor',
      key: 'specialty',
      render: (doctor) => {
        if (!doctor || !doctor.specialty) return '—';
        return doctor.specialty.name || '—';
      },
    },
    {
      title: 'تاریخ',
      dataIndex: 'date',
      key: 'date',
      render: (date) => {
        if (!date) return '—';
        try {
          return new Date(date).toLocaleDateString('fa-IR');
        } catch {
          return date;
        }
      },
    },
    {
      title: 'ساعت',
      dataIndex: 'start_time',
      key: 'time',
      render: (time) => {
        if (!time) return '—';
        return time.substring(0, 5);
      },
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        if (!status) return '—';
        const s = getStatus(status);
        return <Tag color={s.color}>{s.icon} {s.label}</Tag>;
      },
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
          <Space>
            {record.status && (record.status === 'pending' || record.status === 'confirmed') && (
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
                <Title level={2}>📅 نوبت‌ها</Title>
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
                <TabPane
                    tab={`نوبت‌های پیش رو (${upcomingAppointments.length})`}
                    key="upcoming"
                >
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
                <TabPane
                    tab={`نوبت‌های گذشته (${pastAppointments.length})`}
                    key="past"
                >
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
