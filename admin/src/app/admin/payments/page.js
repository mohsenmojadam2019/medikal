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
  Avatar,
  Tabs,
  Statistic,
} from 'antd';
import {
  SearchOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  CreditCardOutlined,
  DollarOutlined,
  UserOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  SyncOutlined,
} from '@ant-design/icons';
import { paymentsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function PaymentsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [payments, setPayments] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');
  const [stats, setStats] = useState(null);

  // ===== دریافت آمار =====
  const fetchStats = async () => {
    try {
      const response = await paymentsService.getStats();
      setStats(response.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ===== دریافت لیست پرداخت‌ها =====
  const fetchPayments = async (params = {}) => {
    setLoading(true);
    try {
      const response = await paymentsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });
      setPayments(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching payments:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
    fetchStats();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchPayments({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchPayments({ page: 1, search: '', ...filters });
  };

  const handleRefund = async (id) => {
    try {
      await paymentsService.refund(id);
      message.success(t('refunded', 'بازگشت وجه با موفقیت انجام شد'));
      fetchPayments();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در بازگشت وجه'));
    }
  };

  const handleView = (record) => {
    setSelectedPayment(record);
    setIsModalVisible(true);
  };

  // ===== وضعیت‌های پرداخت =====
  const statusMap = {
    pending: { color: 'orange', label: 'در انتظار' },
    success: { color: 'green', label: 'موفق' },
    failed: { color: 'red', label: 'ناموفق' },
    refunded: { color: 'blue', label: 'عودت داده شده' },
  };

  const columns = [
    {
      title: t('transaction_id', 'شماره تراکنش'),
      dataIndex: 'transaction_id',
      key: 'transaction_id',
      render: (text) => <span style={{ fontWeight: 700 }}>{text || '—'}</span>,
    },
    {
      title: t('invoice', 'فاکتور'),
      dataIndex: 'invoice',
      key: 'invoice',
      render: (invoice) => invoice?.invoice_number || '—',
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
      title: t('amount', 'مبلغ'),
      dataIndex: 'amount',
      key: 'amount',
      render: (amount) => amount ? `${Number(amount).toLocaleString()} تومان` : '—',
    },
    {
      title: t('gateway', 'درگاه'),
      dataIndex: 'gateway',
      key: 'gateway',
      render: (gateway) => {
        const gatewayMap = {
          zarinpal: 'زرین‌پال',
          paypal: 'پی‌پال',
          cash: 'نقدی',
          wallet: 'کیف پول',
        };
        return gatewayMap[gateway] || gateway || '—';
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
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      width: 200,
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
          {record.status === 'success' && (
            <Tooltip title={t('refund', 'بازگشت وجه')}>
              <Button
                type="text"
                icon={<SyncOutlined />}
                onClick={() => handleRefund(record.id)}
                size="small"
                style={{ color: '#ef4444' }}
              />
            </Tooltip>
          )}
        </Space>
      ),
    },
  ];

  // ===== آیتم‌های تب =====
  const tabItems = [
    { key: 'all', label: t('all', 'همه') },
    { key: 'pending', label: t('pending', 'در انتظار') },
    { key: 'success', label: t('success', 'موفق') },
    { key: 'failed', label: t('failed', 'ناموفق') },
    { key: 'refunded', label: t('refunded', 'عودت داده شده') },
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
            {t('payments_management', 'مدیریت پرداخت‌ها')}
          </Title>
          <Text type="secondary">
            {t('payments_subtitle', 'تاریخچه پرداخت‌های انجام‌شده')}
          </Text>
        </div>
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
                title={t('total_payments', 'تعداد پرداخت‌ها')}
                value={stats.total_payments || 0}
                prefix={<CreditCardOutlined style={{ color: '#2563eb' }} />}
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
                title={t('total_amount', 'مبلغ کل')}
                value={stats.total_amount || 0}
                prefix={<DollarOutlined style={{ color: '#10b981' }} />}
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
                title={t('success_count', 'موفق')}
                value={stats.success_count || 0}
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
                title={t('failed_count', 'ناموفق')}
                value={stats.failed_count || 0}
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
              placeholder={t('search_payment', 'جستجوی پرداخت...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_gateway', 'فیلتر درگاه')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, gateway: value })}
            >
              <Select.Option value="zarinpal">زرین‌پال</Select.Option>
              <Select.Option value="cash">نقدی</Select.Option>
              <Select.Option value="wallet">کیف پول</Select.Option>
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
          dataSource={payments}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'پرداخت')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1200 }}
          locale={{
            emptyText: t('no_payments', 'هیچ پرداختی یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده جزئیات ===== */}
      <Modal
        title={t('payment_details', 'جزئیات پرداخت')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsModalVisible(false)}>
            {t('close', 'بستن')}
          </Button>,
        ]}
        width={600}
      >
        {selectedPayment && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <CreditCardOutlined style={{ fontSize: 32, color: '#2563eb' }} />
              <div>
                <div style={{ fontSize: 18, fontWeight: 700 }}>
                  {selectedPayment.transaction_id || t('no_transaction_id', 'بدون شماره تراکنش')}
                </div>
                <div style={{ color: '#64748b' }}>
                  {t('status', 'وضعیت')}:{' '}
                  <Badge
                    color={statusMap[selectedPayment.status]?.color || 'default'}
                    text={statusMap[selectedPayment.status]?.label || selectedPayment.status}
                  />
                </div>
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('invoice', 'فاکتور')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPayment.invoice?.invoice_number || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('patient', 'بیمار')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPayment.patient?.full_name || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('amount', 'مبلغ')}</Text>
                <div style={{ fontWeight: 500 }}>{Number(selectedPayment.amount || 0).toLocaleString()} تومان</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('gateway', 'درگاه')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedPayment.gateway === 'zarinpal' ? 'زرین‌پال' :
                   selectedPayment.gateway === 'paypal' ? 'پی‌پال' :
                   selectedPayment.gateway === 'cash' ? 'نقدی' :
                   selectedPayment.gateway === 'wallet' ? 'کیف پول' :
                   selectedPayment.gateway || '—'}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('reference_code', 'کد پیگیری')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPayment.reference_code || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('date', 'تاریخ')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedPayment.created_at ? dayjs(selectedPayment.created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                </div>
              </Col>
              <Col span={24}>
                <Text type="secondary">{t('description', 'توضیحات')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPayment.description || '—'}</div>
              </Col>
            </Row>
          </div>
        )}
      </Modal>
    </div>
  );
}
