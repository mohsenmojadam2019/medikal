'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  Upload,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Spin,
  ColorPicker,
  Switch,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  HospitalOutlined,
  PhoneOutlined,
  MailOutlined,
  GlobalOutlined,
} from '@ant-design/icons';
import { clinicService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditClinicPage() {
  const router = useRouter();
  const params = useParams();
  const clinicId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [clinic, setClinic] = useState(null);
  const [logoFileList, setLogoFileList] = useState([]);
  const [faviconFileList, setFaviconFileList] = useState([]);
  const [primaryColor, setPrimaryColor] = useState('#2563eb');
  const [secondaryColor, setSecondaryColor] = useState('#f59e0b');

  useEffect(() => {
    const fetchClinic = async () => {
      try {
        const response = await clinicService.getById(clinicId);
        setClinic(response.data);
        form.setFieldsValue(response.data);
        setPrimaryColor(response.data.primary_color || '#2563eb');
        setSecondaryColor(response.data.secondary_color || '#f59e0b');
        if (response.data.logo_url) {
          setLogoFileList([
            {
              uid: '-1',
              name: 'logo',
              status: 'done',
              url: response.data.logo_url,
            },
          ]);
        }
        if (response.data.favicon_url) {
          setFaviconFileList([
            {
              uid: '-1',
              name: 'favicon',
              status: 'done',
              url: response.data.favicon_url,
            },
          ]);
        }
      } catch (error) {
        console.error('Error fetching clinic:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (clinicId) {
      fetchClinic();
    }
  }, [clinicId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(values).forEach((key) => {
        if (values[key] !== undefined && values[key] !== null) {
          formData.append(key, values[key]);
        }
      });

      formData.append('primary_color', primaryColor);
      formData.append('secondary_color', secondaryColor);

      if (logoFileList.length > 0 && logoFileList[0].originFileObj) {
        formData.append('logo', logoFileList[0].originFileObj);
      }

      if (faviconFileList.length > 0 && faviconFileList[0].originFileObj) {
        formData.append('favicon', faviconFileList[0].originFileObj);
      }

      await clinicService.update(clinicId, formData);
      message.success(t('updated', 'اطلاعات کلینیک با موفقیت به‌روزرسانی شد'));
      router.push('/admin/clinic');
    } catch (error) {
      console.error('Error updating clinic:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  const logoUploadProps = {
    onRemove: () => {
      setLogoFileList([]);
    },
    beforeUpload: (file) => {
      setLogoFileList([file]);
      return false;
    },
    fileList: logoFileList,
    maxCount: 1,
    accept: 'image/*',
  };

  const faviconUploadProps = {
    onRemove: () => {
      setFaviconFileList([]);
    },
    beforeUpload: (file) => {
      setFaviconFileList([file]);
      return false;
    },
    fileList: faviconFileList,
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
                {t('edit_clinic', 'ویرایش کلینیک')}
              </Title>
              <Text type="secondary">
                {clinic?.name || t('edit_clinic_subtitle', 'ویرایش اطلاعات کلینیک')}
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
                    name="name"
                    label={t('clinic_name', 'نام کلینیک')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<HospitalOutlined />}
                      placeholder={t('clinic_name_placeholder', 'نام کلینیک...')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="phone"
                    label={t('phone', 'تلفن')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<PhoneOutlined />}
                      placeholder={t('phone_placeholder', '۰۲۱-۲۲۲۲۲۲۲۲')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="email"
                    label={t('email', 'ایمیل')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { type: 'email', message: t('email_invalid', 'ایمیل نامعتبر است') },
                    ]}
                  >
                    <Input
                      prefix={<MailOutlined />}
                      placeholder={t('email_placeholder', 'info@clinic.com')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="website"
                    label={t('website', 'وبسایت')}
                  >
                    <Input
                      prefix={<GlobalOutlined />}
                      placeholder={t('website_placeholder', 'https://clinic.com')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="address"
                label={t('address', 'آدرس')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <TextArea
                  rows={3}
                  placeholder={t('address_placeholder', 'آدرس کامل کلینیک...')}
                />
              </Form.Item>

              <Divider />

              <Row gutter={[16, 0]}>
                <Col xs={24} md={8}>
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

                <Col xs={24} md={8}>
                  <Form.Item
                    name="invoice_prefix"
                    label={t('invoice_prefix', 'پیشوند فاکتور')}
                  >
                    <Input placeholder="INV" />
                  </Form.Item>
                </Col>

                <Col xs={24} md={8}>
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
                    name="timezone"
                    label={t('timezone', 'منطقه زمانی')}
                  >
                    <Select
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
                  >
                    <Select
                      options={[
                        { value: 'تومان', label: 'تومان' },
                        { value: 'ریال', label: 'ریال' },
                        { value: 'دلار', label: 'دلار' },
                      ]}
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
                <div style={{ textAlign: 'center' }}>
                  <div
                    style={{
                      width: 120,
                      height: 120,
                      margin: '0 auto 16px',
                      borderRadius: 12,
                      background: '#e2e8f0',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                    }}
                  >
                    {logoFileList.length > 0 ? (
                      <img
                        src={logoFileList[0].url || URL.createObjectURL(logoFileList[0].originFileObj)}
                        alt="لوگو"
                        style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                      />
                    ) : (
                      <HospitalOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...logoUploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('change_logo', 'تغییر لوگو')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('logo_size', 'حداکثر ۲ مگابایت')}
                  </Text>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <div
                    style={{
                      width: 64,
                      height: 64,
                      margin: '0 auto 16px',
                      borderRadius: 12,
                      background: '#e2e8f0',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                    }}
                  >
                    {faviconFileList.length > 0 ? (
                      <img
                        src={faviconFileList[0].url || URL.createObjectURL(faviconFileList[0].originFileObj)}
                        alt="فاویکون"
                        style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                      />
                    ) : (
                      <HospitalOutlined style={{ fontSize: 24, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...faviconUploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('change_favicon', 'تغییر فاویکون')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('favicon_size', 'حداکثر ۱ مگابایت')}
                  </Text>
                </div>

                <Divider />

                <Form.Item
                  name="is_active"
                  label={t('status', 'وضعیت کلینیک')}
                  valuePropName="checked"
                >
                  <Switch
                    checkedChildren={t('active', 'فعال')}
                    unCheckedChildren={t('inactive', 'غیرفعال')}
                  />
                </Form.Item>

                <Divider />

                <div>
                  <Text type="secondary">{t('primary_color', 'رنگ اصلی')}</Text>
                  <div style={{ marginTop: 8 }}>
                    <ColorPicker
                      value={primaryColor}
                      onChange={(value) => setPrimaryColor(value.toHexString())}
                      showText
                    />
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('secondary_color', 'رنگ ثانویه')}</Text>
                  <div style={{ marginTop: 8 }}>
                    <ColorPicker
                      value={secondaryColor}
                      onChange={(value) => setSecondaryColor(value.toHexString())}
                      showText
                    />
                  </div>
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
