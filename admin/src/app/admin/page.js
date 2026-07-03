'use client';

import { useState, useEffect } from 'react';
import { Row, Col, Card, Table, Badge, Button, Space } from 'antd';
import {
  UserOutlined,
  TeamOutlined,
  CalendarOutlined,
  CreditCardOutlined,
  FileTextOutlined,
  WalletOutlined,
  EyeOutlined,
  PlusOutlined,
} from '@ant-design/icons';
import StatsCard from '@/components/admin/common/StatsCard';
import Loading from '@/components/admin/common/Loading';
import { dashboardService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';
import jalali from 'dayjs-jalali';

dayjs.extend(jalali);

export default function AdminDashboard() {
  const [stats, setStats] = useState(null);
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const { t } = useLanguage();

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [statsRes, appointmentsRes] = await Promise.all([
          dashboardService.getStats(),
          dashboardService.getRecentActivities(),
        ]);

        setStats(statsRes.data);
        setAppointments(appointmentsRes.data || []);
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading) {
    return <Loading text={t('loading_dashboard', 'در حال بارگذاری داشبورد...')} />;
  }

  const statsData = [
    {
      title: t('active_doctors', 'پزشکان فعال'),
      value: stats?.doctors_count || 0,
      icon: <UserOutlined />,
      iconColor: 'blue',
      change: stats?.doctors_change || '۱۲٪',
      changeType: 'up',
      progress: 75,
    },
    {
      title: t('registered_patients', 'بیماران ثبت‌شده'),
      value: stats?.patients_count || 0,
      icon: <TeamOutlined />,
      iconColor: 'green',
      change: stats?.patients_change || '۸٪',
      changeType: 'up',
      progress: 60,
    },
    {
      title: t('today_appointments', 'نوبت‌های امروز'),
      value: stats?.today_appointments || 0,
      icon: <CalendarOutlined />,
      iconColor: 'yellow',
      change: stats?.appointments_change || '۳٪',
      changeType: 'down',
      progress: 45,
    },
    {
      title: t('monthly_revenue', 'درآمد ماه جاری'),
      value: stats?.monthly_revenue || '۰',
      icon: <CreditCardOutlined />,
      iconColor: 'red',
      change: stats?.revenue_change || '۲۱٪',
      changeType: 'up',
      progress: 80,
    },
    {
      title: t('today_prescriptions', 'نسخه‌های امروز'),
      value: stats?.today_prescriptions || 0,
      icon: <FileTextOutlined />,
      iconColor: 'purple',
      change: stats?.prescriptions_change || '۵٪',
      changeType: 'up',
      progress: 55,
    },
    {
      title: t('wallet_balance', 'کیف پول کاربران'),
      value: stats?.wallet_balance || '۰',
      icon: <WalletOutlined />,
      iconColor: 'cyan',
      change: stats?.wallet_change || '۱۵٪',
      changeType: 'up',
      progress: 40,
    },
  ];

  const columns = [
    {
      title: t('code', 'کد نوبت'),
      dataIndex: 'code',
      key: 'code',
      render: (text) => <span style={{ fontWeight: 700 }}>{text}</span>,
    },
    {
      title: t('patient', 'بیمار'),
      dataIndex: 'patient',
      key: 'patient',
      render: (patient) => patient?.full_name || '—',
    },
    {
      title: t('doctor', 'پزشک'),
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => doctor?.full_name || '—',
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'date',
      key: 'date',
      render: (text) => text ? dayjs(text).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('time', 'ساعت'),
      dataIndex: 'start_time',
      key: 'start_time',
      render: (time) => time || '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const statusMap = {
          confirmed: { color: 'blue', text: 'تایید شده' },
          pending: { color: 'orange', text: 'در انتظار' },
          completed: { color: 'green', text: 'انجام شده' },
          cancelled: { color: 'red', text: 'لغو شده' },
          arrived: { color: 'purple', text: 'حاضر' },
        };
        const s = statusMap[status] || { color: 'default', text: status };
        return <Badge color={s.color} text={s.text} />;
      },
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      render: () => (
        <Space>
          <Button type="text" icon={<EyeOutlined />} size="small" />
        </Space>
      ),
    },
  ];

  return (
    <div>
      <Row gutter={[16, 16]}>
        {statsData.map((stat, index) => (
          <Col xs={24} sm={12} lg={8} xl={8} xxl={8} key={index}>
            <StatsCard {...stat} />
          </Col>
        ))}
      </Row>

      <Card
        style={{
          marginTop: 16,
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
        title={
          <span>
            <CalendarOutlined style={{ color: '#2563eb', marginLeft: 8 }} />
            {t('recent_appointments', 'آخرین نوبت‌ها')}
          </span>
        }
        extra={
          <Space>
            <Button type="default" icon={<EyeOutlined />}>
              {t('view_all', 'مشاهده همه')}
            </Button>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              style={{
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
            >
              {t('new_appointment', 'نوبت جدید')}
            </Button>
          </Space>
        }
      >
        <Table
          columns={columns}
          dataSource={appointments}
          rowKey="id"
          pagination={false}
          locale={{
            emptyText: t('no_appointments', 'هیچ نوبتی یافت نشد'),
          }}
        />
      </Card>

      <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
        <Col xs={24} lg={12}>
          <Card
            style={{
              borderRadius: 12,
              borderColor: '#e8e8f0',
            }}
            title={
              <span>
                <UserOutlined style={{ color: '#f59e0b', marginLeft: 8 }} />
                {t('top_doctors', 'پزشکان برتر')}
              </span>
            }
          >
            <Space direction="vertical" style={{ width: '100%' }}>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '8px 12px',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 20 }}>🥇</span>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600 }}>دکتر علی محمدی</div>
                  <div style={{ fontSize: 12, color: '#64748b' }}>داخلی • ۱۵۰ نوبت</div>
                </div>
                <span style={{ color: '#f59e0b' }}>⭐ ۴.۹</span>
              </div>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '8px 12px',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 20 }}>🥈</span>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600 }}>دکتر سارا محمدی</div>
                  <div style={{ fontSize: 12, color: '#64748b' }}>قلب و عروق • ۱۲۰ نوبت</div>
                </div>
                <span style={{ color: '#f59e0b' }}>⭐ ۴.۷</span>
              </div>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '8px 12px',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 20 }}>🥉</span>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600 }}>دکتر علی رضایی</div>
                  <div style={{ fontSize: 12, color: '#64748b' }}>ارتوپدی • ۹۸ نوبت</div>
                </div>
                <span style={{ color: '#f59e0b' }}>⭐ ۴.۵</span>
              </div>
            </Space>
          </Card>
        </Col>

        <Col xs={24} lg={12}>
          <Card
            style={{
              borderRadius: 12,
              borderColor: '#e8e8f0',
            }}
            title={
              <span>
                <CalendarOutlined style={{ color: '#2563eb', marginLeft: 8 }} />
                {t('today_appointments_list', 'نوبت‌های امروز')}
              </span>
            }
            extra={
              <Button
                type="primary"
                size="small"
                icon={<PlusOutlined />}
                style={{
                  background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                  border: 'none',
                }}
              >
                {t('new', 'جدید')}
              </Button>
            }
          >
            <Space direction="vertical" style={{ width: '100%' }}>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '6px 10px',
                  borderRight: '3px solid #10b981',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>۱۰:۰۰</span>
                <span style={{ fontWeight: 600, flex: 1 }}>رضا کریمی</span>
                <span style={{ fontSize: 12, color: '#64748b' }}>دکتر محمدی</span>
                <Badge color="blue" text="تایید" />
              </div>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '6px 10px',
                  borderRight: '3px solid #f59e0b',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>۱۱:۳۰</span>
                <span style={{ fontWeight: 600, flex: 1 }}>سارا احمدی</span>
                <span style={{ fontSize: 12, color: '#64748b' }}>دکتر محمدی</span>
                <Badge color="orange" text="در انتظار" />
              </div>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '6px 10px',
                  borderRight: '3px solid #2563eb',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>۱۴:۰۰</span>
                <span style={{ fontWeight: 600, flex: 1 }}>محمد رضایی</span>
                <span style={{ fontSize: 12, color: '#64748b' }}>دکتر رضایی</span>
                <Badge color="purple" text="حاضر" />
              </div>
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '6px 10px',
                  borderRight: '3px solid #ef4444',
                  background: '#f8fafc',
                  borderRadius: 8,
                }}
              >
                <span style={{ fontSize: 12, fontWeight: 600, color: '#64748b' }}>۱۶:۰۰</span>
                <span style={{ fontWeight: 600, flex: 1 }}>زهرا حسینی</span>
                <span style={{ fontSize: 12, color: '#64748b' }}>دکتر محمدی</span>
                <Badge color="red" text="لغو" />
              </div>
            </Space>
          </Card>
        </Col>
      </Row>
    </div>
  );
}
