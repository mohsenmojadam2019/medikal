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
  Tabs,
  Avatar,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  ReloadOutlined,
  ExportOutlined,
  PrinterOutlined,
  MedicineBoxOutlined,
  UserOutlined,
} from '@ant-design/icons';
import { prescriptionsService, doctorsService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function PrescriptionsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [prescriptions, setPrescriptions] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedPrescription, setSelectedPrescription] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');

  // ===== دریافت لیست نسخه‌ها =====
  const fetchPrescriptions = async (params = {}) => {
    setLoading(true);
    try {
      const response = await prescriptionsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });
      setPrescriptions(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching prescriptions:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPrescriptions();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchPrescriptions({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchPrescriptions({ page: 1, search: '', ...filters });
  };

  const handleStatusChange = async (id, status) => {
    try {
      await prescriptionsService.changeStatus(id, status);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchPrescriptions();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await prescriptionsService.delete(id);
      message.success(t('deleted', 'نسخه با موفقیت حذف شد'));
      fetchPrescriptions();
    } catch (error) {
      message.error(t('error', 'خطا در حذف نسخه'));
    }
  };

  const handleView = (record) => {
    setSelectedPrescription(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/prescriptions/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/prescriptions/create');
  };

  const handlePrint = async (id) => {
    try {
      const response = await prescriptionsService.print(id);
      const printWindow = window.open('', '_blank');
      if (printWindow) {
        printWindow.document.write(response.data);
        printWindow.document.close();
        printWindow.print();
      }
    } catch (error) {
      message.error(t('print_error', 'خطا در چاپ نسخه'));
    }
  };

  // ===== وضعیت‌های نسخه =====
  const statusMap = {
    pending: { color: 'orange', label: 'در انتظار تایید' },
    active: { color: 'blue', label: 'فعال' },
    completed: { color: 'green', label: 'تکمیل شده' },
    cancelled: { color: 'red', label: 'لغو شده' },
    expired: { color: 'default', label: 'منقضی شده' },
  };

  const statusOptions = [
    { value: 'pending', label: 'در انتظار تایید' },
    { value: 'active', label: 'فعال' },
    { value: 'completed', label: 'تکمیل شده' },
    { value: 'cancelled', label: 'لغو شده' },
    { value: 'expired', label: 'منقضی شده' },
  ];

  const columns = [
    {
      title: t('code', 'کد نسخه'),
      dataIndex: 'code',
      key: 'code',
      render: (text) => <span style={{ fontWeight: 700 }}>{text}</span>,
    },
    {
      title: t('patient', 'بیمار'),
      dataIndex: 'patient',
      key: 'patient',
      render: (patient) => (
        <Space>
          <Avatar icon={<UserOutlined />} size="small" />
          <span>{patient?.full_name || '—'}</span>
        </Space>
      ),
    },
    {
      title: t('doctor', 'پزشک'),
      dataIndex: 'doctor',
      key: 'doctor',
      render: (doctor) => doctor?.full_name || '—',
    },
    {
      title: t('drugs', 'داروها'),
      dataIndex: 'items',
      key: 'items',
      render: (items) => items?.length || 0,
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        const s = statusMap[status] || { color: 'default', label: status };
        return <Badge color={s.color} text={s.label} />;
      },
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 280,
      render: (_, record) => (
        <Space size="small" wrap>
          <Tooltip title={t('view', 'مشاهده')}>
            <Button
              type="text"
              icon={<EyeOutlined />}
              onClick={() => handleView(record)}
              size="small"
            />
          </Tooltip>
          <Tooltip title={t('edit', 'ویرایش')}>
            <Button
              type="text"
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
              size="small"
            />
          </Tooltip>
          <Tooltip title={t('print', 'چاپ')}>
            <Button
              type="text"
              icon={<PrinterOutlined />}
              onClick={() => handlePrint(record.id)}
              size="small"
            />
          </Tooltip>
          <Select
            placeholder={t('change_status', 'تغییر وضعیت')}
            size="small"
            style={{ width: 120 }}
            onChange={(value) => handleStatusChange(record.id, value)}
            value={record.status}
            options={statusOptions}
          />
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این نسخه اطمینان دارید؟')}
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

  // ===== آیتم‌های تب =====
  const tabItems = [
    { key: 'all', label: t('all', 'همه') },
    { key: 'pending', label: t('pending', 'در انتظار تایید') },
    { key: 'active', label: t('active', 'فعال') },
    { key: 'completed', label: t('completed', 'تکمیل شده') },
    { key: 'cancelled', label: t('cancelled', 'لغو شده') },
    { key: 'expired', label: t('expired', 'منقضی شده') },
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
            {t('prescriptions_management', 'مدیریت نسخه‌ها')}
          </Title>
          <Text type="secondary">
            {t('prescriptions_subtitle', 'لیست و مدیریت نسخه‌های الکترونیک')}
          </Text>
        </div>
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
          {t('new_prescription', 'نسخه جدید')}
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

        <Row gutter={[16, 16]} align="middle" style={{ marginTop: 16 }}>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Input
              placeholder={t('search_prescription', 'جستجوی نسخه...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_doctor', 'فیلتر پزشک')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, doctor_id: value })}
            >
              <Select.Option value="1">دکتر علی محمدی</Select.Option>
              <Select.Option value="2">دکتر سارا محمدی</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_patient', 'فیلتر بیمار')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, patient_id: value })}
            >
              <Select.Option value="1">رضا کریمی</Select.Option>
              <Select.Option value="2">سارا احمدی</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={24} md={24} lg={6}>
            <Space>
              <Button type="primary" onClick={handleSearch} icon={<SearchOutlined />}>
                {t('search', 'جستجو')}
              </Button>
              <Button onClick={handleReset} icon={<ReloadOutlined />}>
                {t('reset', 'ریست')}
              </Button>
              <Button icon={<ExportOutlined />}>{t('export', 'خروجی')}</Button>
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
          dataSource={prescriptions}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'نسخه')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1300 }}
          locale={{
            emptyText: t('no_prescriptions', 'هیچ نسخه‌ای یافت نشد'),
          }}
        />
      </Card>

      <Modal
        title={t('prescription_details', 'جزئیات نسخه')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsModalVisible(false)}>
            {t('close', 'بستن')}
          </Button>,
          <Button
            key="print"
            icon={<PrinterOutlined />}
            onClick={() => selectedPrescription && handlePrint(selectedPrescription.id)}
          >
            {t('print', 'چاپ')}
          </Button>,
          <Button
            key="edit"
            type="primary"
            onClick={() => {
              setIsModalVisible(false);
              if (selectedPrescription) {
                router.push(`/admin/prescriptions/${selectedPrescription.id}/edit`);
              }
            }}
          >
            {t('edit', 'ویرایش')}
          </Button>,
        ]}
        width={700}
      >
        {selectedPrescription && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <MedicineBoxOutlined style={{ fontSize: 32, color: '#2563eb' }} />
              <div>
                <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedPrescription.code}</div>
                <div style={{ color: '#64748b' }}>
                  {t('status', 'وضعیت')}:{' '}
                  <Badge
                    color={statusMap[selectedPrescription.status]?.color || 'default'}
                    text={statusMap[selectedPrescription.status]?.label || selectedPrescription.status}
                  />
                </div>
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('patient', 'بیمار')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPrescription.patient?.full_name || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('doctor', 'پزشک')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPrescription.doctor?.full_name || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('date', 'تاریخ')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedPrescription.created_at ? dayjs(selectedPrescription.created_at).format('jYYYY/jMM/jDD') : '—'}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('expiry_date', 'تاریخ انقضا')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedPrescription.expiry_date ? dayjs(selectedPrescription.expiry_date).format('jYYYY/jMM/jDD') : '—'}
                </div>
              </Col>
            </Row>

            <Divider />

            <Title level={5}>{t('drugs', 'داروهای تجویز شده')}</Title>
            <Table
              dataSource={selectedPrescription.items || []}
              rowKey="id"
              pagination={false}
              columns={[
                { title: t('drug_name', 'نام دارو'), dataIndex: 'drug_name', key: 'drug_name' },
                { title: t('dosage', 'دوز مصرفی'), dataIndex: 'dosage', key: 'dosage' },
                { title: t('frequency', 'تعداد در روز'), dataIndex: 'frequency', key: 'frequency' },
                { title: t('duration', 'مدت (روز)'), dataIndex: 'duration', key: 'duration' },
                {
                  title: t('instructions', 'دستورالعمل'),
                  dataIndex: 'instructions',
                  key: 'instructions',
                  ellipsis: true,
                },
              ]}
              size="small"
            />

            {selectedPrescription.instructions && (
              <>
                <Divider />
                <div>
                  <Text type="secondary">{t('additional_instructions', 'دستورالعمل تکمیلی')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {selectedPrescription.instructions}
                  </div>
                </div>
              </>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}
