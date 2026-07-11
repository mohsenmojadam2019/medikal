'use client';

import { useState, useEffect } from 'react';
import {
  Card, Table, Tag, Button, Space, Input, Select,
  Modal, Form, message, Tooltip,
  Row, Col, Statistic, Typography
} from 'antd';
import {
  SearchOutlined, ReloadOutlined, EyeOutlined,
  CheckCircleOutlined, CloseCircleOutlined,
  ClockCircleOutlined, ExperimentOutlined,
  DollarOutlined, CalendarOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/context/LanguageContext';
import { labService } from '@/services/api';
import dayjs from 'dayjs';
import 'dayjs/locale/fa';

dayjs.locale('fa');

const { Title, Text } = Typography;
const { Option } = Select;

export default function AdminLabOrders() {
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [orders, setOrders] = useState([]);
  const [searchText, setSearchText] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [modalVisible, setModalVisible] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [form] = Form.useForm();

  const statusMap = {
    pending: { color: 'warning', label: 'در انتظار' },
    waiting_payment: { color: 'gold', label: 'در انتظار پرداخت' },
    paid: { color: 'cyan', label: 'پرداخت شده' },
    scheduled: { color: 'blue', label: 'نوبت‌دهی شده' },
    sample_collected: { color: 'purple', label: 'نمونه گرفته شده' },
    processing: { color: 'processing', label: 'در حال پردازش' },
    partial: { color: 'orange', label: 'تکمیل بخشی' },
    completed: { color: 'success', label: 'تکمیل شده' },
    cancelled: { color: 'error', label: 'لغو شده' },
    rejected: { color: 'error', label: 'رد شده' },
  };

  const fetchOrders = async () => {
    setLoading(true);
    try {
      const params = {};
      if (searchText) params.search = searchText;
      if (statusFilter !== 'all') params.status = statusFilter;

      const res = await labService.getOrders(params);
      if (res.data.success) {
        setOrders(res.data.data.data || res.data.data || []);
      }
    } catch (error) {
      console.error('Error fetching orders:', error);
      message.error('خطا در دریافت سفارشات');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
  }, [searchText, statusFilter]);

  const handleStatusChange = async (values) => {
    try {
      await labService.updateOrderStatus(selectedOrder.id, {
        status: values.status,
        reason: values.reason,
      });
      message.success('وضعیت سفارش با موفقیت تغییر کرد');
      setModalVisible(false);
      form.resetFields();
      fetchOrders();
    } catch (error) {
      console.error('Error updating order:', error);
      message.error('خطا در تغییر وضعیت سفارش');
    }
  };

  const columns = [
    {
      title: 'شماره سفارش',
      dataIndex: 'order_number',
      key: 'order_number',
      render: (text) => <Text strong style={{ color: '#2563eb' }}>{text}</Text>,
    },
    {
      title: 'بیمار',
      dataIndex: 'patient',
      key: 'patient',
      render: (patient) => patient?.user?.name || '—',
    },
    {
      title: 'تست‌ها',
      key: 'tests_count',
      render: (_, record) => record.order_tests?.length || 0,
    },
    {
      title: 'مبلغ',
      dataIndex: 'total_price',
      key: 'total_price',
      render: (price) => <Text strong>{price?.toLocaleString()} تومان</Text>,
    },
    {
      title: 'تاریخ',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => dayjs(date).format('jD jMMMM jYYYY - HH:mm'),
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const info = statusMap[status] || statusMap.pending;
        return <Tag color={info.color}>{info.label}</Tag>;
      },
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Space>
          <Tooltip title="مشاهده جزئیات">
            <Button type="primary" ghost size="small" icon={<EyeOutlined />} onClick={() => window.open(`/fa/lab/orders/${record.id}`, '_blank')} />
          </Tooltip>
          <Tooltip title="تغییر وضعیت">
            <Button type="primary" size="small" icon={<CheckCircleOutlined />} onClick={() => {
              setSelectedOrder(record);
              setModalVisible(true);
              form.setFieldsValue({ status: record.status });
            }} />
          </Tooltip>
        </Space>
      ),
    },
  ];

  const statusOptions = [
    { value: 'all', label: 'همه' },
    { value: 'pending', label: 'در انتظار' },
    { value: 'waiting_payment', label: 'در انتظار پرداخت' },
    { value: 'paid', label: 'پرداخت شده' },
    { value: 'scheduled', label: 'نوبت‌دهی شده' },
    { value: 'sample_collected', label: 'نمونه گرفته شده' },
    { value: 'processing', label: 'در حال پردازش' },
    { value: 'partial', label: 'تکمیل بخشی' },
    { value: 'completed', label: 'تکمیل شده' },
    { value: 'cancelled', label: 'لغو شده' },
    { value: 'rejected', label: 'رد شده' },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <Title level={2}>🧪 مدیریت سفارشات آزمایشگاه</Title>
        <Button icon={<ReloadOutlined />} onClick={fetchOrders} loading={loading}>بروزرسانی</Button>
      </div>

      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={12} sm={6}>
          <Card><Statistic title="کل سفارشات" value={orders.length} /></Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card><Statistic title="در انتظار" value={orders.filter(o => ['pending', 'waiting_payment'].includes(o.status)).length} valueStyle={{ color: '#faad14' }} /></Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card><Statistic title="در حال پردازش" value={orders.filter(o => ['scheduled', 'sample_collected', 'processing', 'partial'].includes(o.status)).length} valueStyle={{ color: '#1890ff' }} /></Card>
        </Col>
        <Col xs={12} sm={6}>
          <Card><Statistic title="تکمیل شده" value={orders.filter(o => o.status === 'completed').length} valueStyle={{ color: '#52c41a' }} /></Card>
        </Col>
      </Row>

      <Card style={{ borderRadius: 12 }}>
        <div style={{ display: 'flex', gap: 16, marginBottom: 16, flexWrap: 'wrap' }}>
          <Input placeholder="جستجوی سفارش..." prefix={<SearchOutlined />} value={searchText} onChange={(e) => setSearchText(e.target.value)} style={{ width: 300 }} allowClear />
          <Select placeholder="فیلتر وضعیت" style={{ width: 200 }} value={statusFilter} onChange={setStatusFilter}>
            {statusOptions.map(opt => (<Option key={opt.value} value={opt.value}>{opt.label}</Option>))}
          </Select>
        </div>

        <Table columns={columns} dataSource={orders} rowKey="id" loading={loading} pagination={{ pageSize: 15, showSizeChanger: true }} />
      </Card>

      <Modal title="تغییر وضعیت سفارش" open={modalVisible} onCancel={() => { setModalVisible(false); form.resetFields(); }} footer={null} width={500}>
        <Form form={form} layout="vertical" onFinish={handleStatusChange}>
          <Form.Item name="status" label="وضعیت جدید" rules={[{ required: true }]}>
            <Select placeholder="انتخاب وضعیت">
              {statusOptions.filter(o => o.value !== 'all').map(opt => (<Option key={opt.value} value={opt.value}>{opt.label}</Option>))}
            </Select>
          </Form.Item>
          <Form.Item name="reason" label="دلیل (در صورت لغو یا رد)">
            <Input.TextArea rows={3} placeholder="دلیل تغییر وضعیت..." />
          </Form.Item>
          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">تغییر وضعیت</Button>
              <Button onClick={() => { setModalVisible(false); form.resetFields(); }}>انصراف</Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
