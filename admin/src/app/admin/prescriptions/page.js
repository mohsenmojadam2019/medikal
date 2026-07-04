// src/app/admin/prescriptions/page.js

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
  Tag,
  Modal,
  Popconfirm,
  Tooltip,
  Row,
  Col,
  Badge,
  Select,
  Tabs,
  App,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  FileTextOutlined,
} from '@ant-design/icons';
import { prescriptionsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function PrescriptionsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp(); // ✅ استفاده از App.useApp()

  const [loading, setLoading] = useState(false);
  const [prescriptions, setPrescriptions] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedPrescription, setSelectedPrescription] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');

  // ===== دریافت لیست نسخه‌ها =====
  const fetchPrescriptions = async (params = {}) => {
    setLoading(true);
    try {
      const response = await prescriptionsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });

      // ✅ بررسی ساختار پاسخ
      if (response.data?.success) {
        const data = response.data.data;
        const list = data?.data || data || [];
        setPrescriptions(Array.isArray(list) ? list : []);
        setPagination({
          ...pagination,
          total: data?.total || (Array.isArray(list) ? list.length : 0),
          current: data?.current_page || 1,
        });
      } else {
        setPrescriptions([]);
        setPagination({
          ...pagination,
          total: 0,
        });
      }
    } catch (error) {
      console.error('Error fetching prescriptions:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      setPrescriptions([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPrescriptions();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchPrescriptions({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchPrescriptions({ page: 1 });
  };

  const handleStatusChange = async (id, status) => {
    try {
      await prescriptionsService.changeStatus(id, status);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchPrescriptions();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await prescriptionsService.delete(id);
      message.success(t('deleted', 'نسخه با موفقیت حذف شد'));
      fetchPrescriptions();
    } catch (error) {
      message.error(t('error', 'خطا در حذف نسخه'));
    }
  };

  const handleView = (record) => {
    setSelectedPrescription(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/prescriptions/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/prescriptions/create');
  };

  // ===== وضعیت‌های نسخه =====
  const statusMap = {
    pending: { color: 'orange', label: 'در انتظار' },
    active: { color: 'blue', label: 'فعال' },
    completed: { color: 'green', label: 'تکمیل شده' },
    cancelled: { color: 'red', label: 'لغو شده' },
    expired: { color: 'default', label: 'منقضی شده' },
  };

  const statusOptions = [
    { value: 'pending', label: 'در انتظار' },
    { value: 'active', label: 'فعال' },
    { value: 'completed', label: 'تکمیل شده' },
    { value: 'cancelled', label: 'لغو شده' },
    { value: 'expired', label: 'منقضی شده' },
  ];

  const columns = [
    {
      title: t('code', 'کد نسخه'),
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
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const s = statusMap[status] || { color: 'default', label: status };
        return <Badge color={s.color} text={s.label} />;
      },
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 250,
      render: (_, record) => (
          <Space size="small" wrap>
            <Tooltip title={t('view', 'مشاهده')}>
              <Button
                  type="text"
                  icon={<EyeOutlined />}
                  onClick={() => handleView(record)}
                  size="small"
              />
            </Tooltip>
            <Tooltip title={t('edit', 'ویرایش')}>
              <Button
                  type="text"
                  icon={<EditOutlined />}
                  onClick={() => handleEdit(record)}
                  size="small"
              />
            </Tooltip>
            <Select
                placeholder={t('change_status', 'تغییر وضعیت')}
                size="small"
                style={{ width: 120 }}
                onChange={(value) => handleStatusChange(record.id, value)}
                value={record.status}
                options={statusOptions}
            />
            <Popconfirm
                title={t('delete_confirm', 'آیا از حذف این نسخه اطمینان دارید؟')}
                onConfirm={() => handleDelete(record.id)}
                okText={t('yes', 'بله')}
                cancelText={t('no', 'خیر')}
            >
              <Tooltip title={t('delete', 'حذف')}>
                <Button type="text" icon={<DeleteOutlined />} size="small" danger />
              </Tooltip>
            </Popconfirm>
          </Space>
      ),
    },
  ];

  const tabItems = [
    { key: 'all', label: t('all', 'همه') },
    { key: 'pending', label: t('pending', 'در انتظار') },
    { key: 'active', label: t('active', 'فعال') },
    { key: 'completed', label: t('completed', 'تکمیل شده') },
    { key: 'cancelled', label: t('cancelled', 'لغو شده') },
  ];

  // اگر prescriptions آرایه نیست
  if (!Array.isArray(prescriptions)) {
    console.error('⚠️ Prescriptions is not an array:', prescriptions);
    return (
        <div style={{ padding: 24 }}>
          <Title level={4}>خطا در نمایش داده‌ها</Title>
          <Text type="danger">داده‌های دریافتی معتبر نیستند.</Text>
        </div>
    );
  }

  return (
      <div>
        <div
            style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              marginBottom: 24,
            }}
        >
          <div>
            <Title level={2} style={{ margin: 0 }}>
              {t('prescriptions_management', 'مدیریت نسخه‌ها')}
            </Title>
            <Text type="secondary">
              {t('prescriptions_subtitle', 'لیست و مدیریت نسخه‌های پزشکی')}
            </Text>
          </div>
          <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={handleCreate}
              style={{
                height: 40,
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
          >
            {t('new_prescription', 'نسخه جدید')}
          </Button>
        </div>

        <Card
            style={{
              marginBottom: 16,
              borderRadius: 12,
              borderColor: '#e8e8f0',
            }}
        >
          <Tabs
              activeKey={activeTab}
              onChange={setActiveTab}
              items={tabItems.map((item) => ({
                key: item.key,
                label: item.label,
              }))}
          />

          <Row gutter={[16, 16]} align="middle" style={{ marginTop: 16 }}>
            <Col xs={24} sm={12} md={8} lg={6}>
              <Input
                  placeholder={t('search_prescription', 'جستجوی نسخه...')}
                  prefix={<SearchOutlined />}
                  value={searchText}
                  onChange={(e) => setSearchText(e.target.value)}
                  onPressEnter={handleSearch}
                  allowClear
              />
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <Select
                  placeholder={t('filter_doctor', 'فیلتر پزشک')}
                  style={{ width: '100%' }}
                  allowClear
                  onChange={(value) => setFilters({ ...filters, doctor_id: value })}
              >
                <Select.Option value="1">دکتر علی محمدی</Select.Option>
                <Select.Option value="2">دکتر سارا محمدی</Select.Option>
              </Select>
            </Col>
            <Col xs={24} sm={24} md={24} lg={6}>
              <Space>
                <Button type="primary" onClick={handleSearch} icon={<SearchOutlined />}>
                  {t('search', 'جستجو')}
                </Button>
                <Button onClick={handleReset} icon={<ReloadOutlined />}>
                  {t('reset', 'ریست')}
                </Button>
                <Button icon={<ExportOutlined />}>{t('export', 'خروجی')}</Button>
              </Space>
            </Col>
          </Row>
        </Card>

        <Card
            style={{
              borderRadius: 12,
              borderColor: '#e8e8f0',
            }}
        >
          <Table
              columns={columns}
              dataSource={prescriptions}
              loading={loading}
              rowKey="id"
              pagination={{
                current: pagination.current,
                pageSize: pagination.pageSize,
                total: pagination.total,
                showSizeChanger: true,
                showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'نسخه')}`,
                onChange: (page, pageSize) => {
                  setPagination({ ...pagination, current: page, pageSize });
                },
              }}
              scroll={{ x: 1200 }}
              locale={{
                emptyText: t('no_prescriptions', 'هیچ نسخه‌ای یافت نشد'),
              }}
          />
        </Card>

        <Modal
            title={t('prescription_details', 'جزئیات نسخه')}
            open={isModalVisible}
            onCancel={() => setIsModalVisible(false)}
            footer={[
              <Button key="close" onClick={() => setIsModalVisible(false)}>
                {t('close', 'بستن')}
              </Button>,
              <Button
                  key="edit"
                  type="primary"
                  onClick={() => {
                    setIsModalVisible(false);
                    if (selectedPrescription) {
                      router.push(`/admin/prescriptions/${selectedPrescription.id}/edit`);
                    }
                  }}
              >
                {t('edit', 'ویرایش')}
              </Button>,
            ]}
            width={600}
        >
          {selectedPrescription && (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 18, fontWeight: 700 }}>
                      <FileTextOutlined style={{ marginRight: 8 }} />
                      {selectedPrescription.code}
                    </div>
                    <div style={{ color: '#64748b' }}>
                      {t('status', 'وضعیت')}:{' '}
                      <Badge
                          color={statusMap[selectedPrescription.status]?.color || 'default'}
                          text={statusMap[selectedPrescription.status]?.label || selectedPrescription.status}
                      />
                    </div>
                  </div>
                </div>

                <Row gutter={[16, 16]}>
                  <Col span={12}>
                    <Text type="secondary">{t('patient', 'بیمار')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedPrescription.patient?.full_name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('doctor', 'پزشک')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedPrescription.doctor?.full_name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('date', 'تاریخ')}</Text>
                    <div style={{ fontWeight: 500 }}>
                      {selectedPrescription.created_at ? dayjs(selectedPrescription.created_at).format('jYYYY/jMM/jDD') : '—'}
                    </div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('valid_until', 'اعتبار تا')}</Text>
                    <div style={{ fontWeight: 500 }}>
                      {selectedPrescription.valid_until ? dayjs(selectedPrescription.valid_until).format('jYYYY/jMM/jDD') : '—'}
                    </div>
                  </Col>
                  <Col span={24}>
                    <Text type="secondary">{t('description', 'توضیحات')}</Text>
                    <div style={{ fontWeight: 500, whiteSpace: 'pre-wrap' }}>
                      {selectedPrescription.description || '—'}
                    </div>
                  </Col>
                </Row>
              </div>
          )}
        </Modal>
      </div>
  );
}