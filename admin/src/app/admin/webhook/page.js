'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Switch,
  Table,
  Tag,
  Badge,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  ApiOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  ReloadOutlined,
} from '@ant-design/icons';
import { webhookService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function WebhookPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [webhookStatus, setWebhookStatus] = useState(null);
  const [logs, setLogs] = useState([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [statusRes, logsRes] = await Promise.all([
          webhookService.getStatus(),
          webhookService.getLogs(),
        ]);
        setWebhookStatus(statusRes.data);
        form.setFieldsValue(statusRes.data);
        setLogs(logsRes.data || []);
      } catch (error) {
        console.error('Error fetching webhook data:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };
    fetchData();
  }, [form, t]);

  const handleToggle = async () => {
    setLoading(true);
    try {
      const response = await webhookService.toggle();
      setWebhookStatus(response.data);
      form.setFieldsValue(response.data);
      message.success(t('webhook_toggled', 'وضعیت وب‌هوک با موفقیت تغییر کرد'));
    } catch (error) {
      console.error('Error toggling webhook:', error);
      message.error(t('toggle_error', 'خطا در تغییر وضعیت وب‌هوک'));
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (values) => {
    setLoading(true);
    try {
      await webhookService.updateSettings(values);
      message.success(t('settings_saved', 'تنظیمات با موفقیت ذخیره شد'));
    } catch (error) {
      console.error('Error saving webhook settings:', error);
      message.error(t('save_error', 'خطا در ذخیره تنظیمات'));
    } finally {
      setLoading(false);
    }
  };

  const columns = [
    {
      title: t('event', 'رویداد'),
      dataIndex: 'event',
      key: 'event',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'status',
      key: 'status',
      render: (status) => (
        <Badge
          status={status === 'success' ? 'success' : 'error'}
          text={status === 'success' ? t('success', 'موفق') : t('failed', 'ناموفق')}
        />
      ),
    },
    {
      title: t('message', 'پیام'),
      dataIndex: 'message',
      key: 'message',
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD HH:mm') : '—',
    },
  ];

  if (fetchLoading) {
    return <Loading text={t('loading_webhook', 'در حال بارگذاری اطلاعات وب‌هوک...')} />;
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
            {t('webhook_management', 'مدیریت وب‌هوک')}
          </Title>
          <Text type="secondary">
            {t('webhook_subtitle', 'اتصال به سیستم ویپ کلینیک')}
          </Text>
        </div>
      </div>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
          marginBottom: 16,
        }}
        title={
          <Space>
            <ApiOutlined style={{ color: '#2563eb' }} />
            <Text strong>{t('webhook_status', 'وضعیت وب‌هوک')}</Text>
          </Space>
        }
        extra={
          <Space>
            <Button
              type={webhookStatus?.is_enabled ? 'primary' : 'default'}
              icon={webhookStatus?.is_enabled ? <CheckCircleOutlined /> : <CloseCircleOutlined />}
              onClick={handleToggle}
              loading={loading}
              style={{
                background: webhookStatus?.is_enabled
                  ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
                  : '',
                border: webhookStatus?.is_enabled ? 'none' : '',
                color: webhookStatus?.is_enabled ? '#fff' : '',
              }}
            >
              {webhookStatus?.is_enabled ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
            </Button>
            <Button icon={<ReloadOutlined />} onClick={() => window.location.reload()}>
              {t('refresh', 'بروزرسانی')}
            </Button>
          </Space>
        }
      >
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 24 }}>
          <div>
            <Text type="secondary">{t('status', 'وضعیت')}:</Text>
            <Badge
              status={webhookStatus?.is_enabled ? 'success' : 'error'}
              text={webhookStatus?.is_enabled ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
              style={{ marginLeft: 8 }}
            />
          </div>
          <div>
            <Text type="secondary">{t('url', 'آدرس')}:</Text>
            <code style={{ background: '#f1f5f9', padding: '2px 8px', borderRadius: 4, marginLeft: 8 }}>
              {webhookStatus?.url || 'http://localhost:8210/api/webhook/appointment'}
            </code>
          </div>
          <div>
            <Text type="secondary">{t('provider', 'پروایدر')}:</Text>
            <span style={{ marginLeft: 8, fontWeight: 600 }}>ISP (ویپ کلینیک)</span>
          </div>
        </div>
      </Card>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
          marginBottom: 16,
        }}
        title={t('webhook_settings', 'تنظیمات وب‌هوک')}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSave}
          size="large"
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Form.Item
                name="is_enabled"
                label={t('status', 'وضعیت')}
                valuePropName="checked"
              >
                <Switch
                  checkedChildren={t('active', 'فعال')}
                  unCheckedChildren={t('inactive', 'غیرفعال')}
                />
              </Form.Item>

              <Form.Item
                name="secret_key"
                label={t('secret_key', 'Secret Key')}
              >
                <Input.Password
                  placeholder={t('secret_key_placeholder', 'my-secret-key-12345678')}
                />
              </Form.Item>
            </Col>
          </Row>

          <Divider />
          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              icon={<SaveOutlined />}
              size="large"
              style={{
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
            >
              {t('save', 'ذخیره')}
            </Button>
          </div>
        </Form>
      </Card>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
        title={t('webhook_logs', 'لاگ‌های وب‌هوک')}
      >
        <Table
          columns={columns}
          dataSource={logs}
          rowKey="id"
          pagination={{
            pageSize: 10,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('records', 'رکورد')}`,
          }}
          locale={{
            emptyText: t('no_logs', 'هیچ لاگی یافت نشد'),
          }}
        />
      </Card>
    </div>
  );
}
