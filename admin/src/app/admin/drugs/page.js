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
  Form,
  InputNumber,
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
  PlusCircleOutlined,
  MinusCircleOutlined,
  MedicineBoxOutlined,
  TagOutlined,
} from '@ant-design/icons';
import { drugsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';

const { Title, Text } = Typography;

export default function DrugsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [drugs, setDrugs] = useState([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [searchText, setSearchText] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedDrug, setSelectedDrug] = useState(null);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [modalMode, setModalMode] = useState('view');
  const [stockForm] = Form.useForm();
  const [stockLoading, setStockLoading] = useState(false);

  // ===== دریافت لیست داروها =====
  const fetchDrugs = async (params = {}) => {
    setLoading(true);
    try {
      const response = await drugsService.getAll({
        page: pagination.current,
        per_page: pagination.pageSize,
        search: searchText,
        ...filters,
        ...params,
      });
      setDrugs(response.data || []);
      setPagination({
        ...pagination,
        total: response.meta?.total || 0,
        current: response.meta?.current_page || 1,
      });
    } catch (error) {
      console.error('Error fetching drugs:', error);
      message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDrugs();
  }, [pagination.current, pagination.pageSize]);

  const handleSearch = () => {
    fetchDrugs({ page: 1 });
  };

  const handleReset = () => {
    setSearchText('');
    setFilters({});
    fetchDrugs({ page: 1, search: '', ...filters });
  };

  const handleToggleStatus = async (id) => {
    try {
      await drugsService.toggleStatus(id);
      message.success(t('status_changed', 'وضعیت با موفقیت تغییر کرد'));
      fetchDrugs();
    } catch (error) {
      message.error(t('error', 'خطا در تغییر وضعیت'));
    }
  };

  const handleDelete = async (id) => {
    try {
      await drugsService.delete(id);
      message.success(t('deleted', 'دارو با موفقیت حذف شد'));
      fetchDrugs();
    } catch (error) {
      message.error(t('error', 'خطا در حذف دارو'));
    }
  };

  const handleView = (record) => {
    setSelectedDrug(record);
    setModalMode('view');
    setIsModalVisible(true);
  };

  const handleEdit = (record) => {
    router.push(`/admin/drugs/${record.id}/edit`);
  };

  const handleCreate = () => {
    router.push('/admin/drugs/create');
  };

  // ===== مدیریت موجودی =====
  const handleStockAction = (record, action) => {
    setSelectedDrug(record);
    setModalMode(action);
    stockForm.resetFields();
    setIsModalVisible(true);
  };

  const handleStockSubmit = async (values) => {
    if (!selectedDrug) return;
    
    setStockLoading(true);
    try {
      if (modalMode === 'increase') {
        await drugsService.increaseStock(selectedDrug.id, values.quantity);
        message.success(t('stock_increased', 'موجودی با موفقیت افزایش یافت'));
      } else {
        await drugsService.decreaseStock(selectedDrug.id, values.quantity);
        message.success(t('stock_decreased', 'موجودی با موفقیت کاهش یافت'));
      }
      setIsModalVisible(false);
      fetchDrugs();
    } catch (error) {
      message.error(error?.message || t('error', 'خطا در عملیات'));
    } finally {
      setStockLoading(false);
    }
  };

  // ===== وضعیت موجودی =====
  const getStockStatus = (stock) => {
    if (stock <= 0) return { color: 'error', text: 'ناموجود' };
    if (stock <= 10) return { color: 'warning', text: 'کمتر از ۱۰' };
    if (stock <= 50) return { color: 'processing', text: 'متوسط' };
    return { color: 'success', text: 'موجود' };
  };

  const columns = [
    {
      title: t('code', 'کد'),
      dataIndex: 'code',
      key: 'code',
      width: 100,
      render: (text) => <Tag color="blue">{text}</Tag>,
    },
    {
      title: t('drug_name', 'نام دارو'),
      dataIndex: 'name',
      key: 'name',
      render: (text, record) => (
        <Space>
          <Avatar
            icon={<MedicineBoxOutlined />}
            style={{ backgroundColor: record.is_active ? '#10b981' : '#94a3b8' }}
          />
          <div>
            <div style={{ fontWeight: 600 }}>{text}</div>
            <div style={{ fontSize: 12, color: '#64748b' }}>
              {record.generic_name || record.category || ''}
            </div>
          </div>
        </Space>
      ),
    },
    {
      title: t('category', 'دسته‌بندی'),
      dataIndex: 'category',
      key: 'category',
      render: (category) => category || '—',
    },
    {
      title: t('form', 'فرم'),
      dataIndex: 'form',
      key: 'form',
      render: (form) => form || '—',
    },
    {
      title: t('strength', 'قدرت'),
      dataIndex: 'strength',
      key: 'strength',
      render: (strength) => strength || '—',
    },
    {
      title: t('price', 'قیمت'),
      dataIndex: 'price',
      key: 'price',
      render: (price) => price ? `${Number(price).toLocaleString()} تومان` : '—',
    },
    {
      title: t('stock', 'موجودی'),
      dataIndex: 'stock',
      key: 'stock',
      render: (stock) => {
        const status = getStockStatus(stock);
        return (
          <div>
            <div style={{ fontWeight: 600 }}>{stock || 0}</div>
            <Badge status={status.color} text={status.text} />
          </div>
        );
      },
    },
    {
      title: t('prescription', 'نیاز به نسخه'),
      dataIndex: 'requires_prescription',
      key: 'requires_prescription',
      render: (requires) => (
        <Tag color={requires ? 'orange' : 'green'}>
          {requires ? t('yes', 'بله') : t('no', 'خیر')}
        </Tag>
      ),
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
          <Tooltip title={t('increase_stock', 'افزایش موجودی')}>
            <Button
              type="text"
              icon={<PlusCircleOutlined />}
              onClick={() => handleStockAction(record, 'increase')}
              size="small"
              style={{ color: '#10b981' }}
            />
          </Tooltip>
          <Tooltip title={t('decrease_stock', 'کاهش موجودی')}>
            <Button
              type="text"
              icon={<MinusCircleOutlined />}
              onClick={() => handleStockAction(record, 'decrease')}
              size="small"
              style={{ color: '#ef4444' }}
            />
          </Tooltip>
          <Tooltip title={t('toggle_status', 'تغییر وضعیت')}>
            <Button
              type="text"
              icon={record.is_active ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
              onClick={() => handleToggleStatus(record.id)}
              size="small"
              style={{ color: record.is_active ? '#ef4444' : '#10b981' }}
            />
          </Tooltip>
          <Popconfirm
            title={t('delete_confirm', 'آیا از حذف این دارو اطمینان دارید؟')}
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
            {t('drugs_management', 'مدیریت داروها')}
          </Title>
          <Text type="secondary">
            {t('drugs_subtitle', 'لیست و مدیریت داروهای موجود')}
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
          {t('new_drug', 'دارو جدید')}
        </Button>
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
              placeholder={t('search_drug', 'جستجوی دارو...')}
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
              onChange={(value) => setFilters({ ...filters, category: value })}
            >
              <Select.Option value="مسکن">مسکن</Select.Option>
              <Select.Option value="آنتی‌بیوتیک">آنتی‌بیوتیک</Select.Option>
              <Select.Option value="ضدالتهاب">ضدالتهاب</Select.Option>
              <Select.Option value="ضدافسردگی">ضدافسردگی</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_prescription', 'نیاز به نسخه')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, requires_prescription: value })}
            >
              <Select.Option value={true}>{t('requires_prescription', 'نیاز به نسخه')}</Select.Option>
              <Select.Option value={false}>{t('over_the_counter', 'بدون نسخه')}</Select.Option>
            </Select>
          </Col>
          <Col xs={24} sm={12} md={8} lg={6}>
            <Select
              placeholder={t('filter_stock', 'فیلتر موجودی')}
              style={{ width: '100%' }}
              allowClear
              onChange={(value) => setFilters({ ...filters, in_stock: value })}
            >
              <Select.Option value={true}>{t('in_stock', 'موجود')}</Select.Option>
              <Select.Option value={false}>{t('out_of_stock', 'ناموجود')}</Select.Option>
            </Select>
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
          dataSource={drugs}
          loading={loading}
          rowKey="id"
          pagination={{
            current: pagination.current,
            pageSize: pagination.pageSize,
            total: pagination.total,
            showSizeChanger: true,
            showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('items', 'دارو')}`,
            onChange: (page, pageSize) => {
              setPagination({ ...pagination, current: page, pageSize });
            },
          }}
          scroll={{ x: 1400 }}
          locale={{
            emptyText: t('no_drugs', 'هیچ دارویی یافت نشد'),
          }}
        />
      </Card>

      {/* ===== مودال مشاهده/مدیریت موجودی ===== */}
      <Modal
        title={
          modalMode === 'view'
            ? t('drug_details', 'جزئیات دارو')
            : modalMode === 'increase'
            ? t('increase_stock', 'افزایش موجودی')
            : t('decrease_stock', 'کاهش موجودی')
        }
        open={isModalVisible}
        onCancel={() => setIsModalVisible(false)}
        footer={null}
        width={500}
      >
        {modalMode === 'view' && selectedDrug && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
              <Avatar
                size={64}
                icon={<MedicineBoxOutlined />}
                style={{ backgroundColor: selectedDrug.is_active ? '#10b981' : '#94a3b8' }}
              />
              <div>
                <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedDrug.name}</div>
                <div style={{ color: '#64748b' }}>{selectedDrug.code}</div>
              </div>
            </div>

            <Row gutter={[16, 16]}>
              <Col span={12}>
                <Text type="secondary">{t('generic_name', 'نام ژنریک')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDrug.generic_name || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('category', 'دسته‌بندی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDrug.category || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('form', 'فرم')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDrug.form || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('strength', 'قدرت')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDrug.strength || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('manufacturer', 'سازنده')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDrug.manufacturer || '—'}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('price', 'قیمت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  {selectedDrug.price ? `${Number(selectedDrug.price).toLocaleString()} تومان` : '—'}
                </div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('stock', 'موجودی')}</Text>
                <div style={{ fontWeight: 500 }}>{selectedDrug.stock || 0}</div>
              </Col>
              <Col span={12}>
                <Text type="secondary">{t('requires_prescription', 'نیاز به نسخه')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Tag color={selectedDrug.requires_prescription ? 'orange' : 'green'}>
                    {selectedDrug.requires_prescription ? t('yes', 'بله') : t('no', 'خیر')}
                  </Tag>
                </div>
              </Col>
              <Col span={24}>
                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                <div style={{ fontWeight: 500 }}>
                  <Badge
                    status={selectedDrug.is_active ? 'success' : 'error'}
                    text={selectedDrug.is_active ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
                  />
                </div>
              </Col>
            </Row>
          </div>
        )}

        {(modalMode === 'increase' || modalMode === 'decrease') && (
          <Form form={stockForm} onFinish={handleStockSubmit} layout="vertical">
            <Form.Item
              name="quantity"
              label={t('quantity', 'تعداد')}
              rules={[
                { required: true, message: t('required', 'لطفاً تعداد را وارد کنید') },
                { type: 'number', min: 1, message: t('min_1', 'تعداد باید حداقل ۱ باشد') },
              ]}
            >
              <InputNumber
                style={{ width: '100%' }}
                placeholder={t('enter_quantity', 'تعداد را وارد کنید...')}
                min={1}
                size="large"
              />
            </Form.Item>

            <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end', marginTop: 16 }}>
              <Button onClick={() => setIsModalVisible(false)}>
                {t('cancel', 'انصراف')}
              </Button>
              <Button
                type="primary"
                htmlType="submit"
                loading={stockLoading}
                style={{
                  background: modalMode === 'increase' 
                    ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
                    : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                  border: 'none',
                }}
              >
                {modalMode === 'increase' ? t('increase', 'افزایش') : t('decrease', 'کاهش')}
              </Button>
            </div>
          </Form>
        )}
      </Modal>
    </div>
  );
}
