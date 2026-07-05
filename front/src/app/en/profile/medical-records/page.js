'use client';

import { useState, useEffect } from 'react';
import { Card, Table, Tag, Space, Typography, Spin, message, Button } from 'antd';
import { ArrowLeftOutlined, FileTextOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

const { Title, Text } = Typography;

export default function MedicalRecordsPage() {
  const router = useRouter();
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchRecords = async () => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      const res = await fetch(`${API_URL}/api/ehr/records`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setRecords(data.data || []);
      } else {
        message.error(data.message || 'خطا در دریافت پرونده‌ها');
      }
    } catch (error) {
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRecords();
  }, []);

  const columns = [
    {
      title: 'نوع',
      dataIndex: 'type',
      key: 'type',
      render: (type) => type || 'ثبت نشده',
    },
    {
      title: 'عنوان',
      dataIndex: 'title',
      key: 'title',
      render: (title) => title || 'بدون عنوان',
    },
    {
      title: 'نتیجه',
      dataIndex: 'result',
      key: 'result',
      render: (result) => (
        <Tag color={result === 'نرمال' || result === 'normal' ? 'success' : 'warning'}>
          {result || 'ثبت نشده'}
        </Tag>
      ),
    },
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
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Button type="link" size="small" onClick={() => message.info('جزئیات پرونده')}>
          مشاهده
        </Button>
      ),
    },
  ];

  if (loading) {
    return (
      <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Spin size="large" />
        <p style={{ marginTop: '16px' }}>در حال بارگذاری پرونده‌ها...</p>
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
            <span>پرونده پزشکی ({records.length})</span>
          </Space>
        }
        style={{ borderRadius: '16px' }}
      >
        <Table
          dataSource={records}
          columns={columns}
          rowKey="id"
          pagination={{ pageSize: 10 }}
          locale={{ emptyText: 'هیچ پرونده پزشکی ثبت نشده است' }}
        />
      </Card>
    </div>
  );
}
