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
  Form,
  Select,
  message,
  Popconfirm,
  Tooltip,
  Row,
  Col,
  Badge,
  Avatar,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  UserOutlined,
  ReloadOutlined,
  ExportOutlined,
} from '@ant-design/icons';
import { doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';

const { Title, Text } = Typography;
const { confirm } = Modal;

export default function DoctorsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [modalMode, setModalMode] = useState('view'); // view | edit

  // ===== دریافت لیست پزشکان =====
  const fetchDoctors = async (params = {}) => {
    setLoading(true);
    try {
      const response = await doctorsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setDoctors(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching doctors:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  // ===== بارگذاری اولیه =====
  useEffect(() => {
    fetchDoctors();
  }, [pagination.current, pagination.pageSize]);

  // ===== جستجو =====
  const handleSearch = () => {
    fetchDoctors({ page: 1 });
  };

  // ===== ریست فیلترها =====
  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchDoctors({ page: 1, search: '', ...filters });
  };

  // ===== تغییر وضعیت پزشک =====
  const handleToggleStatus = async (id) => {
    try {
      await doctorsService.toggleAvailability(id);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchDoctors();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  // ===== تایید پزشک =====
  const handleVerify = async (id) => {
    try {
      await doctorsService.verify(id);
      message.success(t('verified', 'پزشک با موفقیت تایید شد'));
      fetchDoctors();
    } catch (error) {
      message.error(t('error', 'خطا در تایید پزشک'));
    }
  };

  // ===== حذف پزشک =====
  const handleDelete = async (id) => {
    try {
      await doctorsService.delete(id);
      message.success(t('deleted', 'پزشک با موفقیت حذف شد'));
      fetchDoctors();
    } catch (error) {
      message.error(t('error', 'خطا در حذف پزشک'));
    }
  };

  // ===== مشاهده جزئیات =====
  const handleView = (record) => {
    setSelectedDoctor(record);
    setModalMode('view');
    setIsModalVisible(true);
  };

  // ===== ویرایش =====
  const handleEdit = (record) => {
    router.push(`/admin/doctors/${record.id}/edit`);
  };

  // ===== ایجاد پزشک جدید =====
  const handleCreate = () => {
    router.push('/admin/doctors/create');
  };

  // ===== ستون‌های جدول =====
  const columns = [
    {
      title: t('id', 'شناسه'),
      dataIndex: 'id',
      key: 'id',
      width: 70,
    },
    {
      title: t('doctor', 'پزشک'),
      dataIndex: 'full_name',
      key: 'full_name',
      render: (text, record) => (
        <Space>
          <Avatar
            src={record.profile_image}
            icon={<UserOutlined />}
            style={{ backgroundColor: '#2563eb' }}
          />
          <div>
            <div style={{ fontWeight: 600 }}>{text}</div>
            <div style={{ fontSize: 12, color: '#64748b' }}>
              {record.specialty?.name || t('no_specialty', 'بدون تخصص')}
            </div>
          </div>
        </Space>
      ),
    },
    {
      title: t('license', 'شماره نظام'),
      dataIndex: 'license_number',
      key: 'license_number',
    },
    {
      title: t('mobile', 'موبایل'),
      dataIndex: 'user',
      key: 'mobile',
      render: (user) => user?.mobile || '—',
    },
    {
      title: t('fee', 'هزینه ویزیت'),
      dataIndex: 'consultation_fee',
      key: 'consultation_fee',
      render: (fee) =>
        fee ? `${Number(fee).toLocaleString()} ${t('toman', 'تومان')}` : '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'is_available',
      key: 'is_available',
      render: (isAvailable) => (
        <Badge
          status={isAvailable ? 'success' : 'error'}
          text={isAvailable ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
        />
      ),
    },
    {
      title: t('verified', 'تایید'),
      dataIndex: 'is_verified',
      key: 'is_verified',
      render: (isVerified) => (
        <Tag color={isVerified ? 'success' : 'warning'}>
          {isVerified ? t('verified', 'تایید شده') : t('pending_verification', 'در انتظار تایید')}
        </Tag>
      ),
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 200,
      render: (_, record) => (
        <Space size="small">
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
          <Tooltip title={t('toggle_status', 'تغییر وضعیت')}>
            <Button
              type="text"
              icon={record.is_available ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
              onClick={() => handleToggleStatus(record.id)}
              size="small"
              style={{ color: record.is_available ? '#ef4444' : '#10b981' }}
            />
          </Tooltip>
          {!record.is_verified && (
            <Tooltip title={t('verify', 'تایید')}>
              <Button
                type="text"
                icon={<CheckCircleOutlined />}
                onClick={() => handleVerify(record.id)}
                size="small"
                style={{ color: '#2563eb' }}
              />
            </Tooltip>
          )}
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این پزشک اطمینان دارید؟')}
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

  return (
    <div>
      {/* ===== هدر صفحه ===== */}
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
            {t('doctors_management', 'مدیریت پزشکان')}
          </Title>
          <Text type="secondary">
            {t('doctors_subtitle', 'لیست و مدیریت پزشکان کلینیک')}
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
          {t('new_doctor', 'پزشک جدید')}
        </Button>
      </div>

      {/* ===== فیلترها ===== */}
      <Card
        style={{
          marginBottom: 16,
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Row gutter={[16, 16]} align="middle">
          <Col xs={24} sm={12} md={8} lg={6}>
            <Input
              placeholder={t('search_doctor', 'جستجوی پزشک...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_specialty', 'فیلتر تخصص')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, specialty_id: value })}
            >
              <Select.Option value="1">داخلی</Select.Option>
              <Select.Option value="2">قلب و عروق</Select.Option>
              <Select.Option value="3">ارتوپدی</Select.Option>
              <Select.Option value="4">اعصاب و روان</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_status', 'فیلتر وضعیت')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, is_available: value })}
            >
              <Select.Option value={true}>{t('active', 'فعال')}</Select.Option>
              <Select.Option value={false}>{t('inactive', 'غیرفعال')}</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
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

      {/* ===== لیست پزشکان ===== */}
      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Table
          columns={columns}
          dataSource={doctors}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'نفر')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1200 }}
          locale={{
            emptyText: t('no_doctors', 'هیچ پزشکی یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده جزئیات ===== */}
      <Modal
        title={t('doctor_details', 'جزئیات پزشک')}
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
              if (selectedDoctor) {
                router.push(`/admin/doctors/${selectedDoctor.id}/edit`);
              }
            }}
          >
            {t('edit', 'ویرایش')}
          </Button>,
        ]}
        width={600}
      >
        {selectedDoctor && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <Avatar
                size={64}
                src={selectedDoctor.profile_image}
                icon={<UserOutlined />}
                style={{ backgroundColor: '#2563eb' }}
              />
              <div>
                <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedDoctor.full_name}</div>
                <div style={{ color: '#64748b' }}>
                  {selectedDoctor.specialty?.name || t('no_specialty', 'بدون تخصص')}
                </div>
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('license', 'شماره نظام پزشکی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDoctor.license_number || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('mobile', 'شماره موبایل')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedDoctor.user?.mobile || '—'}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('email', 'ایمیل')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDoctor.user?.email || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('fee', 'هزینه ویزیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedDoctor.consultation_fee
                    ? `${Number(selectedDoctor.consultation_fee).toLocaleString()} ${t('toman', 'تومان')}`
                    : '—'}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    status={selectedDoctor.is_available ? 'success' : 'error'}
                    text={selectedDoctor.is_available ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
                  />
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('verified', 'تایید')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Tag color={selectedDoctor.is_verified ? 'success' : 'warning'}>
                    {selectedDoctor.is_verified
                      ? t('verified', 'تایید شده')
                      : t('pending_verification', 'در انتظار تایید')}
                  </Tag>
                </div>
              </Col>
              <Col span={24}>
                <Text type="secondary">{t('bio', 'بیوگرافی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDoctor.bio || '—'}</div>
              </Col>
            </Row>
          </div>
        )}
      </Modal>
    </div>
  );
}
