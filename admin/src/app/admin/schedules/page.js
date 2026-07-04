// src/app/admin/schedules/page.js

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
  Switch,
  Table,
  Popconfirm,
  Tooltip,
  Tag,
  App,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  PlusOutlined,
  DeleteOutlined,
  EditOutlined,
  ClockCircleOutlined,
  CalendarOutlined,
} from '@ant-design/icons';
import { schedulesService, doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function SchedulesPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();

  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [schedules, setSchedules] = useState([]);
  const [form] = Form.useForm();

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

  // ===== دریافت ساعات کاری پزشک =====
  const fetchSchedules = async (doctorId) => {
    if (!doctorId) {
      setSchedules([]);
      return;
    }

    setLoading(true);
    try {
      const response = await schedulesService.getByDoctor(doctorId);
      // ✅ بررسی ساختار پاسخ
      if (response.data?.success) {
        const data = response.data.data;
        const list = data?.data || data || [];
        setSchedules(Array.isArray(list) ? list : []);
      } else {
        setSchedules([]);
      }
    } catch (error) {
      console.error('Error fetching schedules:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      setSchedules([]);
    } finally {
      setLoading(false);
    }
  };

  // ===== انتخاب پزشک =====
  const handleDoctorSelect = (doctorId) => {
    setSelectedDoctor(doctorId);
    if (doctorId) {
      fetchSchedules(doctorId);
    } else {
      setSchedules([]);
    }
  };

  // ===== ذخیره ساعات کاری =====
  const handleSubmit = async (values) => {
    if (!selectedDoctor) {
      message.warning(t('select_doctor_first', 'لطفاً ابتدا پزشک را انتخاب کنید'));
      return;
    }

    setLoading(true);
    try {
      const data = {
        doctor_id: selectedDoctor,
        schedules: values.schedules.map((item) => ({
          day_of_week: item.day_of_week,
          start_time: item.start_time?.format('HH:mm'),
          end_time: item.end_time?.format('HH:mm'),
          is_working: item.is_working !== false,
          break_start: item.break_start?.format('HH:mm'),
          break_end: item.break_end?.format('HH:mm'),
        })),
      };

      await schedulesService.save(data);
      message.success(t('saved', 'ساعات کاری با موفقیت ذخیره شد'));
      fetchSchedules(selectedDoctor);
    } catch (error) {
      console.error('Error saving schedules:', error);
      message.error(t('save_error', 'خطا در ذخیره ساعات کاری'));
    } finally {
      setLoading(false);
    }
  };

  // ===== ستون‌های جدول ساعات کاری =====
  const columns = [
    {
      title: t('day', 'روز'),
      dataIndex: 'day_of_week',
      key: 'day_of_week',
      render: (day) => {
        const days = {
          saturday: 'شنبه',
          sunday: 'یکشنبه',
          monday: 'دوشنبه',
          tuesday: 'سه‌شنبه',
          wednesday: 'چهارشنبه',
          thursday: 'پنجشنبه',
          friday: 'جمعه',
        };
        return days[day] || day;
      },
    },
    {
      title: t('start_time', 'ساعت شروع'),
      dataIndex: 'start_time',
      key: 'start_time',
    },
    {
      title: t('end_time', 'ساعت پایان'),
      dataIndex: 'end_time',
      key: 'end_time',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'is_working',
      key: 'is_working',
      render: (isWorking) => (
          <Tag color={isWorking ? 'green' : 'red'}>
            {isWorking ? t('working', 'فعال') : t('not_working', 'غیرفعال')}
          </Tag>
      ),
    },
  ];

  // اگر doctors آرایه نیست
  if (!Array.isArray(doctors)) {
    console.error('⚠️ Doctors is not an array:', doctors);
    return (
        <div style={{ padding: 24 }}>
          <Title level={4}>خطا در نمایش داده‌ها</Title>
          <Text type="danger">داده‌های دریافتی معتبر نیستند.</Text>
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
                  onClick={() => router.back()}
                  style={{ fontSize: 18 }}
              />
              <div>
                <Title level={2} style={{ margin: 0 }}>
                  {t('schedules_management', 'مدیریت ساعات کاری')}
                </Title>
                <Text type="secondary">
                  {t('schedules_subtitle', 'تنظیم ساعات کاری پزشکان')}
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
          <Row gutter={[16, 16]}>
            <Col xs={24} md={8}>
              <Form.Item label={t('select_doctor', 'انتخاب پزشک')}>
                <Select
                    placeholder={t('select_doctor', 'انتخاب پزشک...')}
                    loading={loadingDoctors}
                    showSearch
                    optionFilterProp="children"
                    onChange={handleDoctorSelect}
                    style={{ width: '100%' }}
                    options={doctors.map((d) => ({
                      value: d.id,
                      label: `${d.full_name} (${d.specialty?.name || ''})`,
                    }))}
                />
              </Form.Item>
            </Col>
          </Row>

          {selectedDoctor && (
              <div style={{ marginTop: 24 }}>
                <Divider>
                  <Space>
                    <ClockCircleOutlined />
                    <span>{t('working_hours', 'ساعات کاری')}</span>
                  </Space>
                </Divider>

                <Table
                    columns={columns}
                    dataSource={schedules}
                    loading={loading}
                    rowKey="id"
                    pagination={false}
                    locale={{
                      emptyText: t('no_schedules', 'هیچ ساعات کاری ثبت نشده است'),
                    }}
                />

                <Divider />

                <Title level={5}>{t('edit_schedules', 'ویرایش ساعات کاری')}</Title>

                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    size="large"
                >
                  <Form.List name="schedules">
                    {(fields, { add, remove }) => (
                        <>
                          {fields.map(({ key, name, ...restField }) => (
                              <Row key={key} gutter={[16, 16]} align="middle">
                                <Col xs={24} sm={6}>
                                  <Form.Item
                                      {...restField}
                                      name={[name, 'day_of_week']}
                                      label={t('day', 'روز')}
                                      rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                                  >
                                    <Select
                                        options={[
                                          { value: 'saturday', label: 'شنبه' },
                                          { value: 'sunday', label: 'یکشنبه' },
                                          { value: 'monday', label: 'دوشنبه' },
                                          { value: 'tuesday', label: 'سه‌شنبه' },
                                          { value: 'wednesday', label: 'چهارشنبه' },
                                          { value: 'thursday', label: 'پنجشنبه' },
                                          { value: 'friday', label: 'جمعه' },
                                        ]}
                                    />
                                  </Form.Item>
                                </Col>
                                <Col xs={24} sm={5}>
                                  <Form.Item
                                      {...restField}
                                      name={[name, 'start_time']}
                                      label={t('start_time', 'ساعت شروع')}
                                      rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                                  >
                                    <TimePicker format="HH:mm" style={{ width: '100%' }} />
                                  </Form.Item>
                                </Col>
                                <Col xs={24} sm={5}>
                                  <Form.Item
                                      {...restField}
                                      name={[name, 'end_time']}
                                      label={t('end_time', 'ساعت پایان')}
                                      rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                                  >
                                    <TimePicker format="HH:mm" style={{ width: '100%' }} />
                                  </Form.Item>
                                </Col>
                                <Col xs={24} sm={4}>
                                  <Form.Item
                                      {...restField}
                                      name={[name, 'is_working']}
                                      label={t('status', 'وضعیت')}
                                      valuePropName="checked"
                                  >
                                    <Switch checkedChildren="فعال" unCheckedChildren="غیرفعال" />
                                  </Form.Item>
                                </Col>
                                <Col xs={24} sm={4}>
                                  <Button
                                      type="text"
                                      danger
                                      icon={<DeleteOutlined />}
                                      onClick={() => remove(name)}
                                  />
                                </Col>
                              </Row>
                          ))}
                          <Button
                              type="dashed"
                              onClick={() => add()}
                              icon={<PlusOutlined />}
                              style={{ width: '100%', marginTop: 16 }}
                          >
                            {t('add_schedule', 'افزودن ساعات کاری')}
                          </Button>
                        </>
                    )}
                  </Form.List>

                  <Divider />
                  <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                    <Button onClick={() => router.back()} size="large">
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
              </div>
          )}
        </Card>
      </div>
  );
}