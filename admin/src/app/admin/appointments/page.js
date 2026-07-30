'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Table,
  Button,
  Input,
  Space,
  Card,
  Typography,
  Badge,
  Modal,
  Popconfirm,
  Tooltip,
  Row,
  Col,
  Select,
  Tabs,
  message,
  Tag,
  Empty,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
} from '@ant-design/icons';
import { appointmentsService } from '@/services/api';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function AppointmentsPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [appointments, setAppointments] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [activeTab, setActiveTab] = useState('all');

  const fetchAppointments = async (params = {}) => {
    setLoading(true);
    try {
      const requestParams = {
        page: pagination.current,
        per_page: pagination.pageSize,
        ...filters,
        ...params,
      };

      if (searchText.trim()) {
        requestParams.search = searchText.trim();
      }

      if (activeTab !== 'all') {
        requestParams.status = activeTab;
      }

      const response = await appointmentsService.getAll(requestParams);

      if (response.data?.success) {
        const data = response.data.data;
        if (data?.data && Array.isArray(data.data)) {
          setAppointments(data.data);
          setPagination({
            current: data.current_page || 1,
            pageSize: data.per_page || 10,
            total: data.total || 0,
          });
        } else if (Array.isArray(data)) {
          setAppointments(data);
          setPagination({
            ...pagination,
            total: data.length,
          });
        } else {
          setAppointments([]);
          setPagination({
            ...pagination,
            total: 0,
          });
        }
      } else {
        setAppointments([]);
        setPagination({
          ...pagination,
          total: 0,
        });
      }
    } catch (error) {
      console.error('❌ Error:', error);
      setAppointments([]);
      setPagination({
        ...pagination,
        total: 0,
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAppointments();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchAppointments({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchAppointments({ page: 1 });
  };

  const handleDelete = async (id) => {
    try {
      await appointmentsService.delete(id);
      message.success('نوبت با موفقیت حذف شد');
      fetchAppointments();
    } catch (error) {
      message.error('خطا در حذف نوبت');
    }
  };

  const statusMap = {
    pending: { color: 'orange', text: 'در انتظار' },
    confirmed: { color: 'blue', text: 'تایید شده' },
    arrived: { color: 'purple', text: 'حاضر' },
    in_progress: { color: 'cyan', text: 'در حال ویزیت' },
    completed: { color: 'green', text: 'انجام شده' },
    cancelled: { color: 'red', text: 'لغو شده' },
    no_show: { color: 'default', text: 'حاضر نشده' },
  };

  const columns = [
    {
      title: 'کد نوبت',
      dataIndex: 'code',
      key: 'code',
      render: (text) => <Tag color="blue">{text}</Tag>
    },
    {
      title: 'بیمار',
      dataIndex: 'patient',
      key: 'patient',
      render: (p) => p?.full_name || '—'
    },
    {
      title: 'پزشک',
      dataIndex: 'doctor',
      key: 'doctor',
      render: (d) => d?.full_name || '—'
    },
    {
      title: 'تاریخ',
      dataIndex: 'date',
      key: 'date',
      render: (d) => d ? dayjs(d).format('jYYYY/jMM/jDD') : '—'
    },
    {
      title: 'ساعت',
      dataIndex: 'start_time',
      key: 'start_time'
    },
    {
      title: 'وضعیت',
      dataIndex: 'status',
      key: 'status',
      render: (s) => {
        const item = statusMap[s] || { color: 'default', text: s };
        return <Badge color={item.color} text={item.text} />;
      },
    },
    {
      title: 'عملیات',
      key: 'actions',
      width: 200,
      render: (_, record) => (
          <Space size="small">
            <Tooltip title="مشاهده">
              <Button icon={<EyeOutlined />} size="small" />
            </Tooltip>
            <Tooltip title="ویرایش">
              <Button icon={<EditOutlined />} size="small" onClick={() => router.push(`/admin/appointments/${record.id}/edit`)} />
            </Tooltip>
            <Popconfirm title="آیا از حذف این نوبت اطمینان دارید؟" onConfirm={() => handleDelete(record.id)} okText="بله" cancelText="خیر">
              <Tooltip title="حذف">
                <Button icon={<DeleteOutlined />} size="small" danger />
              </Tooltip>
            </Popconfirm>
          </Space>
      ),
    },
  ];

  const tabItems = [
    { key: 'all', label: 'همه' },
    { key: 'pending', label: 'در انتظار' },
    { key: 'confirmed', label: 'تایید شده' },
    { key: 'completed', label: 'انجام شده' },
    { key: 'cancelled', label: 'لغو شده' },
  ];

  return (
      <div style={{ padding: 24 }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 24 }}>
          <div>
            <Title level={2} style={{ margin: 0 }}>مدیریت نوبت‌ها</Title>
            <Text type="secondary">لیست و مدیریت نوبت‌های کلینیک</Text>
          </div>
          <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => router.push('/admin/appointments/create')}
              style={{ height: 40 }}
          >
            نوبت جدید
          </Button>
        </div>

        <Card>
          <Tabs activeKey={activeTab} onChange={setActiveTab} items={tabItems} />

          <Row gutter={16} style={{ marginTop: 16 }}>
            <Col xs={24} sm={12} md={8}>
              <Input
                  placeholder="جستجوی نوبت..."
                  prefix={<SearchOutlined />}
                  value={searchText}
                  onChange={(e) => setSearchText(e.target.value)}
                  onPressEnter={handleSearch}
                  allowClear
              />
            </Col>
            <Col xs={24} sm={12} md={6}>
              <Select
                  placeholder="فیلتر پزشک"
                  style={{ width: '100%' }}
                  allowClear
                  onChange={(val) => setFilters({ ...filters, doctor_id: val })}
              />
            </Col>
            <Col xs={24} sm={24} md={10}>
              <Space>
                <Button type="primary" onClick={handleSearch} icon={<SearchOutlined />}>
                  جستجو
                </Button>
                <Button onClick={handleReset} icon={<ReloadOutlined />}>
                  ریست
                </Button>
              </Space>
            </Col>
          </Row>

          <Table
              columns={columns}
              dataSource={appointments}
              loading={loading}
              rowKey="id"
              pagination={{
                ...pagination,
                showSizeChanger: true,
                showTotal: (total) => `مجموع ${total} نوبت`,
                onChange: (page, pageSize) => setPagination({ ...pagination, current: page, pageSize }),
              }}
              scroll={{ x: 1000 }}
              locale={{
                emptyText: <Empty description="هیچ نوبتی یافت نشد" />
              }}
              style={{ marginTop: 16 }}
          />
        </Card>
      </div>
  );
}
