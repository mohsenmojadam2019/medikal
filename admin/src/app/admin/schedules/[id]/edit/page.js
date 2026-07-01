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
  TimePicker,
  InputNumber,
  Switch,
  Spin,
  Alert,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  ClockCircleOutlined,
} from '@ant-design/icons';
import { schedulesService, doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function EditSchedulePage() {
  const router = useRouter();
  const params = useParams();
  const scheduleId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [schedule, setSchedule] = useState(null);
  const [doctors, setDoctors] = useState([]);

  // ===== دریافت لیست پزشکان =====
  useEffect(() => {
    const fetchDoctors = async () => {
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        setDoctors(response.data || []);
      } catch (error) {
        console.error('Error fetching doctors:', error);
      }
    };
    fetchDoctors();
  }, []);

  // ===== دریافت اطلاعات زمان‌بندی =====
  useEffect(() => {
    const fetchSchedule = async () => {
      try {
        const response = await schedulesService.getById(scheduleId);
        setSchedule(response.data);
        form.setFieldsValue({
          ...response.data,
          start_time: response.data.start_time ? dayjs(response.data.start_time, 'HH:mm') : null,
          end_time: response.data.end_time ? dayjs(response.data.end_time, 'HH:mm') : null,
          break_start: response.data.break_start ? dayjs(response.data.break_start, 'HH:mm') : null,
          break_end: response.data.break_end ? dayjs(response.data.break_end, 'HH:mm') : null,
        });
      } catch (error) {
        console.error('Error fetching schedule:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (scheduleId) {
      fetchSchedule();
    }
  }, [scheduleId, form, t]);

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
      await schedulesService.update(scheduleId, values);
      message.success(t('updated', 'زمان‌بندی با موفقیت به‌روزرسانی شد'));
      router.push('/admin/schedules');
    } catch (error) {
      console.error('Error updating schedule:', error);
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
                {t('edit_schedule', 'ویرایش زمان‌بندی')}
              </Title>
              <Text type="secondary">
                {schedule?.doctor?.full_name || t('edit_schedule_subtitle', 'ویرایش زمان‌بندی پزشک')}
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
                    name="doctor_id"
                    label={t('doctor', 'پزشک')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_doctor', 'انتخاب پزشک...')}
                      showSearch
                      optionFilterProp="children"
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
                    {form.getFieldValue('doctor_id') ? doctors.find(d => d.id === form.getFieldValue('doctor_id'))?.full_name || '—' : '—'}
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
                    {t('schedule_edit_help', 'تغییرات زمان‌بندی روی نوبت‌های آینده تأثیر می‌گذارد')}
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
