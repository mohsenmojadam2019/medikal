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
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  SearchOutlined,
} from '@ant-design/icons';
import { seoService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateSeoPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await seoService.create(values);
      message.success(t('seo_created', 'سئو با موفقیت ایجاد شد'));
      router.push('/admin/seo');
    } catch (error) {
      console.error('Error creating seo:', error);
      message.error(t('create_error', 'خطا در ایجاد سئو'));
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
                {t('new_seo', 'سئو جدید')}
              </Title>
              <Text type="secondary">
                {t('create_seo_subtitle', 'ایجاد سئو برای صفحه')}
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
            robots: 'index, follow',
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Form.Item
                name="seoable_type"
                label={t('page_type', 'نوع صفحه')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Select
                  placeholder={t('select_page_type', 'انتخاب نوع صفحه...')}
                  options={[
                    { value: 'App\\Models\\Doctor', label: 'پزشک' },
                    { value: 'App\\Models\\Patient', label: 'بیمار' },
                    { value: 'App\\Models\\BlogPost', label: 'مقاله' },
                    { value: 'App\\Models\\Page', label: 'صفحه' },
                  ]}
                />
              </Form.Item>

              <Form.Item
                name="seoable_id"
                label={t('page_id', 'شناسه صفحه')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  type="number"
                  placeholder={t('page_id_placeholder', 'مثال: ۱')}
                />
              </Form.Item>

              <Form.Item
                name="title"
                label={t('title', 'عنوان')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  prefix={<SearchOutlined />}
                  placeholder={t('title_placeholder', 'عنوان صفحه...')}
                />
              </Form.Item>

              <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <TextArea
                  rows={3}
                  placeholder={t('description_placeholder', 'توضیحات صفحه...')}
                />
              </Form.Item>

              <Form.Item
                name="keywords"
                label={t('keywords', 'کلمات کلیدی')}
              >
                <Input placeholder={t('keywords_placeholder', 'کلمه کلیدی ۱، کلمه کلیدی ۲')} />
              </Form.Item>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="robots"
                    label={t('robots', 'ربات‌ها')}
                  >
                    <Select
                      options={[
                        { value: 'index, follow', label: 'index, follow' },
                        { value: 'noindex, follow', label: 'noindex, follow' },
                        { value: 'index, nofollow', label: 'index, nofollow' },
                        { value: 'noindex, nofollow', label: 'noindex, nofollow' },
                      ]}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="canonical_url"
                    label={t('canonical', 'لینک اصلی')}
                  >
                    <Input placeholder={t('canonical_placeholder', 'https://clinic.com/page')} />
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
                  <SearchOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('seo_info', 'اطلاعات سئو')}</Text>
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('seo_help', 'سئو برای بهینه‌سازی موتورهای جستجو استفاده می‌شود')}
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
