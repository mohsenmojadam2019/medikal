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
  TimePicker,
  InputNumber,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UserOutlined,
  CalendarOutlined,
  ClockCircleOutlined,
  DollarOutlined,
} from '@ant-design/icons';
import { appointmentsService, doctorsService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateAppointmentPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [loadingPatients, setLoadingPatients] = useState(false);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);

  // ===== دریافت لیست پزشکان =====
  useEffect(() => {
    const fetchDoctors = async () => {
      setLoadingDoctors(true);
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        setDoctors(response.data || []);
      } catch (error) {
        console.error('Error fetching doctors:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست پزشکان'));
      } finally {
        setLoadingDoctors(false);
      }
    };
    fetchDoctors();
  }, [t]);

  // ===== دریافت لیست بیماران =====
  useEffect(() => {
    const fetchPatients = async () => {
      setLoadingPatients(true);
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        setPatients(response.data || []);
      } catch (error) {
        console.error('Error fetching patients:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست بیماران'));
      } finally {
        setLoadingPatients(false);
      }
    };
    fetchPatients();
  }, [t]);

  // ===== دریافت زمان‌های موجود =====
  useEffect(() => {
    const fetchAvailableSlots = async () => {
      const doctorId = form.getFieldValue('doctor_id');
      const date = form.getFieldValue('date');
      
      if (!doctorId || !date) {
        setAvailableSlots([]);
        return;
      }

      try {
        const response = await appointmentsService.getAvailableSlots(doctorId, date);
        setAvailableSlots(response.data || []);
      } catch (error) {
        console.error('Error fetching available slots:', error);
        setAvailableSlots([]);
      }
    };

    fetchAvailableSlots();
  }, [form.getFieldValue('doctor_id'), form.getFieldValue('date')]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await appointmentsService.create(values);
      message.success(t('appointment_created', 'نوبت با موفقیت ایجاد شد'));
      router.push('/admin/appointments');
    } catch (error) {
      console.error('Error creating appointment:', error);
      message.error(t('create_error', 'خطا در ایجاد نوبت'));
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
                {t('new_appointment', 'نوبت جدید')}
              </Title>
              <Text type="secondary">
                {t('create_appointment_subtitle', 'رزرو نوبت جدید در کلینیک')}
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
                        label: `${p.full_name} (${p.national_code || p.phone})`,
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
                        label: `${d.full_name} (${d.specialty?.name || ''})`,
                      }))}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="date"
                    label={t('date', 'تاریخ')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <JalaliDatePicker
                      placeholder={t('select_date', 'انتخاب تاریخ')}
                      format="jYYYY/jMM/jDD"
                      size="large"
                      onChange={(date) => {
                        setSelectedDate(date);
                        form.setFieldsValue({ date });
                      }}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="start_time"
                    label={t('time', 'ساعت')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_time', 'انتخاب ساعت...')}
                      options={availableSlots.map((slot) => ({
                        value: slot,
                        label: slot,
                      }))}
                      disabled={!form.getFieldValue('doctor_id') || !form.getFieldValue('date')}
                    />
                  </Form.Item>
                  {availableSlots.length === 0 && form.getFieldValue('doctor_id') && form.getFieldValue('date') && (
                    <Text type="danger" style={{ fontSize: 12 }}>
                      {t('no_available_slots', 'هیچ زمان خالی برای این تاریخ و پزشک وجود ندارد')}
                    </Text>
                  )}
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="type"
                    label={t('type', 'نوع نوبت')}
                    initialValue="in_person"
                  >
                    <Select
                      options={[
                        { value: 'in_person', label: t('in_person', 'حضوری') },
                        { value: 'online', label: t('online', 'آنلاین') },
                        { value: 'home_visit', label: t('home_visit', 'ویزیت در منزل') },
                      ]}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="fee"
                    label={t('fee', 'هزینه (تومان)')}
                  >
                    <InputNumber
                      prefix={<DollarOutlined />}
                      placeholder={t('fee_placeholder', '۱۵۰۰۰۰')}
                      style={{ width: '100%' }}
                      formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                      parser={(value) => value?.replace(/\$\s?|(,*)/g, '')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('description_placeholder', 'توضیحات نوبت...')}
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
                  <CalendarOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('appointment_info', 'اطلاعات نوبت')}</Text>
                  </div>
                </div>

                <Divider />

                <div>
                  <Text type="secondary">{t('status', 'وضعیت')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    <Badge color="orange" text={t('pending', 'در انتظار')} />
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('created_by', 'ایجادکننده')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {t('admin', 'ادمین')}
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('appointment_help', 'پس از ایجاد نوبت، پیام تایید برای بیمار ارسال می‌شود')}
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
