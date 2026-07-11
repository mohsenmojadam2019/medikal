'use client';

import { useState, useEffect } from 'react';
import {
  Card, Table, Tag, Button, Space, Input, Select,
  Modal, Form, message, Popconfirm, Tooltip,
  Typography, Switch, InputNumber
} from 'antd';
import {
  SearchOutlined, ReloadOutlined, PlusOutlined,
  EditOutlined, DeleteOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/context/LanguageContext';
import { labService } from '@/services/api';

const { Title, Text } = Typography;
const { Option } = Select;

export default function AdminLabTests() {
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [tests, setTests] = useState([]);
  const [categories, setCategories] = useState([]);
  const [searchText, setSearchText] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [modalVisible, setModalVisible] = useState(false);
  const [editingTest, setEditingTest] = useState(null);
  const [form] = Form.useForm();

  const fetchTests = async () => {
    setLoading(true);
    try {
      const params = {};
      if (searchText) params.search = searchText;
      if (categoryFilter !== 'all') params.category_id = categoryFilter;

      const res = await labService.getTests(params);
      if (res.data.success) {
        setTests(res.data.data.data || res.data.data || []);
      }
    } catch (error) {
      console.error('Error fetching tests:', error);
      message.error('خطا در دریافت تست‌ها');
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const res = await labService.getCategories({ per_page: 100 });
      if (res.data.success) {
        setCategories(res.data.data.data || res.data.data || []);
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
    }
  };

  useEffect(() => {
    fetchTests();
    fetchCategories();
  }, [searchText, categoryFilter]);

  const handleSubmit = async (values) => {
    try {
      if (editingTest) {
        await labService.updateTest(editingTest.id, values);
        message.success('تست با موفقیت ویرایش شد');
      } else {
        await labService.createTest(values);
        message.success('تست با موفقیت ایجاد شد');
      }
      setModalVisible(false);
      form.resetFields();
      setEditingTest(null);
      fetchTests();
    } catch (error) {
      console.error('Error saving test:', error);
      message.error('خطا در ذخیره تست');
    }
  };

  const handleDelete = async (id) => {
    try {
      await labService.deleteTest(id);
      message.success('تست با موفقیت حذف شد');
      fetchTests();
    } catch (error) {
      console.error('Error deleting test:', error);
      message.error('خطا در حذف تست');
    }
  };

  const handleToggleStatus = async (id) => {
    try {
      await labService.toggleTestStatus(id);
      message.success('وضعیت تست تغییر کرد');
      fetchTests();
    } catch (error) {
      console.error('Error toggling status:', error);
      message.error('خطا در تغییر وضعیت تست');
    }
  };

  const columns = [
    { title: 'کد', dataIndex: 'code', key: 'code' },
    { title: 'نام تست', dataIndex: 'name', key: 'name' },
    { title: 'نام اختصاری', dataIndex: 'short_name', key: 'short_name', render: (text) => text || '—' },
    { title: 'دسته‌بندی', dataIndex: 'category', key: 'category', render: (category) => category?.name || '—' },
    { title: 'قیمت', dataIndex: 'price', key: 'price', render: (price) => <Text strong>{price?.toLocaleString()} تومان</Text> },
    {
      title: 'وضعیت',
      dataIndex: 'is_active',
      key: 'is_active',
      render: (active) => <Tag color={active ? 'success' : 'error'}>{active ? 'فعال' : 'غیرفعال'}</Tag>,
    },
    {
      title: 'عملیات',
      key: 'action',
      render: (_, record) => (
        <Space>
          <Tooltip title="تغییر وضعیت">
            <Switch checked={record.is_active} onChange={() => handleToggleStatus(record.id)} size="small" />
          </Tooltip>
          <Tooltip title="ویرایش">
            <Button type="primary" ghost size="small" icon={<EditOutlined />} onClick={() => {
              setEditingTest(record);
              form.setFieldsValue(record);
              setModalVisible(true);
            }} />
          </Tooltip>
          <Popconfirm title="حذف تست" description="آیا از حذف این تست اطمینان دارید؟" onConfirm={() => handleDelete(record.id)} okText="بله" cancelText="خیر">
            <Button danger size="small" icon={<DeleteOutlined />} />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <Title level={2}>🔬 مدیریت تست‌های آزمایشگاه</Title>
        <Button type="primary" icon={<PlusOutlined />} onClick={() => { setEditingTest(null); form.resetFields(); setModalVisible(true); }}>
          تست جدید
        </Button>
      </div>

      <Card style={{ borderRadius: 12 }}>
        <div style={{ display: 'flex', gap: 16, marginBottom: 16, flexWrap: 'wrap' }}>
          <Input placeholder="جستجوی تست..." prefix={<SearchOutlined />} value={searchText} onChange={(e) => setSearchText(e.target.value)} style={{ width: 300 }} allowClear />
          <Select placeholder="فیلتر دسته‌بندی" style={{ width: 200 }} value={categoryFilter} onChange={setCategoryFilter} allowClear>
            <Option value="all">همه دسته‌ها</Option>
            {categories.map(cat => (<Option key={cat.id} value={cat.id}>{cat.name}</Option>))}
          </Select>
          <Button icon={<ReloadOutlined />} onClick={fetchTests} loading={loading}>بروزرسانی</Button>
        </div>

        <Table columns={columns} dataSource={tests} rowKey="id" loading={loading} pagination={{ pageSize: 15, showSizeChanger: true }} />
      </Card>

      <Modal title={editingTest ? 'ویرایش تست' : 'تست جدید'} open={modalVisible} onCancel={() => { setModalVisible(false); form.resetFields(); setEditingTest(null); }} footer={null} width={600}>
        <Form form={form} layout="vertical" onFinish={handleSubmit}>
          <Form.Item name="category_id" label="دسته‌بندی" rules={[{ required: true, message: 'لطفاً دسته‌بندی را انتخاب کنید' }]}>
            <Select placeholder="انتخاب دسته‌بندی">
              {categories.map(cat => (<Option key={cat.id} value={cat.id}>{cat.name}</Option>))}
            </Select>
          </Form.Item>
          <Form.Item name="name" label="نام تست" rules={[{ required: true, message: 'لطفاً نام تست را وارد کنید' }]}>
            <Input placeholder="نام تست..." />
          </Form.Item>
          <Form.Item name="short_name" label="نام اختصاری">
            <Input placeholder="نام اختصاری..." />
          </Form.Item>
          <Form.Item name="code" label="کد">
            <Input placeholder="کد تست..." />
          </Form.Item>
          <Form.Item name="price" label="قیمت (تومان)">
            <InputNumber placeholder="قیمت..." style={{ width: '100%' }} min={0} />
          </Form.Item>
          <Form.Item name="unit" label="واحد">
            <Input placeholder="واحد..." />
          </Form.Item>
          <Form.Item name="is_active" label="فعال" valuePropName="checked">
            <Switch />
          </Form.Item>
          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">{editingTest ? 'ویرایش' : 'ایجاد'}</Button>
              <Button onClick={() => { setModalVisible(false); form.resetFields(); setEditingTest(null); }}>انصراف</Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
