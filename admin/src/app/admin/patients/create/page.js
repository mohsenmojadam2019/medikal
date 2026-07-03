// src/app/admin/patients/create/page.js

'use client';

import { useState, useEffect } from 'react';
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
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  UserOutlined,
  PhoneOutlined,
  MailOutlined,
  IdcardOutlined,
} from '@ant-design/icons';
import { patientsService, doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreatePatientPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fileList, setFileList] = useState([]);
  const [previewImage, setPreviewImage] = useState(null);
  const [doctors, setDoctors] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);

  // دریافت لیست پزشکان
  useEffect(() => {
    const fetchDoctors = async () => {
      setLoadingDoctors(true);
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        if (response.data?.success) {
          const doctorsData = response.data.data?.data || response.data.data || [];
          setDoctors(doctorsData);
        }
      } catch (error) {
        console.error('Error fetching doctors:', error);
      } finally {
        setLoadingDoctors(false);
      }
    };
    fetchDoctors();
  }, []);

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
        formData.append('profile_image', fileList[0].originFileObj);
      }

      await patientsService.create(formData);
      message.success(t('patient_created', 'بیمار با موفقیت ایجاد شد'));
      router.push('/admin/patients');
    } catch (error) {
      console.error('Error creating patient:', error);
      message.error(t('create_error', 'خطا در ایجاد بیمار'));
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
      setPreviewImage(null);
    },
    beforeUpload: (file) => {
      if (!file.type.startsWith('image/')) {
        message.error(t('invalid_image', 'لطفاً یک فایل تصویری انتخاب کنید'));
        return false;
      }

      if (file.size > 2 * 1024 * 1024) {
        message.error(t('file_too_large', 'حجم فایل نباید بیشتر از ۲ مگابایت باشد'));
        return false;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        setPreviewImage(e.target.result);
      };
      reader.readAsDataURL(file);

      setFileList([file]);
      return false;
    },
    fileList: fileList.map((file) => ({
      uid: file.uid || '-1',
      name: file.name,
      status: 'done',
      url: previewImage || URL.createObjectURL(file),
    })),
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
                  {t('new_patient', 'بیمار جدید')}
                </Title>
                <Text type="secondary">
                  {t('create_patient_subtitle', 'ایجاد بیمار جدید در کلینیک')}
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
                          placeholder={t('full_name_placeholder', 'مثال: علی محمدی')}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="national_code"
                        label={t('national_code', 'کدملی')}
                        rules={[
                          { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                          { pattern: /^[0-9]{10}$/, message: t('national_code_invalid', 'کدملی باید ۱۰ رقم باشد') },
                        ]}
                    >
                      <Input
                          prefix={<IdcardOutlined />}
                          placeholder={t('national_code_placeholder', 'مثال: ۱۲۳۴۵۶۷۸۹۰')}
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
                          placeholder={t('email_placeholder', 'patient@clinic.com')}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Row gutter={[16, 0]}>
                  <Col xs={24} md={12}>
                    <Form.Item
                        name="doctor_id"
                        label={t('doctor', 'پزشک معالج')}
                    >
                      <Select
                          placeholder={t('select_doctor', 'انتخاب پزشک...')}
                          loading={loadingDoctors}
                          options={doctors.map((doctor) => ({
                            value: doctor.id,
                            label: doctor.full_name || doctor.name || 'بدون نام',
                          }))}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="blood_type"
                        label={t('blood_type', 'گروه خونی')}
                    >
                      <Select
                          placeholder={t('select_blood_type', 'انتخاب گروه خونی...')}
                          options={[
                            { value: 'A+', label: 'A+' },
                            { value: 'A-', label: 'A-' },
                            { value: 'B+', label: 'B+' },
                            { value: 'B-', label: 'B-' },
                            { value: 'AB+', label: 'AB+' },
                            { value: 'AB-', label: 'AB-' },
                            { value: 'O+', label: 'O+' },
                            { value: 'O-', label: 'O-' },
                          ]}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Form.Item
                    name="address"
                    label={t('address', 'آدرس')}
                >
                  <TextArea
                      rows={3}
                      placeholder={t('address_placeholder', 'آدرس کامل...')}
                  />
                </Form.Item>

                <Divider />

                <Title level={4}>{t('additional_info', 'اطلاعات تکمیلی')}</Title>

                <Row gutter={[16, 0]}>
                  <Col xs={24} md={12}>
                    <Form.Item
                        name="date_of_birth"
                        label={t('date_of_birth', 'تاریخ تولد')}
                    >
                      <JalaliDatePicker
                          placeholder={t('date_of_birth_placeholder', 'انتخاب تاریخ تولد')}
                          format="jYYYY/jMM/jDD"
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="gender"
                        label={t('gender', 'جنسیت')}
                    >
                      <Select
                          placeholder={t('select_gender', 'انتخاب جنسیت...')}
                          options={[
                            { value: 'male', label: t('male', 'مرد') },
                            { value: 'female', label: t('female', 'زن') },
                          ]}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Form.Item
                    name="allergies"
                    label={t('allergies', 'آلرژی‌ها')}
                >
                  <Input placeholder={t('allergies_placeholder', 'مثال: پنی‌سیلین، گرده گل...')} />
                </Form.Item>

                <Form.Item
                    name="medical_history"
                    label={t('medical_history', 'سابقه پزشکی')}
                >
                  <TextArea
                      rows={3}
                      placeholder={t('medical_history_placeholder', 'توضیحات درباره سابقه پزشکی...')}
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
                      {previewImage ? (
                          <img
                              src={previewImage}
                              alt="پروفایل"
                              style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                          />
                      ) : fileList.length > 0 && fileList[0].url ? (
                          <img
                              src={fileList[0].url}
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
                      name="is_active"
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
                      {t('patient_help', 'پس از ایجاد، بیمار در لیست بیماران نمایش داده می‌شود')}
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
                    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
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