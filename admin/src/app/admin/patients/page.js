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
  message,
  Popconfirm,
  Tooltip,
  Row,
  Col,
  Badge,
  Avatar,
  Select,
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
import { patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function PatientsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [patients, setPatients] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);

  // ===== دریافت لیست بیماران =====
  const fetchPatients = async (params = {}) => {
    setLoading(true);
    try {
      const response = await patientsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setPatients(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching patients:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPatients();
  }, [pagination.current, pagination.pageSize]);

  const handleSearch = () => {
    fetchPatients({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchPatients({ page: 1, search: '', ...filters });
  };

  const handleToggleStatus = async (id) => {
    try {
      await patientsService.toggleStatus(id);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchPatients();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleVerify = async (id) => {
    try {
      await patientsService.verify(id);
      message.success(t('verified', 'بیمار با موفقیت تایید شد'));
      fetchPatients();
    } catch (error) {
      message.error(t('error', 'خطا در تایید بیمار'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await patientsService.delete(id);
      message.success(t('deleted', 'بیمار با موفقیت حذف شد'));
      fetchPatients();
    } catch (error) {
      message.error(t('error', 'خطا در حذف بیمار'));
    }
  };

  const handleView = (record) => {
    setSelectedPatient(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/patients/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/patients/create');
  };

  const columns = [
    {
      title: t('id', 'شناسه'),
      dataIndex: 'id',
      key: 'id',
      width: 70,
    },
    {
      title: t('patient', 'بیمار'),
      dataIndex: 'full_name',
      key: 'full_name',
      render: (text, record) => (
        <Space>
          <Avatar
            src={record.profile_image}
            icon={<UserOutlined />}
            style={{ backgroundColor: '#10b981' }}
          />
          <div>
            <div style={{ fontWeight: 600 }}>{text}</div>
            <div style={{ fontSize: 12, color: '#64748b' }}>
              {record.national_code || t('no_national_code', 'بدون کدملی')}
            </div>
          </div>
        </Space>
      ),
    },
    {
      title: t('national_code', 'کدملی'),
      dataIndex: 'national_code',
      key: 'national_code',
    },
    {
      title: t('mobile', 'موبایل'),
      dataIndex: 'phone',
      key: 'phone',
      render: (phone) => phone || '—',
    },
    {
      title: t('doctor', 'پزشک معالج'),
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => doctor?.full_name || '—',
    },
    {
      title: t('appointments', 'تعداد نوبت'),
      dataIndex: 'appointments_count',
      key: 'appointments_count',
      render: (count) => count || 0,
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'is_active',
      key: 'is_active',
      render: (isActive) => (
        <Badge
          status={isActive ? 'success' : 'error'}
          text={isActive ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
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
              icon={record.is_active ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
              onClick={() => handleToggleStatus(record.id)}
              size="small"
              style={{ color: record.is_active ? '#ef4444' : '#10b981' }}
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
            title={t('delete_confirm', 'آیا از حذف این بیمار اطمینان دارید؟')}
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
            {t('patients_management', 'مدیریت بیماران')}
          </Title>
          <Text type="secondary">
            {t('patients_subtitle', 'لیست و مدیریت بیماران کلینیک')}
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
          {t('new_patient', 'بیمار جدید')}
        </Button>
      </div>

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
              placeholder={t('search_patient', 'جستجوی بیمار...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_status', 'فیلتر وضعیت')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, is_active: value })}
            >
              <Select.Option value={true}>{t('active', 'فعال')}</Select.Option>
              <Select.Option value={false}>{t('inactive', 'غیرفعال')}</Select.Option>
            </Select>
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

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Table
          columns={columns}
          dataSource={patients}
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
            emptyText: t('no_patients', 'هیچ بیماری یافت نشد'),
          }}
        />
      </Card>

      <Modal
        title={t('patient_details', 'جزئیات بیمار')}
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
              if (selectedPatient) {
                router.push(`/admin/patients/${selectedPatient.id}/edit`);
              }
            }}
          >
            {t('edit', 'ویرایش')}
          </Button>,
        ]}
        width={600}
      >
        {selectedPatient && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <Avatar
                size={64}
                src={selectedPatient.profile_image}
                icon={<UserOutlined />}
                style={{ backgroundColor: '#10b981' }}
              />
              <div>
                <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedPatient.full_name}</div>
                <div style={{ color: '#64748b' }}>
                  {selectedPatient.national_code || t('no_national_code', 'بدون کدملی')}
                </div>
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('national_code', 'کدملی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPatient.national_code || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('mobile', 'شماره موبایل')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPatient.phone || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('blood_type', 'گروه خونی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPatient.blood_type || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('doctor', 'پزشک معالج')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPatient.doctor?.full_name || '—'}</div>
              </Col>
              <Col span={24}>
                <Text type="secondary">{t('address', 'آدرس')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPatient.address || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    status={selectedPatient.is_active ? 'success' : 'error'}
                    text={selectedPatient.is_active ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
                  />
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('verified', 'تایید')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Tag color={selectedPatient.is_verified ? 'success' : 'warning'}>
                    {selectedPatient.is_verified
                      ? t('verified', 'تایید شده')
                      : t('pending_verification', 'در انتظار تایید')}
                  </Tag>
                </div>
              </Col>
              <Col span={24}>
                <Text type="secondary">{t('created_at', 'تاریخ ثبت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedPatient.created_at
                    ? dayjs(selectedPatient.created_at).format('jYYYY/jMM/jDD HH:mm')
                    : '—'}
                </div>
              </Col>
            </Row>
          </div>
        )}
      </Modal>
    </div>
  );
}
