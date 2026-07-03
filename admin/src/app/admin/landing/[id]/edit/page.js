'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Upload,
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
  UploadOutlined,
  HomeOutlined,
} from '@ant-design/icons';
import { landingService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditLandingPage() {
  const router = useRouter();
  const params = useParams();
  const landingId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [landing, setLanding] = useState(null);
  const [fileList, setFileList] = useState([]);

  useEffect(() => {
    const fetchLanding = async () => {
      try {
        const response = await landingService.getById(landingId);
        setLanding(response.data);
        form.setFieldsValue(response.data);
        if (response.data.hero_image) {
          setFileList([
            {
              uid: '-1',
              name: 'hero_image',
              status: 'done',
              url: response.data.hero_image,
            },
          ]);
        }
      } catch (error) {
        console.error('Error fetching landing:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (landingId) {
      fetchLanding();
    }
  }, [landingId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(values).forEach((key) => {
        if (values[key] !== undefined && values[key] !== null) {
          formData.append(key, values[key]);
        }
      });

      if (fileList.length > 0 && fileList[0].originFileObj) {
        formData.append('hero_image', fileList[0].originFileObj);
      }

      await landingService.update(landingId, formData);
      message.success(t('updated', 'صفحه اصلی با موفقیت به‌روزرسانی شد'));
      router.push('/admin/landing');
    } catch (error) {
      console.error('Error updating landing:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  const uploadProps = {
    onRemove: () => {
      setFileList([]);
    },
    beforeUpload: (file) => {
      setFileList([file]);
      return false;
    },
    fileList,
    maxCount: 1,
    accept: 'image/*',
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
                {t('edit_landing', 'ویرایش صفحه اصلی')}
              </Title>
              <Text type="secondary">
                {t('edit_landing_subtitle', 'ویرایش محتوای صفحه اصلی')}
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
                name="hero_title"
                label={t('hero_title', 'عنوان اصلی')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  prefix={<HomeOutlined />}
                  placeholder={t('hero_title_placeholder', 'عنوان اصلی صفحه...')}
                />
              </Form.Item>

              <Form.Item
                name="hero_subtitle"
                label={t('hero_subtitle', 'زیر عنوان')}
              >
                <Input placeholder={t('hero_subtitle_placeholder', 'زیر عنوان صفحه...')} />
              </Form.Item>

              <Form.Item
                name="hero_description"
                label={t('hero_description', 'متن معرفی')}
              >
                <TextArea
                  rows={4}
                  placeholder={t('hero_description_placeholder', 'متن معرفی صفحه اصلی...')}
                />
              </Form.Item>

              <Divider />

              <Title level={4}>{t('seo_settings', 'تنظیمات سئو')}</Title>

              <Form.Item
                name="seo_title"
                label={t('seo_title', 'عنوان سئو')}
              >
                <Input placeholder={t('seo_title_placeholder', 'عنوان برای موتورهای جستجو...')} />
              </Form.Item>

              <Form.Item
                name="seo_description"
                label={t('seo_description', 'توضیحات سئو')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('seo_description_placeholder', 'توضیحات برای موتورهای جستجو...')}
                />
              </Form.Item>

              <Form.Item
                name="seo_keywords"
                label={t('seo_keywords', 'کلمات کلیدی')}
              >
                <Input placeholder={t('seo_keywords_placeholder', 'کلمه کلیدی ۱، کلمه کلیدی ۲')} />
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
                <div style={{ textAlign: 'center' }}>
                  <div
                    style={{
                      width: '100%',
                      height: 150,
                      background: '#e2e8f0',
                      borderRadius: 8,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                      marginBottom: 16,
                    }}
                  >
                    {fileList.length > 0 ? (
                      <img
                        src={fileList[0].url || URL.createObjectURL(fileList[0].originFileObj)}
                        alt="تصویر هدر"
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    ) : (
                      <HomeOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...uploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('change_hero_image', 'تغییر تصویر')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('image_size', 'حداکثر ۵ مگابایت')}
                  </Text>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('landing_edit_help', 'تغییرات روی صفحه اصلی اعمال می‌شود')}
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
