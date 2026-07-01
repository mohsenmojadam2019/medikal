'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
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
  Switch,
  Alert,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  ClockCircleOutlined,
  CalendarOutlined,
  CopyOutlined,
} from '@ant-design/icons';
import { schedulesService, doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function CreateSchedulePage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const doctorIdParam = searchParams.get('doctor_id');
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(doctorIdParam ? parseInt(doctorIdParam) : null);

  // ===== دریافت لیست پزشکان =====
  useEffect(() => {
    const fetchDoctors = async () => {
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        setDoctors(response.data || []);
      } catch (error) {
        console.error('Error fetching doctors:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست پزشکان'));
      }
    };
    fetchDoctors();
  }, [t]);

  // ===== روزهای هفته =====
  const daysOfWeek = [
    { value: 'saturday', label: t('saturday', 'شنبه') },
    { value: 'sunday', label: t('sunday', 'یکشنبه') },
    { value: 'monday', label: t('monday', 'دوشنبه') },
    { value: 'tuesday', label: t('tuesday', 'سه‌شنبه') },
    { value: 'wednesday', label: t('wednesday', 'چهارشنبه') },
    { value: 'thursday', label: t('thursday', 'پنج‌شنبه') },
    { value: 'friday', label: t('friday', 'جمعه') },
  ];

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await schedulesService.create({
        ...values,
        doctor_id: selectedDoctor,
      });
      message.success(t('schedule_created', 'زمان‌بندی با موفقیت ایجاد شد'));
      router.push('/admin/schedules');
    } catch (error) {
      console.error('Error creating schedule:', error);
      message.error(t('create_error', 'خطا در ایجاد زمان‌بندی'));
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
                {t('new_schedule', 'زمان‌بندی جدید')}
              </Title>
              <Text type="secondary">
                {t('create_schedule_subtitle', 'تنظیم زمان‌بندی پزشک')}
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
            slot_duration: 30,
            max_appointments: 20,
          }}
        >
          <Alert
            message={t('schedule_info', 'تنظیمات زمان‌بندی برای پزشک انتخاب شده اعمال می‌شود')}
            type="info"
            showIcon
            style={{ marginBottom: 16 }}
          />

          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="doctor_id"
                    label={t('doctor', 'پزشک')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_doctor', 'انتخاب پزشک...')}
                      showSearch
                      optionFilterProp="children"
                      value={selectedDoctor}
                      onChange={setSelectedDoctor}
                      options={doctors.map((d) => ({
                        value: d.id,
                        label: `${d.full_name} (${d.specialty?.name || ''})`,
                      }))}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="day_of_week"
                    label={t('day', 'روز هفته')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_day', 'انتخاب روز...')}
                      options={daysOfWeek}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="start_time"
                    label={t('start_time', 'ساعت شروع')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <TimePicker
                      format="HH:mm"
                      style={{ width: '100%' }}
                      placeholder="۰۸:۰۰"
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="end_time"
                    label={t('end_time', 'ساعت پایان')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <TimePicker
                      format="HH:mm"
                      style={{ width: '100%' }}
                      placeholder="۱۴:۰۰"
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="break_start"
                    label={t('break_start', 'شروع استراحت')}
                  >
                    <TimePicker
                      format="HH:mm"
                      style={{ width: '100%' }}
                      placeholder="۱۲:۰۰"
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="break_end"
                    label={t('break_end', 'پایان استراحت')}
                  >
                    <TimePicker
                      format="HH:mm"
                      style={{ width: '100%' }}
                      placeholder="۱۲:۳۰"
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="slot_duration"
                    label={t('slot_duration', 'مدت هر نوبت (دقیقه)')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <InputNumber
                      min={5}
                      max={120}
                      step={5}
                      style={{ width: '100%' }}
                      placeholder="۳۰"
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="max_appointments"
                    label={t('max_appointments', 'حداکثر نوبت در روز')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <InputNumber
                      min={1}
                      max={50}
                      style={{ width: '100%' }}
                      placeholder="۲۰"
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
                <div style={{ textAlign: 'center', padding: '16px 0' }}>
                  <ClockCircleOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('schedule_settings', 'تنظیمات زمان‌بندی')}</Text>
                  </div>
                </div>

                <Divider />

                <Form.Item
                  name="is_active"
                  label={t('active', 'فعال')}
                  valuePropName="checked"
                >
                  <Switch checkedChildren={t('active', 'فعال')} unCheckedChildren={t('inactive', 'غیرفعال')} />
                </Form.Item>

                <Divider />

                <div>
                  <Text type="secondary">{t('doctor', 'پزشک')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {selectedDoctor ? doctors.find(d => d.id === selectedDoctor)?.full_name || '—' : t('not_selected', 'انتخاب نشده')}
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('day', 'روز')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {form.getFieldValue('day_of_week') ? daysOfWeek.find(d => d.value === form.getFieldValue('day_of_week'))?.label : '—'}
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('schedule_help', 'پس از ایجاد، این زمان‌بندی برای رزرو نوبت‌ها استفاده می‌شود')}
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
