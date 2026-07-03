'use client';

import { useState } from 'react';
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
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  ApiOutlined,
} from '@ant-design/icons';
import { webhookService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

export default function CreateWebhookPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await webhookService.create(values);
      message.success(t('webhook_created', 'وب‌هوک با موفقیت ایجاد شد'));
      router.push('/admin/webhook');
    } catch (error) {
      console.error('Error creating webhook:', error);
      message.error(t('create_error', 'خطا در ایجاد وب‌هوک'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

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
          <Space>
            <Button
              type="text"
              icon={<ArrowLeftOutlined />}
              onClick={handleBack}
              style={{ fontSize: 18 }}
            />
            <div>
              <Title level={2} style={{ margin: 0 }}>
                {t('new_webhook', 'وب‌هوک جدید')}
              </Title>
              <Text type="secondary">
                {t('create_webhook_subtitle', 'ایجاد وب‌هوک جدید')}
              </Text>
            </div>
          </Space>
        </div>
      </div>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          size="large"
          initialValues={{
            is_enabled: true,
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Form.Item
                name="name"
                label={t('name', 'نام وب‌هوک')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  prefix={<ApiOutlined />}
                  placeholder={t('name_placeholder', 'نام وب‌هوک...')}
                />
              </Form.Item>

              <Form.Item
                name="url"
                label={t('url', 'آدرس')}
                rules={[
                  { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                  { type: 'url', message: t('url_invalid', 'آدرس نامعتبر است') },
                ]}
              >
                <Input placeholder={t('url_placeholder', 'https://example.com/webhook')} />
              </Form.Item>

              <Form.Item
                name="secret_key"
                label={t('secret_key', 'Secret Key')}
              >
                <Input.Password
                  placeholder={t('secret_key_placeholder', 'my-secret-key-12345678')}
                />
              </Form.Item>

              <Form.Item
                name="events"
                label={t('events', 'رویدادها')}
              >
                <Select
                  mode="multiple"
                  placeholder={t('select_events', 'انتخاب رویدادها...')}
                  options={[
                    { value: 'appointment.created', label: 'ایجاد نوبت' },
                    { value: 'appointment.updated', label: 'بروزرسانی نوبت' },
                    { value: 'appointment.cancelled', label: 'لغو نوبت' },
                    { value: 'payment.success', label: 'پرداخت موفق' },
                    { value: 'user.registered', label: 'ثبت نام کاربر' },
                  ]}
                />
              </Form.Item>
            </Col>

            <Col xs={24} lg={8}>
              <Card
                style={{
                  borderRadius: 12,
                  borderColor: '#e8e8f0',
                  background: '#f8fafc',
                }}
              >
                <div style={{ textAlign: 'center', padding: '16px 0' }}>
                  <ApiOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('webhook_info', 'اطلاعات وب‌هوک')}</Text>
                  </div>
                </div>

                <Divider />

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

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('webhook_help', 'وب‌هوک برای اتصال به سیستم‌های خارجی استفاده می‌شود')}
                  </Text>
                </div>
              </Card>
            </Col>
          </Row>

          <Divider />
          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <Button onClick={handleBack} size="large">
              {t('cancel', 'انصراف')}
            </Button>
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
    </div>
  );
}
