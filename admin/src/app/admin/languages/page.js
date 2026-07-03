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
  Switch,
  Form,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  GlobalOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
} from '@ant-design/icons';
import { languageService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function LanguagesPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [languages, setLanguages] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [modalMode, setModalMode] = useState('create');
  const [selectedLanguage, setSelectedLanguage] = useState(null);
  const [form] = Form.useForm();
  const [formLoading, setFormLoading] = useState(false);

  const fetchLanguages = async (params = {}) => {
    setLoading(true);
    try {
      const response = await languageService.getLanguages();
      setLanguages(response.data?.languages || []);
      setPagination({
        ...pagination,
        total: response.data?.languages?.length || 0,
      });
    } catch (error) {
      console.error('Error fetching languages:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLanguages();
  }, []);

  const handleCreate = () => {
    setModalMode('create');
    setSelectedLanguage(null);
    form.resetFields();
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    setModalMode('edit');
    setSelectedLanguage(record);
    form.setFieldsValue(record);
    setIsModalVisible(true);
  };

  const handleDelete = async (id) => {
    try {
      await languageService.deleteLanguage(id);
      message.success(t('deleted', 'زبان با موفقیت حذف شد'));
      fetchLanguages();
    } catch (error) {
      message.error(t('error', 'خطا در حذف زبان'));
    }
  };

  const handleToggleStatus = async (id) => {
    try {
      await languageService.toggleLanguage(id);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchLanguages();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleSubmit = async (values) => {
    setFormLoading(true);
    try {
      if (modalMode === 'create') {
        await languageService.createLanguage(values);
        message.success(t('language_created', 'زبان با موفقیت ایجاد شد'));
      } else {
        await languageService.updateLanguage(selectedLanguage.id, values);
        message.success(t('language_updated', 'زبان با موفقیت به‌روزرسانی شد'));
      }
      setIsModalVisible(false);
      fetchLanguages();
    } catch (error) {
      console.error('Error saving language:', error);
      message.error(t('save_error', 'خطا در ذخیره زبان'));
    } finally {
      setFormLoading(false);
    }
  };

  const handleSetDefault = async (id) => {
    try {
      await languageService.setDefault(id);
      message.success(t('default_set', 'زبان پیش‌فرض با موفقیت تغییر کرد'));
      fetchLanguages();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر زبان پیش‌فرض'));
    }
  };

  const columns = [
    {
      title: t('code', 'کد'),
      dataIndex: 'code',
      key: 'code',
      render: (text) => <Tag color="blue">{text}</Tag>,
    },
    {
      title: t('name', 'نام'),
      dataIndex: 'name',
      key: 'name',
    },
    {
      title: t('native_name', 'نام بومی'),
      dataIndex: 'native_name',
      key: 'native_name',
    },
    {
      title: t('direction', 'جهت'),
      dataIndex: 'direction',
      key: 'direction',
      render: (direction) => (
        <Tag color={direction === 'rtl' ? 'orange' : 'green'}>
          {direction === 'rtl' ? 'راست‌چین' : 'چپ‌چین'}
        </Tag>
      ),
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'is_active',
      key: 'is_active',
      render: (isActive, record) => (
        <Space>
          <Badge
            status={isActive ? 'success' : 'error'}
            text={isActive ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
          />
          {record.is_default && (
            <Tag color="gold">{t('default', 'پیش‌فرض')}</Tag>
          )}
        </Space>
      ),
    },
    {
      title: t('sort_order', 'ترتیب'),
      dataIndex: 'sort_order',
      key: 'sort_order',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 280,
      render: (_, record) => (
        <Space size="small" wrap>
          <Tooltip title={t('edit', 'ویرایش')}>
            <Button
              type="text"
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
              size="small"
            />
          </Tooltip>
          {!record.is_default && (
            <Tooltip title={t('set_default', 'تنظیم به عنوان پیش‌فرض')}>
              <Button
                type="text"
                icon={<CheckCircleOutlined />}
                onClick={() => handleSetDefault(record.id)}
                size="small"
                style={{ color: '#f59e0b' }}
              />
            </Tooltip>
          )}
          <Tooltip title={t('toggle_status', 'تغییر وضعیت')}>
            <Button
              type="text"
              icon={record.is_active ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
              onClick={() => handleToggleStatus(record.id)}
              size="small"
              style={{ color: record.is_active ? '#ef4444' : '#10b981' }}
            />
          </Tooltip>
          {!record.is_default && (
            <Popconfirm
              title={t('delete_confirm', 'آیا از حذف این زبان اطمینان دارید؟')}
              onConfirm={() => handleDelete(record.id)}
              okText={t('yes', 'بله')}
              cancelText={t('no', 'خیر')}
            >
              <Tooltip title={t('delete', 'حذف')}>
                <Button type="text" icon={<DeleteOutlined />} size="small" danger />
              </Tooltip>
            </Popconfirm>
          )}
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
            {t('languages_management', 'مدیریت زبان‌ها')}
          </Title>
          <Text type="secondary">
            {t('languages_subtitle', 'مدیریت زبان‌های سیستم')}
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
          {t('new_language', 'زبان جدید')}
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
              placeholder={t('search_language', 'جستجوی زبان...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Space>
              <Button type="primary" icon={<SearchOutlined />}>
                {t('search', 'جستجو')}
              </Button>
              <Button icon={<ReloadOutlined />} onClick={fetchLanguages}>
                {t('refresh', 'بروزرسانی')}
              </Button>
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
          dataSource={languages}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'زبان')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          locale={{
            emptyText: t('no_languages', 'هیچ زبانی یافت نشد'),
          }}
        />
      </Card>

      <Modal
        title={modalMode === 'create' ? t('new_language', 'زبان جدید') : t('edit_language', 'ویرایش زبان')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={null}
        width={500}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          size="large"
        >
          <Form.Item
            name="code"
            label={t('code', 'کد زبان')}
            rules={[
              { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
              { pattern: /^[a-z]{2}$/, message: t('code_invalid', 'کد زبان باید ۲ حرف کوچک باشد') },
            ]}
          >
            <Input placeholder={t('code_placeholder', 'مثال: fa')} />
          </Form.Item>

          <Form.Item
            name="name"
            label={t('name', 'نام زبان')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Input placeholder={t('name_placeholder', 'مثال: فارسی')} />
          </Form.Item>

          <Form.Item
            name="native_name"
            label={t('native_name', 'نام بومی')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Input placeholder={t('native_name_placeholder', 'مثال: فارسی')} />
          </Form.Item>

          <Form.Item
            name="direction"
            label={t('direction', 'جهت')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Select
              options={[
                { value: 'rtl', label: 'راست‌چین (RTL)' },
                { value: 'ltr', label: 'چپ‌چین (LTR)' },
              ]}
            />
          </Form.Item>

          <Form.Item
            name="sort_order"
            label={t('sort_order', 'ترتیب')}
          >
            <InputNumber
              style={{ width: '100%' }}
              min={0}
              placeholder={t('sort_order_placeholder', '۰')}
            />
          </Form.Item>

          <Form.Item
            name="is_active"
            label={t('status', 'وضعیت')}
            valuePropName="checked"
            initialValue={true}
          >
            <Switch
              checkedChildren={t('active', 'فعال')}
              unCheckedChildren={t('inactive', 'غیرفعال')}
            />
          </Form.Item>

          {modalMode === 'create' && (
            <Form.Item
              name="is_default"
              label={t('default', 'زبان پیش‌فرض')}
              valuePropName="checked"
            >
              <Switch
                checkedChildren={t('yes', 'بله')}
                unCheckedChildren={t('no', 'خیر')}
              />
            </Form.Item>
          )}

          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <Button onClick={() => setIsModalVisible(false)}>
              {t('cancel', 'انصراف')}
            </Button>
            <Button
              type="primary"
              htmlType="submit"
              loading={formLoading}
              style={{
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
            >
              {t('save', 'ذخیره')}
            </Button>
          </div>
        </Form>
      </Modal>
    </div>
  );
}
