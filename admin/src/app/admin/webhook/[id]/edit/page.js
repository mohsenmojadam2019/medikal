'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
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
  Spin,
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

export default function EditWebhookPage() {
  const router = useRouter();
  const params = useParams();
  const webhookId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [webhook, setWebhook] = useState(null);

  useEffect(() => {
    const fetchWebhook = async () => {
      try {
        const response = await webhookService.getById(webhookId);
        setWebhook(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching webhook:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (webhookId) {
      fetchWebhook();
    }
  }, [webhookId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await webhookService.update(webhookId, values);
      message.success(t('webhook_updated', 'وب‌هوک با موفقیت به‌روزرسانی شد'));
      router.push('/admin/webhook');
    } catch (error) {
      console.error('Error updating webhook:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  if (fetchLoading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: 400 }}>
        <Spin size="large" />
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
          <Space>
            <Button
              type="text"
              icon={<ArrowLeftOutlined />}
              onClick={handleBack}
              style={{ fontSize: 18 }}
            />
            <div>
              <Title level={2} style={{ margin: 0 }}>
                {t('edit_webhook', 'ویرایش وب‌هوک')}
              </Title>
              <Text type="secondary">
                {webhook?.name || t('edit_webhook_subtitle', 'ویرایش اطلاعات وب‌هوک')}
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
                    {t('webhook_edit_help', 'تغییرات روی وب‌هوک اعمال می‌شود')}
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
