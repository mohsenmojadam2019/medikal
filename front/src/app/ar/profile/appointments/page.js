'use client';

import { useState, useEffect } from 'react';
import { Card, Table, Tag, Button, Space, Typography, message, Spin } from 'antd';
import { ArrowLeftOutlined, CheckCircleOutlined, ClockCircleOutlined, CloseCircleOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

const { Title, Text } = Typography;

export default function AppointmentsPage() {
  const router = useRouter();
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchAppointments = async () => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

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
      title: 'هزینه',
      dataIndex: 'fee',
      key: 'fee',
      render: (fee) => `${(fee || 0).toLocaleString()} تومان`,
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        (record.status === 'pending' || record.status === 'confirmed') && (
          <Button 
            type="link" 
            danger 
            size="small" 
            onClick={() => handleCancel(record.id)}
          >
            لغو
          </Button>
        )
      ),
    },
  ];

  if (loading) {
    return (
      <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Spin size="large" />
        <p style={{ marginTop: '16px' }}>در حال بارگذاری نوبت‌ها...</p>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
      <Card
        title={
          <Space>
            <Link href="/profile">
              <Button type="text" icon={<ArrowLeftOutlined />} />
            </Link>
            <span>لیست نوبت‌ها ({appointments.length})</span>
          </Space>
        }
        style={{ borderRadius: '16px' }}
      >
        <Table
          dataSource={appointments}
          columns={columns}
          rowKey="id"
          pagination={{ pageSize: 10 }}
          locale={{ emptyText: 'هیچ نوبتی ثبت نشده است' }}
        />
      </Card>
    </div>
  );
}
