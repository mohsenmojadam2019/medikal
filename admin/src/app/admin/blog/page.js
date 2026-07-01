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
  Image,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  FileTextOutlined,
  TagOutlined,
  CalendarOutlined,
} from '@ant-design/icons';
import { blogService, categoriesService, tagsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function BlogPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [posts, setPosts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [tags, setTags] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedPost, setSelectedPost] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [activeTab, setActiveTab] = useState('all');
  const [stats, setStats] = useState(null);

  // ===== دریافت آمار =====
  const fetchStats = async () => {
    try {
      const response = await blogService.getStats();
      setStats(response.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ===== دریافت لیست دسته‌بندی‌ها و تگ‌ها =====
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await categoriesService.getAll();
        setCategories(response.data || []);
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };
    const fetchTags = async () => {
      try {
        const response = await tagsService.getAll();
        setTags(response.data || []);
      } catch (error) {
        console.error('Error fetching tags:', error);
      }
    };
    fetchCategories();
    fetchTags();
  }, []);

  // ===== دریافت لیست مقالات =====
  const fetchPosts = async (params = {}) => {
    setLoading(true);
    try {
      const response = await blogService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        status: activeTab !== 'all' ? activeTab : undefined,
        ...filters,
        ...params,
      });
      setPosts(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching posts:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPosts();
    fetchStats();
  }, [pagination.current, pagination.pageSize, activeTab]);

  const handleSearch = () => {
    fetchPosts({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchPosts({ page: 1, search: '', ...filters });
  };

  const handleDelete = async (id) => {
    try {
      await blogService.delete(id);
      message.success(t('deleted', 'مقاله با موفقیت حذف شد'));
      fetchPosts();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در حذف مقاله'));
    }
  };

  const handlePublish = async (id) => {
    try {
      await blogService.publish(id);
      message.success(t('published', 'مقاله با موفقیت منتشر شد'));
      fetchPosts();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در انتشار مقاله'));
    }
  };

  const handleUnpublish = async (id) => {
    try {
      await blogService.unpublish(id);
      message.success(t('unpublished', 'مقاله با موفقیت از انتشار خارج شد'));
      fetchPosts();
      fetchStats();
    } catch (error) {
      message.error(t('error', 'خطا در خارج کردن از انتشار'));
    }
  };

  const handleView = (record) => {
    setSelectedPost(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/blog/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/blog/create');
  };

  // ===== وضعیت‌های مقاله =====
  const statusMap = {
    draft: { color: 'default', label: 'پیش‌نویس' },
    published: { color: 'green', label: 'منتشر شده' },
    archived: { color: 'orange', label: 'بایگانی شده' },
  };

  const columns = [
    {
      title: t('title', 'عنوان'),
      dataIndex: 'title',
      key: 'title',
      render: (text, record) => (
        <Space>
          {record.featured_image && (
            <Image
              src={record.featured_image}
              alt={text}
              width={50}
              height={50}
              style={{ objectFit: 'cover', borderRadius: 4 }}
              preview={false}
            />
          )}
          <div>
            <div style={{ fontWeight: 600 }}>{text}</div>
            <div style={{ fontSize: 12, color: '#64748b' }}>
              {record.category?.name || t('no_category', 'بدون دسته‌بندی')}
            </div>
          </div>
        </Space>
      ),
    },
    {
      title: t('category', 'دسته‌بندی'),
      dataIndex: 'category',
      key: 'category',
      render: (category) => category?.name || '—',
    },
    {
      title: t('tags', 'تگ‌ها'),
      dataIndex: 'tags',
      key: 'tags',
      render: (tags) => (
        <Space wrap>
          {tags?.slice(0, 3).map((tag) => (
            <Tag key={tag.id} color="blue">{tag.name}</Tag>
          ))}
          {tags?.length > 3 && <Tag>+{tags.length - 3}</Tag>}
        </Space>
      ),
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
      title: t('views', 'بازدید'),
      dataIndex: 'views',
      key: 'views',
      render: (views) => views || 0,
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
          {record.status === 'draft' && (
            <Tooltip title={t('publish', 'انتشار')}>
              <Button
                type="text"
                icon={<CheckCircleOutlined />}
                onClick={() => handlePublish(record.id)}
                size="small"
                style={{ color: '#10b981' }}
              />
            </Tooltip>
          )}
          {record.status === 'published' && (
            <Tooltip title={t('unpublish', 'خارج از انتشار')}>
              <Button
                type="text"
                icon={<CloseCircleOutlined />}
                onClick={() => handleUnpublish(record.id)}
                size="small"
                style={{ color: '#f59e0b' }}
              />
            </Tooltip>
          )}
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این مقاله اطمینان دارید؟')}
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
    { key: 'published', label: t('published', 'منتشر شده') },
    { key: 'draft', label: t('draft', 'پیش‌نویس') },
    { key: 'archived', label: t('archived', 'بایگانی شده') },
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
            {t('blog_management', 'مدیریت وبلاگ')}
          </Title>
          <Text type="secondary">
            {t('blog_subtitle', 'مدیریت مقالات و مطالب وبلاگ')}
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
          {t('new_post', 'مقاله جدید')}
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
                title={t('total_posts', 'تعداد مقالات')}
                value={stats.total || 0}
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
                title={t('published_posts', 'منتشر شده')}
                value={stats.published || 0}
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
                title={t('draft_posts', 'پیش‌نویس')}
                value={stats.draft || 0}
                valueStyle={{ color: '#f59e0b' }}
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
                title={t('total_views', 'کل بازدیدها')}
                value={stats.total_views || 0}
                prefix={<EyeOutlined style={{ color: '#2563eb' }} />}
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
              placeholder={t('search_post', 'جستجوی مقاله...')}
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onPressEnter={handleSearch}
              allowClear
            />
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_category', 'فیلتر دسته‌بندی')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, category_id: value })}
            >
              {categories.map((cat) => (
                <Select.Option key={cat.id} value={cat.id}>
                  {cat.name}
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
          dataSource={posts}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'مقاله')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1300 }}
          locale={{
            emptyText: t('no_posts', 'هیچ مقاله‌ای یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده جزئیات ===== */}
      <Modal
        title={t('post_details', 'جزئیات مقاله')}
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
              if (selectedPost) {
                router.push(`/admin/blog/${selectedPost.id}/edit`);
              }
            }}
          >
            {t('edit', 'ویرایش')}
          </Button>,
        ]}
        width={600}
      >
        {selectedPost && (
          <div>
            {selectedPost.featured_image && (
              <div style={{ textAlign: 'center', marginBottom: 16 }}>
                <Image
                  src={selectedPost.featured_image}
                  alt={selectedPost.title}
                  width="100%"
                  style={{ maxHeight: 200, objectFit: 'cover', borderRadius: 8 }}
                />
              </div>
            )}

            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t('title', 'عنوان')}</Text>
              <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedPost.title}</div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('category', 'دسته‌بندی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPost.category?.name || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    color={statusMap[selectedPost.status]?.color || 'default'}
                    text={statusMap[selectedPost.status]?.label || selectedPost.status}
                  />
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('views', 'بازدید')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedPost.views || 0}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('date', 'تاریخ')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedPost.created_at ? dayjs(selectedPost.created_at).format('jYYYY/jMM/jDD') : '—'}
                </div>
              </Col>
            </Row>

            <Divider />

            <div>
              <Text type="secondary">{t('excerpt', 'خلاصه')}</Text>
              <div style={{ padding: '8px 12px', background: '#f8fafc', borderRadius: 8, marginTop: 4 }}>
                {selectedPost.excerpt || '—'}
              </div>
            </div>

            <div style={{ marginTop: 12 }}>
              <Text type="secondary">{t('content', 'محتوا')}</Text>
              <div
                style={{
                  padding: '8px 12px',
                  background: '#f8fafc',
                  borderRadius: 8,
                  marginTop: 4,
                  maxHeight: 200,
                  overflow: 'auto',
                }}
                dangerouslySetInnerHTML={{ __html: selectedPost.content || '—' }}
              />
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
