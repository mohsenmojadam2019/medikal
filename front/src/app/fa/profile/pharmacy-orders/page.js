// /src/app/fa/profile/pharmacy-orders/page.js
'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
    Card, Table, Tag, Button, Typography, Space,
    Spin, Empty, Tabs, Badge, Avatar, Descriptions,
    Modal, Divider, Timeline, Alert, Skeleton, App, Tooltip
} from 'antd';
import {
    MedicineBoxOutlined, EyeOutlined, CheckCircleOutlined,
    CloseCircleOutlined, ClockCircleOutlined, TruckOutlined,
    HomeOutlined, UserOutlined, PhoneOutlined,
    WalletOutlined, CreditCardOutlined, ReloadOutlined,
    ShoppingCartOutlined, LeftOutlined, InfoCircleOutlined,
    DeleteOutlined, ArrowRightOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

// ============================================
// توابع کمکی
// ============================================

function toPersianNumber(num) {
    if (!num && num !== 0) return '۰';
    const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return num.toString().replace(/\d/g, d => persian[d]);
}

function formatPrice(price) {
    if (!price && price !== 0) return '۰ تومان';
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return toPersianNumber(num.toLocaleString()) + ' تومان';
}

function formatDate(dateStr) {
    if (!dateStr) return '---';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '---';
        const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
        return formatter.format(date);
    } catch {
        return dateStr;
    }
}

function getStatusConfig(status) {
    const statusMap = {
        'pending': { color: '#faad14', label: 'در انتظار تایید', icon: <ClockCircleOutlined />, bg: '#fff7e6' },
        'payment_pending': { color: '#faad14', label: 'در انتظار پرداخت', icon: <ClockCircleOutlined />, bg: '#fff7e6' },
        'processing': { color: '#1890ff', label: 'در حال پردازش', icon: <TruckOutlined />, bg: '#e6f7ff' },
        'shipped': { color: '#722ed1', label: 'ارسال شده', icon: <TruckOutlined />, bg: '#f9f0ff' },
        'delivered': { color: '#52c41a', label: 'تحویل شده', icon: <CheckCircleOutlined />, bg: '#f6ffed' },
        'cancelled': { color: '#ff4d4f', label: 'لغو شده', icon: <CloseCircleOutlined />, bg: '#fff1f0' },
        'paid': { color: '#13c2c2', label: 'پرداخت شده', icon: <WalletOutlined />, bg: '#e6fffa' },
        'preparing': { color: '#1890ff', label: 'در حال آماده‌سازی', icon: <MedicineBoxOutlined />, bg: '#e6f7ff' },
        'ready': { color: '#faad14', label: 'آماده تحویل', icon: <CheckCircleOutlined />, bg: '#fff7e6' },
    };
    return statusMap[status] || { color: '#d9d9d9', label: status, icon: null, bg: '#fafafa' };
}

function getPaymentStatusConfig(status) {
    const statusMap = {
        'pending': { color: '#faad14', label: 'در انتظار پرداخت' },
        'payment_pending': { color: '#faad14', label: 'در انتظار پرداخت' },
        'paid': { color: '#52c41a', label: 'پرداخت شده' },
        'failed': { color: '#ff4d4f', label: 'ناموفق' },
        'refunded': { color: '#722ed1', label: 'عودت داده شده' },
    };
    return statusMap[status] || { color: '#d9d9d9', label: status };
}

// ============================================
// کامپوننت اصلی
// ============================================

