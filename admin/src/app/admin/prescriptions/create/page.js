// src/app/admin/prescriptions/create/page.js

'use client';

import { useState, useEffect } from 'react';
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
  InputNumber,
  App,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UserOutlined,
  CalendarOutlined,
  ClockCircleOutlined,
  DollarOutlined,
  MedicineBoxOutlined,
} from '@ant-design/icons';
import { prescriptionsService, doctorsService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreatePrescriptionPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [loadingPatients, setLoadingPatients] = useState(false);

  // ===== دریافت لیست پزشکان =====
  useEffect(() => {
    const fetchDoctors = async () => {
      setLoadingDoctors(true);
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        // ✅ بررسی ساختار پاسخ
        if (response.data?.success) {
          const data = response.data.data;
          const list = data?.data || data || [];
          setDoctors(Array.isArray(list) ? list : []);
        } else {
          setDoctors([]);
        }
      } catch (error) {
        console.error('Error fetching doctors:', error);
        setDoctors([]);
      } finally {
        setLoadingDoctors(false);
      }
    };
    fetchDoctors();
  }, []);

  // ===== دریافت لیست بیماران =====
  useEffect(() => {
    const fetchPatients = async () => {
      setLoadingPatients(true);
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        // ✅ بررسی ساختار پاسخ
        if (response.data?.success) {
          const data = response.data.data;
          const list = data?.data || data || [];
          setPatients(Array.isArray(list) ? list : []);
        } else {
          setPatients([]);
        }
      } catch (error) {
        console.error('Error fetching patients:', error);
        setPatients([]);
      } finally {
        setLoadingPatients(false);
      }
    };
    fetchPatients();
  }, []);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await prescriptionsService.create(values);
      message.success(t('prescription_created', 'نسخه با موفقیت ایجاد شد'));
      router.push('/admin/prescriptions');
    } catch (error) {
      console.error('Error creating prescription:', error);
      message.error(t('create_error', 'خطا در ایجاد نسخه'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  // اگر patients آرایه نیست
  if (!Array.isArray(patients)) {
    console.error('⚠️ Patients is not an array:', patients);
    return (
        <div style={{ padding: 24 }}>
          <Title level={4}>خطا در دریافت اطلاعات</Title>
          <Text type="danger">لطفاً صفحه را مجدداً بارگذاری کنید.</Text>
          <Button onClick={() => window.location.reload()} style={{ marginTop: 16 }}>
            بارگذاری مجدد
          </Button>
        </div>
    );
  }

  // اگر doctors آرایه نیست
  if (!Array.isArray(doctors)) {
    console.error('⚠️ Doctors is not an array:', doctors);
    return (
        <div style={{ padding: 24 }}>
          <Title level={4}>خطا در دریافت اطلاعات</Title>
          <Text type="danger">لطفاً صفحه را مجدداً بارگذاری کنید.</Text>
          <Button onClick={() => window.location.reload()} style={{ marginTop: 16 }}>
            بارگذاری مجدد
          </Button>
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
                  {t('new_prescription', 'نسخه جدید')}
                </Title>
                <Text type="secondary">
                  {t('create_prescription_subtitle', 'ایجاد نسخه جدید')}
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
                status: 'pending',
              }}
          >
            <Row gutter={[24, 0]}>
              <Col xs={24} lg={16}>
                <Row gutter={[16, 0]}>
                  <Col xs={24} md={12}>
                    <Form.Item
                        name="patient_id"
                        label={t('patient', 'بیمار')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                    >
                      <Select
                          placeholder={t('select_patient', 'انتخاب بیمار...')}
                          loading={loadingPatients}
                          showSearch
                          optionFilterProp="children"
                          options={patients.map((p) => ({
                            value: p.id,
                            label: p.name || p.full_name || `بیمار ${p.id}`,
                          }))}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="doctor_id"
                        label={t('doctor', 'پزشک')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                    >
                      <Select
                          placeholder={t('select_doctor', 'انتخاب پزشک...')}
                          loading={loadingDoctors}
                          showSearch
                          optionFilterProp="children"
                          options={doctors.map((d) => ({
                            value: d.id,
                            label: d.name || d.full_name || `پزشک ${d.id}`,
                          }))}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Row gutter={[16, 0]}>
                  <Col xs={24} md={12}>
                    <Form.Item
                        name="drug_name"
                        label={t('drug_name', 'نام دارو')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                    >
                      <Input
                          prefix={<MedicineBoxOutlined />}
                          placeholder={t('drug_name_placeholder', 'مثال: آموکسی‌سیلین')}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="dosage"
                        label={t('dosage', 'دوز مصرف')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                    >
                      <Input
                          placeholder={t('dosage_placeholder', 'مثال: ۵۰۰mg')}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Row gutter={[16, 0]}>
                  <Col xs={24} md={8}>
                    <Form.Item
                        name="frequency"
                        label={t('frequency', 'تعداد در روز')}
                    >
                      <Select
                          placeholder={t('select_frequency', 'انتخاب تعداد...')}
                          options={[
                            { value: 1, label: 'یک بار در روز' },
                            { value: 2, label: 'دو بار در روز' },
                            { value: 3, label: 'سه بار در روز' },
                            { value: 4, label: 'چهار بار در روز' },
                          ]}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={8}>
                    <Form.Item
                        name="duration"
                        label={t('duration', 'مدت (روز)')}
                    >
                      <InputNumber
                          style={{ width: '100%' }}
                          min={1}
                          max={365}
                          placeholder={t('duration_placeholder', '۷')}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={8}>
                    <Form.Item
                        name="start_date"
                        label={t('start_date', 'تاریخ شروع')}
                    >
                      <JalaliDatePicker
                          placeholder={t('select_start_date', 'انتخاب تاریخ شروع')}
                          format="jYYYY/jMM/jDD"
                          size="large"
                          onChange={(date) => {
                            form.setFieldsValue({ start_date: date });
                          }}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Form.Item
                    name="diagnosis"
                    label={t('diagnosis', 'تشخیص')}
                >
                  <Input placeholder={t('diagnosis_placeholder', 'مثال: عفونت تنفسی')} />
                </Form.Item>

                <Form.Item
                    name="instructions"
                    label={t('instructions', 'دستورات مصرف')}
                >
                  <TextArea
                      rows={3}
                      placeholder={t('instructions_placeholder', 'مثال: بعد از غذا مصرف شود')}
                  />
                </Form.Item>

                <Form.Item
                    name="notes"
                    label={t('notes', 'توضیحات اضافی')}
                >
                  <TextArea
                      rows={2}
                      placeholder={t('notes_placeholder', 'توضیحات اضافی...')}
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
                    <MedicineBoxOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                    <div style={{ marginTop: 8 }}>
                      <Text type="secondary">{t('prescription_info', 'اطلاعات نسخه')}</Text>
                    </div>
                  </div>

                  <Divider />

                  <Form.Item
                      name="status"
                      label={t('status', 'وضعیت')}
                  >
                    <Select
                        options={[
                          { value: 'pending', label: t('pending', 'در انتظار') },
                          { value: 'active', label: t('active', 'فعال') },
                          { value: 'completed', label: t('completed', 'تکمیل شده') },
                          { value: 'cancelled', label: t('cancelled', 'لغو شده') },
                        ]}
                    />
                  </Form.Item>

                  <Divider />

                  <div style={{ textAlign: 'center' }}>
                    <Text type="secondary" style={{ fontSize: 12 }}>
                      {t('prescription_help', 'پس از ایجاد نسخه، برای بیمار ارسال می‌شود')}
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