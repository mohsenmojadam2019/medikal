// src/app/admin/seo/page.js

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
  Form,
  Tabs,
  Divider,
  App,
} from 'antd';
import {
  SearchOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  ReloadOutlined,
  ExportOutlined,
  SaveOutlined,
} from '@ant-design/icons';
import { seoService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function SeoPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();

  const [loading, setLoading] = useState(false);
  const [seoItems, setSeoItems] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedSeo, setSelectedSeo] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [isEditModalVisible, setIsEditModalVisible] = useState(false);
  const [editForm] = Form.useForm();
  const [editLoading, setEditLoading] = useState(false);

  // ===== دریافت لیست سئو =====
  const fetchSeo = async (params = {}) => {
    setLoading(true);
    try {
      const response = await seoService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });

      // ✅ بررسی ساختار پاسخ
      if (response.data?.success) {
        const data = response.data.data;
        const list = data?.data || data || [];
        setSeoItems(Array.isArray(list) ? list : []);
        setPagination({
          ...pagination,
          total: data?.total || (Array.isArray(list) ? list.length : 0),
          current: data?.current_page || 1,
        });
      } else {
        setSeoItems([]);
        setPagination({
          ...pagination,
          total: 0,
        });
      }
    } catch (error) {
      console.error('Error fetching seo:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      setSeoItems([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSeo();
  }, [pagination.current, pagination.pageSize]);

  const handleSearch = () => {
    fetchSeo({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchSeo({ page: 1 });
  };

  const handleDelete = async (id) => {
    try {
      await seoService.delete(id);
      message.success(t('deleted', 'سئو با موفقیت حذف شد'));
      fetchSeo();
    } catch (error) {
      message.error(t('error', 'خطا در حذف سئو'));
    }
  };

  const handleView = (record) => {
    setSelectedSeo(record);
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    setSelectedSeo(record);
    editForm.setFieldsValue(record);
    setIsEditModalVisible(true);
  };

  const handleEditSubmit = async (values) => {
    if (!selectedSeo) return;

    setEditLoading(true);
    try {
      await seoService.update(selectedSeo.id, values);
      message.success(t('updated', 'سئو با موفقیت به‌روزرسانی شد'));
      setIsEditModalVisible(false);
      fetchSeo();
    } catch (error) {
      console.error('Error updating seo:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setEditLoading(false);
    }
  };

  // ===== فرمت تاریخ =====
  const formatJalaliDate = (date) => {
    if (!date) return '—';
    try {
      return moment(date).format('jYYYY/jMM/jDD');
    } catch (error) {
      return '—';
    }
  };

  const columns = [
    {
      title: t('page', 'صفحه'),
      dataIndex: 'seoable_type',
      key: 'seoable_type',
      render: (type, record) => (
          <div>
            <div style={{ fontWeight: 600 }}>
              {record.seoable?.name || record.seoable?.title || record.seoable?.full_name || '—'}
            </div>
            <div style={{ fontSize: 12, color: '#64748b' }}>
              {type ? type.replace('App\\Models\\', '') : '—'}
            </div>
          </div>
      ),
    },
    {
      title: t('title', 'عنوان'),
      dataIndex: 'title',
      key: 'title',
      ellipsis: true,
      render: (text) => text || '—',
    },
    {
      title: t('description', 'توضیحات'),
      dataIndex: 'description',
      key: 'description',
      ellipsis: true,
      render: (text) => text || '—',
    },
    {
      title: t('keywords', 'کلمات کلیدی'),
      dataIndex: 'keywords',
      key: 'keywords',
      render: (keywords) => keywords || '—',
    },
    {
      title: t('robots', 'ربات‌ها'),
      dataIndex: 'robots',
      key: 'robots',
      render: (robots) => robots || '—',
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'updated_at',
      key: 'updated_at',
      render: (date) => date ? formatJalaliDate(date) : '—',
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
            <Tooltip title={t('edit', 'ویرایش')}>
              <Button
                  type="text"
                  icon={<EditOutlined />}
                  onClick={() => handleEdit(record)}
                  size="small"
              />
            </Tooltip>
            <Popconfirm
                title={t('delete_confirm', 'آیا از حذف این سئو اطمینان دارید؟')}
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

  // اگر seoItems آرایه نیست
  if (!Array.isArray(seoItems)) {
    console.error('⚠️ SeoItems is not an array:', seoItems);
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
              {t('seo_management', 'مدیریت سئو')}
            </Title>
            <Text type="secondary">
              {t('seo_subtitle', 'بهینه‌سازی موتورهای جستجو')}
            </Text>
          </div>
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
              <Input
                  placeholder={t('search_seo', 'جستجوی سئو...')}
                  prefix={<SearchOutlined />}
                  value={searchText}
                  onChange={(e) => setSearchText(e.target.value)}
                  onPressEnter={handleSearch}
                  allowClear
              />
            </Col>
            <Col xs={24} sm={12} md={8} lg={6}>
              <Select
                  placeholder={t('filter_type', 'فیلتر نوع')}
                  style={{ width: '100%' }}
                  allowClear
                  onChange={(value) => setFilters({ ...filters, seoable_type: value })}
              >
                <Select.Option value="App\Models\Doctor">پزشک</Select.Option>
                <Select.Option value="App\Models\Patient">بیمار</Select.Option>
                <Select.Option value="App\Models\Post">مقاله</Select.Option>
                <Select.Option value="App\Models\Page">صفحه</Select.Option>
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
              dataSource={seoItems}
              loading={loading}
              rowKey="id"
              pagination={{
                current: pagination.current,
                pageSize: pagination.pageSize,
                total: pagination.total,
                showSizeChanger: true,
                showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'سئو')}`,
                onChange: (page, pageSize) => {
                  setPagination({ ...pagination, current: page, pageSize });
                },
              }}
              scroll={{ x: 1100 }}
              locale={{
                emptyText: t('no_seo', 'هیچ سئویی یافت نشد'),
              }}
          />
        </Card>

        {/* ===== مودال مشاهده جزئیات ===== */}
        <Modal
            title={t('seo_details', 'جزئیات سئو')}
            open={isModalVisible}
            onCancel={() => setIsModalVisible(false)}
            footer={[
              <Button key="close" onClick={() => setIsModalVisible(false)}>
                {t('close', 'بستن')}
              </Button>,
            ]}
            width={500}
        >
          {selectedSeo && (
              <div>
                <div style={{ marginBottom: 16 }}>
                  <Text type="secondary">{t('page', 'صفحه')}</Text>
                  <div style={{ fontWeight: 600 }}>
                    {selectedSeo.seoable?.name || selectedSeo.seoable?.title || selectedSeo.seoable?.full_name || '—'}
                  </div>
                </div>

                <div style={{ marginBottom: 16 }}>
                  <Text type="secondary">{t('title', 'عنوان')}</Text>
                  <div style={{ fontWeight: 500 }}>{selectedSeo.title || '—'}</div>
                </div>

                <div style={{ marginBottom: 16 }}>
                  <Text type="secondary">{t('description', 'توضیحات')}</Text>
                  <div style={{ fontWeight: 500 }}>{selectedSeo.description || '—'}</div>
                </div>

                <div style={{ marginBottom: 16 }}>
                  <Text type="secondary">{t('keywords', 'کلمات کلیدی')}</Text>
                  <div style={{ fontWeight: 500 }}>{selectedSeo.keywords || '—'}</div>
                </div>

                <Row gutter={[16, 16]}>
                  <Col span={12}>
                    <Text type="secondary">{t('robots', 'ربات‌ها')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedSeo.robots || '—'}</div>
                  </Col>
                  <Col span={12}>
                    <Text type="secondary">{t('canonical', 'لینک اصلی')}</Text>
                    <div style={{ fontWeight: 500 }}>{selectedSeo.canonical_url || '—'}</div>
                  </Col>
                </Row>

                <Divider />

                <div style={{ marginBottom: 8 }}>
                  <Text type="secondary">{t('preview', 'پیش‌نمایش')}</Text>
                </div>
                <div
                    style={{
                      background: 'white',
                      border: '1px solid #e8e8f0',
                      borderRadius: 8,
                      padding: 16,
                    }}
                >
                  <div style={{ color: '#1a0dab', fontSize: 18, fontWeight: 500 }}>
                    {selectedSeo.title || '—'}
                  </div>
                  <div style={{ color: '#006621', fontSize: 14 }}>
                    {selectedSeo.canonical_url || 'https://clinic.com'}
                  </div>
                  <div style={{ color: '#545454', fontSize: 13 }}>
                    {selectedSeo.description || '—'}
                  </div>
                </div>
              </div>
          )}
        </Modal>

        {/* ===== مودال ویرایش ===== */}
        <Modal
            title={t('edit_seo', 'ویرایش سئو')}
            open={isEditModalVisible}
            onCancel={() => setIsEditModalVisible(false)}
            footer={null}
            width={550}
        >
          <Form
              form={editForm}
              layout="vertical"
              onFinish={handleEditSubmit}
              size="large"
          >
            <Form.Item
                name="title"
                label={t('title', 'عنوان')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
            >
              <Input placeholder={t('title_placeholder', 'عنوان صفحه...')} />
            </Form.Item>

            <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
            >
              <TextArea
                  rows={3}
                  placeholder={t('description_placeholder', 'توضیحات صفحه...')}
              />
            </Form.Item>

            <Form.Item
                name="keywords"
                label={t('keywords', 'کلمات کلیدی')}
            >
              <Input placeholder={t('keywords_placeholder', 'کلمه کلیدی ۱، کلمه کلیدی ۲')} />
            </Form.Item>

            <Form.Item
                name="robots"
                label={t('robots', 'ربات‌ها')}
            >
              <Select
                  options={[
                    { value: 'index, follow', label: 'index, follow' },
                    { value: 'noindex, follow', label: 'noindex, follow' },
                    { value: 'index, nofollow', label: 'index, nofollow' },
                    { value: 'noindex, nofollow', label: 'noindex, nofollow' },
                  ]}
              />
            </Form.Item>

            <Form.Item
                name="canonical_url"
                label={t('canonical', 'لینک اصلی')}
            >
              <Input placeholder={t('canonical_placeholder', 'https://clinic.com/page')} />
            </Form.Item>

            <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
              <Button onClick={() => setIsEditModalVisible(false)}>
                {t('cancel', 'انصراف')}
              </Button>
              <Button
                  type="primary"
                  htmlType="submit"
                  loading={editLoading}
                  icon={<SaveOutlined />}
                  style={{
                    background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                    border: 'none',
                  }}
              >
                {t('save', 'ذخیره')}
              </Button>
            </div>
          </Form>
        </Modal>
      </div>
  );
}