// src/app/admin/referrals/page.js

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
  CheckCircleOutlined,
  CloseCircleOutlined,
  ReloadOutlined,
  ExportOutlined,
  SwapOutlined,
} from '@ant-design/icons';
import { referralsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function ReferralsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();

  const [loading, setLoading] = useState(false);
  const [referrals, setReferrals] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedReferral, setSelectedReferral] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');

  // ===== دریافت لیست ارجاعات =====
  const fetchReferrals = async (params = {}) => {
    setLoading(true);
    try {
      const response = await referralsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });

      if (response.data?.success) {
        const data = response.data.data;
        const list = data?.data || data || [];
        setReferrals(Array.isArray(list) ? list : []);
        setPagination({
          ...pagination,
          total: data?.total || (Array.isArray(list) ? list.length : 0),
          current: data?.current_page || 1,
        });
      } else {
        setReferrals([]);
        setPagination({
          ...pagination,
          total: 0,
        });
      }
    } catch (error) {
      console.error('Error fetching referrals:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      setReferrals([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReferrals();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchReferrals({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchReferrals({ page: 1 });
  };

  const handleStatusChange = async (id, status) => {
    try {
      if (status === 'accepted') {
        await referralsService.accept(id);
      } else if (status === 'rejected') {
        await referralsService.reject(id);
      } else if (status === 'completed') {
        await referralsService.complete(id);
      }
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchReferrals();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await referralsService.delete(id);
      message.success(t('deleted', 'ارجاع با موفقیت حذف شد'));
      fetchReferrals();
    } catch (error) {
      message.error(t('error', 'خطا در حذف ارجاع'));
    }
  };

  const handleView = (record) => {
    setSelectedReferral(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/referrals/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/referrals/create');
  };

  // ===== وضعیت‌های ارجاع =====
  const statusMap = {
    pending: { color: 'orange', label: 'در انتظار' },
    accepted: { color: 'blue', label: 'پذیرفته شده' },
    rejected: { color: 'red', label: 'رد شده' },
    completed: { color: 'green', label: 'تکمیل شده' },
  };

  const statusOptions = [
    { value: 'pending', label: 'در انتظار' },
    { value: 'accepted', label: 'پذیرفته شده' },
    { value: 'rejected', label: 'رد شده' },
    { value: 'completed', label: 'تکمیل شده' },
  ];

  const columns = [
    {
      title: t('id', 'شناسه'),
      dataIndex: 'id',
      key: 'id',
      width: 70,
    },
    {
      title: t('patient', 'بیمار'),
      dataIndex: 'patient',
      key: 'patient',
      render: (patient) => patient?.full_name || '—',
    },
    {
      title: t('from_doctor', 'پزشک مبدا'),
      dataIndex: 'from_doctor',
      key: 'from_doctor',
      render: (doctor) => doctor?.full_name || '—',
    },
    {
      title: t('to_doctor', 'پزشک مقصد'),
      dataIndex: 'to_doctor',
      key: 'to_doctor',
      render: (doctor) => doctor?.full_name || '—',
    },
    {
      title: t('reason', 'دلیل ارجاع'),
      dataIndex: 'reason',
      key: 'reason',
      ellipsis: true,
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
      title: t('date', 'تاریخ'),
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 280,
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
                style={{ width: 130 }}
                onChange={(value) => handleStatusChange(record.id, value)}
                value={record.status}
                options={statusOptions}
            />
            <Popconfirm
                title={t('delete_confirm', 'آیا از حذف این ارجاع اطمینان دارید؟')}
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
    { key: 'accepted', label: t('accepted', 'پذیرفته شده') },
    { key: 'rejected', label: t('rejected', 'رد شده') },
    { key: 'completed', label: t('completed', 'تکمیل شده') },
  ];

  // اگر referrals آرایه نیست
  if (!Array.isArray(referrals)) {
    console.error('⚠️ Referrals is not an array:', referrals);
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
              {t('referrals_management', 'مدیریت ارجاعات')}
            </Title>
            <Text type="secondary">
              {t('referrals_subtitle', 'لیست و مدیریت ارجاعات بیماران')}
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
            {t('new_referral', 'ارجاع جدید')}
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
                  placeholder={t('search_referral', 'جستجوی ارجاع...')}
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
                  onChange={(value) => setFilters({ ...filters, to_doctor_id: value })}
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
              dataSource={referrals}
              loading={loading}
              rowKey="id"
              pagination={{
                current: pagination.current,
                pageSize: pagination.pageSize,
                total: pagination.total,
                showSizeChanger: true,
                showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'ارجاع')}`,
                onChange: (page, pageSize) => {
                  setPagination({ ...pagination, current: page, pageSize });
                },
              }}
              scroll={{ x: 1200 }}
              locale={{
                emptyText: t('no_referrals', 'هیچ ارجاعی یافت نشد'),
              }}
          />
        </Card>

        <Modal
            title={t('referral_details', 'جزئیات ارجاع')}
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
                    if (selectedReferral) {
                      router.push(`/admin/referrals/${selectedReferral.id}/edit`);
                    }
                  }}
              >
                {t('edit', 'ویرایش')}
              </Button>,
            ]}
            width={600}
        >
          {selectedReferral && (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
                  <SwapOutlined style={{ fontSize: 32, color: '#2563eb' }} />
                  <div>
                    <div style={{ fontSize: 18, fontWeight: 700 }}>
                      {t('referral', 'ارجاع')} #{selectedReferral.id}
                    </div>
                    <div style={{ color: '#64748b' }}>
                      {t('status', 'وضعیت')}:{' '}
                      <Badge
                          color={statusMap[selectedReferral.status]?.color || 'default'}
                          text={statusMap[selectedReferral.status]?.label || selectedReferral.status}
                      />
                    </div>
                  </div>
                </div>

                <Row gutter={[16, 16]}>
                  <Col span={12}>
                    <Text type="secondary">{t('patient', 'بیمار')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedReferral.patient?.full_name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('from_doctor', 'پزشک مبدا')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedReferral.from_doctor?.full_name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('to_doctor', 'پزشک مقصد')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedReferral.to_doctor?.full_name || '—'}</div>
                  </Col>
                  <Col span={24}>
                    <Text type="secondary">{t('reason', 'دلیل ارجاع')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedReferral.reason || '—'}</div>
                  </Col>
                  <Col span={24}>
                    <Text type="secondary">{t('notes', 'توضیحات')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedReferral.notes || '—'}</div>
                  </Col>
                </Row>
              </div>
          )}
        </Modal>
      </div>
  );
}