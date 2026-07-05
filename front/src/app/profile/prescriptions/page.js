'use client';

import { useState, useEffect } from 'react';
import { Card, Table, Tag, Space, Typography, Spin, message, Button } from 'antd';
import { ArrowLeftOutlined, MedicineBoxOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

const { Title, Text } = Typography;

export default function PrescriptionsPage() {
  const router = useRouter();
  const [prescriptions, setPrescriptions] = useState([]);
  const [loading, setLoading] = useState(true);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchPrescriptions = async () => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      const res = await fetch(`${API_URL}/api/prescriptions/my`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      
      if (data.success) {
        setPrescriptions(data.data || []);
      } else {
        message.error(data.message || 'خطا در دریافت نسخه‌ها');
      }
    } catch (error) {
      console.error('Error:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPrescriptions();
  }, []);

  const getStatus = (status) => {
    const map = {
      active: { color: 'success', label: 'فعال' },
      completed: { color: 'blue', label: 'تکمیل شده' },
      pending: { color: 'warning', label: 'در انتظار' },
      cancelled: { color: 'error', label: 'لغو شده' },
      expired: { color: 'error', label: 'منقضی شده' },
    };
    return map[status] || map.pending;
  };

  const columns = [
    {
      title: 'پزشک',
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => doctor?.full_name || doctor?.name || 'نامشخص',
    },
    {
      title: 'تاریخ',
      dataIndex: 'date',
      key: 'date',
    },
    {
      title: 'داروها',
      dataIndex: 'medicines',
      key: 'medicines',
      render: (medicines) => {
        if (Array.isArray(medicines)) {
          return medicines.join(' - ');
        }
        return medicines || 'ثبت نشده';
      },
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const s = getStatus(status);
        return <Tag color={s.color}>{s.label}</Tag>;
      },
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Button 
          type="link" 
          size="small" 
          onClick={() => message.info(`جزئیات نسخه ${record.id}`)}
        >
          مشاهده
        </Button>
      ),
    },
  ];

  if (loading) {
    return (
      <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Spin size="large" />
        <p style={{ marginTop: '16px' }}>در حال بارگذاری نسخه‌ها...</p>
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
            <span>لیست نسخه‌های دارویی ({prescriptions.length})</span>
          </Space>
        }
        style={{ borderRadius: '16px' }}
      >
        <Table
          dataSource={prescriptions}
          columns={columns}
          rowKey="id"
          pagination={{ pageSize: 10 }}
          locale={{ emptyText: 'هیچ نسخه‌ای ثبت نشده است' }}
        />
      </Card>
    </div>
  );
}
