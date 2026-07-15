// /src/app/fa/profile/pharmacy-orders/page.js
'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
    Card, Table, Tag, Button, Typography, Space,
    Spin, Empty, Tabs, Badge, Avatar, Descriptions,
    Modal, Divider, Timeline, Alert, Skeleton, message
} from 'antd';
import {
    MedicineBoxOutlined, EyeOutlined, CheckCircleOutlined,
    CloseCircleOutlined, ClockCircleOutlined, TruckOutlined,
    HomeOutlined, UserOutlined, PhoneOutlined,
    WalletOutlined, CreditCardOutlined, ReloadOutlined,
    ShoppingCartOutlined, LeftOutlined
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
    return toPersianNumber(price.toLocaleString()) + ' تومان';
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

const getStatusConfig = (status) => {
    const statusMap = {
        'pending': { color: 'orange', label: 'در انتظار تایید', icon: <ClockCircleOutlined /> },
        'processing': { color: 'blue', label: 'در حال پردازش', icon: <TruckOutlined /> },
        'shipped': { color: 'purple', label: 'ارسال شده', icon: <TruckOutlined /> },
        'delivered': { color: 'green', label: 'تحویل شده', icon: <CheckCircleOutlined /> },
        'cancelled': { color: 'red', label: 'لغو شده', icon: <CloseCircleOutlined /> },
        'paid': { color: 'cyan', label: 'پرداخت شده', icon: <WalletOutlined /> },
        'preparing': { color: 'blue', label: 'در حال آماده‌سازی', icon: <MedicineBoxOutlined /> },
        'ready': { color: 'gold', label: 'آماده تحویل', icon: <CheckCircleOutlined /> },
        'payment_pending': { color: 'orange', label: 'در انتظار پرداخت', icon: <ClockCircleOutlined /> },
    };
    return statusMap[status] || { color: 'default', label: status, icon: null };
};

const getPaymentStatusConfig = (status) => {
    const statusMap = {
        'pending': { color: 'orange', label: 'در انتظار پرداخت' },
        'paid': { color: 'green', label: 'پرداخت شده' },
        'failed': { color: 'red', label: 'ناموفق' },
        'refunded': { color: 'purple', label: 'عودت داده شده' },
        'payment_pending': { color: 'orange', label: 'در انتظار پرداخت' },
    };
    return statusMap[status] || { color: 'default', label: status };
};

// ============================================
// کامپوننت اصلی
// ============================================

export default function PharmacyOrdersPage() {
    const router = useRouter();
    const { locale } = useLanguage();
    const [loading, setLoading] = useState(true);
    const [orders, setOrders] = useState([]);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [modalVisible, setModalVisible] = useState(false);
    const [activeTab, setActiveTab] = useState('all');
    const [isMounted, setIsMounted] = useState(false);

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

            // بررسی اینکه داده آرایه است
            if (data.success && Array.isArray(data.data)) {
                setOrders(data.data);
            } else if (data.success && data.data && typeof data.data === 'object') {
                // اگر داده به صورت آبجکت برگشت (مثلاً paginated)
                const items = data.data.data || data.data.items || [];
                setOrders(Array.isArray(items) ? items : []);
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
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            if (data.success) {
                message.success('سفارش با موفقیت لغو شد');
                fetchOrders();
                setModalVisible(false);
            } else {
                message.error(data.message || 'خطا در لغو سفارش');
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            message.error('خطا در لغو سفارش');
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
            price: item.price || 0,
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

    // تنظیمات تب‌ها با استفاده از items
    const tabItems = [
        { key: 'all', label: 'همه' },
        { key: 'pending', label: 'در انتظار' },
        { key: 'processing', label: 'در حال پردازش' },
        { key: 'shipped', label: 'ارسال شده' },
        { key: 'delivered', label: 'تحویل شده' },
        { key: 'cancelled', label: 'لغو شده' },
        { key: 'payment_pending', label: 'در انتظار پرداخت' },
    ];

    // ستون‌های جدول
    const columns = [
        {
            title: 'شماره سفارش',
            dataIndex: 'order_number',
            key: 'order_number',
            render: (text) => <Text strong>#{text || '---'}</Text>,
        },
        {
            title: 'تاریخ',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => formatDate(date),
        },
        {
            title: 'تعداد اقلام',
            key: 'items_count',
            render: (_, record) => {
                const count = record.items?.length || 0;
                return <Badge count={count} showZero />;
            },
        },
        {
            title: 'مبلغ کل',
            dataIndex: 'total_price',
            key: 'total_price',
            render: (price) => (
                <Text strong style={{ color: '#2563eb' }}>
                    {formatPrice(price || 0)}
                </Text>
            ),
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
                    <Tag color={config.color} icon={config.icon}>
                        {config.label}
                    </Tag>
                );
            },
        },
        {
            title: 'عملیات',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button
                        type="primary"
                        size="small"
                        icon={<EyeOutlined />}
                        onClick={() => viewOrderDetails(record)}
                    >
                        جزئیات
                    </Button>
                    {(record.status === 'pending' || record.status === 'payment_pending') && (
                        <Button
                            danger
                            size="small"
                            onClick={() => {
                                Modal.confirm({
                                    title: 'لغو سفارش',
                                    content: 'آیا از لغو این سفارش مطمئن هستید؟',
                                    onOk: () => cancelOrder(record.id),
                                });
                            }}
                        >
                            لغو
                        </Button>
                    )}
                    {record.status === 'delivered' && (
                        <Button
                            type="default"
                            size="small"
                            icon={<ReloadOutlined />}
                            onClick={() => reorder(record)}
                        >
                            سفارش مجدد
                        </Button>
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
                <LoadingSpinner />
                <Footer />
            </>
        );
    }

    return (
        <>
            <Header />
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
                        >
                            خرید مجدد
                        </Button>
                    </div>

                    <Card style={{ marginTop: '24px', borderRadius: '16px' }}>
                        <Tabs
                            activeKey={activeTab}
                            onChange={setActiveTab}
                            items={tabItems}
                        />

                        {filteredOrders.length === 0 ? (
                            <Empty
                                description="هیچ سفارشی در این دسته‌بندی یافت نشد"
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                style={{ padding: '40px 0' }}
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
                                }}
                                scroll={{ x: '100%' }}
                                locale={{
                                    emptyText: 'هیچ سفارشی یافت نشد',
                                }}
                            />
                        )}
                    </Card>
                </div>
            </main>

            {/* مودال جزئیات سفارش */}
            <Modal
                title={`جزئیات سفارش #${selectedOrder?.order_number || ''}`}
                open={modalVisible}
                onCancel={() => setModalVisible(false)}
                footer={[
                    (selectedOrder?.status === 'pending' || selectedOrder?.status === 'payment_pending') && (
                        <Button
                            key="cancel"
                            danger
                            onClick={() => {
                                Modal.confirm({
                                    title: 'لغو سفارش',
                                    content: 'آیا از لغو این سفارش مطمئن هستید؟',
                                    onOk: () => cancelOrder(selectedOrder.id),
                                });
                            }}
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
                width={700}
                centered
            >
                {selectedOrder && (
                    <div>
                        <Descriptions bordered size="small" column={2}>
                            <Descriptions.Item label="شماره سفارش">
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
                            <Descriptions.Item label="روش پرداخت" span={2}>
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
                        </Descriptions>

                        <Divider />

                        <Title level={5}>📋 اقلام سفارش</Title>
                        {selectedOrder.items?.length > 0 ? (
                            selectedOrder.items.map((item, index) => (
                                <div
                                    key={index}
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        padding: '8px 0',
                                        borderBottom: index < selectedOrder.items.length - 1 ? '1px solid #f0f0f0' : 'none',
                                    }}
                                >
                                    <Space>
                                        <MedicineBoxOutlined />
                                        <Text>{item.drug?.name || item.name || 'محصول'}</Text>
                                        <Text type="secondary">× {toPersianNumber(item.quantity)}</Text>
                                    </Space>
                                    <Text>{formatPrice((item.price || 0) * (item.quantity || 1))}</Text>
                                </div>
                            ))
                        ) : (
                            <Text type="secondary">هیچ اقلامی برای این سفارش وجود ندارد</Text>
                        )}

                        <Divider />

                        <div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                <Text>جمع اقلام:</Text>
                                <Text>{formatPrice(selectedOrder.subtotal || 0)}</Text>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                <Text>هزینه ارسال:</Text>
                                <Text>{formatPrice(selectedOrder.shipping_fee || 0)}</Text>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                                <Text>مالیات:</Text>
                                <Text>{formatPrice(selectedOrder.tax || 0)}</Text>
                            </div>
                            <Divider style={{ margin: '8px 0' }} />
                            <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '8px' }}>
                                <Text strong>جمع کل:</Text>
                                <Text strong style={{ color: '#2563eb', fontSize: '18px' }}>
                                    {formatPrice(selectedOrder.total_price || 0)}
                                </Text>
                            </div>
                        </div>

                        <Divider />

                        <div>
                            <Title level={5}>📦 اطلاعات تحویل</Title>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <UserOutlined />
                                    <Text>گیرنده: {selectedOrder.recipient_name || selectedOrder.recipientName || '---'}</Text>
                                </div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <PhoneOutlined />
                                    <Text>شماره تماس: {selectedOrder.recipient_phone || selectedOrder.recipientPhone || '---'}</Text>
                                </div>
                                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px' }}>
                                    <HomeOutlined style={{ marginTop: '4px' }} />
                                    <Text>آدرس: {selectedOrder.delivery_address || selectedOrder.deliveryAddress || '---'}</Text>
                                </div>
                                {selectedOrder.delivery_notes && (
                                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px' }}>
                                        <Text type="secondary">توضیحات: {selectedOrder.delivery_notes}</Text>
                                    </div>
                                )}
                            </div>
                        </div>

                        <Divider />

                        <div>
                            <Title level={5}>⏳ زمان‌بندی</Title>
                            <Timeline
                                items={[
                                    {
                                        color: 'blue',
                                        children: (
                                            <>
                                                <Text strong>ثبت سفارش</Text>
                                                <br />
                                                <Text type="secondary">{formatDate(selectedOrder.created_at)}</Text>
                                            </>
                                        ),
                                    },
                                    ...(selectedOrder.paid_at ? [{
                                        color: 'green',
                                        children: (
                                            <>
                                                <Text strong>پرداخت</Text>
                                                <br />
                                                <Text type="secondary">{formatDate(selectedOrder.paid_at)}</Text>
                                            </>
                                        ),
                                    }] : []),
                                    ...(selectedOrder.shipped_at ? [{
                                        color: 'purple',
                                        children: (
                                            <>
                                                <Text strong>ارسال</Text>
                                                <br />
                                                <Text type="secondary">{formatDate(selectedOrder.shipped_at)}</Text>
                                            </>
                                        ),
                                    }] : []),
                                    ...(selectedOrder.delivered_at ? [{
                                        color: 'green',
                                        children: (
                                            <>
                                                <Text strong>تحویل</Text>
                                                <br />
                                                <Text type="secondary">{formatDate(selectedOrder.delivered_at)}</Text>
                                            </>
                                        ),
                                    }] : []),
                                ]}
                            />
                        </div>
                    </div>
                )}
            </Modal>

            <Footer />
        </>
    );
}