export default function PharmacyOrdersPage() {
    const router = useRouter();
    const { locale } = useLanguage();
    const { message, modal } = App.useApp();

    const [loading, setLoading] = useState(true);
    const [orders, setOrders] = useState([]);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [modalVisible, setModalVisible] = useState(false);
    const [activeTab, setActiveTab] = useState('all');
    const [isMounted, setIsMounted] = useState(false);
    const [cancelling, setCancelling] = useState(false);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
    const getToken = () => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('token');
        }
        return null;
    };

    // حل مشکل Hydration
    useEffect(() => {
        setIsMounted(true);
    }, []);

    // دریافت لیست سفارشات
    useEffect(() => {
        if (!isMounted) return;

        const token = getToken();
        if (!token) {
            router.push(`/${locale}/login?redirect=/${locale}/profile/pharmacy-orders`);
            return;
        }

        fetchOrders();
    }, [locale, router, isMounted]);

    const fetchOrders = async () => {
        setLoading(true);
        try {
            const token = getToken();
            const res = await fetch(`${API_URL}/api/pharmacy/orders`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();

            if (data.success && Array.isArray(data.data?.data)) {
                setOrders(data.data.data);
            } else if (data.success && Array.isArray(data.data)) {
                setOrders(data.data);
            } else {
                setOrders([]);
                if (data.message) {
                    message.warning(data.message);
                }
            }
        } catch (error) {
            console.error('Error fetching orders:', error);
            message.error('خطا در دریافت لیست سفارشات');
            setOrders([]);
        } finally {
            setLoading(false);
        }
    };

    // مشاهده جزئیات سفارش
    const viewOrderDetails = (order) => {
        setSelectedOrder(order);
        setModalVisible(true);
    };

    // لغو سفارش
    const cancelOrder = async (orderId) => {
        setCancelling(true);
        try {
            const token = getToken();
            const res = await fetch(`${API_URL}/api/pharmacy/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            if (!res.ok) {
                const errorData = await res.json();
                throw new Error(errorData.message || `HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            if (data.success) {
                message.success('✅ سفارش با موفقیت لغو شد');
                await fetchOrders();
                setModalVisible(false);
            } else {
                message.error(data.message || 'خطا در لغو سفارش');
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            message.error(error.message || 'خطا در لغو سفارش');
        } finally {
            setCancelling(false);
        }
    };

    // دریافت مجدد سفارش
    const reorder = (order) => {
        if (!order || !order.items) {
            message.warning('امکان سفارش مجدد وجود ندارد');
            return;
        }
        const items = order.items.map(item => ({
            id: item.drug_id || item.id,
            name: item.drug?.name || item.name || 'محصول',
            price: item.price || item.unit_price || 0,
            quantity: item.quantity || 1,
        }));
        localStorage.setItem('pharmacyCart', JSON.stringify(items));
        message.success('آیتم‌ها به سبد خرید اضافه شدند');
        router.push(`/${locale}/pharmacy`);
    };

    // فیلتر کردن سفارشات بر اساس وضعیت
    const getFilteredOrders = () => {
        if (!Array.isArray(orders)) return [];
        if (activeTab === 'all') return orders;
        return orders.filter(order => order.status === activeTab);
    };

    const filteredOrders = getFilteredOrders();

    // تنظیمات تب‌ها با شمارش
    const getTabItems = () => {
        const counts = {
            all: orders.length,
            pending: orders.filter(o => o.status === 'pending').length,
            payment_pending: orders.filter(o => o.status === 'payment_pending').length,
            processing: orders.filter(o => o.status === 'processing').length,
            shipped: orders.filter(o => o.status === 'shipped').length,
            delivered: orders.filter(o => o.status === 'delivered').length,
            cancelled: orders.filter(o => o.status === 'cancelled').length,
        };

        return [
            { key: 'all', label: `همه (${counts.all})` },
            { key: 'pending', label: `در انتظار (${counts.pending})` },
            { key: 'payment_pending', label: `در انتظار پرداخت (${counts.payment_pending})` },
            { key: 'processing', label: `در حال پردازش (${counts.processing})` },
            { key: 'shipped', label: `ارسال شده (${counts.shipped})` },
            { key: 'delivered', label: `تحویل شده (${counts.delivered})` },
            { key: 'cancelled', label: `لغو شده (${counts.cancelled})` },
        ];
    };

    // ستون‌های جدول
    const columns = [
        {
            title: 'شماره سفارش',
            dataIndex: 'order_number',
            key: 'order_number',
            render: (text) => <Text strong style={{ fontSize: '13px' }}>#{text || '---'}</Text>,
        },
        {
            title: 'تاریخ',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => <Text>{formatDate(date)}</Text>,
            sorter: (a, b) => new Date(a.created_at) - new Date(b.created_at),
        },
        {
            title: 'اقلام',
            key: 'items_count',
            align: 'center',
            render: (_, record) => {
                const count = record.items?.length || 0;
                return <Badge count={count} showZero style={{ backgroundColor: count > 0 ? '#52c41a' : '#d9d9d9' }} />;
            },
        },
        {
            title: 'مبلغ کل',
            dataIndex: 'total_amount',
            key: 'total_amount',
            render: (price) => (
                <Text strong style={{ color: '#2563eb', fontSize: '14px' }}>
                    {formatPrice(price || 0)}
                </Text>
            ),
            sorter: (a, b) => (a.total_amount || 0) - (b.total_amount || 0),
        },
        {
            title: 'وضعیت پرداخت',
            dataIndex: 'payment_status',
            key: 'payment_status',
            render: (status) => {
                const config = getPaymentStatusConfig(status);
                return <Tag color={config.color}>{config.label}</Tag>;
            },
        },
        {
            title: 'وضعیت سفارش',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const config = getStatusConfig(status);
                return (
                    <Tag color={config.color} style={{ borderRadius: '12px', padding: '2px 12px' }}>
                        {config.icon} {config.label}
                    </Tag>
                );
            },
        },
        {
            title: 'عملیات',
            key: 'actions',
            width: 180,
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="مشاهده جزئیات">
                        <Button
                            type="primary"
                            size="small"
                            icon={<EyeOutlined />}
                            onClick={() => viewOrderDetails(record)}
                        />
                    </Tooltip>

                    {(record.status === 'pending' || record.status === 'payment_pending') && (
                        <Tooltip title="لغو سفارش">
                            <Button
                                danger
                                size="small"
                                icon={<DeleteOutlined />}
                                onClick={() => {
                                    modal.confirm({
                                        title: 'لغو سفارش',
                                        content: (
                                            <div>
                                                <p>آیا از لغو سفارش <strong>#{record.order_number}</strong> مطمئن هستید؟</p>
                                                <Text type="secondary" style={{ fontSize: '12px' }}>
                                                    این اقدام قابل بازگشت نیست.
                                                </Text>
                                            </div>
                                        ),
                                        okText: 'بله، لغو کن',
                                        cancelText: 'انصراف',
                                        okType: 'danger',
                                        onOk: () => cancelOrder(record.id),
                                        confirmLoading: cancelling,
                                    });
                                }}
                            />
                        </Tooltip>
                    )}

                    {record.status === 'delivered' && (
                        <Tooltip title="سفارش مجدد">
                            <Button
                                type="default"
                                size="small"
                                icon={<ReloadOutlined />}
                                onClick={() => reorder(record)}
                            />
                        </Tooltip>
                    )}
                </Space>
            ),
        },
    ];

    // ============================================
    // رندر
    // ============================================

    if (!isMounted) {
        return (
            <>
                <Header />
                <LoadingSpinner />
                <Footer />
            </>
        );
    }

    if (loading) {
        return (
            <>
                <Header />
                <div style={{ display: 'flex', justifyContent: 'center', padding: '60px' }}>
                    <Spin size="large" />
                </div>
                <Footer />
            </>
        );
    }

    return (
        <>
            <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
                    <Breadcrumb
                        items={[
                            { title: 'خانه', href: `/${locale}` },
                            { title: 'پروفایل', href: `/${locale}/profile` },
                            { title: 'سفارشات داروخانه' },
                        ]}
                    />

                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '16px' }}>
                        <div>
                            <Title level={2} style={{ marginBottom: '4px' }}>
                                📦 سفارشات داروخانه
                            </Title>
                            <Text type="secondary">
                                {orders.length > 0 ? `${toPersianNumber(orders.length)} سفارش` : 'هیچ سفارشی ثبت نشده است'}
                            </Text>
                        </div>
                        <Button
                            type="primary"
                            icon={<ShoppingCartOutlined />}
                            onClick={() => router.push(`/${locale}/pharmacy`)}
                            size="large"
                        >
                            خرید مجدد
                        </Button>
                    </div>

                    <Card style={{ marginTop: '24px', borderRadius: '16px', boxShadow: '0 1px 2px rgba(0,0,0,0.05)' }}>
                        <Tabs
                            activeKey={activeTab}
                            onChange={setActiveTab}
                            items={getTabItems()}
                            type="card"
                        />

                        {filteredOrders.length === 0 ? (
                            <Empty
                                description="هیچ سفارشی در این دسته‌بندی یافت نشد"
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                style={{ padding: '60px 0' }}
                            >
                                <Button
                                    type="primary"
                                    onClick={() => router.push(`/${locale}/pharmacy`)}
                                >
                                    شروع خرید
                                </Button>
                            </Empty>
                        ) : (
                            <Table
                                columns={columns}
                                dataSource={filteredOrders}
                                rowKey="id"
                                pagination={{
                                    pageSize: 10,
                                    showTotal: (total) => `تعداد ${toPersianNumber(total)} سفارش`,
                                    locale: { items_per_page: '/ صفحه' },
                                    placement: ['bottomCenter'],
                                    showSizeChanger: true,
                                    showQuickJumper: true,
                                    pageSizeOptions: ['5', '10', '20', '50', '100'],
                                }}
                                scroll={{ x: '100%' }}
                                locale={{
                                    emptyText: 'هیچ سفارشی یافت نشد',
                                }}
                                rowClassName={(record) => {
                                    if (record.status === 'cancelled') return 'row-cancelled';
                                    if (record.status === 'delivered') return 'row-delivered';
                                    return '';
                                }}
                            />
                        )}
                    </Card>
                </div>
            </main>

            {/* مودال جزئیات سفارش */}
            <Modal
                title={
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <span>📋 جزئیات سفارش</span>
                        <Tag color={getStatusConfig(selectedOrder?.status)?.color}>
                            {getStatusConfig(selectedOrder?.status)?.label}
                        </Tag>
                    </div>
                }
                open={modalVisible}
                onCancel={() => setModalVisible(false)}
                footer={[
                    (selectedOrder?.status === 'pending' || selectedOrder?.status === 'payment_pending') && (
                        <Button
                            key="cancel"
                            danger
                            icon={<DeleteOutlined />}
                            onClick={() => {
                                modal.confirm({
                                    title: 'لغو سفارش',
                                    content: 'آیا از لغو این سفارش مطمئن هستید؟',
                                    onOk: () => cancelOrder(selectedOrder.id),
                                    okText: 'بله، لغو کن',
                                    cancelText: 'انصراف',
                                });
                            }}
                            loading={cancelling}
                        >
                            لغو سفارش
                        </Button>
                    ),
                    selectedOrder?.status === 'delivered' && (
                        <Button
                            key="reorder"
                            type="primary"
                            icon={<ReloadOutlined />}
                            onClick={() => reorder(selectedOrder)}
                        >
                            سفارش مجدد
                        </Button>
                    ),
                    <Button key="close" onClick={() => setModalVisible(false)}>
                        بستن
                    </Button>,
                ]}
                width={750}
                centered
                style={{ maxWidth: '95vw' }}
            >
                {selectedOrder && (
                    <div>
                        <Descriptions bordered size="small" column={2}>
                            <Descriptions.Item label="شماره سفارش" span={2}>
                                <Text strong>#{selectedOrder.order_number}</Text>
                            </Descriptions.Item>
                            <Descriptions.Item label="تاریخ ثبت">
                                {formatDate(selectedOrder.created_at)}
                            </Descriptions.Item>
                            <Descriptions.Item label="وضعیت سفارش">
                                <Tag color={getStatusConfig(selectedOrder.status).color}>
                                    {getStatusConfig(selectedOrder.status).label}
                                </Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="وضعیت پرداخت">
                                <Tag color={getPaymentStatusConfig(selectedOrder.payment_status).color}>
                                    {getPaymentStatusConfig(selectedOrder.payment_status).label}
                                </Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="روش پرداخت">
                                {selectedOrder.payment_method === 'wallet' ? (
                                    <Space>
                                        <WalletOutlined />
                                        کیف پول
                                    </Space>
                                ) : (
                                    <Space>
                                        <CreditCardOutlined />
                                        درگاه پرداخت
                                    </Space>
                                )}
                            </Descriptions.Item>
                            <Descriptions.Item label="گیرنده">
                                {selectedOrder.recipient_name || '---'}
                            </Descriptions.Item>
                            <Descriptions.Item label="شماره تماس">
                                {selectedOrder.recipient_phone || '---'}
                            </Descriptions.Item>
                            <Descriptions.Item label="آدرس تحویل" span={2}>
                                {selectedOrder.delivery_address || '---'}
                            </Descriptions.Item>
                            {selectedOrder.delivery_notes && (
                                <Descriptions.Item label="توضیحات" span={2}>
                                    {selectedOrder.delivery_notes}
                                </Descriptions.Item>
                            )}
                        </Descriptions>

                        <Divider style={{ margin: '16px 0' }} />

                        <Title level={5}>📋 اقلام سفارش</Title>
                        {selectedOrder.items?.length > 0 ? (
                            selectedOrder.items.map((item, index) => (
                                <div
                                    key={index}
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        padding: '10px 0',
                                        borderBottom: index < selectedOrder.items.length - 1 ? '1px solid #f0f0f0' : 'none',
                                    }}
                                >
                                    <Space>
                                        <MedicineBoxOutlined style={{ color: '#1890ff', fontSize: '18px' }} />
                                        <div>
                                            <Text strong>{item.drug?.name || item.name || 'محصول'}</Text>
                                            <Text type="secondary" style={{ fontSize: '12px', display: 'block' }}>
                                                {item.drug?.generic_name || ''}
                                            </Text>
                                        </div>
                                    </Space>
                                    <Space>
                                        <Text type="secondary">× {toPersianNumber(item.quantity)}</Text>
                                        <Text strong style={{ color: '#2563eb' }}>
                                            {formatPrice((item.price || item.unit_price || 0) * (item.quantity || 1))}
                                        </Text>
                                    </Space>
                                </div>
                            ))
                        ) : (
                            <Text type="secondary">هیچ اقلامی برای این سفارش وجود ندارد</Text>
                        )}

                        <Divider style={{ margin: '16px 0' }} />

                        <div style={{
                            background: '#f8fafc',
                            padding: '16px',
                            borderRadius: '12px',
                            marginTop: '8px'
                        }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                <Text>جمع اقلام:</Text>
                                <Text>{formatPrice(selectedOrder.subtotal || 0)}</Text>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                <Text>هزینه ارسال:</Text>
                                <Text>{formatPrice(selectedOrder.delivery_fee || 0)}</Text>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                <Text>مالیات:</Text>
                                <Text>{formatPrice(selectedOrder.tax || 0)}</Text>
                            </div>
                            {selectedOrder.discount_amount > 0 && (
                                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                    <Text style={{ color: '#ff4d4f' }}>تخفیف:</Text>
                                    <Text style={{ color: '#ff4d4f' }}>-{formatPrice(selectedOrder.discount_amount)}</Text>
                                </div>
                            )}
                            <Divider style={{ margin: '8px 0' }} />
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '4px' }}>
                                <Text strong style={{ fontSize: '16px' }}>جمع کل:</Text>
                                <Text strong style={{ color: '#2563eb', fontSize: '18px' }}>
                                    {formatPrice(selectedOrder.total_amount || selectedOrder.total_price || 0)}
                                </Text>
                            </div>
                        </div>

                        {selectedOrder.cancelled_at && (
                            <>
                                <Divider />
                                <div style={{ padding: '12px', background: '#fff1f0', borderRadius: '8px' }}>
                                    <Text type="danger">
                                        <CloseCircleOutlined /> لغو شده در: {formatDate(selectedOrder.cancelled_at)}
                                    </Text>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </Modal>

        </>
    );
}