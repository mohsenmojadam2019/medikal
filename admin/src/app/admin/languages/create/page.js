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
  GlobalOutlined,
} from '@ant-design/icons';
import { languageService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

export default function CreateLanguagePage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await languageService.createLanguage(values);
      message.success(t('language_created', 'زبان با موفقیت ایجاد شد'));
      router.push('/admin/languages');
    } catch (error) {
      console.error('Error creating language:', error);
      message.error(t('create_error', 'خطا در ایجاد زبان'));
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
                {t('new_language', 'زبان جدید')}
              </Title>
              <Text type="secondary">
                {t('create_language_subtitle', 'ایجاد زبان جدید در سیستم')}
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
            is_active: true,
            direction: 'rtl',
            sort_order: 0,
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="code"
                    label={t('code', 'کد زبان')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { pattern: /^[a-z]{2}$/, message: t('code_invalid', 'کد زبان باید ۲ حرف کوچک باشد') },
                    ]}
                  >
                    <Input
                      prefix={<GlobalOutlined />}
                      placeholder={t('code_placeholder', 'مثال: fa')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="name"
                    label={t('name', 'نام زبان')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input placeholder={t('name_placeholder', 'مثال: فارسی')} />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="native_name"
                    label={t('native_name', 'نام بومی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input placeholder={t('native_name_placeholder', 'مثال: فارسی')} />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="sort_order"
                    label={t('sort_order', 'ترتیب')}
                  >
                    <InputNumber
                      style={{ width: '100%' }}
                      min={0}
                      placeholder="۰"
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="direction"
                label={t('direction', 'جهت')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Select
                  options={[
                    { value: 'rtl', label: t('rtl', 'راست‌چین (RTL)') },
                    { value: 'ltr', label: t('ltr', 'چپ‌چین (LTR)') },
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
                  <GlobalOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('language_info', 'اطلاعات زبان')}</Text>
                  </div>
                </div>

                <Divider />

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

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('language_help', 'پس از ایجاد، زبان در لیست زبان‌ها نمایش داده می‌شود')}
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
