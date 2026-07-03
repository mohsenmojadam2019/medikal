'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
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
  InputNumber,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  UserOutlined,
  PhoneOutlined,
  MailOutlined,
  IdcardOutlined,
  DollarOutlined,
} from '@ant-design/icons';
import { doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateDoctorPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fileList, setFileList] = useState([]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(values).forEach((key) => {
        if (values[key] !== undefined && values[key] !== null) {
          formData.append(key, values[key]);
        }
      });

      if (fileList.length > 0) {
        formData.append('profile_image', fileList[0].originFileObj);
      }

      await doctorsService.create(formData);
      message.success(t('doctor_created', 'پزشک با موفقیت ایجاد شد'));
      router.push('/admin/doctors');
    } catch (error) {
      console.error('Error creating doctor:', error);
      message.error(t('create_error', 'خطا در ایجاد پزشک'));
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
                {t('new_doctor', 'پزشک جدید')}
              </Title>
              <Text type="secondary">
                {t('create_doctor_subtitle', 'ایجاد پزشک جدید در کلینیک')}
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
            is_available: true,
            is_verified: false,
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Title level={4}>{t('basic_info', 'اطلاعات پایه')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="full_name"
                    label={t('full_name', 'نام و نام خانوادگی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<UserOutlined />}
                      placeholder={t('full_name_placeholder', 'مثال: دکتر علی محمدی')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="license_number"
                    label={t('license_number', 'شماره نظام پزشکی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<IdcardOutlined />}
                      placeholder={t('license_placeholder', 'مثال: ۱۲۳۴۵۶')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="mobile"
                    label={t('mobile', 'شماره موبایل')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { pattern: /^09[0-9]{9}$/, message: t('mobile_invalid', 'شماره موبایل نامعتبر است') },
                    ]}
                  >
                    <Input
                      prefix={<PhoneOutlined />}
                      placeholder={t('mobile_placeholder', '۰۹۱۲۳۴۵۶۷۸۹')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="email"
                    label={t('email', 'ایمیل')}
                    rules={[
                      { type: 'email', message: t('email_invalid', 'ایمیل نامعتبر است') },
                    ]}
                  >
                    <Input
                      prefix={<MailOutlined />}
                      placeholder={t('email_placeholder', 'doctor@clinic.com')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="specialty_id"
                    label={t('specialty', 'تخصص')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_specialty', 'انتخاب تخصص...')}
                      options={[
                        { value: 1, label: 'داخلی' },
                        { value: 2, label: 'قلب و عروق' },
                        { value: 3, label: 'ارتوپدی' },
                        { value: 4, label: 'اعصاب و روان' },
                        { value: 5, label: 'کودکان' },
                        { value: 6, label: 'زنان و زایمان' },
                      ]}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="consultation_fee"
                    label={t('consultation_fee', 'هزینه ویزیت (تومان)')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                    ]}
                  >
                    <Input
                      prefix={<DollarOutlined />}
                      type="number"
                      placeholder={t('fee_placeholder', '۱۵۰۰۰۰')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="bio"
                label={t('bio', 'بیوگرافی')}
              >
                <TextArea
                  rows={4}
                  placeholder={t('bio_placeholder', 'توضیحات درباره پزشک...')}
                />
              </Form.Item>

              <Divider />

              <Title level={4}>{t('additional_info', 'اطلاعات تکمیلی')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="clinic_name"
                    label={t('clinic_name', 'نام مطب')}
                  >
                    <Input placeholder={t('clinic_name_placeholder', 'مطب دکتر محمدی')} />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="clinic_phone"
                    label={t('clinic_phone', 'تلفن مطب')}
                  >
                    <Input placeholder={t('clinic_phone_placeholder', '۰۲۱-۲۲۲۲۲۲۲۲')} />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="clinic_address"
                label={t('clinic_address', 'آدرس مطب')}
              >
                <TextArea
                  rows={2}
                  placeholder={t('clinic_address_placeholder', 'آدرس کامل مطب...')}
                />
              </Form.Item>

              <Form.Item
                name="experience_years"
                label={t('experience_years', 'سال‌های تجربه')}
              >
                <InputNumber
                  style={{ width: '100%' }}
                  min={0}
                  max={100}
                  placeholder={t('experience_placeholder', 'مثال: ۱۰')}
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
                      width: 120,
                      height: 120,
                      margin: '0 auto 16px',
                      borderRadius: '50%',
                      background: '#e2e8f0',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                    }}
                  >
                    {fileList.length > 0 ? (
                      <img
                        src={URL.createObjectURL(fileList[0].originFileObj)}
                        alt="پروفایل"
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    ) : (
                      <UserOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...uploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('upload_photo', 'آپلود عکس')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('photo_size', 'حداکثر ۲ مگابایت')}
                  </Text>
                </div>

                <Divider />

                <Form.Item
                  name="is_available"
                  label={t('status', 'وضعیت')}
                >
                  <Select
                    options={[
                      { value: true, label: t('active', 'فعال') },
                      { value: false, label: t('inactive', 'غیرفعال') },
                    ]}
                  />
                </Form.Item>

                <Form.Item
                  name="is_verified"
                  label={t('verified', 'تایید')}
                >
                  <Select
                    options={[
                      { value: true, label: t('verified', 'تایید شده') },
                      { value: false, label: t('pending_verification', 'در انتظار تایید') },
                    ]}
                  />
                </Form.Item>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('doctor_help', 'پس از ایجاد، پزشک در لیست پزشکان نمایش داده می‌شود')}
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
