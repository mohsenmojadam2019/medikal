
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
  Popconfirm,
  Tooltip,
  Row,
  Col,
  Badge,
  Select,
  Tabs,
  App,
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
  ExportOutlined,
  FilterOutlined,
} from '@ant-design/icons';
import { appointmentsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function AppointmentsPage() {
  const router = useRouter();
  const {t} = useLanguage();
  const {message} = App.useApp();

  const [loading, setLoading] = useState(false);
  const [appointments, setAppointments] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedAppointment, setSelectedAppointment] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');

  // ===== دریافت لیست نوبت‌ها =====
  const fetchAppointments = async (params = {}) => {
    setLoading(true);
    try {
      const response = await appointmentsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });

      if (response.data?.success) {
        const data = response.data.data;
        setAppointments(data?.data || []);
        setPagination({
          ...pagination,
          total: data?.total || 0,
          current: data?.current_page || 1,
        });
      } else {
        setAppointments([]);
      }
    } catch (error) {
      console.error('Error fetching appointments:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAppointments();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchAppointments({page: 1});
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchAppointments({page: 1});
  };

  const handleStatusChange = async (id, status) => {
    try {
      await appointmentsService.changeStatus(id, status);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchAppointments();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await appointmentsService.delete(id);
      message.success(t('deleted', 'نوبت با موفقیت حذف شد'));
      fetchAppointments();
    } catch (error) {
      message.error(t('error', 'خطا در حذف نوبت'));
    }
  };

  const handleView = (record) => {
    setSelectedAppointment(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/appointments/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/appointments/create');
  };

// ===== وضعیت‌های نوبت =====
  const statusMap = {
    pending: {color: 'orange', label: 'در انتظار'},
    confirmed: {color: 'blue', label: 'تایید شده'},
    arrived: {color: 'purple', label: 'حاضر'},
    in_progress: {color: 'cyan', label: 'در حال ویزیت'},
    completed: {color: 'green', label: 'انجام شده'},
    cancelled: {color: 'red', label: 'لغو شده'},
    no_show: {color: 'default', label: 'حاضر نشده'},
  };

  const statusOptions = [
    {value: 'pending', label: 'در انتظار'},
    {value: 'confirmed', label: 'تایید شده'},
    {value: 'arrived', label: 'حاضر'},
    {value: 'in_progress', label: 'در حال ویزیت'},
    {value: 'completed', label: 'انجام شده'},
    {value: 'cancelled', label: 'لغو شده'},
    {value: 'no_show', label: 'حاضر نشده'},
  ];

  const columns = [
    {
      title: t('code', 'کد نوبت'),
      dataIndex: 'code',
      key: 'code',
      render: (text) => <span style={{fontWeight: 700}}>{text}</span>,
    },
    {
      title: t('patient', 'بیمار'),
      dataIndex: 'patient',
      key: 'patient',
      render: (patient) => patient?.full_name || '—',
    },
    {
      title: t('doctor', 'پزشک'),
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => doctor?.full_name || '—',
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'date',
      key: 'date',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('time', 'ساعت'),
      dataIndex: 'start_time',
      key: 'start_time',
      render: (time) => time || '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const s = statusMap[status] || {color: 'default', label: status};
        return <Badge color={s.color} text={s.label}/>;
      },
    },
    {
      title: t('fee', 'هزینه'),
      dataIndex: 'fee',
      key: 'fee',
      render: (fee) => fee ? `${Number(fee).toLocaleString()} تومان` : '—',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 250,
      render: (_, record) => (
          <Space size="small" wrap>
            <Tooltip title={t('view', 'مشاهده')}>
              <Button
                  type="text"
                  icon={<EyeOutlined/>}
                  onClick={() => handleView(record)}
                  size="small"
              />
            </Tooltip>
            <Tooltip title={t('edit', 'ویرایش')}>
              <Button
                  type="text"
                  icon={<EditOutlined/>}
                  onClick={() => handleEdit(record)}
                  size="small"
              />
            </Tooltip>
            <Select
                placeholder={t('change_status', 'تغییر وضعیت')}
                size="small"
                style={{width: 120}}
                onChange={(value) => handleStatusChange(record.id, value)}
                value={record.status}
                options={statusOptions}
            />
            <Popconfirm
                title={t('delete_confirm', 'آیا از حذف این نوبت اطمینان دارید؟')}
                onConfirm={() => handleDelete(record.id)}
                okText={t('yes', 'بله')}
                cancelText={t('no', 'خیر')}
            >
              <Tooltip title={t('delete', 'حذف')}>
                <Button type="text" icon={<DeleteOutlined/>} size="small" danger/>
              </Tooltip>
            </Popconfirm>
          </Space>
      ),
    },
  ];

  const tabItems = [
    {key: 'all', label: t('all', 'همه')},
    {key: 'pending', label: t('pending', 'در انتظار')},
    {key: 'confirmed', label: t('confirmed', 'تایید شده')},
    {key: 'arrived', label: t('arrived', 'حاضر')},
    {key: 'in_progress', label: t('in_progress', 'در حال ویزیت')},
    {key: 'completed', label: t('completed', 'انجام شده')},
    {key: 'cancelled', label: t('cancelled', 'لغو شده')},
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
            <Title level={2} style={{margin: 0}}>
              {t('appointments_management', 'مدیریت نوبت‌ها')}
            </Title>
            <Text type="secondary">
              {t('appointments_subtitle', 'لیست و مدیریت نوبت‌های کلینیک')}
            </Text>
          </div>
          <Button
              type="primary"
              icon={<PlusOutlined/>}
              onClick={handleCreate}
              style={{
                height: 40,
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
          >
            {t('new_appointment', 'نوبت جدید')}
          </Button>
        </div>

        <Card
            style={{
              marginBottom: 16,
              borderRadius: 12,
              borderColor: '#e8e8f0',
            }}
        >
          <Tabs
              activeKey={activeTab}
              onChange={setActiveTab}
              items={tabItems.map((item) => ({
                key: item.key,
                label: item.label,
              }))}
          />

          <Row gutter={[16, 16]} align="middle" style={{marginTop: 16}}>
            <Col xs={24} sm={12} md={8} lg={6}>
              <Input
                  placeholder={t('search_appointment', 'جستجوی نوبت...')}
                  prefix={<SearchOutlined/>}
                  value={searchText}
                  onChange={(e) => setSearchText(e.target.value)}
                  onPressEnter={handleSearch}
                  allowClear
              />
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <Select
                  placeholder={t('filter_doctor', 'فیلتر پزشک')}
                  style={{width: '100%'}}
                  allowClear
                  onChange={(value) => setFilters({...filters, doctor_id: value})}
              >
                <Select.Option value="1">دکتر علی محمدی</Select.Option>
                <Select.Option value="2">دکتر سارا محمدی</Select.Option>
                <Select.Option value="3">دکتر علی رضایی</Select.Option>
              </Select>
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <JalaliDatePicker
                  placeholder={t('from_date', 'از تاریخ')}
                  size="middle"
                  onChange={(date) => setFilters({...filters, from_date: date})}
              />
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <JalaliDatePicker
                  placeholder={t('to_date', 'تا تاریخ')}
                  size="middle"
                  onChange={(date) => setFilters({...filters, to_date: date})}
              />
            </Col>
            <Col xs={24} sm={24} md={24} lg={24}>
              <Space>
                <Button type="primary" onClick={handleSearch} icon={<SearchOutlined/>}>
                  {t('search', 'جستجو')}
                </Button>
                <Button onClick={handleReset} icon={<ReloadOutlined/>}>
                  {t('reset', 'ریست')}
                </Button>
                <Button icon={<ExportOutlined/>}>{t('export', 'خروجی')}</Button>
              </Space>
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
              dataSource={appointments}
              loading={loading}
              rowKey="id"
              pagination={{
                current: pagination.current,
                pageSize: pagination.pageSize,
                total: pagination.total,
                showSizeChanger: true,
                showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'نوبت')}`,
                onChange: (page, pageSize) => {
                  setPagination({...pagination, current: page, pageSize});
                },
              }}
              scroll={{x: 1300}}
              locale={{
                emptyText: t('no_appointments', 'هیچ نوبتی یافت نشد'),
              }}
          />
        </Card>

        <Modal
            title={t('appointment_details', 'جزئیات نوبت')}
            open={isModalVisible}
            onCancel={() => setIsModalVisible(false)}
            footer={[
              <Button key="close" onClick={() => setIsModalVisible(false)}>
                {t('close', 'بستن')}
              </Button>,
              <Button
                  key="edit"
                  type="primary"
                  onClick={() => {
                    setIsModalVisible(false);
                    if (selectedAppointment) {
                      router.push(`/admin/appointments/${selectedAppointment.id}/edit`);
                    }
                  }}
              >
                {t('edit', 'ویرایش')}
              </Button>,
            ]}
            width={600}
        >
          {selectedAppointment && (
              <div>
                <div style={{display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16}}>
                  <div style={{flex: 1}}>
                    <div style={{fontSize: 18, fontWeight: 700}}>{selectedAppointment.code}</div>
                    <div style={{color: '#64748b'}}>
                      {t('status', 'وضعیت')}:{' '}
                      <Badge
                          color={statusMap[selectedAppointment.status]?.color || 'default'}
                          text={statusMap[selectedAppointment.status]?.label || selectedAppointment.status}
                      />
                    </div>
                  </div>
                </div>

                <Row gutter={[16, 16]}>
                  <Col span={12}>
                    <Text type="secondary">{t('patient', 'بیمار')}</Text>
                    <div style={{fontWeight: 500}}>{selectedAppointment.patient?.full_name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('doctor', 'پزشک')}</Text>
                    <div style={{fontWeight: 500}}>{selectedAppointment.doctor?.full_name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('date', 'تاریخ')}</Text>
                    <div style={{fontWeight: 500}}>
                      {selectedAppointment.date ? dayjs(selectedAppointment.date).format('jYYYY/jMM/jDD') : '—'}
                    </div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('time', 'ساعت')}</Text>
                    <div style={{fontWeight: 500}}>{selectedAppointment.start_time || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('fee', 'هزینه')}</Text>
                    <div style={{fontWeight: 500}}>
                      {selectedAppointment.fee ? `${Number(selectedAppointment.fee).toLocaleString()} تومان` : '—'}
                    </div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('type', 'نوع نوبت')}</Text>
                    <div style={{fontWeight: 500}}>{selectedAppointment.type || 'حضوری'}</div>
                  </Col>
                  <Col span={24}>
                    <Text type="secondary">{t('description', 'توضیحات')}</Text>
                    <div style={{fontWeight: 500}}>{selectedAppointment.notes || '—'}</div>
                  </Col>
                </Row>
              </div>
          )}
        </Modal>
      </div>
  );
}
