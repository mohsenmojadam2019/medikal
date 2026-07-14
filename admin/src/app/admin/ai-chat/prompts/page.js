'use client';

import { useState, useEffect } from 'react';
import {
    Table,
    Button,
    Modal,
    Form,
    Input,
    Select,
    Switch,
    Space,
    Tag,
    message,
    Popconfirm,
    Spin,
    Alert,
} from 'antd';
import {
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    ReloadOutlined,
    CopyOutlined,
} from '@ant-design/icons';
import axios from 'axios';
import { useAuth } from '@/context/AuthContext';
import dayjs from 'dayjs';

const { TextArea } = Input;
const { Option } = Select;

export default function PromptsManagement() {
    const { token } = useAuth();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [prompts, setPrompts] = useState([]);
    const [modalVisible, setModalVisible] = useState(false);
    const [editingPrompt, setEditingPrompt] = useState(null);
    const [form] = Form.useForm();
    const [submitting, setSubmitting] = useState(false);

    const fetchPrompts = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get('/api/v1/admin/chat/prompts', {
                headers: { Authorization: `Bearer ${token}` },
            });
            if (response.data.success) {
                setPrompts(response.data.data);
            } else {
                setError('خطا در دریافت پرامپت‌ها');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchPrompts();
    }, []);

    const handleCreate = () => {
        setEditingPrompt(null);
        form.resetFields();
        form.setFieldsValue({ is_active: true, category: 'general', priority: 0 });
        setModalVisible(true);
    };

    const handleEdit = (prompt) => {
        setEditingPrompt(prompt);
        form.setFieldsValue({
            name: prompt.name,
            slug: prompt.slug,
            category: prompt.category,
            system_prompt: prompt.system_prompt,
            user_prompt_template: prompt.user_prompt_template,
            is_active: prompt.is_active,
            is_default: prompt.is_default,
            priority: prompt.priority,
        });
        setModalVisible(true);
    };

    const handleDelete = async (id) => {
        try {
            await axios.delete(`/api/v1/admin/chat/prompts/${id}`, {
                headers: { Authorization: `Bearer ${token}` },
            });
            message.success('پرامپت با موفقیت حذف شد');
            fetchPrompts();
        } catch (err) {
            message.error(err.response?.data?.message || 'خطا در حذف پرامپت');
        }
    };

    const handleToggle = async (id, isActive) => {
        try {
            await axios.post(
                `/api/v1/admin/chat/prompts/${id}/toggle`,
                {},
                { headers: { Authorization: `Bearer ${token}` } }
            );
            message.success(`پرامپت ${isActive ? 'غیرفعال' : 'فعال'} شد`);
            fetchPrompts();
        } catch (err) {
            message.error(err.response?.data?.message || 'خطا در تغییر وضعیت');
        }
    };

    const handleClone = async (prompt) => {
        try {
            await axios.post(
                `/api/v1/admin/chat/prompts/${prompt.id}/clone`,
                {},
                { headers: { Authorization: `Bearer ${token}` } }
            );
            message.success('پرامپت با موفقیت کپی شد');
            fetchPrompts();
        } catch (err) {
            message.error(err.response?.data?.message || 'خطا در کپی پرامپت');
        }
    };

    const handleSubmit = async (values) => {
        setSubmitting(true);
        try {
            if (editingPrompt) {
                await axios.put(`/api/v1/admin/chat/prompts/${editingPrompt.id}`, values, {
                    headers: { Authorization: `Bearer ${token}` },
                });
                message.success('پرامپت با موفقیت به‌روزرسانی شد');
            } else {
                await axios.post('/api/v1/admin/chat/prompts', values, {
                    headers: { Authorization: `Bearer ${token}` },
                });
                message.success('پرامپت با موفقیت ایجاد شد');
            }
            setModalVisible(false);
            fetchPrompts();
        } catch (err) {
            message.error(err.response?.data?.message || 'خطا در ذخیره پرامپت');
        } finally {
            setSubmitting(false);
        }
    };

    const columns = [
        { title: 'نام', dataIndex: 'name', key: 'name' },
        { title: 'نامک', dataIndex: 'slug', key: 'slug' },
        {
            title: 'دسته‌بندی',
            dataIndex: 'category',
            key: 'category',
            render: (category) => {
                const colors = {
                    general: 'default',
                    medical: 'blue',
                    pharmacy: 'green',
                    emergency: 'red',
                    nutrition: 'orange',
                    psychology: 'purple',
                };
                const labels = {
                    general: 'عمومی',
                    medical: 'پزشکی',
                    pharmacy: 'دارویی',
                    emergency: 'اورژانسی',
                    nutrition: 'تغذیه',
                    psychology: 'روانشناسی',
                };
                return <Tag color={colors[category] || 'default'}>{labels[category] || category}</Tag>;
            },
        },
        {
            title: 'وضعیت',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (isActive, record) => (
                <Switch
                    checked={isActive}
                    onChange={() => handleToggle(record.id, isActive)}
                    checkedChildren="فعال"
                    unCheckedChildren="غیرفعال"
                />
            ),
        },
        {
            title: 'پیش‌فرض',
            dataIndex: 'is_default',
            key: 'is_default',
            render: (isDefault) => (
                <Tag color={isDefault ? 'gold' : 'default'}>
                    {isDefault ? '⭐ پیش‌فرض' : '—'}
                </Tag>
            ),
        },
        { title: 'نسخه', dataIndex: 'version', key: 'version' },
        { title: 'استفاده', dataIndex: 'usage_count', key: 'usage_count' },
        {
            title: 'تاریخ ایجاد',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('YYYY/MM/DD HH:mm'),
        },
        {
            title: 'عملیات',
            key: 'actions',
            render: (_, record) => (
                <Space size="small">
                    <Button
                        type="primary"
                        size="small"
                        icon={<EditOutlined />}
                        onClick={() => handleEdit(record)}
                    />
                    <Button size="small" icon={<CopyOutlined />} onClick={() => handleClone(record)} />
                    <Popconfirm
                        title="آیا از حذف این پرامپت اطمینان دارید؟"
                        onConfirm={() => handleDelete(record.id)}
                        okText="بله"
                        cancelText="خیر"
                    >
                        <Button danger size="small" icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    if (loading) {
        return (
            <div className="flex justify-center items-center h-96">
                <Spin size="large" tip="در حال بارگذاری..." />
            </div>
        );
    }

    if (error) {
        return (
            <Alert
                message="خطا"
                description={error}
                type="error"
                showIcon
                action={
                    <Button onClick={fetchPrompts} icon={<ReloadOutlined />}>
                        تلاش مجدد
                    </Button>
                }
            />
        );
    }

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">📝 مدیریت پرامپت‌ها</h1>
                <Button type="primary" icon={<PlusOutlined />} onClick={handleCreate}>
                    پرامپت جدید
                </Button>
            </div>

            <Table
                columns={columns}
                dataSource={prompts}
                rowKey="id"
                pagination={{ pageSize: 10, showTotal: (total) => `کل ${total} پرامپت` }}
                scroll={{ x: true }}
            />

            <Modal
                title={editingPrompt ? 'ویرایش پرامپت' : 'ایجاد پرامپت جدید'}
                open={modalVisible}
                onCancel={() => setModalVisible(false)}
                footer={null}
                width={800}
                destroyOnClose
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    initialValues={{ is_active: true, category: 'general', priority: 0 }}
                >
                    <Form.Item name="name" label="نام" rules={[{ required: true, message: 'لطفاً نام را وارد کنید' }]}>
                        <Input placeholder="مثال: پزشک عمومی" />
                    </Form.Item>

                    <Form.Item name="slug" label="نامک" rules={[{ required: true, message: 'لطفاً نامک را وارد کنید' }]}>
                        <Input placeholder="مثال: doctor-general" />
                    </Form.Item>

                    <Form.Item name="category" label="دسته‌بندی" rules={[{ required: true }]}>
                        <Select>
                            <Option value="general">عمومی</Option>
                            <Option value="medical">پزشکی</Option>
                            <Option value="pharmacy">دارویی</Option>
                            <Option value="emergency">اورژانسی</Option>
                            <Option value="nutrition">تغذیه</Option>
                            <Option value="psychology">روانشناسی</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="system_prompt"
                        label="پرامپت سیستمی"
                        rules={[{ required: true, message: 'لطفاً پرامپت سیستمی را وارد کنید' }]}
                    >
                        <TextArea rows={4} placeholder="شما یک پزشک مجازی هستید..." />
                    </Form.Item>

                    <Form.Item
                        name="user_prompt_template"
                        label="قالب پرامپت کاربر"
                        rules={[{ required: true, message: 'لطفاً قالب پرامپت کاربر را وارد کنید' }]}
                    >
                        <TextArea rows={3} placeholder="سوال: {question} ..." />
                    </Form.Item>

                    <div className="grid grid-cols-2 gap-4">
                        <Form.Item name="is_active" label="فعال" valuePropName="checked">
                            <Switch />
                        </Form.Item>
                        <Form.Item name="is_default" label="پیش‌فرض" valuePropName="checked">
                            <Switch />
                        </Form.Item>
                    </div>

                    <Form.Item name="priority" label="اولویت">
                        <Input type="number" placeholder="۰" />
                    </Form.Item>

                    <div className="flex justify-end gap-2">
                        <Button onClick={() => setModalVisible(false)}>انصراف</Button>
                        <Button type="primary" htmlType="submit" loading={submitting}>
                            {editingPrompt ? 'به‌روزرسانی' : 'ایجاد'}
                        </Button>
                    </div>
                </Form>
            </Modal>
        </div>
    );
}
