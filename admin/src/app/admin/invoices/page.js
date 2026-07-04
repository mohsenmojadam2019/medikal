// src/app/admin/invoices/page.js

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
  Avatar,
  Tabs,
  Statistic,
  App,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  PrinterOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  DollarOutlined,
  FileTextOutlined,
  UserOutlined,
  WalletOutlined,
} from '@ant-design/icons';
import { invoicesService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import moment from 'moment-jalaali'; // ✅ استفاده از moment-jalaali

// تنظیم moment-jalaali
moment.loadPersian({ dialect: 'persian-modern' });

const { Title, Text } = Typography;

export default function InvoicesPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();

  const [loading, setLoading] = useState(false);
  const [invoices, setInvoices] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedInvoice, setSelectedInvoice] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');
  const [stats, setStats] = useState(null);
  const [patients, setPatients] = useState([]);
  const [loadingPatients, setLoadingPatients] = useState(false);

  // ===== دریافت لیست بیماران =====
  useEffect(() => {
    const fetchPatients = async () => {
      setLoadingPatients(true);
      try {
        const response = await patientsService.getAll({ per_page: 100 });
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

  // ===== دریافت آمار =====
  const fetchStats = async () => {
    try {
      const response = await invoicesService.getStats();
      if (response.data?.success) {
        setStats(response.data.data);
      }
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ===== دریافت لیست فاکتورها =====
  const fetchInvoices = async (params = {}) => {
    setLoading(true);
    try {
      const response = await invoicesService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });

      if (response.data?.success) {
        const data = response.data.data;
        const list = data?.data || data || [];
        setInvoices(Array.isArray(list) ? list : []);
        setPagination({
          ...pagination,
          total: data?.total || (Array.isArray(list) ? list.length : 0),
          current: data?.current_page || 1,
        });
      } else {
        setInvoices([]);
        setPagination({
          ...pagination,
          total: 0,
        });
      }
    } catch (error) {
      console.error('Error fetching invoices:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      setInvoices([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInvoices();
    fetchStats();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchInvoices({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchInvoices({ page: 1 });
  };

  const handleDelete = async (id) => {
    try {
      await invoicesService.delete(id);
      message.success(t('deleted', 'فاکتور با موفقیت حذف شد'));
      fetchInvoices();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در حذف فاکتور'));
    }
  };

  const handleView = (record) => {
    setSelectedInvoice(record);
    setIsModalVisible(true);
  };

  const handleCreate = () => {
    router.push('/admin/invoices/create');
  };

  const handlePrint = async (id) => {
    try {
      const response = await invoicesService.print(id);
      const printWindow = window.open('', '_blank');
      if (printWindow) {
        printWindow.document.write(response.data);
        printWindow.document.close();
        printWindow.print();
      }
    } catch (error) {
      message.error(t('print_error', 'خطا در چاپ فاکتور'));
    }
  };

  // ===== وضعیت‌های فاکتور =====
  const statusMap = {
    draft: { color: 'default', label: 'پیش‌نویس' },
    issued: { color: 'blue', label: 'صادر شده' },
    paid: { color: 'green', label: 'پرداخت شده' },
    cancelled: { color: 'red', label: 'لغو شده' },
    overdue: { color: 'orange', label: 'سررسید گذشته' },
  };

  const statusOptions = [
    { value: 'draft', label: 'پیش‌نویس' },
    { value: 'issued', label: 'صادر شده' },
    { value: 'paid', label: 'پرداخت شده' },
    { value: 'cancelled', label: 'لغو شده' },
    { value: 'overdue', label: 'سررسید گذشته' },
  ];

  // ✅ تابع تبدیل تاریخ به شمسی با moment-jalaali
  const formatJalaliDate = (date) => {
    if (!date) return '—';
    try {
      return moment(date).format('jYYYY/jMM/jDD');
    } catch (error) {
      console.error('Error formatting date:', error);
      return '—';
    }
  };

  const columns = [
    {
      title: t('invoice_number', 'شماره فاکتور'),
      dataIndex: 'invoice_number',
      key: 'invoice_number',
      render: (text) => <span style={{ fontWeight: 700 }}>{text}</span>,
    },
    {
      title: t('patient', 'بیمار'),
      dataIndex: 'patient',
      key: 'patient',
      render: (patient) => (
          <Space>
            <Avatar icon={<UserOutlined />} size="small" />
            <span>{patient?.full_name || patient?.name || '—'}</span>
          </Space>
      ),
    },
    {
      title: t('amount', 'مبلغ'),
      dataIndex: 'amount',
      key: 'amount',
      render: (amount) => amount ? `${Number(amount).toLocaleString()} تومان` : '—',
    },
    {
      title: t('tax', 'مالیات'),
      dataIndex: 'tax',
      key: 'tax',
      render: (tax) => tax ? `${Number(tax).toLocaleString()} تومان` : '—',
    },
    {
      title: t('discount', 'تخفیف'),
      dataIndex: 'discount',
      key: 'discount',
      render: (discount) => discount ? `${Number(discount).toLocaleString()} تومان` : '—',
    },
    {
      title: t('total', 'مبلغ کل'),
      dataIndex: 'total_amount',
      key: 'total_amount',
      render: (total) => {
        const amount = total || 0;
        return <span style={{ fontWeight: 700, color: '#2563eb' }}>{Number(amount).toLocaleString()} تومان</span>;
      },
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
      title: t('date', 'تاریخ'),
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => formatJalaliDate(date),
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 220,
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
            <Tooltip title={t('print', 'چاپ')}>
              <Button
                  type="text"
                  icon={<PrinterOutlined />}
                  onClick={() => handlePrint(record.id)}
                  size="small"
              />
            </Tooltip>
            <Popconfirm
                title={t('delete_confirm', 'آیا از حذف این فاکتور اطمینان دارید؟')}
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
    { key: 'draft', label: t('draft', 'پیش‌نویس') },
    { key: 'issued', label: t('issued', 'صادر شده') },
    { key: 'paid', label: t('paid', 'پرداخت شده') },
    { key: 'cancelled', label: t('cancelled', 'لغو شده') },
    { key: 'overdue', label: t('overdue', 'سررسید گذشته') },
  ];

  // اگر invoices آرایه نیست
  if (!Array.isArray(invoices)) {
    console.error('⚠️ Invoices is not an array:', invoices);
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
            <Title level={2} style={{ margin: 0 }}>
              {t('invoices_management', 'مدیریت فاکتورها')}
            </Title>
            <Text type="secondary">
              {t('invoices_subtitle', 'لیست فاکتورهای مالی')}
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
            {t('new_invoice', 'فاکتور جدید')}
          </Button>
        </div>

        {/* ===== آمار ===== */}
        {stats && (
            <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
              <Col xs={24} sm={12} md={6}>
                <Card
                    style={{
                      borderRadius: 12,
                      borderColor: '#e8e8f0',
                    }}
                >
                  <Statistic
                      title={t('total_invoices', 'تعداد فاکتورها')}
                      value={stats.total_invoices || 0}
                      prefix={<FileTextOutlined style={{ color: '#2563eb' }} />}
                  />
                </Card>
              </Col>
              <Col xs={24} sm={12} md={6}>
                <Card
                    style={{
                      borderRadius: 12,
                      borderColor: '#e8e8f0',
                    }}
                >
                  <Statistic
                      title={t('total_revenue', 'درآمد کل')}
                      value={stats.total_revenue || 0}
                      prefix={<WalletOutlined style={{ color: '#10b981' }} />}
                      formatter={(value) => `${Number(value).toLocaleString()} تومان`}
                  />
                </Card>
              </Col>
              <Col xs={24} sm={12} md={6}>
                <Card
                    style={{
                      borderRadius: 12,
                      borderColor: '#e8e8f0',
                    }}
                >
                  <Statistic
                      title={t('paid_count', 'پرداخت شده')}
                      value={stats.paid_count || 0}
                      valueStyle={{ color: '#10b981' }}
                  />
                </Card>
              </Col>
              <Col xs={24} sm={12} md={6}>
                <Card
                    style={{
                      borderRadius: 12,
                      borderColor: '#e8e8f0',
                    }}
                >
                  <Statistic
                      title={t('overdue_count', 'سررسید گذشته')}
                      value={stats.overdue_count || 0}
                      valueStyle={{ color: '#ef4444' }}
                  />
                </Card>
              </Col>
            </Row>
        )}

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
                  placeholder={t('search_invoice', 'جستجوی فاکتور...')}
                  prefix={<SearchOutlined />}
                  value={searchText}
                  onChange={(e) => setSearchText(e.target.value)}
                  onPressEnter={handleSearch}
                  allowClear
              />
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <Select
                  placeholder={t('filter_patient', 'فیلتر بیمار')}
                  style={{ width: '100%' }}
                  loading={loadingPatients}
                  allowClear
                  showSearch
                  optionFilterProp="children"
                  onChange={(value) => setFilters({ ...filters, patient_id: value })}
              >
                {patients.map((patient) => (
                    <Select.Option key={patient.id} value={patient.id}>
                      {patient.full_name || patient.name || `بیمار ${patient.id}`}
                    </Select.Option>
                ))}
              </Select>
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <JalaliDatePicker
                  placeholder={t('from_date', 'از تاریخ')}
                  size="middle"
                  onChange={(date) => setFilters({ ...filters, from_date: date })}
              />
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <JalaliDatePicker
                  placeholder={t('to_date', 'تا تاریخ')}
                  size="middle"
                  onChange={(date) => setFilters({ ...filters, to_date: date })}
              />
            </Col>
            <Col xs={24} sm={24} md={24} lg={24}>
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
              dataSource={invoices}
              loading={loading}
              rowKey="id"
              pagination={{
                current: pagination.current,
                pageSize: pagination.pageSize,
                total: pagination.total,
                showSizeChanger: true,
                showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'فاکتور')}`,
                onChange: (page, pageSize) => {
                  setPagination({ ...pagination, current: page, pageSize });
                },
              }}
              scroll={{ x: 1300 }}
              locale={{
                emptyText: t('no_invoices', 'هیچ فاکتوری یافت نشد'),
              }}
          />
        </Card>

        {/* ===== مودال مشاهده جزئیات ===== */}
        <Modal
            title={t('invoice_details', 'جزئیات فاکتور')}
            open={isModalVisible}
            onCancel={() => setIsModalVisible(false)}
            footer={[
              <Button key="close" onClick={() => setIsModalVisible(false)}>
                {t('close', 'بستن')}
              </Button>,
              <Button
                  key="print"
                  icon={<PrinterOutlined />}
                  onClick={() => selectedInvoice && handlePrint(selectedInvoice.id)}
              >
                {t('print', 'چاپ')}
              </Button>,
            ]}
            width={600}
        >
          {selectedInvoice && (
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
                  <FileTextOutlined style={{ fontSize: 32, color: '#2563eb' }} />
                  <div>
                    <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedInvoice.invoice_number}</div>
                    <div style={{ color: '#64748b' }}>
                      {t('status', 'وضعیت')}:{' '}
                      <Badge
                          color={statusMap[selectedInvoice.status]?.color || 'default'}
                          text={statusMap[selectedInvoice.status]?.label || selectedInvoice.status}
                      />
                    </div>
                  </div>
                </div>

                <Row gutter={[16, 16]}>
                  <Col span={12}>
                    <Text type="secondary">{t('patient', 'بیمار')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedInvoice.patient?.full_name || selectedInvoice.patient?.name || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('appointment', 'نوبت')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedInvoice.appointment?.code || '—'}</div>
                  </Col>
                  <Col span={8}>
                    <Text type="secondary">{t('amount', 'مبلغ')}</Text>
                    <div style={{ fontWeight: 500 }}>{Number(selectedInvoice.amount || 0).toLocaleString()} تومان</div>
                  </Col>
                  <Col span={8}>
                    <Text type="secondary">{t('tax', 'مالیات')}</Text>
                    <div style={{ fontWeight: 500 }}>{Number(selectedInvoice.tax || 0).toLocaleString()} تومان</div>
                  </Col>
                  <Col span={8}>
                    <Text type="secondary">{t('discount', 'تخفیف')}</Text>
                    <div style={{ fontWeight: 500 }}>{Number(selectedInvoice.discount || 0).toLocaleString()} تومان</div>
                  </Col>
                  <Col span={24}>
                    <Text type="secondary">{t('total', 'مبلغ کل')}</Text>
                    <div style={{ fontSize: 18, fontWeight: 700, color: '#2563eb' }}>
                      {Number(selectedInvoice.total_amount || 0).toLocaleString()} تومان
                    </div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('created_at', 'تاریخ ایجاد')}</Text>
                    <div style={{ fontWeight: 500 }}>
                      {formatJalaliDate(selectedInvoice.created_at)}
                    </div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('paid_at', 'تاریخ پرداخت')}</Text>
                    <div style={{ fontWeight: 500 }}>
                      {formatJalaliDate(selectedInvoice.paid_at)}
                    </div>
                  </Col>
                  <Col span={24}>
                    <Text type="secondary">{t('description', 'توضیحات')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedInvoice.description || '—'}</div>
                  </Col>
                </Row>
              </div>
          )}
        </Modal>
      </div>
  );
}