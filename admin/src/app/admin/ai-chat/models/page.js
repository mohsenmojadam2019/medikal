'use client';

import { useState, useEffect } from 'react';
import { Table, Card, Button, Tag, Space, Spin, Alert, message, Modal, Form, Select, InputNumber } from 'antd';
import { ReloadOutlined, CheckCircleOutlined, CloseCircleOutlined } from '@ant-design/icons';
import axios from 'axios';
import { useAuth } from '@/context/AuthContext';

const { Option } = Select;

export default function ModelsManagement() {
    const { token } = useAuth();
    const [loading, setLoading] = useState(true);
    const [testLoading, setTestLoading] = useState(false);
    const [error, setError] = useState(null);
    const [models, setModels] = useState([]);
    const [defaultModel, setDefaultModel] = useState(null);
    const [testModalVisible, setTestModalVisible] = useState(false);
    const [testForm] = Form.useForm();
    const [testResult, setTestResult] = useState(null);

    const fetchModels = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get('/api/v1/admin/chat/models', {
                headers: { Authorization: `Bearer ${token}` },
            });
            if (response.data.success) {
                setModels(response.data.data.models || []);
                setDefaultModel(response.data.data.default_model);
            } else {
                setError('خطا در دریافت مدل‌ها');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchModels(); }, []);

    const handleSetDefault = async (modelName) => {
        try {
            await axios.put('/api/v1/admin/chat/models/default', { model: modelName }, {
                headers: { Authorization: `Bearer ${token}` },
            });
            message.success(`مدل ${modelName} به عنوان پیش‌فرض تنظیم شد`);
            fetchModels();
        } catch (err) {
            message.error(err.response?.data?.message || 'خطا در تنظیم مدل پیش‌فرض');
        }
    };

    const handleTestModel = async (values) => {
        setTestLoading(true);
        setTestResult(null);
        try {
            const response = await axios.post('/api/v1/admin/chat/models/test', values, {
                headers: { Authorization: `Bearer ${token}` },
            });
            if (response.data.success) {
                setTestResult({ success: true, response: response.data.data.response, model: values.model });
            } else {
                setTestResult({ success: false, error: response.data.message });
            }
        } catch (err) {
            setTestResult({ success: false, error: err.response?.data?.message || 'خطا در تست مدل' });
        } finally {
            setTestLoading(false);
        }
    };

    const columns = [
        {
            title: 'نام مدل',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <div>
                    <span className="font-mono">{name}</span>
                    {record.is_default && <Tag color="gold" className="ml-2">⭐ پیش‌فرض</Tag>}
                </div>
            ),
        },
        { title: 'توضیحات', dataIndex: 'description', key: 'description' },
        { title: 'حداکثر توکن', dataIndex: 'max_tokens', key: 'max_tokens' },
        { title: 'طول متن', dataIndex: 'context_length', key: 'context_length' },
        { title: 'دمای پیش‌فرض', dataIndex: 'recommended_temperature', key: 'recommended_temperature' },
        {
            title: 'وضعیت',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (isActive) => <Tag color={isActive ? 'green' : 'red'}>{isActive ? 'فعال' : 'غیرفعال'}</Tag>,
        },
        {
            title: 'عملیات',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    {record.is_active && !record.is_default && (
                        <Button size="small" onClick={() => handleSetDefault(record.name)}>تنظیم به‌عنوان پیش‌فرض</Button>
                    )}
                </Space>
            ),
        },
    ];

    if (loading) return <div className="flex justify-center items-center h-96"><Spin size="large" /></div>;
    if (error) return <Alert message="خطا" description={error} type="error" showIcon />;

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">🤖 مدیریت مدل‌ها</h1>
                <Button type="primary" icon={<CheckCircleOutlined />} onClick={() => setTestModalVisible(true)}>
                    تست مدل
                </Button>
            </div>

            <Card title="مدل‌های هوش مصنوعی" className="mb-6">
                <Table columns={columns} dataSource={models} rowKey="name" pagination={false} />
            </Card>

            <Card title="تنظیمات پیشرفته">
                <div className="grid grid-cols-3 gap-4">
                    <div><label className="block text-sm font-medium">مدل پیش‌فرض</label><div className="text-lg font-bold text-blue-600">{defaultModel || 'تعیین نشده'}</div></div>
                    <div><label className="block text-sm font-medium">مدل‌های فعال</label><div className="text-lg font-bold">{models.filter(m => m.is_active).length}</div></div>
                    <div><label className="block text-sm font-medium">کل مدل‌ها</label><div className="text-lg font-bold">{models.length}</div></div>
                </div>
            </Card>

            <Modal title="🧪 تست مدل" open={testModalVisible} onCancel={() => { setTestModalVisible(false); setTestResult(null); testForm.resetFields(); }} footer={null} width={600}>
                <Form form={testForm} layout="vertical" onFinish={handleTestModel} initialValues={{ model: defaultModel || models.find(m => m.is_active)?.name }}>
                    <Form.Item name="model" label="مدل" rules={[{ required: true }]}>
                        <Select>{models.filter(m => m.is_active).map((m) => <Option key={m.name} value={m.name}>{m.name}</Option>)}</Select>
                    </Form.Item>
                    <Form.Item name="prompt" label="پرامپت تست" rules={[{ required: true }]}>
                        <Input.TextArea rows={3} placeholder="سلام، چگونه می‌توانم به شما کمک کنم؟" />
                    </Form.Item>
                    <div className="grid grid-cols-2 gap-4">
                        <Form.Item name="temperature" label="دمای خلاقیت" initialValue={0.7}><InputNumber min={0} max={1} step={0.1} className="w-full" /></Form.Item>
                        <Form.Item name="max_tokens" label="حداکثر توکن" initialValue={500}><InputNumber min={1} max={4096} className="w-full" /></Form.Item>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button onClick={() => { setTestModalVisible(false); setTestResult(null); testForm.resetFields(); }}>انصراف</Button>
                        <Button type="primary" htmlType="submit" loading={testLoading}>تست مدل</Button>
                    </div>
                </Form>
                {testResult && (
                    <div className={`mt-4 p-4 rounded-lg ${testResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}`}>
                        <div className="flex items-center gap-2">
                            {testResult.success ? <CheckCircleOutlined className="text-green-600" /> : <CloseCircleOutlined className="text-red-600" />}
                            <span className="font-bold">{testResult.success ? '✅ تست موفق' : '❌ تست ناموفق'}</span>
                        </div>
                        {testResult.success ? (
                            <div><div className="text-sm text-gray-500 mt-2">پاسخ:</div><div className="bg-white p-2 rounded border">{testResult.response}</div></div>
                        ) : (
                            <div className="text-red-600">{testResult.error}</div>
                        )}
                    </div>
                )}
            </Modal>
        </div>
    );
}
