'use client';

import { useState, useEffect } from 'react';
import { Card, Table, Tag, Statistic, Row, Col, Button, Space, Typography, Spin, message } from 'antd';
import { ArrowLeftOutlined, WalletOutlined, PlusOutlined } from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

const { Title, Text } = Typography;

export default function WalletPage() {
  const router = useRouter();
  const [transactions, setTransactions] = useState([]);
  const [balance, setBalance] = useState(0);
  const [loading, setLoading] = useState(true);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchData = async () => {
    const token = getToken();
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      // دریافت موجودی
      const balanceRes = await fetch(`${API_URL}/api/wallet/balance`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const balanceData = await balanceRes.json();
      if (balanceData.success) {
        setBalance(balanceData.data.balance || 0);
      }

      // دریافت تراکنش‌ها
      const transRes = await fetch(`${API_URL}/api/wallet/transactions`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const transData = await transRes.json();
      if (transData.success) {
        setTransactions(transData.data || []);
      } else {
        message.error(transData.message || 'خطا در دریافت تراکنش‌ها');
      }
    } catch (error) {
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const getStatus = (status) => {
    const map = {
      success: { color: 'success', label: 'موفق' },
      pending: { color: 'warning', label: 'در انتظار' },
      failed: { color: 'error', label: 'ناموفق' },
      refunded: { color: 'blue', label: 'عودت داده شده' },
    };
    return map[status] || map.pending;
  };

  const getType = (type) => {
    const map = {
      deposit: 'شارژ',
      payment: 'پرداخت',
      withdraw: 'برداشت',
      credit: 'شارژ',
      debit: 'برداشت',
      refund: 'عودت',
    };
    return map[type] || type;
  };

  const columns = [
    {
      title: 'توضیحات',
      dataIndex: 'description',
      key: 'description',
    },
    {
      title: 'نوع',
      dataIndex: 'type',
      key: 'type',
      render: (type) => getType(type),
    },
    {
      title: 'مبلغ',
      dataIndex: 'amount',
      key: 'amount',
      render: (amount, record) => (
        <span style={{ 
          color: (record.type === 'deposit' || record.type === 'credit') ? '#10b981' : '#ef4444' 
        }}>
          {(record.type === 'deposit' || record.type === 'credit') ? '+' : '-'} {amount.toLocaleString()} تومان
        </span>
      ),
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
        <p style={{ marginTop: '16px' }}>در حال بارگذاری اطلاعات کیف پول...</p>
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
            <span>کیف پول و تراکنش‌ها</span>
          </Space>
        }
        style={{ borderRadius: '16px' }}
      >
        <Card size="small" style={{ marginBottom: '24px', background: '#f0f5ff' }}>
          <Row gutter={[16, 16]} align="middle">
            <Col>
              <WalletOutlined style={{ fontSize: '48px', color: '#2563eb' }} />
            </Col>
            <Col flex="auto">
              <Text type="secondary">موجودی فعلی</Text>
              <div>
                <Text strong style={{ fontSize: '28px', color: '#2563eb' }}>
                  {balance.toLocaleString()}
                </Text>
                <Text> تومان</Text>
              </div>
            </Col>
            <Col>
              <Button type="primary" icon={<PlusOutlined />} size="large">
                شارژ کیف پول
              </Button>
            </Col>
          </Row>
        </Card>

        <Row gutter={[16, 16]} style={{ marginBottom: '24px' }}>
          <Col xs={24} sm={8}>
            <Statistic 
              title="مجموع شارژها" 
              value={transactions.filter(t => t.type === 'deposit' || t.type === 'credit').reduce((sum, t) => sum + t.amount, 0)} 
              suffix="تومان"
            />
          </Col>
          <Col xs={24} sm={8}>
            <Statistic 
              title="مجموع پرداخت‌ها" 
              value={transactions.filter(t => t.type === 'payment' || t.type === 'debit').reduce((sum, t) => sum + t.amount, 0)} 
              suffix="تومان"
            />
          </Col>
          <Col xs={24} sm={8}>
            <Statistic 
              title="تعداد تراکنش‌ها" 
              value={transactions.length} 
            />
          </Col>
        </Row>

        <Table
          dataSource={transactions}
          columns={columns}
          rowKey="id"
          pagination={{ pageSize: 10 }}
          locale={{ emptyText: 'هیچ تراکنشی ثبت نشده است' }}
        />
      </Card>
    </div>
  );
}
