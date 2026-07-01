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
import { patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditPatientPage() {
  const router = useRouter();
  const params = useParams();
  const patientId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [patient, setPatient] = useState(null);
  const [fileList, setFileList] = useState([]);

  useEffect(() => {
    const fetchPatient = async () => {
      try {
        const response = await patientsService.getById(patientId);
        setPatient(response.data);
        form.setFieldsValue({
          ...response.data,
          birth_date: response.data.birth_date,
        });
        if (response.data.profile_image) {
          setFileList([
            {
              uid: '-1',
              name: 'profile_image',
              status: 'done',
              url: response.data.profile_image,
            },
          ]);
        }
      } catch (error) {
        console.error('Error fetching patient:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (patientId) {
      fetchPatient();
    }
  }, [patientId, form, t]);

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

      await patientsService.update(patientId, formData);
      message.success(t('updated', 'اطلاعات با موفقیت به‌روزرسانی شد'));
      router.push('/admin/patients');
    } catch (error) {
      console.error('Error updating patient:', error);
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
                {t('edit_patient', 'ویرایش بیمار')}
              </Title>
              <Text type="secondary">
                {patient?.full_name || t('edit_patient_subtitle', 'ویرایش اطلاعات بیمار')}
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
                      placeholder={t('full_name_placeholder', 'مثال: رضا کریمی')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="national_code"
                    label={t('national_code', 'کدملی')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { len: 10, message: t('national_code_invalid', 'کدملی باید ۱۰ رقم باشد') },
                    ]}
                  >
                    <Input
                      prefix={<IdcardOutlined />}
                      placeholder={t('national_code_placeholder', '۱۲۳۴۵۶۷۸۹۰')}
                      maxLength={10}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="phone"
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
                    name="birth_date"
                    label={t('birth_date', 'تاریخ تولد')}
                  >
                    <JalaliDatePicker
                      placeholder={t('select_birth_date', 'انتخاب تاریخ تولد')}
                      format="jYYYY/jMM/jDD"
                      size="large"
                      value={form.getFieldValue('birth_date')}
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
                  rows={2}
                  placeholder={t('address_placeholder', 'آدرس کامل...')}
                />
              </Form.Item>

              <Divider />

              <Title level={4}>{t('medical_info', 'اطلاعات پزشکی')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="doctor_id"
                    label={t('doctor', 'پزشک معالج')}
                  >
                    <Select
                      placeholder={t('select_doctor', 'انتخاب پزشک...')}
                      options={[
                        { value: 1, label: 'دکتر علی محمدی' },
                        { value: 2, label: 'دکتر سارا محمدی' },
                        { value: 3, label: 'دکتر علی رضایی' },
                      ]}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="allergies"
                    label={t('allergies', 'حساسیت‌ها')}
                  >
                    <Input
                      placeholder={t('allergies_placeholder', 'مثال: پنی‌سیلین، گرده گل...')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="medical_history"
                label={t('medical_history', 'سابقه پزشکی')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('medical_history_placeholder', 'سابقه بیماری‌ها، جراحی‌ها و ...')}
                />
              </Form.Item>
            </Col>

            <Col xs={24} lg={8}>
              <Card
                style={{
                  borderRadius: 12,
                  borderColor: '#e8e8f0',
                }}
              >
                <div style={{ textAlign: 'center' }}>
                  <div
                    style={{
                      width: 120,
                      height: 120,
                      margin: '0 auto 16px',
                      borderRadius: '50%',
                      background: '#f0f2f5',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                    }}
                  >
                    {fileList.length > 0 ? (
                      <img
                        src={fileList[0].url || URL.createObjectURL(fileList[0].originFileObj)}
                        alt="پروفایل"
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    ) : (
                      <UserOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...uploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('change_photo', 'تغییر عکس')}
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
