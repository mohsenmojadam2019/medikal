'use client';

import { useState, useEffect } from 'react';
import {
  Card, Table, Tag, Button, Space, Input,
  Modal, Form, message, Popconfirm, Tooltip,
  Typography, Switch
} from 'antd';
import {
  SearchOutlined, ReloadOutlined, PlusOutlined,
  EditOutlined, DeleteOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/context/LanguageContext';
import { labService } from '@/services/api';

const { Title, Text } = Typography;

export default function AdminLabCategories() {
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState([]);
  const [searchText, setSearchText] = useState('');
  const [modalVisible, setModalVisible] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [form] = Form.useForm();

  const fetchCategories = async () => {
    setLoading(true);
    try {
      const params = {};
      if (searchText) params.search = searchText;

      const res = await labService.getCategories(params);
      if (res.data.success) {
        setCategories(res.data.data.data || res.data.data || []);
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
      message.error('خطا در دریافت دسته‌بندی‌ها');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, [searchText]);

  const handleSubmit = async (values) => {
    try {
      if (editingCategory) {
        await labService.updateCategory(editingCategory.id, values);
        message.success('دسته‌بندی با موفقیت ویرایش شد');
      } else {
        await labService.createCategory(values);
        message.success('دسته‌بندی با موفقیت ایجاد شد');
      }
      setModalVisible(false);
      form.resetFields();
      setEditingCategory(null);
      fetchCategories();
    } catch (error) {
      console.error('Error saving category:', error);
      message.error('خطا در ذخیره دسته‌بندی');
    }
  };

  const handleDelete = async (id) => {
    try {
      await labService.deleteCategory(id);
      message.success('دسته‌بندی با موفقیت حذف شد');
      fetchCategories();
    } catch (error) {
      console.error('Error deleting category:', error);
      message.error('خطا در حذف دسته‌بندی');
    }
  };

  const handleToggleStatus = async (id) => {
    try {
      await labService.toggleCategoryStatus(id);
      message.success('وضعیت دسته‌بندی تغییر کرد');
      fetchCategories();
    } catch (error) {
      console.error('Error toggling status:', error);
      message.error('خطا در تغییر وضعیت دسته‌بندی');
    }
  };

  const columns = [
    { title: 'نام', dataIndex: 'name', key: 'name' },
    { title: 'slug', dataIndex: 'slug', key: 'slug' },
    { title: 'توضیحات', dataIndex: 'description', key: 'description', render: (text) => text || '—' },
    { title: 'تعداد تست', dataIndex: 'tests_count', key: 'tests_count', render: (count) => count || 0 },
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
              setEditingCategory(record);
              form.setFieldsValue(record);
              setModalVisible(true);
            }} />
          </Tooltip>
          <Popconfirm title="حذف دسته‌بندی" description="آیا از حذف این دسته‌بندی اطمینان دارید؟" onConfirm={() => handleDelete(record.id)} okText="بله" cancelText="خیر">
            <Button danger size="small" icon={<DeleteOutlined />} />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <Title level={2}>📂 مدیریت دسته‌بندی‌های آزمایشگاه</Title>
        <Button type="primary" icon={<PlusOutlined />} onClick={() => { setEditingCategory(null); form.resetFields(); setModalVisible(true); }}>
          دسته‌بندی جدید
        </Button>
      </div>

      <Card style={{ borderRadius: 12 }}>
        <div style={{ display: 'flex', gap: 16, marginBottom: 16, flexWrap: 'wrap' }}>
          <Input placeholder="جستجوی دسته‌بندی..." prefix={<SearchOutlined />} value={searchText} onChange={(e) => setSearchText(e.target.value)} style={{ width: 300 }} allowClear />
          <Button icon={<ReloadOutlined />} onClick={fetchCategories} loading={loading}>بروزرسانی</Button>
        </div>

        <Table columns={columns} dataSource={categories} rowKey="id" loading={loading} pagination={{ pageSize: 15, showSizeChanger: true }} />
      </Card>

      <Modal title={editingCategory ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید'} open={modalVisible} onCancel={() => { setModalVisible(false); form.resetFields(); setEditingCategory(null); }} footer={null} width={500}>
        <Form form={form} layout="vertical" onFinish={handleSubmit}>
          <Form.Item name="name" label="نام" rules={[{ required: true, message: 'لطفاً نام را وارد کنید' }]}>
            <Input placeholder="نام دسته‌بندی..." />
          </Form.Item>
          <Form.Item name="slug" label="slug">
            <Input placeholder="slug..." />
          </Form.Item>
          <Form.Item name="description" label="توضیحات">
            <Input.TextArea rows={3} placeholder="توضیحات..." />
          </Form.Item>
          <Form.Item name="is_active" label="فعال" valuePropName="checked">
            <Switch />
          </Form.Item>
          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">{editingCategory ? 'ویرایش' : 'ایجاد'}</Button>
              <Button onClick={() => { setModalVisible(false); form.resetFields(); setEditingCategory(null); }}>انصراف</Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
