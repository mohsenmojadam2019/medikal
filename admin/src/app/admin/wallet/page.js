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
  Form,
  InputNumber,
  Statistic,
} from 'antd';
import {
  SearchOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  WalletOutlined,
  PlusOutlined,
  DollarOutlined,
  UserOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
} from '@ant-design/icons';
import { walletService, usersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function WalletPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [wallets, setWallets] = useState([]);
  const [users, setUsers] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedUser, setSelectedUser] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [isBonusModalVisible, setIsBonusModalVisible] = useState(false);
  const [stats, setStats] = useState(null);
  const [bonusForm] = Form.useForm();
  const [bonusLoading, setBonusLoading] = useState(false);

  // ===== دریافت آمار =====
  const fetchStats = async () => {
    try {
      const response = await walletService.getStats();
      setStats(response.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ===== دریافت لیست کیف پول‌ها =====
  const fetchWallets = async (params = {}) => {
    setLoading(true);
    try {
      const response = await walletService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setWallets(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching wallets:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  // ===== دریافت لیست کاربران =====
  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const response = await usersService.getAll({ per_page: 100 });
        setUsers(response.data || []);
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    };
    fetchUsers();
  }, []);

  useEffect(() => {
    fetchWallets();
    fetchStats();
  }, [pagination.current, pagination.pageSize]);

  const handleSearch = () => {
    fetchWallets({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchWallets({ page: 1, search: '', ...filters });
  };

  const handleView = (record) => {
    setSelectedUser(record);
    setIsModalVisible(true);
  };

  const handleToggleStatus = async (userId) => {
    try {
      await walletService.toggleStatus(userId);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchWallets();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleAddBonus = (record) => {
    setSelectedUser(record);
    bonusForm.resetFields();
    setIsBonusModalVisible(true);
  };

  const handleBonusSubmit = async (values) => {
    if (!selectedUser) return;

    setBonusLoading(true);
    try {
      await walletService.addBonus(selectedUser.user_id, values.amount, values.description);
      message.success(t('bonus_added', 'پاداش با موفقیت اضافه شد'));
      setIsBonusModalVisible(false);
      fetchWallets();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در اضافه کردن پاداش'));
    } finally {
      setBonusLoading(false);
    }
  };

  const columns = [
    {
      title: t('user', 'کاربر'),
      dataIndex: 'user',
      key: 'user',
      render: (user) => (
        <Space>
          <Avatar icon={<UserOutlined />} size="small" />
          <span>{user?.name || '—'}</span>
        </Space>
      ),
    },
    {
      title: t('balance', 'موجودی'),
      dataIndex: 'balance',
      key: 'balance',
      render: (balance) => (
        <span style={{ fontWeight: 600, color: balance > 0 ? '#10b981' : '#64748b' }}>
          {Number(balance || 0).toLocaleString()} تومان
        </span>
      ),
    },
    {
      title: t('transactions', 'تعداد تراکنش'),
      dataIndex: 'transactions_count',
      key: 'transactions_count',
      render: (count) => count || 0,
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
      title: t('last_transaction', 'آخرین تراکنش'),
      dataIndex: 'last_transaction_at',
      key: 'last_transaction_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD HH:mm') : '—',
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
          <Tooltip title={t('add_bonus', 'افزودن پاداش')}>
            <Button
              type="text"
              icon={<PlusOutlined />}
              onClick={() => handleAddBonus(record)}
              size="small"
              style={{ color: '#10b981' }}
            />
          </Tooltip>
          <Tooltip title={t('toggle_status', 'تغییر وضعیت')}>
            <Button
              type="text"
              icon={record.is_active ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
              onClick={() => handleToggleStatus(record.user_id)}
              size="small"
              style={{ color: record.is_active ? '#ef4444' : '#10b981' }}
            />
          </Tooltip>
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
            {t('wallet_management', 'مدیریت کیف پول')}
          </Title>
          <Text type="secondary">
            {t('wallet_subtitle', 'مدیریت کیف پول کاربران')}
          </Text>
        </div>
      </div>

      {/* ===== آمار ===== */}
      {stats && (
        <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
          <Col xs={24} sm={12} md={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('total_balance', 'کل موجودی')}
                value={stats.total_balance || 0}
                prefix={<WalletOutlined style={{ color: '#2563eb' }} />}
                formatter={(value) => `${Number(value).toLocaleString()} تومان`}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('active_wallets', 'کیف پول فعال')}
                value={stats.active_wallets || 0}
                valueStyle={{ color: '#10b981' }}
              />
            </Card>
          </Col>
          <Col xs={24} sm={12} md={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
              }}
            >
              <Statistic
                title={t('total_transactions', 'تعداد تراکنش‌ها')}
                value={stats.total_transactions || 0}
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
        <Row gutter={[16, 16]} align="middle">
          <Col xs={24} sm={12} md={8} lg={6}>
            <Input
              placeholder={t('search_user', 'جستجوی کاربر...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_status', 'فیلتر وضعیت')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, is_active: value })}
            >
              <Select.Option value={true}>{t('active', 'فعال')}</Select.Option>
              <Select.Option value={false}>{t('inactive', 'غیرفعال')}</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
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
          dataSource={wallets}
          loading={loading}
          rowKey="user_id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'کیف پول')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1000 }}
          locale={{
            emptyText: t('no_wallets', 'هیچ کیف پولی یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده جزئیات ===== */}
      <Modal
        title={t('wallet_details', 'جزئیات کیف پول')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsModalVisible(false)}>
            {t('close', 'بستن')}
          </Button>,
        ]}
        width={600}
      >
        {selectedUser && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <WalletOutlined style={{ fontSize: 32, color: '#2563eb' }} />
              <div>
                <div style={{ fontSize: 18, fontWeight: 700 }}>
                  {selectedUser.user?.name || '—'}
                </div>
                <div style={{ color: '#64748b' }}>
                  {t('balance', 'موجودی')}: {Number(selectedUser.balance || 0).toLocaleString()} تومان
                </div>
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    status={selectedUser.is_active ? 'success' : 'error'}
                    text={selectedUser.is_active ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
                  />
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('transactions', 'تعداد تراکنش')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedUser.transactions_count || 0}</div>
              </Col>
              <Col span={24}>
                <Text type="secondary">{t('last_transaction', 'آخرین تراکنش')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedUser.last_transaction_at ? dayjs(selectedUser.last_transaction_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                </div>
              </Col>
            </Row>

            <Divider />

            <Title level={5}>{t('recent_transactions', 'تراکنش‌های اخیر')}</Title>
            <Table
              dataSource={selectedUser.recent_transactions || []}
              rowKey="id"
              pagination={false}
              size="small"
              columns={[
                { title: t('type', 'نوع'), dataIndex: 'type', key: 'type' },
                { title: t('amount', 'مبلغ'), dataIndex: 'amount', key: 'amount', render: (amount) => `${Number(amount).toLocaleString()} تومان` },
                { title: t('date', 'تاریخ'), dataIndex: 'created_at', key: 'created_at', render: (date) => dayjs(date).format('jYYYY/jMM/jDD') },
              ]}
            />
          </div>
        )}
      </Modal>

      {/* ===== مودال افزودن پاداش ===== */}
      <Modal
        title={t('add_bonus', 'افزودن پاداش')}
        open={isBonusModalVisible}
        onCancel={() => setIsBonusModalVisible(false)}
        footer={null}
        width={450}
      >
        {selectedUser && (
          <div>
            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t('user', 'کاربر')}</Text>
              <div style={{ fontWeight: 600 }}>{selectedUser.user?.name || '—'}</div>
            </div>

            <Form form={bonusForm} onFinish={handleBonusSubmit} layout="vertical">
              <Form.Item
                name="amount"
                label={t('amount', 'مبلغ (تومان)')}
                rules={[
                  { required: true, message: t('required', 'لطفاً مبلغ را وارد کنید') },
                  { type: 'number', min: 1000, message: t('min_1000', 'مبلغ باید حداقل ۱۰۰۰ تومان باشد') },
                ]}
              >
                <InputNumber
                  style={{ width: '100%' }}
                  placeholder={t('amount_placeholder', '۱۰۰۰۰۰')}
                  formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                  parser={(value) => value?.replace(/\$\s?|(,*)/g, '')}
                />
              </Form.Item>

              <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
              >
                <Input.TextArea
                  rows={2}
                  placeholder={t('description_placeholder', 'توضیحات پاداش...')}
                />
              </Form.Item>

              <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                <Button onClick={() => setIsBonusModalVisible(false)}>
                  {t('cancel', 'انصراف')}
                </Button>
                <Button
                  type="primary"
                  htmlType="submit"
                  loading={bonusLoading}
                  style={{
                    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    border: 'none',
                  }}
                >
                  {t('add_bonus', 'افزودن پاداش')}
                </Button>
              </div>
            </Form>
          </div>
        )}
      </Modal>
    </div>
  );
}
