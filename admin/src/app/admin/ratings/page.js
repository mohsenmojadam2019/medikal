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
  Rate,
  Form,
  Tabs,
  Statistic,
} from 'antd';
import {
  SearchOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  ReplyOutlined,
  StarOutlined,
  UserOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
} from '@ant-design/icons';
import { ratingsService, doctorsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function RatingsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [ratings, setRatings] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedRating, setSelectedRating] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [isReplyModalVisible, setIsReplyModalVisible] = useState(false);
  const [replyForm] = Form.useForm();
  const [replyLoading, setReplyLoading] = useState(false);
  const [stats, setStats] = useState(null);
  const [activeTab, setActiveTab] = useState('all');

  // ===== دریافت آمار =====
  const fetchStats = async () => {
    try {
      const response = await ratingsService.getStats();
      setStats(response.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ===== دریافت لیست نظرات =====
  const fetchRatings = async (params = {}) => {
    setLoading(true);
    try {
      const response = await ratingsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setRatings(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching ratings:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRatings();
    fetchStats();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchRatings({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchRatings({ page: 1, search: '', ...filters });
  };

  const handleDelete = async (id) => {
    try {
      await ratingsService.delete(id);
      message.success(t('deleted', 'نظر با موفقیت حذف شد'));
      fetchRatings();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در حذف نظر'));
    }
  };

  const handleView = (record) => {
    setSelectedRating(record);
    setIsModalVisible(true);
  };

  const handleReply = (record) => {
    setSelectedRating(record);
    replyForm.resetFields();
    setIsReplyModalVisible(true);
  };

  const handleReplySubmit = async (values) => {
    if (!selectedRating) return;
    
    setReplyLoading(true);
    try {
      await ratingsService.reply(selectedRating.id, values.reply);
      message.success(t('reply_sent', 'پاسخ با موفقیت ارسال شد'));
      setIsReplyModalVisible(false);
      fetchRatings();
    } catch (error) {
      message.error(t('error', 'خطا در ارسال پاسخ'));
    } finally {
      setReplyLoading(false);
    }
  };

  const handleDeleteReply = async (id) => {
    try {
      await ratingsService.deleteReply(id);
      message.success(t('reply_deleted', 'پاسخ با موفقیت حذف شد'));
      fetchRatings();
    } catch (error) {
      message.error(t('error', 'خطا در حذف پاسخ'));
    }
  };

  // ===== ستون‌های جدول =====
  const columns = [
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
      title: t('rating', 'امتیاز'),
      dataIndex: 'rating',
      key: 'rating',
      render: (rating) => (
        <Rate disabled defaultValue={rating} allowHalf count={5} style={{ fontSize: 16 }} />
      ),
    },
    {
      title: t('comment', 'نظر'),
      dataIndex: 'comment',
      key: 'comment',
      ellipsis: true,
      render: (comment) => comment || '—',
    },
    {
      title: t('reply', 'پاسخ'),
      dataIndex: 'reply',
      key: 'reply',
      render: (reply) => reply ? (
        <Tooltip title={reply}>
          <Tag color="blue">{t('has_reply', 'پاسخ داده شده')}</Tag>
        </Tooltip>
      ) : (
        <Tag color="default">{t('no_reply', 'بدون پاسخ')}</Tag>
      ),
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date) => date ? dayjs(date).format('jYYYY/jMM/jDD') : '—',
    },
    {
      title: t('status', 'وضعیت'),
      dataIndex: 'is_approved',
      key: 'is_approved',
      render: (isApproved) => (
        <Badge
          status={isApproved ? 'success' : 'warning'}
          text={isApproved ? t('approved', 'تایید شده') : t('pending_approval', 'در انتظار تایید')}
        />
      ),
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
          <Tooltip title={t('reply', 'پاسخ')}>
            <Button
              type="text"
              icon={<ReplyOutlined />}
              onClick={() => handleReply(record)}
              size="small"
              style={{ color: '#2563eb' }}
            />
          </Tooltip>
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این نظر اطمینان دارید؟')}
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
    { key: 'approved', label: t('approved', 'تایید شده') },
    { key: 'pending', label: t('pending_approval', 'در انتظار تایید') },
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
            {t('ratings_management', 'مدیریت نظرات و امتیازات')}
          </Title>
          <Text type="secondary">
            {t('ratings_subtitle', 'مدیریت نظرات و امتیازات بیماران')}
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
                title={t('average_rating', 'میانگین امتیاز')}
                value={stats.average_rating || 0}
                precision={1}
                prefix={<StarOutlined style={{ color: '#f59e0b' }} />}
                suffix={`/ 5`}
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
                title={t('total_ratings', 'تعداد نظرات')}
                value={stats.total_ratings || 0}
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
                title={t('satisfaction_rate', 'رضایت‌مندی')}
                value={stats.satisfaction_rate || 0}
                suffix="%"
                valueStyle={{ color: stats.satisfaction_rate > 80 ? '#10b981' : '#f59e0b' }}
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
                title={t('with_reply', 'پاسخ داده شده')}
                value={stats.with_reply || 0}
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
              placeholder={t('search_rating', 'جستجوی نظر...')}
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
              placeholder={t('filter_rating', 'فیلتر امتیاز')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, rating: value })}
            >
              {[1, 2, 3, 4, 5].map((r) => (
                <Select.Option key={r} value={r}>
                  {r} {t('stars', 'ستاره')}
                </Select.Option>
              ))}
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
          dataSource={ratings}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'نظر')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1200 }}
          locale={{
            emptyText: t('no_ratings', 'هیچ نظری یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده جزئیات ===== */}
      <Modal
        title={t('rating_details', 'جزئیات نظر')}
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={[
          <Button key="close" onClick={() => setIsModalVisible(false)}>
            {t('close', 'بستن')}
          </Button>,
        ]}
        width={600}
      >
        {selectedRating && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <Avatar icon={<UserOutlined />} size={48} />
              <div>
                <div style={{ fontSize: 16, fontWeight: 600 }}>
                  {selectedRating.patient?.full_name || '—'}
                </div>
                <div style={{ color: '#64748b' }}>
                  {t('doctor', 'پزشک')}: {selectedRating.doctor?.full_name || '—'}
                </div>
              </div>
            </div>

            <div style={{ marginBottom: 16 }}>
              <Rate disabled defaultValue={selectedRating.rating} allowHalf count={5} />
            </div>

            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t('comment', 'نظر')}</Text>
              <div style={{ padding: '8px 12px', background: '#f8fafc', borderRadius: 8, marginTop: 4 }}>
                {selectedRating.comment || '—'}
              </div>
            </div>

            {selectedRating.reply && (
              <div style={{ marginBottom: 16 }}>
                <Text type="secondary">{t('reply', 'پاسخ پزشک')}</Text>
                <div style={{ padding: '8px 12px', background: '#dbeafe', borderRadius: 8, marginTop: 4 }}>
                  {selectedRating.reply}
                </div>
                <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>
                  {t('replied_at', 'تاریخ پاسخ')}: {selectedRating.replied_at ? dayjs(selectedRating.replied_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                </div>
              </div>
            )}

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('date', 'تاریخ')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedRating.created_at ? dayjs(selectedRating.created_at).format('jYYYY/jMM/jDD') : '—'}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    status={selectedRating.is_approved ? 'success' : 'warning'}
                    text={selectedRating.is_approved ? t('approved', 'تایید شده') : t('pending_approval', 'در انتظار تایید')}
                  />
                </div>
              </Col>
            </Row>
          </div>
        )}
      </Modal>

      {/* ===== مودال پاسخ به نظر ===== */}
      <Modal
        title={t('reply_to_rating', 'پاسخ به نظر')}
        open={isReplyModalVisible}
        onCancel={() => setIsReplyModalVisible(false)}
        footer={null}
        width={500}
      >
        {selectedRating && (
          <div>
            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t('original_comment', 'نظر اصلی')}</Text>
              <div style={{ padding: '8px 12px', background: '#f8fafc', borderRadius: 8, marginTop: 4 }}>
                {selectedRating.comment || '—'}
              </div>
            </div>

            <Form form={replyForm} onFinish={handleReplySubmit} layout="vertical">
              <Form.Item
                name="reply"
                label={t('your_reply', 'پاسخ شما')}
                rules={[{ required: true, message: t('required', 'لطفاً پاسخ را وارد کنید') }]}
              >
                <TextArea
                  rows={4}
                  placeholder={t('reply_placeholder', 'متن پاسخ...')}
                />
              </Form.Item>

              <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                <Button onClick={() => setIsReplyModalVisible(false)}>
                  {t('cancel', 'انصراف')}
                </Button>
                <Button
                  type="primary"
                  htmlType="submit"
                  loading={replyLoading}
                  style={{
                    background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                    border: 'none',
                  }}
                >
                  {t('send_reply', 'ارسال پاسخ')}
                </Button>
              </div>
            </Form>
          </div>
        )}
      </Modal>
    </div>
  );
}
