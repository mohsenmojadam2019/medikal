'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
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
  Switch,
  InputNumber,
  Tabs,
  Image,
  Statistic,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  HomeOutlined,
  UserOutlined,
  TeamOutlined,
  CalendarOutlined,
  StarOutlined,
} from '@ant-design/icons';
import { landingService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function LandingPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [fileList, setFileList] = useState([]);
  const [stats, setStats] = useState(null);

  // ===== دریافت اطلاعات صفحه اصلی =====
  useEffect(() => {
    const fetchData = async () => {
      try {
        const [landingRes, statsRes] = await Promise.all([
          landingService.getSettings(),
          landingService.getStats(),
        ]);
        form.setFieldsValue(landingRes.data);
        setStats(statsRes.data);
        if (landingRes.data.hero_image) {
          setFileList([
            {
              uid: '-1',
              name: 'hero_image',
              status: 'done',
              url: landingRes.data.hero_image,
            },
          ]);
        }
      } catch (error) {
        console.error('Error fetching landing data:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };
    fetchData();
  }, [form, t]);

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

      await landingService.update(formData);
      message.success(t('updated', 'صفحه اصلی با موفقیت به‌روزرسانی شد'));
    } catch (error) {
      console.error('Error updating landing:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setLoading(false);
    }
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
    return <Loading text={t('loading_landing', 'در حال بارگذاری صفحه اصلی...')} />;
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
            {t('landing_management', 'مدیریت صفحه اصلی')}
          </Title>
          <Text type="secondary">
            {t('landing_subtitle', 'مدیریت محتوای صفحه اصلی')}
          </Text>
        </div>
      </div>

      {/* ===== آمار ===== */}
      {stats && (
        <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
          <Col xs={24} sm={12} md={6}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('total_doctors', 'پزشکان')}
                value={stats.doctors_count || 0}
                prefix={<UserOutlined style={{ color: '#2563eb' }} />}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={6}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('total_patients', 'بیماران')}
                value={stats.patients_count || 0}
                prefix={<TeamOutlined style={{ color: '#10b981' }} />}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={6}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('total_appointments', 'نوبت‌ها')}
                value={stats.appointments_count || 0}
                prefix={<CalendarOutlined style={{ color: '#f59e0b' }} />}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={6}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('average_rating', 'میانگین امتیاز')}
                value={stats.average_rating || 0}
                precision={1}
                prefix={<StarOutlined style={{ color: '#ef4444' }} />}
              />
            </Card>
          </Col>
        </Row>
      )}

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Tabs
          items={[
            {
              key: 'hero',
              label: t('hero_section', 'بخش هدر'),
              children: (
                <Form form={form} layout="vertical" onFinish={handleSubmit} size="large">
                  <Row gutter={[24, 0]}>
                    <Col xs={24} lg={16}>
                      <Form.Item
                        name="hero_title"
                        label={t('hero_title', 'عنوان اصلی')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                      >
                        <Input placeholder={t('hero_title_placeholder', 'عنوان اصلی صفحه...')} />
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
                              {t('upload_hero_image', 'آپلود تصویر')}
                            </Button>
                          </Upload>
                          <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                            {t('image_size', 'حداکثر ۵ مگابایت')}
                          </Text>
                        </div>
                      </Card>
                    </Col>
                  </Row>

                  <Divider />

                  <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                    <Button type="primary" htmlType="submit" loading={loading} icon={<SaveOutlined />}>
                      {t('save', 'ذخیره')}
                    </Button>
                  </div>
                </Form>
              ),
            },
            {
              key: 'stats',
              label: t('stats_section', 'آمار'),
              children: (
                <Form form={form} layout="vertical" onFinish={handleSubmit} size="large">
                  <Row gutter={[16, 0]}>
                    <Col xs={24} md={6}>
                      <Form.Item
                        name="stat_doctors"
                        label={t('doctors_count', 'تعداد پزشکان')}
                      >
                        <InputNumber style={{ width: '100%' }} min={0} />
                      </Form.Item>
                    </Col>
                    <Col xs={24} md={6}>
                      <Form.Item
                        name="stat_patients"
                        label={t('patients_count', 'تعداد بیماران')}
                      >
                        <InputNumber style={{ width: '100%' }} min={0} />
                      </Form.Item>
                    </Col>
                    <Col xs={24} md={6}>
                      <Form.Item
                        name="stat_appointments"
                        label={t('appointments_count', 'تعداد نوبت‌ها')}
                      >
                        <InputNumber style={{ width: '100%' }} min={0} />
                      </Form.Item>
                    </Col>
                    <Col xs={24} md={6}>
                      <Form.Item
                        name="stat_experience"
                        label={t('experience_years', 'سال تجربه')}
                      >
                        <InputNumber style={{ width: '100%' }} min={0} />
                      </Form.Item>
                    </Col>
                  </Row>

                  <Divider />

                  <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                    <Button type="primary" htmlType="submit" loading={loading} icon={<SaveOutlined />}>
                      {t('save', 'ذخیره')}
                    </Button>
                  </div>
                </Form>
              ),
            },
            {
              key: 'seo',
              label: t('seo', 'سئو'),
              children: (
                <Form form={form} layout="vertical" onFinish={handleSubmit} size="large">
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

                  <Divider />

                  <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                    <Button type="primary" htmlType="submit" loading={loading} icon={<SaveOutlined />}>
                      {t('save', 'ذخیره')}
                    </Button>
                  </div>
                </Form>
              ),
            },
          ]}
        />
      </Card>
    </div>
  );
}
