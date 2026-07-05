'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Button, Table, message, Space, Statistic } from 'antd';
import { FileTextOutlined, PlusOutlined, EyeOutlined, DownloadOutlined, DeleteOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function RecordsPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({});
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  const getToken = () => localStorage.getItem('token');

  const fetchRecords = async () => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }

    setLoading(true);
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
        // محاسبه آمار
        const total = data.data?.length || 0;
        const completed = data.data?.filter(r => r.status === 'completed').length || 0;
        const pending = data.data?.filter(r => r.status === 'pending').length || 0;
        setStats({ total, completed, pending });
      } else {
        message.error(data.message || 'خطا در دریافت پرونده‌ها');
      }
    } catch (error) {
      console.error('Error fetching records:', error);
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
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => (
        <Tag color={status === 'completed' ? 'success' : 'warning'}>
          {status === 'completed' ? 'تکمیل شده' : 'در انتظار'}
        </Tag>
      ),
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Space>
          <Button 
            type="link" 
            size="small" 
            icon={<EyeOutlined />}
            onClick={() => message.info(`جزئیات پرونده ${record.id}`)}
          />
          <Button 
            type="link" 
            size="small" 
            icon={<DownloadOutlined />}
            onClick={() => message.info('دانلود پرونده')}
          />
        </Space>
      ),
    },
  ];

  if (loading) {
    return (
      <>
        <Header />
        <LoadingSpinner />
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />
          
          <div style={{ marginBottom: '32px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div>
              <Title level={2}>📋 {t('nav.records')}</Title>
              <Text type="secondary">پرونده الکترونیک سلامت شما</Text>
            </div>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => message.info('افزودن پرونده جدید')}
              size="large"
            >
              پرونده جدید
            </Button>
          </div>

          <Row gutter={[16, 16]} style={{ marginBottom: '24px' }}>
            <Col xs={24} sm={8}>
              <Card>
                <Statistic title="تعداد کل پرونده‌ها" value={stats.total || 0} prefix={<FileTextOutlined />} />
              </Card>
            </Col>
            <Col xs={24} sm={8}>
              <Card>
                <Statistic title="تکمیل شده" value={stats.completed || 0} valueStyle={{ color: '#10b981' }} />
              </Card>
            </Col>
            <Col xs={24} sm={8}>
              <Card>
                <Statistic title="در انتظار" value={stats.pending || 0} valueStyle={{ color: '#f59e0b' }} />
              </Card>
            </Col>
          </Row>

          <Card style={{ borderRadius: '16px' }}>
            {records.length > 0 ? (
              <Table
                dataSource={records}
                columns={columns}
                rowKey="id"
                pagination={{ pageSize: 10 }}
              />
            ) : (
              <Empty description="هیچ پرونده‌ای ثبت نشده است" />
            )}
          </Card>
        </div>
      </main>
      <Footer />
    </>
  );
}
