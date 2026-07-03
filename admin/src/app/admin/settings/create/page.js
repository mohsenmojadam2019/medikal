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
  InputNumber,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  SettingOutlined,
  ClockCircleOutlined,
  DollarOutlined,
} from '@ant-design/icons';
import { settingsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

export default function CreateSettingsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await settingsService.create(values);
      message.success(t('settings_created', 'تنظیمات با موفقیت ایجاد شد'));
      router.push('/admin/settings');
    } catch (error) {
      console.error('Error creating settings:', error);
      message.error(t('create_error', 'خطا در ایجاد تنظیمات'));
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
                {t('new_settings', 'تنظیمات جدید')}
              </Title>
              <Text type="secondary">
                {t('create_settings_subtitle', 'ایجاد تنظیمات سیستم')}
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
            timezone: 'Asia/Tehran',
            currency: 'تومان',
            invoice_prefix: 'INV',
            appointment_prefix: 'APP',
            tax_rate: 9,
            slot_duration: 30,
            maintenance_mode: false,
            debug_mode: false,
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Title level={4}>{t('general_settings', 'تنظیمات عمومی')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="timezone"
                    label={t('timezone', 'منطقه زمانی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      prefix={<ClockCircleOutlined />}
                      options={[
                        { value: 'Asia/Tehran', label: 'Asia/Tehran' },
                        { value: 'UTC', label: 'UTC' },
                        { value: 'America/New_York', label: 'America/New_York' },
                      ]}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="currency"
                    label={t('currency', 'واحد پول')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      prefix={<DollarOutlined />}
                      options={[
                        { value: 'تومان', label: 'تومان' },
                        { value: 'ریال', label: 'ریال' },
                        { value: 'دلار', label: 'دلار' },
                      ]}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="invoice_prefix"
                    label={t('invoice_prefix', 'پیشوند فاکتور')}
                  >
                    <Input placeholder="INV" />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="appointment_prefix"
                    label={t('appointment_prefix', 'پیشوند نوبت')}
                  >
                    <Input placeholder="APP" />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="tax_rate"
                    label={t('tax_rate', 'نرخ مالیات (%)')}
                  >
                    <InputNumber
                      style={{ width: '100%' }}
                      min={0}
                      max={100}
                      placeholder="۹"
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="slot_duration"
                    label={t('slot_duration', 'مدت زمان هر نوبت (دقیقه)')}
                  >
                    <InputNumber
                      style={{ width: '100%' }}
                      min={5}
                      max={120}
                      step={5}
                      placeholder="۳۰"
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Divider />

              <Title level={4}>{t('system_settings', 'تنظیمات سیستم')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="maintenance_mode"
                    label={t('maintenance_mode', 'حالت نگهداری')}
                    valuePropName="checked"
                  >
                    <Switch
                      checkedChildren={t('on', 'روشن')}
                      unCheckedChildren={t('off', 'خاموش')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="debug_mode"
                    label={t('debug_mode', 'حالت دیباگ')}
                    valuePropName="checked"
                  >
                    <Switch
                      checkedChildren={t('on', 'روشن')}
                      unCheckedChildren={t('off', 'خاموش')}
                    />
                  </Form.Item>
                </Col>
              </Row>
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
                  <SettingOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('settings_summary', 'خلاصه تنظیمات')}</Text>
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('settings_help', 'تنظیمات روی کل سیستم تأثیر می‌گذارد')}
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
