// src/app/admin/webhook/page.js

'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
    Card,
    Form,
    Input,
    Button,
    Switch,
    message,
    Row,
    Col,
    Typography,
    Divider,
    Space,
    Table,
    Tag,
    Modal,
    Select,
    App,
    Badge,
} from 'antd';
import {
    ReloadOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    EyeOutlined,
    DeleteOutlined,
} from '@ant-design/icons';
import { webhookService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import Loading from '@/components/admin/common/Loading';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function WebhookPage() {
    const router = useRouter();
    const { t } = useLanguage();
    const { message } = App.useApp();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState(null);
    const [logs, setLogs] = useState([]);
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 10,
        total: 0,
    });
    const [selectedLog, setSelectedLog] = useState(null);
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [settings, setSettings] = useState(null);

    // ===== دریافت وضعیت =====
    const fetchStatus = async () => {
        try {
            const response = await webhookService.getStatus();
            if (response.data?.success) {
                setStatus(response.data.data);
            }
        } catch (error) {
            console.error('Error fetching status:', error);
            message.error(t('fetch_error', 'خطا در دریافت وضعیت'));
        }
    };

    // ===== دریافت لاگ‌ها =====
    const fetchLogs = async (params = {}) => {
        setLoading(true);
        try {
            const response = await webhookService.getLogs({
                page: pagination.current,
                per_page: pagination.pageSize,
                ...params,
            });
            if (response.data?.success) {
                const data = response.data.data;
                const list = data?.data || data || [];
                setLogs(Array.isArray(list) ? list : []);
                setPagination({
                    ...pagination,
                    total: data?.total || (Array.isArray(list) ? list.length : 0),
                    current: data?.current_page || 1,
                });
            } else {
                setLogs([]);
            }
        } catch (error) {
            console.error('Error fetching logs:', error);
            message.error(t('fetch_error', 'خطا در دریافت لاگ‌ها'));
            setLogs([]);
        } finally {
            setLoading(false);
        }
    };

    // ===== دریافت تنظیمات =====
    const fetchSettings = async () => {
        try {
            const response = await webhookService.getSettings();
            if (response.data?.success) {
                setSettings(response.data.data);
                form.setFieldsValue(response.data.data);
            }
        } catch (error) {
            console.error('Error fetching settings:', error);
        }
    };

    useEffect(() => {
        fetchStatus();
        fetchLogs();
        fetchSettings();
    }, [pagination.current, pagination.pageSize]);

    // ===== تغییر وضعیت =====
    const handleToggle = async () => {
        try {
            const response = await webhookService.toggle();
            if (response.data?.success) {
                message.success(t('toggled', 'وضعیت با موفقیت تغییر کرد'));
                fetchStatus();
            }
        } catch (error) {
            message.error(t('error', 'خطا در تغییر وضعیت'));
        }
    };

    // ===== ذخیره تنظیمات =====
    const handleSaveSettings = async (values) => {
        try {
            const response = await webhookService.updateSettings(values);
            if (response.data?.success) {
                message.success(t('saved', 'تنظیمات با موفقیت ذخیره شد'));
                fetchSettings();
            }
        } catch (error) {
            message.error(t('error', 'خطا در ذخیره تنظیمات'));
        }
    };

    // ===== مشاهده جزئیات لاگ =====
    const handleViewLog = (record) => {
        setSelectedLog(record);
        setIsModalVisible(true);
    };

    // ===== فرمت تاریخ =====
    const formatJalaliDateTime = (date) => {
        if (!date) return '—';
        try {
            return moment(date).format('jYYYY/jMM/jDD HH:mm');
        } catch (error) {
            return '—';
        }
    };

    // ===== ستون‌های جدول =====
    const columns = [
        {
            title: t('provider', 'ارائه‌دهنده'),
            dataIndex: 'provider',
            key: 'provider',
            render: (provider) => (
                <Tag color={provider === 'isp' ? 'blue' : 'green'}>
                    {provider || '—'}
                </Tag>
            ),
        },
        {
            title: t('event_type', 'نوع رویداد'),
            dataIndex: 'event_type',
            key: 'event_type',
            render: (type) => type || '—',
        },
        {
            title: t('status', 'وضعیت'),
            dataIndex: 'status_code',
            key: 'status_code',
            render: (code) => {
                const isSuccess = code >= 200 && code < 300;
                return (
                    <Tag color={isSuccess ? 'success' : 'error'}>
                        {isSuccess ? t('success', 'موفق') : t('failed', 'ناموفق')}
                    </Tag>
                );
            },
        },
        {
            title: t('date', 'تاریخ'),
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => date ? formatJalaliDateTime(date) : '—',
        },
        {
            title: t('actions', 'عملیات'),
            key: 'actions',
            width: 100,
            render: (_, record) => (
                <Button
                    type="text"
                    icon={<EyeOutlined />}
                    onClick={() => handleViewLog(record)}
                    size="small"
                />
            ),
        },
    ];

    // اگر logs آرایه نیست
    if (!Array.isArray(logs)) {
        console.error('⚠️ Logs is not an array:', logs);
        setLogs([]);
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
                        {t('webhook_management', 'مدیریت وب‌هوک')}
                    </Title>
                    <Text type="secondary">
                        {t('webhook_subtitle', 'مدیریت وب‌هوک‌های سیستم')}
                    </Text>
                </div>
            </div>

            {/* ===== وضعیت ===== */}
            <Card
                style={{
                    borderRadius: 12,
                    borderColor: '#e8e8f0',
                    marginBottom: 16,
                }}
            >
                <Row gutter={[16, 16]} align="middle">
                    <Col>
                        <Text strong>{t('status', 'وضعیت')}:</Text>
                    </Col>
                    <Col>
                        <Badge
                            status={status?.enabled ? 'success' : 'error'}
                            text={status?.enabled ? t('active', 'فعال') : t('inactive', 'غیرفعال')}
                        />
                    </Col>
                    <Col>
                        <Button
                            type={status?.enabled ? 'default' : 'primary'}
                            onClick={handleToggle}
                            style={{
                                background: status?.enabled ? 'none' : 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                                border: status?.enabled ? 'none' : 'none',
                                color: status?.enabled ? '#333' : 'white',
                            }}
                        >
                            {status?.enabled ? t('disable', 'غیرفعال کردن') : t('enable', 'فعال کردن')}
                        </Button>
                    </Col>
                    <Col>
                        <Text type="secondary">
                            {t('provider', 'ارائه‌دهنده')}: {status?.provider || '—'}
                        </Text>
                    </Col>
                    {status?.has_secret && (
                        <Col>
                            <Tag color="green">{t('has_secret', 'دارای کلید مخفی')}</Tag>
                        </Col>
                    )}
                </Row>
            </Card>

            {/* ===== تنظیمات ===== */}
            <Card
                style={{
                    borderRadius: 12,
                    borderColor: '#e8e8f0',
                    marginBottom: 16,
                }}
                title={t('settings', 'تنظیمات')}
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSaveSettings}
                    size="large"
                >
                    <Row gutter={[16, 0]}>
                        <Col xs={24} md={12}>
                            <Form.Item
                                name="url"
                                label={t('webhook_url', 'آدرس وب‌هوک')}
                            >
                                <Input placeholder="https://example.com/webhook" />
                            </Form.Item>
                        </Col>

                        <Col xs={24} md={12}>
                            <Form.Item
                                name="secret"
                                label={t('secret', 'کلید مخفی')}
                            >
                                <Input.Password placeholder="******" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={[16, 0]}>
                        <Col xs={24} md={12}>
                            <Form.Item
                                name="provider"
                                label={t('provider', 'ارائه‌دهنده')}
                            >
                                <Select
                                    options={[
                                        { value: 'isp', label: 'ISP' },
                                        { value: 'telegram', label: 'Telegram' },
                                        { value: 'whatsapp', label: 'WhatsApp' },
                                        { value: 'custom', label: t('custom', 'سفارشی') },
                                    ]}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} md={12}>
                            <Form.Item
                                name="events"
                                label={t('events', 'رویدادها')}
                            >
                                <Select
                                    mode="tags"
                                    placeholder={t('select_events', 'انتخاب رویدادها...')}
                                    options={[
                                        { value: 'appointment_created', label: 'ایجاد نوبت' },
                                        { value: 'appointment_cancelled', label: 'لغو نوبت' },
                                        { value: 'appointment_updated', label: 'ویرایش نوبت' },
                                        { value: 'payment_success', label: 'پرداخت موفق' },
                                        { value: 'payment_failed', label: 'پرداخت ناموفق' },
                                    ]}
                                />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={[16, 0]}>
                        <Col xs={24} md={12}>
                            <Form.Item
                                name="retry_count"
                                label={t('retry_count', 'تعداد تلاش مجدد')}
                            >
                                <Input type="number" placeholder="3" />
                            </Form.Item>
                        </Col>

                        <Col xs={24} md={12}>
                            <Form.Item
                                name="timeout"
                                label={t('timeout', 'زمان انتظار (ثانیه)')}
                            >
                                <Input type="number" placeholder="30" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item>
                        <Button
                            type="primary"
                            htmlType="submit"
                            style={{
                                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                                border: 'none',
                            }}
                        >
                            {t('save', 'ذخیره تنظیمات')}
                        </Button>
                    </Form.Item>
                </Form>
            </Card>

            {/* ===== لاگ‌ها ===== */}
            <Card
                style={{
                    borderRadius: 12,
                    borderColor: '#e8e8f0',
                }}
                title={
                    <Space>
                        <Text strong>{t('logs', 'لاگ‌ها')}</Text>
                        <Button
                            icon={<ReloadOutlined />}
                            onClick={() => fetchLogs({ page: 1 })}
                            size="small"
                        />
                    </Space>
                }
            >
                <Table
                    columns={columns}
                    dataSource={logs}
                    loading={loading}
                    rowKey="id"
                    pagination={{
                        current: pagination.current,
                        pageSize: pagination.pageSize,
                        total: pagination.total,
                        showSizeChanger: true,
                        showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('records', 'رکورد')}`,
                        onChange: (page, pageSize) => {
                            setPagination({ ...pagination, current: page, pageSize });
                        },
                    }}
                    locale={{
                        emptyText: t('no_logs', 'هیچ لاگی یافت نشد'),
                    }}
                />
            </Card>

            {/* ===== مودال مشاهده جزئیات لاگ ===== */}
            <Modal
                title={t('log_details', 'جزئیات لاگ')}
                open={isModalVisible}
                onCancel={() => setIsModalVisible(false)}
                footer={[
                    <Button key="close" onClick={() => setIsModalVisible(false)}>
                        {t('close', 'بستن')}
                    </Button>,
                ]}
                width={600}
            >
                {selectedLog && (
                    <div>
                        <Row gutter={[16, 16]}>
                            <Col span={12}>
                                <Text type="secondary">{t('provider', 'ارائه‌دهنده')}</Text>
                                <div style={{ fontWeight: 500 }}>{selectedLog.provider || '—'}</div>
                            </Col>
                            <Col span={12}>
                                <Text type="secondary">{t('event_type', 'نوع رویداد')}</Text>
                                <div style={{ fontWeight: 500 }}>{selectedLog.event_type || '—'}</div>
                            </Col>
                            <Col span={12}>
                                <Text type="secondary">{t('status', 'وضعیت')}</Text>
                                <div style={{ fontWeight: 500 }}>
                                    <Tag color={selectedLog.status_code >= 200 && selectedLog.status_code < 300 ? 'success' : 'error'}>
                                        {selectedLog.status_code || '—'}
                                    </Tag>
                                </div>
                            </Col>
                            <Col span={12}>
                                <Text type="secondary">{t('date', 'تاریخ')}</Text>
                                <div style={{ fontWeight: 500 }}>
                                    {formatJalaliDateTime(selectedLog.created_at)}
                                </div>
                            </Col>
                            <Col span={24}>
                                <Text type="secondary">{t('payload', 'داده')}</Text>
                                <div style={{ padding: '8px 12px', background: '#f8fafc', borderRadius: 8, marginTop: 4, maxHeight: 150, overflow: 'auto' }}>
                  <pre style={{ margin: 0, fontSize: 12 }}>
                    {JSON.stringify(selectedLog.payload, null, 2)}
                  </pre>
                                </div>
                            </Col>
                            {selectedLog.error_message && (
                                <Col span={24}>
                                    <Text type="secondary">{t('error', 'خطا')}</Text>
                                    <div style={{ padding: '8px 12px', background: '#fef2f2', borderRadius: 8, marginTop: 4, color: '#ef4444' }}>
                                        {selectedLog.error_message}
                                    </div>
                                </Col>
                            )}
                            {selectedLog.response && (
                                <Col span={24}>
                                    <Text type="secondary">{t('response', 'پاسخ')}</Text>
                                    <div style={{ padding: '8px 12px', background: '#f8fafc', borderRadius: 8, marginTop: 4, maxHeight: 150, overflow: 'auto' }}>
                    <pre style={{ margin: 0, fontSize: 12 }}>
                      {JSON.stringify(selectedLog.response, null, 2)}
                    </pre>
                                    </div>
                                </Col>
                            )}
                        </Row>
                    </div>
                )}
            </Modal>
        </div>
    );
}