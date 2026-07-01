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
  Form,
  TextArea,
  Tabs,
  Statistic,
} from 'antd';
import {
  SearchOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  BellOutlined,
  SendOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  UserOutlined,
  TeamOutlined,
} from '@ant-design/icons';
import { notificationsService, usersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function NotificationsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [users, setUsers] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedNotification, setSelectedNotification] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [isSendModalVisible, setIsSendModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');
  const [sendForm] = Form.useForm();
  const [sendLoading, setSendLoading] = useState(false);
  const [stats, setStats] = useState(null);

  // ===== دریافت آمار =====
  const fetchStats = async () => {
    try {
      const response = await notificationsService.getStats();
      setStats(response.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ===== دریافت لیست کاربران =====
  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const response = await usersService.getAll({ per_page: 100 });
        setUsers(response.data || []);
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    };
    fetchUsers();
  }, []);

  // ===== دریافت لیست اعلان‌ها =====
  const fetchNotifications = async (params = {}) => {
    setLoading(true);
    try {
      const response = await notificationsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setNotifications(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching notifications:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
    fetchStats();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchNotifications({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchNotifications({ page: 1, search: '', ...filters });
  };

  const handleDelete = async (id) => {
    try {
      await notificationsService.delete(id);
      message.success(t('deleted', 'اعلان با موفقیت حذف شد'));
      fetchNotifications();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در حذف اعلان'));
    }
  };

  const handleMarkAsRead = async (id) => {
    try {
      await notificationsService.markAsRead(id);
      message.success(t('marked_as_read', 'اعلان به عنوان خوانده شده علامت‌گذاری شد'));
      fetchNotifications();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در علامت‌گذاری'));
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await notificationsService.markAllAsRead();
      message.success(t('all_marked_as_read', 'همه اعلان‌ها به عنوان خوانده شده علامت‌گذاری شدند'));
      fetchNotifications();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در علامت‌گذاری'));
    }
  };

  const handleDeleteAllRead = async () => {
    try {
      await notificationsService.deleteAllRead();
      message.success(t('all_read_deleted', 'همه اعلان‌های خوانده شده حذف شدند'));
      fetchNotifications();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در حذف اعلان‌ها'));
    }
  };

  const handleView = (record) => {
    setSelectedNotification(record);
    setIsModalVisible(true);
    if (!record.read_at) {
      handleMarkAsRead(record.id);
    }
  };

  const handleSend = (values) => {
    setSelectedNotification(null);
    sendForm.resetFields();
    setIsSendModalVisible(true);
  };

  const handleSendSubmit = async (values) => {
    setSendLoading(true);
    try {
      if (values.send_to === 'all') {
        await notificationsService.sendToAll(values.title, values.message, values.priority);
      } else if (values.send_to === 'doctors') {
        await notificationsService.sendToDoctors(values.title, values.message, values.priority);
      } else if (values.send_to === 'patients') {
        await notificationsService.sendToPatients(values.title, values.message, values.priority);
      } else if (values.send_to === 'user' && values.user_id) {
        await notificationsService.sendToUser(values.user_id, values.title, values.message, values.priority);
      }
      message.success(t('notification_sent', 'اعلان با موفقیت ارسال شد'));
      setIsSendModalVisible(false);
      fetchNotifications();
      fetchStats();
    } catch (error) {
      console.error('Error sending notification:', error);
      message.error(t('send_error', 'خطا در ارسال اعلان'));
    } finally {
      setSendLoading(false);
    }
  };

  // ===== اولویت‌ها =====
  const priorityMap = {
    low: { color: 'default', label: 'معمولی' },
    medium: { color: 'blue', label: 'متوسط' },
    high: { color: 'orange', label: 'بالا' },
    urgent: { color: 'red', label: 'فوری' },
  };

  const columns = [
    {
      title: t('title', 'عنوان'),
      dataIndex: 'title',
      key: 'title',
      render: (text, record) => (
        <div>
          <div style={{ fontWeight: record.read_at ? 400 : 600 }}>
            {text}
          </div>
          <div style={{ fontSize: 12, color: '#64748b' }}>
            {record.message?.substring(0, 50)}
            {record.message?.length > 50 ? '...' : ''}
          </div>
        </div>
      ),
    },
    {
      title: t('priority', 'اولویت'),
      dataIndex: 'priority',
      key: 'priority',
      render: (priority) => {
        const p = priorityMap[priority] || { color: 'default', label: priority };
        return <Tag color={p.color}>{p.label}</Tag>;
      },
    },
    {
      title: t('receiver', 'گیرنده'),
      dataIndex: 'receiver',
      key: 'receiver',
      render: (receiver) => receiver?.name || t('all_users', 'همه کاربران'),
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'read_at',
      key: 'status',
      render: (readAt) => (
        <Badge
          status={readAt ? 'success' : 'warning'}
          text={readAt ? t('read', 'خوانده شده') : t('unread', 'خوانده نشده')}
        />
      ),
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD HH:mm') : '—',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 200,
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
          {!record.read_at && (
            <Tooltip title={t('mark_as_read', 'علامت‌گذاری به عنوان خوانده شده')}>
              <Button
                type="text"
                icon={<CheckCircleOutlined />}
                onClick={() => handleMarkAsRead(record.id)}
                size="small"
                style={{ color: '#10b981' }}
              />
            </Tooltip>
          )}
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این اعلان اطمینان دارید؟')}
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

  // ===== آیتم‌های تب =====
  const tabItems = [
    { key: 'all', label: t('all', 'همه') },
    { key: 'unread', label: t('unread', 'خوانده نشده') },
    { key: 'read', label: t('read', 'خوانده شده') },
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
            {t('notifications_management', 'مدیریت اعلان‌ها')}
          </Title>
          <Text type="secondary">
            {t('notifications_subtitle', 'لیست اعلان‌های سیستم')}
          </Text>
        </div>
        <Space>
          <Button
            icon={<CheckCircleOutlined />}
            onClick={handleMarkAllAsRead}
          >
            {t('mark_all_as_read', 'خواندن همه')}
          </Button>
          <Button
            icon={<DeleteOutlined />}
            onClick={handleDeleteAllRead}
            danger
          >
            {t('delete_all_read', 'حذف همه')}
          </Button>
          <Button
            type="primary"
            icon={<SendOutlined />}
            onClick={handleSend}
            style={{
              height: 40,
              background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
              border: 'none',
            }}
          >
            {t('send_notification', 'ارسال اعلان')}
          </Button>
        </Space>
      </div>

      {/* ===== آمار ===== */}
      {stats && (
        <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
          <Col xs={24} sm={12} md={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('total_notifications', 'تعداد اعلان‌ها')}
                value={stats.total || 0}
                prefix={<BellOutlined style={{ color: '#2563eb' }} />}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('unread_count', 'خوانده نشده')}
                value={stats.unread || 0}
                valueStyle={{ color: '#f59e0b' }}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('read_count', 'خوانده شده')}
                value={stats.read || 0}
                valueStyle={{ color: '#10b981' }}
              />
            </Card>
          </Col>
        </Row>
      )}

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
              placeholder={t('search_notification', 'جستجوی اعلان...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_priority', 'فیلتر اولویت')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, priority: value })}
            >
              <Select.Option value="low">معمولی</Select.Option>
              <Select.Option value="medium">متوسط</Select.Option>
              <Select.Option value="high">بالا</Select.Option>
              <Select.Option value="urgent">فوری</Select.Option>
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
          dataSource={notifications}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'اعلان')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1100 }}
          locale={{
            emptyText: t('no_notifications', 'هیچ اعلانی یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده جزئیات ===== */}
      <Modal
        title={t('notification_details', 'جزئیات اعلان')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsModalVisible(false)}>
            {t('close', 'بستن')}
          </Button>,
        ]}
        width={500}
      >
        {selectedNotification && (
          <div>
            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t('title', 'عنوان')}</Text>
              <div style={{ fontSize: 16, fontWeight: 600 }}>
                {selectedNotification.title}
              </div>
            </div>

            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t('message', 'متن')}</Text>
              <div style={{ padding: '8px 12px', background: '#f8fafc', borderRadius: 8, marginTop: 4 }}>
                {selectedNotification.message}
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('priority', 'اولویت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Tag color={priorityMap[selectedNotification.priority]?.color || 'default'}>
                    {priorityMap[selectedNotification.priority]?.label || selectedNotification.priority}
                  </Tag>
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    status={selectedNotification.read_at ? 'success' : 'warning'}
                    text={selectedNotification.read_at ? t('read', 'خوانده شده') : t('unread', 'خوانده نشده')}
                  />
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('receiver', 'گیرنده')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedNotification.receiver?.name || t('all_users', 'همه کاربران')}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('date', 'تاریخ')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedNotification.created_at ? dayjs(selectedNotification.created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                </div>
              </Col>
            </Row>
          </div>
        )}
      </Modal>

      {/* ===== مودال ارسال اعلان ===== */}
      <Modal
        title={t('send_notification', 'ارسال اعلان جدید')}
        open={isSendModalVisible}
        onCancel={() => setIsSendModalVisible(false)}
        footer={null}
        width={550}
      >
        <Form
          form={sendForm}
          layout="vertical"
          onFinish={handleSendSubmit}
          size="large"
          initialValues={{
            send_to: 'all',
            priority: 'medium',
          }}
        >
          <Form.Item
            name="send_to"
            label={t('send_to', 'ارسال به')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Select
              options={[
                { value: 'all', label: t('all_users', 'همه کاربران') },
                { value: 'doctors', label: t('all_doctors', 'همه پزشکان') },
                { value: 'patients', label: t('all_patients', 'همه بیماران') },
                { value: 'user', label: t('specific_user', 'کاربر خاص') },
              ]}
            />
          </Form.Item>

          <Form.Item
            noStyle
            shouldUpdate={(prevValues, currentValues) => prevValues.send_to !== currentValues.send_to}
          >
            {({ getFieldValue }) => {
              const sendTo = getFieldValue('send_to');
              if (sendTo === 'user') {
                return (
                  <Form.Item
                    name="user_id"
                    label={t('select_user', 'انتخاب کاربر')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_user', 'انتخاب کاربر...')}
                      showSearch
                      optionFilterProp="children"
                      options={users.map((u) => ({
                        value: u.id,
                        label: u.name,
                      }))}
                    />
                  </Form.Item>
                );
              }
              return null;
            }}
          </Form.Item>

          <Form.Item
            name="title"
            label={t('title', 'عنوان')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Input placeholder={t('title_placeholder', 'عنوان اعلان...')} />
          </Form.Item>

          <Form.Item
            name="message"
            label={t('message', 'متن')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <TextArea
              rows={4}
              placeholder={t('message_placeholder', 'متن اعلان...')}
            />
          </Form.Item>

          <Form.Item
            name="priority"
            label={t('priority', 'اولویت')}
          >
            <Select
              options={[
                { value: 'low', label: t('low', 'معمولی') },
                { value: 'medium', label: t('medium', 'متوسط') },
                { value: 'high', label: t('high', 'بالا') },
                { value: 'urgent', label: t('urgent', 'فوری') },
              ]}
            />
          </Form.Item>

          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <Button onClick={() => setIsSendModalVisible(false)}>
              {t('cancel', 'انصراف')}
            </Button>
            <Button
              type="primary"
              htmlType="submit"
              loading={sendLoading}
              icon={<SendOutlined />}
              style={{
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
            >
              {t('send', 'ارسال')}
            </Button>
          </div>
        </Form>
      </Modal>
    </div>
  );
}
