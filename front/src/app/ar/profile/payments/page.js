'use client';

import { useState, useEffect } from 'react';
import { Card, Table, Tag, Statistic, Row, Col, Space, Typography, Spin, message } from 'antd';
import { ArrowLeftOutlined, CreditCardOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

const { Title, Text } = Typography;

export default function PaymentsPage() {
  const router = useRouter();
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    total: 0,
    count: 0,
    successful: 0,
  });
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchPayments = async () => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      const res = await fetch(`${API_URL}/api/invoices/my`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        const paymentData = data.data || [];
        setPayments(paymentData);
        
        // محاسبه آمار
        const total = paymentData.reduce((sum, p) => sum + (p.amount || 0), 0);
        const successful = paymentData.filter(p => p.status === 'paid' || p.status === 'success').length;
        setStats({
          total,
          count: paymentData.length,
          successful,
        });
      } else {
        message.error(data.message || 'خطا در دریافت پرداخت‌ها');
      }
    } catch (error) {
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
  }, []);

  const getStatus = (status) => {
    const map = {
      paid: { color: 'success', label: 'پرداخت شده' },
      success: { color: 'success', label: 'موفق' },
      pending: { color: 'warning', label: 'در انتظار' },
      failed: { color: 'error', label: 'ناموفق' },
      refunded: { color: 'blue', label: 'عودت داده شده' },
      draft: { color: 'default', label: 'پیش‌نویس' },
      issued: { color: 'processing', label: 'صادر شده' },
    };
    return map[status] || map.pending;
  };

  const columns = [
    {
      title: 'شماره فاکتور',
      dataIndex: 'invoice_number',
      key: 'invoice_number',
    },
    {
      title: 'توضیحات',
      dataIndex: 'description',
      key: 'description',
    },
    {
      title: 'مبلغ',
      dataIndex: 'amount',
      key: 'amount',
      render: (amount) => `${(amount || 0).toLocaleString()} تومان`,
    },
    {
      title: 'تاریخ',
      dataIndex: 'date',
      key: 'date',
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
  ];

  if (loading) {
    return (
      <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
        <Spin size="large" />
        <p style={{ marginTop: '16px' }}>در حال بارگذاری پرداخت‌ها...</p>
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
            <span>گزارش پرداخت‌ها</span>
          </Space>
        }
        style={{ borderRadius: '16px' }}
      >
        <Card size="small" style={{ marginBottom: '24px' }}>
          <Row gutter={[16, 16]}>
            <Col xs={24} sm={8}>
              <Statistic 
                title="مجموع پرداخت‌ها" 
                value={stats.total.toLocaleString()} 
                prefix={<CreditCardOutlined />}
                suffix="تومان"
              />
            </Col>
            <Col xs={24} sm={8}>
              <Statistic 
                title="تعداد پرداخت‌ها" 
                value={stats.count} 
              />
            </Col>
            <Col xs={24} sm={8}>
              <Statistic 
                title="پرداخت‌های موفق" 
                value={stats.successful} 
              />
            </Col>
          </Row>
        </Card>

        <Table
          dataSource={payments}
          columns={columns}
          rowKey="id"
          pagination={{ pageSize: 10 }}
          locale={{ emptyText: 'هیچ پرداختی ثبت نشده است' }}
        />
      </Card>
    </div>
  );
}
