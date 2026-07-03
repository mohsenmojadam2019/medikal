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
  Switch,
} from 'antd';
import {
  SearchOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  BellOutlined,
  ClockCircleOutlined,
  PlusOutlined,
} from '@ant-design/icons';
import { remindersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function RemindersPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [reminders, setReminders] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [settingsForm] = Form.useForm();
  const [settingsLoading, setSettingsLoading] = useState(false);
  const [settings, setSettings] = useState(null);

  const fetchReminders = async (params = {}) => {
    setLoading(true);
    try {
      const response = await remindersService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setReminders(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching reminders:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReminders();
    fetchSettings();
  }, [pagination.current, pagination.pageSize]);

  const fetchSettings = async () => {
    try {
      const response = await remindersService.getSettings();
      setSettings(response.data);
      settingsForm.setFieldsValue(response.data);
    } catch (error) {
      console.error('Error fetching settings:', error);
    }
  };

  const handleSaveSettings = async (values) => {
    setSettingsLoading(true);
    try {
      await remindersService.updateSettings(values);
      message.success(t('settings_saved', 'تنظیمات با موفقیت ذخیره شد'));
      fetchSettings();
      setIsModalVisible(false);
    } catch (error) {
      console.error('Error saving settings:', error);
      message.error(t('save_error', 'خطا در ذخیره تنظیمات'));
    } finally {
      setSettingsLoading(false);
    }
  };

  const handleDelete = async (id) => {
    try {
      await remindersService.delete(id);
      message.success(t('deleted', 'یادآوری با موفقیت حذف شد'));
      fetchReminders();
    } catch (error) {
      message.error(t('error', 'خطا در حذف یادآوری'));
    }
  };

  const handleProcess = async () => {
    try {
      await remindersService.process();
      message.success(t('processed', 'یادآوری‌ها با موفقیت پردازش شدند'));
      fetchReminders();
    } catch (error) {
      message.error(t('error', 'خطا در پردازش یادآوری‌ها'));
    }
  };

  const statusMap = {
    pending: { color: 'orange', label: 'در انتظار' },
    sent: { color: 'green', label: 'ارسال شده' },
    failed: { color: 'red', label: 'ناموفق' },
  };

  const columns = [
    {
      title: t('type', 'نوع'),
      dataIndex: 'type',
      key: 'type',
      render: (type) => (
        <Tag color={type === 'sms' ? 'blue' : 'green'}>
          {type === 'sms' ? 'SMS' : 'Email'}
        </Tag>
      ),
    },
    {
      title: t('receiver', 'گیرنده'),
      dataIndex: 'receiver',
      key: 'receiver',
      render: (receiver) => receiver?.name || '—',
    },
    {
      title: t('message', 'متن'),
      dataIndex: 'message',
      key: 'message',
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
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD HH:mm') : '—',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      render: (_, record) => (
        <Space>
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این یادآوری اطمینان دارید؟')}
            onConfirm={() => handleDelete(record.id)}
            okText={t('yes', 'بله')}
            cancelText={t('no', 'خیر')}
          >
            <Button type="text" icon={<DeleteOutlined />} size="small" danger />
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
            {t('reminders_management', 'مدیریت یادآوری‌ها')}
          </Title>
          <Text type="secondary">
            {t('reminders_subtitle', 'مدیریت یادآوری‌های نوبت و دارو')}
          </Text>
        </div>
        <Space>
          <Button
            icon={<ClockCircleOutlined />}
            onClick={handleProcess}
          >
            {t('process_now', 'پردازش هم‌اکنون')}
          </Button>
          <Button
            icon={<BellOutlined />}
            onClick={() => setIsModalVisible(true)}
          >
            {t('settings', 'تنظیمات')}
          </Button>
        </Space>
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
              placeholder={t('search_reminder', 'جستجوی یادآوری...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={() => fetchReminders({ page: 1 })}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_status', 'فیلتر وضعیت')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, status: value })}
            >
              <Select.Option value="pending">{t('pending', 'در انتظار')}</Select.Option>
              <Select.Option value="sent">{t('sent', 'ارسال شده')}</Select.Option>
              <Select.Option value="failed">{t('failed', 'ناموفق')}</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Space>
              <Button type="primary" icon={<SearchOutlined />}>
                {t('search', 'جستجو')}
              </Button>
              <Button icon={<ReloadOutlined />} onClick={fetchReminders}>
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
          dataSource={reminders}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'یادآوری')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          locale={{
            emptyText: t('no_reminders', 'هیچ یادآوری‌ای یافت نشد'),
          }}
        />
      </Card>

      <Modal
        title={t('reminder_settings', 'تنظیمات یادآوری')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={null}
        width={500}
      >
        <Form
          form={settingsForm}
          layout="vertical"
          onFinish={handleSaveSettings}
          size="large"
        >
          <Form.Item
            name="sms_enabled"
            label={t('sms_reminder', 'یادآوری SMS')}
            valuePropName="checked"
          >
            <Switch
              checkedChildren={t('active', 'فعال')}
              unCheckedChildren={t('inactive', 'غیرفعال')}
            />
          </Form.Item>

          <Form.Item
            name="email_enabled"
            label={t('email_reminder', 'یادآوری Email')}
            valuePropName="checked"
          >
            <Switch
              checkedChildren={t('active', 'فعال')}
              unCheckedChildren={t('inactive', 'غیرفعال')}
            />
          </Form.Item>

          <Form.Item
            name="reminder_time"
            label={t('reminder_time', 'زمان یادآوری (ساعت قبل از نوبت)')}
            rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
          >
            <Select
              options={[
                { value: 1, label: '۱ ساعت' },
                { value: 2, label: '۲ ساعت' },
                { value: 24, label: '۲۴ ساعت' },
                { value: 48, label: '۴۸ ساعت' },
              ]}
            />
          </Form.Item>

          <Form.Item
            name="is_active"
            label={t('status', 'وضعیت')}
            valuePropName="checked"
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
              loading={settingsLoading}
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
