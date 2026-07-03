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
  InputNumber,
} from 'antd';
import {
  SaveOutlined,
  SettingOutlined,
  ClockCircleOutlined,
  DollarOutlined,
  GlobalOutlined,
} from '@ant-design/icons';
import { settingsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';

const { Title, Text } = Typography;

export default function SettingsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await settingsService.getSettings();
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching settings:', error);
        message.error(t('fetch_error', 'خطا در دریافت تنظیمات'));
      } finally {
        setFetchLoading(false);
      }
    };
    fetchSettings();
  }, [form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await settingsService.updateSettings(values);
      message.success(t('settings_saved', 'تنظیمات با موفقیت ذخیره شد'));
    } catch (error) {
      console.error('Error saving settings:', error);
      message.error(t('save_error', 'خطا در ذخیره تنظیمات'));
    } finally {
      setLoading(false);
    }
  };

  if (fetchLoading) {
    return <Loading text={t('loading_settings', 'در حال بارگذاری تنظیمات...')} />;
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
            {t('settings', 'تنظیمات')}
          </Title>
          <Text type="secondary">
            {t('settings_subtitle', 'تنظیمات عمومی سیستم')}
          </Text>
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
                    {t('settings_help', 'تغییرات تنظیمات روی کل سیستم تأثیر می‌گذارد')}
                  </Text>
                </div>
              </Card>
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
    </div>
  );
}
