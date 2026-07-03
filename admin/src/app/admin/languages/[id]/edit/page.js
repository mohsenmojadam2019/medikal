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

export default function EditLanguagePage() {
  const router = useRouter();
  const params = useParams();
  const languageId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [language, setLanguage] = useState(null);

  useEffect(() => {
    const fetchLanguage = async () => {
      try {
        const response = await languageService.getLanguageById(languageId);
        setLanguage(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching language:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (languageId) {
      fetchLanguage();
    }
  }, [languageId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await languageService.updateLanguage(languageId, values);
      message.success(t('language_updated', 'زبان با موفقیت به‌روزرسانی شد'));
      router.push('/admin/languages');
    } catch (error) {
      console.error('Error updating language:', error);
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
                {t('edit_language', 'ویرایش زبان')}
              </Title>
              <Text type="secondary">
                {language?.name || t('edit_language_subtitle', 'ویرایش اطلاعات زبان')}
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
                    {t('language_edit_help', 'تغییرات روی اطلاعات زبان اعمال می‌شود')}
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
