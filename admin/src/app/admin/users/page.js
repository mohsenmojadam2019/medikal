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
  Select,
  Avatar,
  Form,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  UserOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
} from '@ant-design/icons';
import { usersService, rolesService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function UsersPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [users, setUsers] = useState([]);
  const [roles, setRoles] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [modalMode, setModalMode] = useState('create');
  const [selectedUser, setSelectedUser] = useState(null);
  const [form] = Form.useForm();
  const [formLoading, setFormLoading] = useState(false);

  useEffect(() => {
    const fetchRoles = async () => {
      try {
        const response = await rolesService.getAll();
        setRoles(response.data || []);
      } catch (error) {
        console.error('Error fetching roles:', error);
        message.error(t('fetch_error', 'خطا در دریافت نقش‌ها'));
      }
    };
    fetchRoles();
  }, [t]);

  const fetchUsers = async (params = {}) => {
    setLoading(true);
    try {
      const response = await usersService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setUsers(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching users:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, [pagination.current, pagination.pageSize]);

  const handleCreate = () => {
    setModalMode('create');
    setSelectedUser(null);
    form.resetFields();
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    setModalMode('edit');
    setSelectedUser(record);
    form.setFieldsValue(record);
    setIsModalVisible(true);
  };

  const handleDelete = async (id) => {
    try {
      await usersService.delete(id);
      message.success(t('deleted', 'کاربر با موفقیت حذف شد'));
      fetchUsers();
    } catch (error) {
      message.error(t('error', 'خطا در حذف کاربر'));
    }
  };

  const handleToggleStatus = async (id) => {
    try {
      await usersService.toggleStatus(id);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchUsers();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleSubmit = async (values) => {
    setFormLoading(true);
    try {
      if (modalMode === 'create') {
        await usersService.create(values);
        message.success(t('user_created', 'کاربر با موفقیت ایجاد شد'));
      } else {
        await usersService.update(selectedUser.id, values);
        message.success(t('user_updated', 'کاربر با موفقیت به‌روزرسانی شد'));
      }
      setIsModalVisible(false);
      fetchUsers();
    } catch (error) {
      console.error('Error saving user:', error);
      message.error(t('save_error', 'خطا در ذخیره کاربر'));
    } finally {
      setFormLoading(false);
    }
  };

  const handleAssignRole = async (userId, role) => {
    try {
      await usersService.assignRole(userId, role);
      message.success(t('role_assigned', 'نقش با موفقیت اختصاص داده شد'));
      fetchUsers();
    } catch (error) {
      message.error(t('error', 'خطا در اختصاص نقش'));
    }
  };

  const columns = [
    {
      title: t('user', 'کاربر'),
      dataIndex: 'name',
      key: 'name',
      render: (text, record) => (
        <Space>
          <Avatar icon={<UserOutlined />} style={{ backgroundColor: '#2563eb' }} />
          <div>
            <div style={{ fontWeight: 600 }}>{text}</div>
            <div style={{ fontSize: 12, color: '#64748b' }}>{record.email || record.mobile}</div>
          </div>
        </Space>
      ),
    },
    {
      title: t('mobile', 'موبایل'),
      dataIndex: 'mobile',
      key: 'mobile',
    },
    {
      title: t('email', 'ایمیل'),
      dataIndex: 'email',
      key: 'email',
      render: (email) => email || '—',
    },
    {
      title: t('role', 'نقش'),
      dataIndex: 'roles',
      key: 'roles',
      render: (roles) => (
        <Space wrap>
          {roles?.map((role) => (
            <Tag key={role.id} color="blue">{role.name}</Tag>
          )) || '—'}
        </Space>
      ),
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
      title: t('last_login', 'آخرین ورود'),
      dataIndex: 'last_login_at',
      key: 'last_login_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD HH:mm') : '—',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 220,
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
            title={t('delete_confirm', 'آیا از حذف این کاربر اطمینان دارید؟')}
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
            {t('users_management', 'مدیریت کاربران')}
          </Title>
          <Text type="secondary">
            {t('users_subtitle', 'لیست کاربران سیستم')}
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
          {t('new_user', 'کاربر جدید')}
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
              placeholder={t('search_user', 'جستجوی کاربر...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={() => fetchUsers({ page: 1 })}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_role', 'فیلتر نقش')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, role: value })}
            >
              {roles.map((role) => (
                <Select.Option key={role.id} value={role.name}>
                  {role.name}
                </Select.Option>
              ))}
            </Select>
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
          <Col xs={24} sm={24} md={24} lg={6}>
            <Space>
              <Button type="primary" icon={<SearchOutlined />}>
                {t('search', 'جستجو')}
              </Button>
              <Button icon={<ReloadOutlined />} onClick={fetchUsers}>
                {t('refresh', 'بروزرسانی')}
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
          dataSource={users}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'کاربر')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1100 }}
          locale={{
            emptyText: t('no_users', 'هیچ کاربری یافت نشد'),
          }}
        />
      </Card>

      <Modal
        title={modalMode === 'create' ? t('new_user', 'کاربر جدید') : t('edit_user', 'ویرایش کاربر')}
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
            name="name"
            label={t('name', 'نام و نام خانوادگی')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Input placeholder={t('name_placeholder', 'نام کامل...')} />
          </Form.Item>

          <Form.Item
            name="mobile"
            label={t('mobile', 'شماره موبایل')}
            rules={[
              { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
              { pattern: /^09[0-9]{9}$/, message: t('mobile_invalid', 'شماره موبایل نامعتبر است') },
            ]}
          >
            <Input placeholder={t('mobile_placeholder', '۰۹۱۲۳۴۵۶۷۸۹')} />
          </Form.Item>

          <Form.Item
            name="email"
            label={t('email', 'ایمیل')}
            rules={[
              { type: 'email', message: t('email_invalid', 'ایمیل نامعتبر است') },
            ]}
          >
            <Input placeholder={t('email_placeholder', 'user@clinic.com')} />
          </Form.Item>

          <Form.Item
            name="role"
            label={t('role', 'نقش')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Select
              placeholder={t('select_role', 'انتخاب نقش...')}
              options={roles.map((role) => ({
                value: role.name,
                label: role.name,
              }))}
            />
          </Form.Item>

          {modalMode === 'create' && (
            <Form.Item
              name="password"
              label={t('password', 'رمز عبور')}
              rules={[
                { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                { min: 8, message: t('password_min', 'رمز عبور باید حداقل ۸ کاراکتر باشد') },
              ]}
            >
              <Input.Password placeholder="********" />
            </Form.Item>
          )}

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
