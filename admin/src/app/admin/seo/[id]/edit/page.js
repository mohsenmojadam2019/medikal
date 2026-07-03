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

export default function EditSeoPage() {
  const router = useRouter();
  const params = useParams();
  const seoId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [seo, setSeo] = useState(null);

  useEffect(() => {
    const fetchSeo = async () => {
      try {
        const response = await seoService.getById(seoId);
        setSeo(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching seo:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (seoId) {
      fetchSeo();
    }
  }, [seoId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await seoService.update(seoId, values);
      message.success(t('seo_updated', 'سئو با موفقیت به‌روزرسانی شد'));
      router.push('/admin/seo');
    } catch (error) {
      console.error('Error updating seo:', error);
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
                {t('edit_seo', 'ویرایش سئو')}
              </Title>
              <Text type="secondary">
                {seo?.seoable?.name || seo?.seoable?.title || t('edit_seo_subtitle', 'ویرایش اطلاعات سئو')}
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

              <Divider />

              <Title level={4}>{t('social_media', 'شبکه‌های اجتماعی')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="og_title"
                    label={t('og_title', 'عنوان OG')}
                  >
                    <Input placeholder={t('og_title_placeholder', 'عنوان برای اشتراک‌گذاری')} />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="og_image"
                    label={t('og_image', 'تصویر OG')}
                  >
                    <Input placeholder={t('og_image_placeholder', 'آدرس تصویر')} />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="og_description"
                label={t('og_description', 'توضیحات OG')}
              >
                <TextArea
                  rows={2}
                  placeholder={t('og_description_placeholder', 'توضیحات برای اشتراک‌گذاری...')}
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
                  <SearchOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('seo_info', 'اطلاعات سئو')}</Text>
                  </div>
                </div>

                <Divider />

                <div>
                  <Text type="secondary">{t('page_type', 'نوع صفحه')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {seo?.seoable_type?.replace('App\\Models\\', '') || '—'}
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('page_id', 'شناسه صفحه')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {seo?.seoable_id || '—'}
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('seo_edit_help', 'تغییرات روی سئو اعمال می‌شود')}
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
