'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { 
  Card, Table, Tag, Button, Typography, Space, Spin, 
  Empty, App, Tabs, Statistic, Row, Col, Avatar
} from 'antd';
import { 
  CalendarOutlined, ClockCircleOutlined, 
  CheckCircleOutlined, CloseCircleOutlined,
  DollarOutlined, ReloadOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Breadcrumb from '@/components/shared/Breadcrumb';

const { Title, Text } = Typography;

// ✅ تبدیل تاریخ میلادی به شمسی
function toPersianDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  if (!date || isNaN(date.getTime())) return '';
  try {
    const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      weekday: 'long',
    });
    return formatter.format(date);
  } catch {
    return '';
  }
}

// ✅ فرمت زمان (فقط HH:MM)
function formatTime(timeStr) {
  if (!timeStr) return '';
  if (timeStr.includes('T')) {
    const date = new Date(timeStr);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
  }
  if (timeStr.includes(':')) {
    const parts = timeStr.split(':');
    return parts.length >= 2 ? `${parts[0]}:${parts[1]}` : timeStr;
  }
  return timeStr;
}

export default function AppointmentsPage() {
  const router = useRouter();
  const { locale } = useLanguage();
  const { message: appMessage } = App.useApp();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    total: 0,
    pending: 0,
    confirmed: 0,
    completed: 0,
    cancelled: 0,
  });
  const [activeTab, setActiveTab] = useState('all');
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  const fetchAppointments = async (status = 'all') => {
    setLoading(true);
    try {
      const token = getToken();
      if (!token) {
        router.push(`/${locale}/login`);
        return;
      }

      let url = `${API_URL}/api/appointments/my/appointments`;
      if (status !== 'all') {
        url += `?status=${status}`;
      }

      const res = await fetch(url, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await res.json();
      console.log('📦 Appointments data:', data);

      if (data.success) {
        let appointmentList = [];
        if (data.data && Array.isArray(data.data)) {
          appointmentList = data.data;
        } else if (data.data && data.data.data && Array.isArray(data.data.data)) {
          appointmentList = data.data.data;
        } else if (Array.isArray(data)) {
          appointmentList = data;
        } else {
          appointmentList = [];
        }

        setAppointments(appointmentList);

        const statsData = {
          total: appointmentList.length,
          pending: appointmentList.filter(a => a.status === 'pending').length,
          confirmed: appointmentList.filter(a => a.status === 'confirmed' || a.status === 'arrived' || a.status === 'in_progress').length,
          completed: appointmentList.filter(a => a.status === 'completed').length,
          cancelled: appointmentList.filter(a => a.status === 'cancelled' || a.status === 'no_show').length,
        };
        setStats(statsData);
      } else {
        appMessage.error(data.message || 'خطا در دریافت نوبت‌ها');
        setAppointments([]);
      }
    } catch (error) {
      console.error('Error fetching appointments:', error);
      appMessage.error('خطا در ارتباط با سرور');
      setAppointments([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAppointments('all');
  }, []);

  const handleTabChange = (key) => {
    setActiveTab(key);
    fetchAppointments(key);
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: 'warning',
      confirmed: 'info',
      arrived: 'primary',
      in_progress: 'blue',
      completed: 'success',
      cancelled: 'danger',
      no_show: 'secondary',
    };
    return colors[status] || 'default';
  };

  const getStatusText = (status) => {
    const texts = {
      pending: 'در انتظار تایید',
      confirmed: 'تایید شده',
      arrived: 'حاضر در مطب',
      in_progress: 'در حال ویزیت',
      completed: 'انجام شده',
      cancelled: 'لغو شده',
      no_show: 'حاضر نشده',
    };
    return texts[status] || status;
  };

  const columns = [
    {
      title: 'کد نوبت',
      dataIndex: 'code',
      key: 'code',
      render: (code) => <Text strong>{code || '—'}</Text>,
    },
    {
      title: 'پزشک',
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => {
        if (!doctor) return '—';
        const name = doctor.name || doctor.full_name || 'پزشک';
        const specialty = doctor.specialty?.name || '';
        return (
          <Space>
            <Avatar size="small" style={{ background: '#2563eb' }}>
              {name.charAt(0)}
            </Avatar>
            <div>
              <div>{name}</div>
              {specialty && <Text type="secondary" style={{ fontSize: '12px' }}>{specialty}</Text>}
            </div>
          </Space>
        );
      },
    },
    {
      title: 'تاریخ و ساعت',
      key: 'datetime',
      render: (_, record) => {
        // ✅ فقط تاریخ شمسی و زمان را نمایش بده
        const persianDate = toPersianDate(record.date);
        const time = formatTime(record.start_time);
        return (
          <Space direction="vertical" size={0}>
            <Text>{persianDate}</Text>
            <Text type="secondary" style={{ fontSize: '12px' }}>
              <ClockCircleOutlined /> {time}
            </Text>
          </Space>
        );
      },
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => (
        <Tag color={getStatusColor(status)}>
          {getStatusText(status)}
        </Tag>
      ),
    },
    {
      title: 'هزینه',
      key: 'fee',
      render: (_, record) => {
        const fee = record.fee || record.doctor?.consultation_fee || 0;
        return <Text>{parseFloat(fee).toLocaleString()} تومان</Text>;
      },
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Space>
          <Button 
            type="link" 
            size="small"
            onClick={() => router.push(`/${locale}/appointments/${record.id}`)}
          >
            مشاهده
          </Button>
          {record.status === 'pending' && (
            <Button 
              type="link" 
              size="small" 
              danger
              onClick={() => handleCancel(record.id)}
            >
              لغو
            </Button>
          )}
        </Space>
      ),
    },
  ];

  const handleCancel = async (id) => {
    try {
      const token = getToken();
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
        fetchAppointments(activeTab);
      } else {
        appMessage.error(data.message || 'خطا در لغو نوبت');
      }
    } catch (error) {
      console.error('Error cancelling appointment:', error);
      appMessage.error('خطا در ارتباط با سرور');
    }
  };

  const tabsItems = [
    { key: 'all', label: 'همه' },
    { key: 'pending', label: `در انتظار (${stats.pending})` },
    { key: 'confirmed', label: `تایید شده (${stats.confirmed})` },
    { key: 'completed', label: `انجام شده (${stats.completed})` },
    { key: 'cancelled', label: `لغو شده (${stats.cancelled})` },
  ];

  if (loading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '70vh' }}>
        <Spin size="large" description="در حال بارگذاری..." />
      </div>
    );
  }

  return (
    <>
      <Breadcrumb />

      <Title level={2} style={{ marginBottom: '4px' }}>📋 نوبت‌های من</Title>
      <Text type="secondary">لیست تمام نوبت‌های شما</Text>

      <Row gutter={[16, 16]} style={{ marginTop: '24px' }}>
        <Col xs={12} sm={6}>
          <Card>
            <Statistic 
              title="کل نوبت‌ها" 
              value={stats.total}
              prefix={<CalendarOutlined />}
            />
          </Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card>
            <Statistic 
              title="در انتظار" 
              value={stats.pending}
              valueStyle={{ color: '#faad14' }}
              prefix={<ClockCircleOutlined />}
            />
          </Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card>
            <Statistic 
              title="تایید شده" 
              value={stats.confirmed}
              valueStyle={{ color: '#1890ff' }}
              prefix={<CheckCircleOutlined />}
            />
          </Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card>
            <Statistic 
              title="انجام شده" 
              value={stats.completed}
              valueStyle={{ color: '#52c41a' }}
              prefix={<CheckCircleOutlined />}
            />
          </Card>
        </Col>
      </Row>

      <Card style={{ marginTop: '24px', borderRadius: '16px' }}>
        <Tabs
          activeKey={activeTab}
          onChange={handleTabChange}
          items={tabsItems}
        />

        {appointments.length === 0 ? (
          <Empty 
            description="هیچ نوبتی یافت نشد" 
            image={Empty.PRESENTED_IMAGE_SIMPLE}
          >
            <Button 
              type="primary" 
              onClick={() => router.push(`/${locale}/doctors`)}
            >
              رزرو نوبت جدید
            </Button>
          </Empty>
        ) : (
          <Table
            columns={columns}
            dataSource={appointments}
            rowKey="id"
            pagination={{
              pageSize: 10,
              showTotal: (total) => `تعداد ${total} نوبت`,
            }}
            scroll={{ x: 'max-content' }}
          />
        )}
      </Card>

      <div style={{ marginTop: '24px', textAlign: 'center' }}>
        <Button 
          icon={<ReloadOutlined />} 
          onClick={() => fetchAppointments(activeTab)}
        >
          بروزرسانی
        </Button>
      </div>
    </>
  );
}
