// src/app/admin/specialties/page.js

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
  Form,
  Select,
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
  UploadOutlined,
  HeartOutlined,
} from '@ant-design/icons';
import { specialtiesService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';

const { Title, Text } = Typography;

export default function SpecialtiesPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();

  const [loading, setLoading] = useState(false);
  const [specialties, setSpecialties] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedSpecialty, setSelectedSpecialty] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);

  // ===== دریافت لیست تخصص‌ها =====
  const fetchSpecialties = async (params = {}) => {
    setLoading(true);
    try {
      const response = await specialtiesService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });

      // ✅ بررسی ساختار پاسخ
      if (response.data?.success) {
        const data = response.data.data;
        const list = data?.data || data || [];
        setSpecialties(Array.isArray(list) ? list : []);
        setPagination({
          ...pagination,
          total: data?.total || (Array.isArray(list) ? list.length : 0),
          current: data?.current_page || 1,
        });
      } else {
        setSpecialties([]);
        setPagination({
          ...pagination,
          total: 0,
        });
      }
    } catch (error) {
      console.error('Error fetching specialties:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      setSpecialties([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSpecialties();
  }, [pagination.current, pagination.pageSize]);

  const handleSearch = () => {
    fetchSpecialties({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchSpecialties({ page: 1 });
  };

  const handleToggleStatus = async (id) => {
    try {
      await specialtiesService.toggleStatus(id);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchSpecialties();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await specialtiesService.delete(id);
      message.success(t('deleted', 'تخصص با موفقیت حذف شد'));
      fetchSpecialties();
    } catch (error) {
      message.error(t('error', 'خطا در حذف تخصص'));
    }
  };

  const handleView = (record) => {
    setSelectedSpecialty(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/specialties/${record.id}/edit`);
};

const handleCreate = () => {
    router.push('/admin/specialties/create');
};

// ===== رنگ‌های تصادفی برای آیکون‌ها =====
const colors = [
    '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
    '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#06b6d4',
];

const columns = [
    {
        title: t('id', 'شناسه'),
        dataIndex: 'id',
        key: 'id',
        width: 70,
    },
    {
        title: t('specialty', 'تخصص'),
        dataIndex: 'name',
        key: 'name',
        render: (text, record) => (
            <Space>
                <Avatar
                    src={record.icon_url}
                    icon={<HeartOutlined />}
                    style={{
                        backgroundColor: record.icon_color || colors[record.id % colors.length],
                        color: '#fff',
                    }}
                />
                <div>
                    <div style={{ fontWeight: 600 }}>{text}</div>
                    <div style={{ fontSize: 12, color: '#64748b' }}>
                        {record.slug || text}
                    </div>
                </div>
            </Space>
        ),
    },
    {
        title: t('slug', 'شناسه یکتا'),
        dataIndex: 'slug',
        key: 'slug',
        render: (slug) => slug || '—',
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
        title: t('appointments', 'تعداد نوبت'),
        dataIndex: 'appointments_count',
        key: 'appointments_count',
        render: (count) => count || 0,
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
                <Popconfirm
                    title={t('delete_confirm', 'آیا از حذف این تخصص اطمینان دارید؟')}
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

// اگر specialties آرایه نیست
if (!Array.isArray(specialties)) {
    console.error('⚠️ Specialties is not an array:', specialties);
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
                    {t('specialties_management', 'مدیریت تخصص‌ها')}
                </Title>
                <Text type="secondary">
                    {t('specialties_subtitle', 'لیست و مدیریت تخصص‌های پزشکی')}
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
                {t('new_specialty', 'تخصص جدید')}
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
                        placeholder={t('search_specialty', 'جستجوی تخصص...')}
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
                dataSource={specialties}
                loading={loading}
                rowKey="id"
                pagination={{
                    current: pagination.current,
                    pageSize: pagination.pageSize,
                    total: pagination.total,
                    showSizeChanger: true,
                    showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'تخصص')}`,
                    onChange: (page, pageSize) => {
                        setPagination({ ...pagination, current: page, pageSize });
                    },
                }}
                scroll={{ x: 1000 }}
                locale={{
                    emptyText: t('no_specialties', 'هیچ تخصصی یافت نشد'),
                }}
            />
        </Card>

        <Modal
            title={t('specialty_details', 'جزئیات تخصص')}
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
                        if (selectedSpecialty) {
                            router.push(`/admin/specialties/${selectedSpecialty.id}/edit`);
                        }
                    }}
                >
                    {t('edit', 'ویرایش')}
                </Button>,
            ]}
            width={500}
        >
            {selectedSpecialty && (
                <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
                        <Avatar
                            size={64}
                            src={selectedSpecialty.icon_url}
                            icon={<HeartOutlined />}
                            style={{
                                backgroundColor: selectedSpecialty.icon_color || colors[selectedSpecialty.id % colors.length],
                                color: '#fff',
                            }}
                        />
                        <div>
                            <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedSpecialty.name}</div>
                            <div style={{ color: '#64748b' }}>
                                {selectedSpecialty.slug || selectedSpecialty.name}
                            </div>
                        </div>
                    </div>

                    <Row gutter={[16, 16]}>
                        <Col span={12}>
                            <Text type="secondary">{t('slug', 'شناسه یکتا')}</Text>
                            <div style={{ fontWeight: 500 }}>{selectedSpecialty.slug || '—'}</div>
                        </Col>
                        <Col span={12}>
                            <Text type="secondary">{t('status', 'وضعیت')}</Text>
                            <div style={{ fontWeight: 500 }}>
                                <Badge
                                    status={selectedSpecialty.is_active ? 'success' : 'error'}
                                    text={selectedSpecialty.is_active ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
                                />
                            </div>
                        </Col>
                        <Col span={24}>
                            <Text type="secondary">{t('description', 'توضیحات')}</Text>
                            <div style={{ fontWeight: 500 }}>{selectedSpecialty.description || '—'}</div>
                        </Col>
                        <Col span={12}>
                            <Text type="secondary">{t('appointments', 'تعداد نوبت‌ها')}</Text>
                            <div style={{ fontWeight: 500 }}>{selectedSpecialty.appointments_count || 0}</div>
                        </Col>
                        <Col span={12}>
                            <Text type="secondary">{t('doctors', 'تعداد پزشکان')}</Text>
                            <div style={{ fontWeight: 500 }}>{selectedSpecialty.doctors_count || 0}</div>
                        </Col>
                    </Row>
                </div>
            )}
        </Modal>
    </div>
);
}
