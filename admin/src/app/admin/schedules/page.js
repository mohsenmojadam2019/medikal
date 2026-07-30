'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import {
  App,
  Button,
  Card,
  Col,
  Divider,
  Empty,
  Form,
  Row,
  Select,
  Space,
  Switch,
  Table,
  TimePicker,
  Typography,
} from 'antd';
import {
  ArrowLeftOutlined,
  ClockCircleOutlined,
  DeleteOutlined,
  PlusOutlined,
  SaveOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { doctorsService, schedulesService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

const DAYS = [
  { value: 6, label: 'شنبه' },
  { value: 0, label: 'یکشنبه' },
  { value: 1, label: 'دوشنبه' },
  { value: 2, label: 'سه‌شنبه' },
  { value: 3, label: 'چهارشنبه' },
  { value: 4, label: 'پنج‌شنبه' },
  { value: 5, label: 'جمعه' },
];

const extractList = (response) => {
  const body = response?.data;
  const root = body?.data ?? body;

  if (Array.isArray(root)) {
    return root;
  }

  if (Array.isArray(root?.data)) {
    return root.data;
  }

  if (Array.isArray(root?.doctors)) {
    return root.doctors;
  }

  if (Array.isArray(root?.schedules)) {
    return root.schedules;
  }

  return [];
};

const doctorName = (doctor) => {
  return (
    doctor?.full_name ||
    doctor?.name ||
    doctor?.user?.full_name ||
    doctor?.user?.name ||
    doctor?.user?.email ||
    `پزشک شماره ${doctor?.id}`
  );
};

const doctorLabel = (doctor) => {
  const name = doctorName(doctor);
  const specialty =
    doctor?.specialty?.name ||
    doctor?.specialty_name ||
    '';

  return specialty ? `${name} (${specialty})` : name;
};

const normalizeTimeString = (value) => {
  if (!value) {
    return null;
  }

  if (typeof value === 'string') {
    return value.substring(0, 5);
  }

  if (dayjs.isDayjs(value)) {
    return value.format('HH:mm');
  }

  return null;
};

const timeToDayjs = (value) => {
  const time = normalizeTimeString(value);

  if (!time) {
    return null;
  }

  return dayjs(`2000-01-01T${time}:00`);
};

const normalizeSchedule = (schedule) => ({
  id: schedule?.id,
  day_of_week: Number(schedule?.day_of_week),
  start_time: normalizeTimeString(schedule?.start_time),
  end_time: normalizeTimeString(schedule?.end_time),
  break_start: normalizeTimeString(schedule?.break_start),
  break_end: normalizeTimeString(schedule?.break_end),
  slot_duration: Number(schedule?.slot_duration || 30),
  max_slots_per_day: schedule?.max_slots_per_day
    ? Number(schedule.max_slots_per_day)
    : null,
  is_active: schedule?.is_active !== false,
});

export default function SchedulesPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();
  const [form] = Form.useForm();

  const [doctors, setDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [savedSchedules, setSavedSchedules] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [loadingSchedules, setLoadingSchedules] = useState(false);
  const [saving, setSaving] = useState(false);

  const loadSchedules = useCallback(
    async (doctorId) => {
      if (!doctorId) {
        setSavedSchedules([]);
        form.setFieldsValue({ schedules: [] });
        return;
      }

      setLoadingSchedules(true);

      try {
        const response =
          await schedulesService.getByDoctor(doctorId);

        const list = extractList(response)
          .map(normalizeSchedule)
          .filter((item) =>
            Number.isInteger(item.day_of_week)
          )
          .sort((a, b) => {
            const order = [6, 0, 1, 2, 3, 4, 5];
            return (
              order.indexOf(a.day_of_week) -
              order.indexOf(b.day_of_week)
            );
          });

        setSavedSchedules(list);

        form.setFieldsValue({
          schedules: list.map((item) => ({
            ...item,
            start_time: timeToDayjs(item.start_time),
            end_time: timeToDayjs(item.end_time),
            break_start: timeToDayjs(item.break_start),
            break_end: timeToDayjs(item.break_end),
          })),
        });
      } catch (error) {
        console.error('Error fetching schedules:', error);
        setSavedSchedules([]);
        form.setFieldsValue({ schedules: [] });

        message.error(
          error?.response?.data?.message ||
            t('fetch_error', 'خطا در دریافت اطلاعات')
        );
      } finally {
        setLoadingSchedules(false);
      }
    },
    [form, message, t]
  );

  useEffect(() => {
    let active = true;

    const loadDoctors = async () => {
      setLoadingDoctors(true);

      try {
        const response = await doctorsService.getAll({
          per_page: 100,
        });

        if (!active) {
          return;
        }

        const list = extractList(response);
        setDoctors(list);

        if (list.length > 0) {
          const firstDoctorId = list[0].id;
          setSelectedDoctor(firstDoctorId);
          await loadSchedules(firstDoctorId);
        } else {
          setSelectedDoctor(null);
          setSavedSchedules([]);
          form.setFieldsValue({ schedules: [] });
        }
      } catch (error) {
        console.error('Error fetching doctors:', error);

        if (active) {
          setDoctors([]);
          setSelectedDoctor(null);

          message.error(
            error?.response?.data?.message ||
              t(
                'fetch_doctors_error',
                'خطا در دریافت لیست پزشکان'
              )
          );
        }
      } finally {
        if (active) {
          setLoadingDoctors(false);
        }
      }
    };

    loadDoctors();

    return () => {
      active = false;
    };
  }, [form, loadSchedules, message, t]);

  const doctorOptions = useMemo(
    () =>
      doctors.map((doctor) => ({
        value: doctor.id,
        label: doctorLabel(doctor),
      })),
    [doctors]
  );

  const handleDoctorChange = async (doctorId) => {
    setSelectedDoctor(doctorId);
    await loadSchedules(doctorId);
  };

  const handleSubmit = async (values) => {
    if (!selectedDoctor) {
      message.warning(
        t(
          'select_doctor_first',
          'لطفاً ابتدا پزشک را انتخاب کنید'
        )
      );
      return;
    }

    const formSchedules = Array.isArray(values?.schedules)
      ? values.schedules
      : [];

    if (formSchedules.length === 0) {
      message.warning('حداقل یک ساعت کاری اضافه کنید');
      return;
    }

    const uniqueSchedules = new Map();

    formSchedules.forEach((item) => {
      if (
        item?.day_of_week === undefined ||
        item?.day_of_week === null
      ) {
        return;
      }

      uniqueSchedules.set(Number(item.day_of_week), {
        day_of_week: Number(item.day_of_week),
        start_time: normalizeTimeString(item.start_time),
        end_time: normalizeTimeString(item.end_time),
        break_start: normalizeTimeString(item.break_start),
        break_end: normalizeTimeString(item.break_end),
        slot_duration: Number(item.slot_duration || 30),
        max_slots_per_day: item.max_slots_per_day
          ? Number(item.max_slots_per_day)
          : null,
        is_active: item.is_active !== false,
      });
    });

    const schedules = Array.from(uniqueSchedules.values());

    if (schedules.length === 0) {
      message.warning('اطلاعات ساعات کاری کامل نیست');
      return;
    }

    setSaving(true);

    try {
      const response = await schedulesService.setWeekly(
        selectedDoctor,
        { schedules }
      );

      if (response.data?.success === false) {
        throw new Error(
          response.data?.message || 'خطا در ذخیره اطلاعات'
        );
      }

      message.success(
        response.data?.message ||
          t(
            'schedule_saved',
            'ساعات کاری با موفقیت ذخیره شد'
          )
      );

      await loadSchedules(selectedDoctor);
    } catch (error) {
      console.error('Error saving schedules:', error);

      const validationErrors =
        error?.response?.data?.errors;

      const firstValidationError = validationErrors
        ? Object.values(validationErrors)?.flat()?.[0]
        : null;

      message.error(
        firstValidationError ||
          error?.response?.data?.message ||
          error?.message ||
          t('save_error', 'خطا در ذخیره ساعات کاری')
      );
    } finally {
      setSaving(false);
    }
  };

  const tableColumns = [
    {
      title: 'روز',
      dataIndex: 'day_of_week',
      key: 'day_of_week',
      render: (value) =>
        DAYS.find((day) => day.value === Number(value))
          ?.label || 'نامشخص',
    },
    {
      title: 'ساعت شروع',
      dataIndex: 'start_time',
      key: 'start_time',
      render: (value) => normalizeTimeString(value) || '—',
    },
    {
      title: 'ساعت پایان',
      dataIndex: 'end_time',
      key: 'end_time',
      render: (value) => normalizeTimeString(value) || '—',
    },
    {
      title: 'وضعیت',
      dataIndex: 'is_active',
      key: 'is_active',
      render: (value) => (value ? 'فعال' : 'غیرفعال'),
    },
  ];

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
        <Space>
          <Button
            type="text"
            icon={<ArrowLeftOutlined />}
            onClick={() => router.back()}
          />

          <div>
            <Title level={2} style={{ margin: 0 }}>
              مدیریت ساعات کاری
            </Title>

            <Text type="secondary">
              تنظیم ساعات کاری پزشکان
            </Text>
          </div>
        </Space>
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
          initialValues={{ schedules: [] }}
        >
          <Form.Item label="انتخاب پزشک">
            <Select
              showSearch
              allowClear
              loading={loadingDoctors}
              value={selectedDoctor}
              options={doctorOptions}
              optionFilterProp="label"
              placeholder="پزشک را انتخاب کنید"
              onChange={handleDoctorChange}
            />
          </Form.Item>

          <Divider>
            <Space>
              <ClockCircleOutlined />
              ساعات کاری ثبت‌شده
            </Space>
          </Divider>

          <Table
            rowKey={(record) =>
              record.id || `day-${record.day_of_week}`
            }
            loading={loadingSchedules}
            columns={tableColumns}
            dataSource={savedSchedules}
            pagination={false}
            locale={{
              emptyText: (
                <Empty description="هیچ ساعت کاری ثبت نشده است" />
              ),
            }}
          />

          <Divider orientation="right">
            ویرایش ساعات کاری
          </Divider>

          <Form.List name="schedules">
            {(fields, { add, remove }) => (
              <Space
                direction="vertical"
                size={16}
                style={{ width: '100%' }}
              >
                {fields.map((field) => (
                  <Card
                    key={field.key}
                    size="small"
                    styles={{ body: { padding: 16 } }}
                  >
                    <Row gutter={[12, 12]} align="middle">
                      <Col xs={24} md={5}>
                        <Form.Item
                          {...field}
                          name={[field.name, 'day_of_week']}
                          label="روز"
                          rules={[
                            {
                              required: true,
                              message: 'روز را انتخاب کنید',
                            },
                          ]}
                        >
                          <Select options={DAYS} />
                        </Form.Item>
                      </Col>

                      <Col xs={24} md={5}>
                        <Form.Item
                          {...field}
                          name={[field.name, 'start_time']}
                          label="ساعت شروع"
                          rules={[
                            {
                              required: true,
                              message:
                                'ساعت شروع را انتخاب کنید',
                            },
                          ]}
                        >
                          <TimePicker
                            format="HH:mm"
                            minuteStep={15}
                            style={{ width: '100%' }}
                          />
                        </Form.Item>
                      </Col>

                      <Col xs={24} md={5}>
                        <Form.Item
                          {...field}
                          name={[field.name, 'end_time']}
                          label="ساعت پایان"
                          rules={[
                            {
                              required: true,
                              message:
                                'ساعت پایان را انتخاب کنید',
                            },
                          ]}
                        >
                          <TimePicker
                            format="HH:mm"
                            minuteStep={15}
                            style={{ width: '100%' }}
                          />
                        </Form.Item>
                      </Col>

                      <Col xs={12} md={5}>
                        <Form.Item
                          {...field}
                          name={[field.name, 'is_active']}
                          label="وضعیت"
                          valuePropName="checked"
                          initialValue
                        >
                          <Switch
                            checkedChildren="فعال"
                            unCheckedChildren="غیرفعال"
                          />
                        </Form.Item>
                      </Col>

                      <Col xs={12} md={4}>
                        <Button
                          danger
                          block
                          icon={<DeleteOutlined />}
                          onClick={() => remove(field.name)}
                        >
                          حذف
                        </Button>
                      </Col>
                    </Row>
                  </Card>
                ))}

                <Button
                  block
                  type="dashed"
                  icon={<PlusOutlined />}
                  onClick={() =>
                    add({
                      day_of_week: 6,
                      start_time: dayjs(
                        '2000-01-01T08:00:00'
                      ),
                      end_time: dayjs(
                        '2000-01-01T16:00:00'
                      ),
                      slot_duration: 30,
                      is_active: true,
                    })
                  }
                >
                  افزودن ساعات کاری
                </Button>
              </Space>
            )}
          </Form.List>

          <Divider />

          <Space>
            <Button
              type="primary"
              htmlType="submit"
              loading={saving}
              disabled={!selectedDoctor}
              icon={<SaveOutlined />}
            >
              ذخیره
            </Button>

            <Button onClick={() => router.back()}>
              انصراف
            </Button>
          </Space>
        </Form>
      </Card>
    </div>
  );
}
