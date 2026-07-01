'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Table,
  Button,
  Input,
  Space,
  Card,
  Typography,
  Tag,
  Modal,
  message,
  Popconfirm,
  Tooltip,
  Row,
  Col,
  Badge,
  Select,
  DatePicker,
  TimePicker,
  Form,
  Switch,
  Tabs,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  CalendarOutlined,
  ReloadOutlined,
  CopyOutlined,
  ClockCircleOutlined,
} from '@ant-design/icons';
import { schedulesService, doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { RangePicker } = DatePicker;

export default function SchedulesPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [schedules, setSchedules] = useState([]);
  const [doctors, setDoctors] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [modalMode, setModalMode] = useState('view');

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

  // ===== دریافت زمان‌بندی پزشک انتخاب شده =====
  const fetchSchedules = async (doctorId) => {
    if (!doctorId) {
      setSchedules([]);
      return;
    }
    
    setLoading(true);
    try {
      const response = await schedulesService.getWeekly(doctorId);
      setSchedules(response.data || []);
    } catch (error) {
      console.error('Error fetching schedules:', error);
      message.error(t('fetch_error', 'خطا در دریافت زمان‌بندی'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (selectedDoctor) {
      fetchSchedules(selectedDoctor);
    }
  }, [selectedDoctor]);

  const handleDoctorChange = (doctorId) => {
    setSelectedDoctor(doctorId);
  };

  const handleView = (record) => {
    setModalMode('view');
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/schedules/${record.id}/edit`);
  };

  const handleCreate = () => {
    if (!selectedDoctor) {
      message.warning(t('select_doctor_first', 'لطفاً ابتدا یک پزشک انتخاب کنید'));
      return;
    }
    router.push(`/admin/schedules/create?doctor_id=${selectedDoctor}`);
  };

  const handleDelete = async (id) => {
    try {
      await schedulesService.delete(id);
      message.success(t('deleted', 'زمان‌بندی با موفقیت حذف شد'));
      if (selectedDoctor) {
        fetchSchedules(selectedDoctor);
      }
    } catch (error) {
      message.error(t('error', 'خطا در حذف زمان‌بندی'));
    }
  };

  const handleToggleStatus = async (id, isActive) => {
    try {
      await schedulesService.toggleStatus(id, !isActive);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      if (selectedDoctor) {
        fetchSchedules(selectedDoctor);
      }
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleCopyPreviousWeek = async () => {
    if (!selectedDoctor) {
      message.warning(t('select_doctor_first', 'لطفاً ابتدا یک پزشک انتخاب کنید'));
      return;
    }
    
    try {
      await schedulesService.copyFromPreviousWeek(selectedDoctor);
      message.success(t('copied', 'زمان‌بندی از هفته قبل کپی شد'));
      fetchSchedules(selectedDoctor);
    } catch (error) {
      message.error(t('error', 'خطا در کپی زمان‌بندی'));
    }
  };

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

  const columns = [
    {
      title: t('day', 'روز'),
      dataIndex: 'day_of_week',
      key: 'day_of_week',
      render: (day) => {
        const found = daysOfWeek.find(d => d.value === day);
        return found?.label || day;
      },
    },
    {
      title: t('start_time', 'ساعت شروع'),
      dataIndex: 'start_time',
      key: 'start_time',
      render: (time) => time || '—',
    },
    {
      title: t('end_time', 'ساعت پایان'),
      dataIndex: 'end_time',
      key: 'end_time',
      render: (time) => time || '—',
    },
    {
      title: t('break_time', 'زمان استراحت'),
      dataIndex: 'break_start',
      key: 'break_start',
      render: (breakStart, record) => {
        if (breakStart && record.break_end) {
          return `${breakStart} - ${record.break_end}`;
        }
        return '—';
      },
    },
    {
      title: t('slot_duration', 'مدت هر نوبت'),
      dataIndex: 'slot_duration',
      key: 'slot_duration',
      render: (duration) => duration ? `${duration} ${t('minutes', 'دقیقه')}` : '—',
    },
    {
      title: t('max_appointments', 'حداکثر نوبت'),
      dataIndex: 'max_appointments',
      key: 'max_appointments',
      render: (max) => max || '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'is_active',
      key: 'is_active',
      render: (isActive) => (
        <Badge
          status={isActive ? 'success' : 'error'}
          text={isActive ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
        />
      ),
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 200,
      render: (_, record) => (
        <Space size="small">
          <Tooltip title={t('edit', 'ویرایش')}>
            <Button
              type="text"
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
              size="small"
            />
          </Tooltip>
          <Tooltip title={t('toggle_status', 'تغییر وضعیت')}>
            <Button
              type="text"
              icon={record.is_active ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
              onClick={() => handleToggleStatus(record.id, record.is_active)}
              size="small"
              style={{ color: record.is_active ? '#ef4444' : '#10b981' }}
            />
          </Tooltip>
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این زمان‌بندی اطمینان دارید؟')}
            onConfirm={() => handleDelete(record.id)}
            okText={t('yes', 'بله')}
            cancelText={t('no', 'خیر')}
          >
            <Tooltip title={t('delete', 'حذف')}>
              <Button type="text" icon={<DeleteOutlined />} size="small" danger />
            </Tooltip>
          </Popconfirm>
        </Space>
      ),
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
        <div>
          <Title level={2} style={{ margin: 0 }}>
            {t('schedules_management', 'مدیریت زمان‌بندی')}
          </Title>
          <Text type="secondary">
            {t('schedules_subtitle', 'تنظیم زمان‌بندی پزشکان')}
          </Text>
        </div>
        <Space>
          <Button
            icon={<CopyOutlined />}
            onClick={handleCopyPreviousWeek}
          >
            {t('copy_previous_week', 'کپی از هفته قبل')}
          </Button>
          <Button
            type="primary"
            icon={<PlusOutlined />}
            onClick={handleCreate}
            style={{
              height: 40,
              background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
              border: 'none',
            }}
          >
            {t('new_schedule', 'زمان‌بندی جدید')}
          </Button>
        </Space>
      </div>

      <Card
        style={{
          marginBottom: 16,
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Row gutter={[16, 16]} align="middle">
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('select_doctor', 'انتخاب پزشک...')}
              style={{ width: '100%' }}
              showSearch
              optionFilterProp="children"
              onChange={handleDoctorChange}
              value={selectedDoctor}
              options={doctors.map((d) => ({
                value: d.id,
                label: `${d.full_name} (${d.specialty?.name || ''})`,
              }))}
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Button icon={<ReloadOutlined />} onClick={() => selectedDoctor && fetchSchedules(selectedDoctor)}>
              {t('refresh', 'بروزرسانی')}
            </Button>
          </Col>
        </Row>
      </Card>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Table
          columns={columns}
          dataSource={schedules}
          loading={loading}
          rowKey="id"
          pagination={false}
          locale={{
            emptyText: selectedDoctor 
              ? t('no_schedules', 'هیچ زمان‌بندی برای این پزشک یافت نشد') 
              : t('select_doctor_first', 'لطفاً ابتدا یک پزشک انتخاب کنید'),
          }}
        />
      </Card>

      <Modal
        title={t('schedule_details', 'جزئیات زمان‌بندی')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsModalVisible(false)}>
            {t('close', 'بستن')}
          </Button>,
        ]}
        width={600}
      >
        {/* محتوای مودال */}
      </Modal>
    </div>
  );
}
