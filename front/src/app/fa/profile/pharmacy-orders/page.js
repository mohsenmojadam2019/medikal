'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import {
    Card, Table, Tag, Button, Space, Typography, Spin,
    Tabs, Empty, App, Modal, Descriptions, Divider,
    Badge, Timeline, Statistic, Row, Col, Tooltip, message,
    Popconfirm
} from 'antd';
import {
    EyeOutlined, CheckCircleOutlined, ClockCircleOutlined,
    CloseCircleOutlined, TruckOutlined, HomeOutlined,
    ShoppingOutlined, DollarOutlined, MedicineBoxOutlined,
    UserOutlined, PhoneOutlined, EnvironmentOutlined,
    ReloadOutlined, SendOutlined, CheckOutlined,
    SyncOutlined, InboxOutlined, CreditCardOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { TabPane } = Tabs;

// ============================================================
// ✅ تبدیل اعداد به فارسی
// ============================================================
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
    if (!dateStr) return '—';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('fa-IR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
}

// ============================================================
// ✅ وضعیت‌های سفارش
// ============================================================
const ORDER_STATUS = {
    pending: { label: 'در انتظار', color: 'default', icon: <ClockCircleOutlined /> },
    payment_pending: { label: 'در انتظار پرداخت', color: 'warning', icon: <ClockCircleOutlined /> },
    paid: { label: 'پرداخت شده', color: 'blue', icon: <CheckCircleOutlined /> },
    processing: { label: 'در حال آماده‌سازی', color: 'processing', icon: <SyncOutlined spin /> },
    shipped: { label: 'ارسال شده', color: 'purple', icon: <TruckOutlined /> },
    delivered: { label: 'تحویل داده شد', color: 'success', icon: <HomeOutlined /> },
    cancelled: { label: 'لغو شده', color: 'error', icon: <CloseCircleOutlined /> },
    failed: { label: 'ناموفق', color: 'error', icon: <CloseCircleOutlined /> },
};

const STATUS_ORDER = ['pending', 'payment_pending', 'paid', 'processing', 'shipped', 'delivered'];

export default function PharmacyOrdersPage() {
    const router = useRouter();
    const { locale } = useLanguage();
    const { message: appMessage } = App.useApp();

    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('all');
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [modalVisible, setModalVisible] = useState(false);
    const [updating, setUpdating] = useState(false);
    const [isAdmin, setIsAdmin] = useState(false);
    const [payingOrderId, setPayingOrderId] = useState(null);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    const getToken = () => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('token');
        }
        return null;
    };

    // ============================================================
    // ✅ دریافت لیست سفارشات
    // ============================================================
    const fetchOrders = useCallback(async () => {
        const token = getToken();
        if (!token) {
            router.push(`/${locale}/login`);
            return;
        }

        setLoading(true);
        try {
            const userRes = await fetch(`${API_URL}/api/auth/me`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const userData = await userRes.json();
            const userRole = userData.data?.role || userData.data?.roles?.[0] || '';
            setIsAdmin(userRole === 'admin' || userRole === 'super_admin');

            const res = await fetch(`${API_URL}/api/pharmacy/orders`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            const data = await res.json();
            console.log('📦 Orders response:', data);

            if (data.success) {
                let ordersData = data.data?.data || data.data || [];
                setOrders(ordersData);
            } else {
                appMessage.error(data.message || 'خطا در دریافت سفارشات');
            }
        } catch (error) {
            console.error('❌ Error fetching orders:', error);
            appMessage.error('خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    }, [router, locale, appMessage, API_URL]);

    useEffect(() => {
        fetchOrders();
    }, [fetchOrders]);

    // ============================================================
    // ✅ پرداخت سفارش (کاربر)
    // ============================================================
    const handlePayOrder = async (orderId) => {
        setPayingOrderId(orderId);
        try {
            const token = getToken();

            const res = await fetch(`${API_URL}/api/pharmacy/orders/${orderId}/pay`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ gateway: 'local' }),
            });

            const data = await res.json();
            console.log('💳 Pay response:', data);

            if (data.success) {
                const paymentLink = data.data?.redirect_url || data.data?.payment_link;

                if (paymentLink) {
                    message.success('در حال انتقال به درگاه پرداخت...');

                    // ✅ اصلاح لینک
                    let cleanLink = paymentLink
                        .replace(/\\/g, '')
                        .replace(/"/g, '')
                        .replace(/\s/g, '');

                    // ✅ اصلاح ? های اضافی
                    const firstQIndex = cleanLink.indexOf('?');
                    if (firstQIndex !== -1) {
                        const baseUrl = cleanLink.substring(0, firstQIndex);
                        let params = cleanLink.substring(firstQIndex + 1);
                        params = params.replace(/\?/g, '&');
                        cleanLink = baseUrl + '?' + params;
                    }

                    if (!cleanLink.includes('success=true')) {
                        cleanLink = cleanLink.includes('?')
                            ? `${cleanLink}&success=true`
                            : `${cleanLink}?success=true`;
                    }

                    console.log('✅ Final payment link:', cleanLink);

                    setTimeout(() => {
                        window.location.href = cleanLink;
                    }, 500);
                } else {
                    appMessage.error('لینک پرداخت یافت نشد');
                }
            } else {
                appMessage.error(data.message || 'خطا در شروع پرداخت');
            }
        } catch (error) {
            console.error('❌ Pay error:', error);
            appMessage.error('خطا در ارتباط با سرور');
        } finally {
            setPayingOrderId(null);
        }
    };

    // ============================================================
    // ✅ بروزرسانی وضعیت سفارش (ادمین)
    // ============================================================
    const updateOrderStatus = async (orderId, newStatus) => {
        if (!isAdmin) {
            appMessage.warning('شما دسترسی ادمین ندارید');
            return;
        }

        setUpdating(true);
        try {
            const token = getToken();

            const res = await fetch(`${API_URL}/api/admin/pharmacy-orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ status: newStatus }),
            });

            const data = await res.json();
            if (data.success) {
                const statusLabel = ORDER_STATUS[newStatus]?.label || newStatus;
                appMessage.success(`✅ وضعیت سفارش به "${statusLabel}" تغییر کرد`);
                fetchOrders();
                setModalVisible(false);
            } else {
                appMessage.error(data.message || 'خطا در بروزرسانی وضعیت');
            }
        } catch (error) {
            console.error('❌ Error updating order:', error);
            appMessage.error('خطا در ارتباط با سرور');
        } finally {
            setUpdating(false);
        }
    };

    // ============================================================
    // ✅ فیلتر کردن سفارشات بر اساس تب
    // ============================================================
    const getFilteredOrders = () => {
        if (activeTab === 'all') return orders;
        return orders.filter(order => order.status === activeTab);
    };

    // ============================================================
    // ✅ ستون‌های جدول
    // ============================================================
    const columns = [
        {
            title: 'شماره سفارش',
            dataIndex: 'order_number',
            key: 'order_number',
            render: (text) => <Text strong>{text}</Text>,
        },
        {
            title: 'تاریخ',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (text) => formatDate(text),
        },
        {
            title: 'مبلغ',
            dataIndex: 'total_amount',
            key: 'total_amount',
            render: (amount) => <Text strong>{formatPrice(amount)}</Text>,
        },
        {
            title: 'وضعیت',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const statusInfo = ORDER_STATUS[status] || ORDER_STATUS.pending;
                return (
                    <Tag color={statusInfo.color} icon={statusInfo.icon}>
                        {statusInfo.label}
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
                        onClick={() => {
                            setSelectedOrder(record);
                            setModalVisible(true);
                        }}
                    >
                        مشاهده
                    </Button>

                    {/* ✅ دکمه پرداخت برای کاربر (فقط در انتظار پرداخت) */}
                    {record.status === 'payment_pending' && (
                        <Button
                            type="primary"
                            size="small"
                            icon={<CreditCardOutlined />}
                            onClick={() => handlePayOrder(record.id)}
                            loading={payingOrderId === record.id}
                            style={{ background: '#22c55e', borderColor: '#22c55e' }}
                        >
                            پرداخت
                        </Button>
                    )}

                    {/* ✅ دکمه‌های ادمین */}
                    {isAdmin && record.status === 'paid' && (
                        <Popconfirm
                            title="تایید آماده‌سازی"
                            description="آیا از شروع فرآیند آماده‌سازی سفارش اطمینان دارید؟"
                            onConfirm={() => updateOrderStatus(record.id, 'processing')}
                            okText="بله، شروع کن"
                            cancelText="انصراف"
                        >
                            <Button
                                type="primary"
                                size="small"
                                icon={<SyncOutlined />}
                                loading={updating}
                                style={{ background: '#f59e0b', borderColor: '#f59e0b' }}
                            >
                                شروع آماده‌سازی
                            </Button>
                        </Popconfirm>
                    )}

                    {isAdmin && record.status === 'processing' && (
                        <Popconfirm
                            title="تایید ارسال"
                            description="آیا از ارسال این سفارش اطمینان دارید؟"
                            onConfirm={() => updateOrderStatus(record.id, 'shipped')}
                            okText="بله، ارسال کن"
                            cancelText="انصراف"
                        >
                            <Button
                                type="primary"
                                size="small"
                                icon={<SendOutlined />}
                                loading={updating}
                                style={{ background: '#8b5cf6', borderColor: '#8b5cf6' }}
                            >
                                ارسال دارو
                            </Button>
                        </Popconfirm>
                    )}

                    {isAdmin && record.status === 'shipped' && (
                        <Popconfirm
                            title="تایید تحویل"
                            description="آیا از تحویل سفارش به بیمار اطمینان دارید؟"
                            onConfirm={() => updateOrderStatus(record.id, 'delivered')}
                            okText="بله، تحویل شد"
                            cancelText="انصراف"
                        >
                            <Button
                                type="primary"
                                size="small"
                                icon={<CheckOutlined />}
                                loading={updating}
                                style={{ background: '#10b981', borderColor: '#10b981' }}
                            >
                                تحویل داده شد
                            </Button>
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    // ============================================================
    // ✅ تایم‌لاین وضعیت
    // ============================================================
    const getStatusTimeline = (order) => {
        const currentIndex = STATUS_ORDER.indexOf(order.status);

        return STATUS_ORDER.map((status, index) => {
            const isCompleted = index <= currentIndex;
            const isCurrent = index === currentIndex;
            const statusInfo = ORDER_STATUS[status];

            return {
                dot: isCompleted ? (
                    <CheckCircleOutlined style={{ color: '#10b981' }} />
                ) : (
                    <ClockCircleOutlined style={{ color: '#d1d5db' }} />
                ),
                children: (
                    <div>
                        <Text strong style={{ color: isCompleted ? '#1e293b' : '#94a3b8' }}>
                            {statusInfo.icon} {statusInfo.label}
                        </Text>
                        {isCurrent && (
                            <Tag color="blue" style={{ marginLeft: 8 }}>
                                فعلی
                            </Tag>
                        )}
                        {isCompleted && !isCurrent && (
                            <Tag color="green" style={{ marginLeft: 8 }}>
                                انجام شد
                            </Tag>
                        )}
                    </div>
                ),
                color: isCompleted ? '#10b981' : '#d1d5db',
            };
        });
    };

    // ============================================================
    // ✅ آمار وضعیت‌ها
    // ============================================================
    const getStatusCount = (status) => {
        return orders.filter(o => o.status === status).length;
    };

    // ============================================================
    // ✅ رندر
    // ============================================================
    if (loading) {
        return (
            <>
                <Header />
                <LoadingSpinner />
                <Footer />
            </>
        );
    }

    const filteredOrders = getFilteredOrders();

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

                    <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginBottom: '24px',
                        flexWrap: 'wrap',
                        gap: '16px'
                    }}>
                        <div>
                            <Title level={2} style={{ marginBottom: '4px' }}>
                                💊 سفارشات داروخانه
                            </Title>
                            <Text type="secondary">
                                {isAdmin ? 'مدیریت تمام سفارشات' : 'مشاهده سفارشات شما'}
                            </Text>
                        </div>
                        <Button
                            type="primary"
                            icon={<ReloadOutlined />}
                            onClick={fetchOrders}
                        >
                            بروزرسانی
                        </Button>
                    </div>

                    {/* آمار سفارشات */}
                    <Row gutter={[16, 16]} style={{ marginBottom: '24px' }}>
                        <Col xs={12} sm={4}>
                            <Card size="small">
                                <Statistic
                                    title="کل سفارشات"
                                    value={orders.length}
                                    prefix={<ShoppingOutlined />}
                                />
                            </Card>
                        </Col>
                        <Col xs={12} sm={4}>
                            <Card size="small">
                                <Statistic
                                    title="در انتظار پرداخت"
                                    value={getStatusCount('payment_pending')}
                                    prefix={<ClockCircleOutlined style={{ color: '#f59e0b' }} />}
                                    valueStyle={{ color: '#f59e0b' }}
                                />
                            </Card>
                        </Col>
                        <Col xs={12} sm={4}>
                            <Card size="small">
                                <Statistic
                                    title="پرداخت شده"
                                    value={getStatusCount('paid')}
                                    prefix={<CheckCircleOutlined style={{ color: '#3b82f6' }} />}
                                    valueStyle={{ color: '#3b82f6' }}
                                />
                            </Card>
                        </Col>
                        <Col xs={12} sm={4}>
                            <Card size="small">
                                <Statistic
                                    title="در حال آماده‌سازی"
                                    value={getStatusCount('processing')}
                                    prefix={<SyncOutlined style={{ color: '#f59e0b' }} spin />}
                                    valueStyle={{ color: '#f59e0b' }}
                                />
                            </Card>
                        </Col>
                        <Col xs={12} sm={4}>
                            <Card size="small">
                                <Statistic
                                    title="ارسال شده"
                                    value={getStatusCount('shipped')}
                                    prefix={<TruckOutlined style={{ color: '#8b5cf6' }} />}
                                    valueStyle={{ color: '#8b5cf6' }}
                                />
                            </Card>
                        </Col>
                        <Col xs={12} sm={4}>
                            <Card size="small">
                                <Statistic
                                    title="تحویل شده"
                                    value={getStatusCount('delivered')}
                                    prefix={<HomeOutlined style={{ color: '#10b981' }} />}
                                    valueStyle={{ color: '#10b981' }}
                                />
                            </Card>
                        </Col>
                    </Row>

                    {/* تب‌ها */}
                    <Card style={{ borderRadius: '16px' }}>
                        <Tabs activeKey={activeTab} onChange={setActiveTab}>
                            <TabPane tab={`همه (${orders.length})`} key="all" />
                            <TabPane tab={`در انتظار پرداخت (${getStatusCount('payment_pending')})`} key="payment_pending" />
                            <TabPane tab={`پرداخت شده (${getStatusCount('paid')})`} key="paid" />
                            <TabPane tab={`در حال آماده‌سازی (${getStatusCount('processing')})`} key="processing" />
                            <TabPane tab={`ارسال شده (${getStatusCount('shipped')})`} key="shipped" />
                            <TabPane tab={`تحویل شده (${getStatusCount('delivered')})`} key="delivered" />
                            <TabPane tab={`لغو شده (${getStatusCount('cancelled')})`} key="cancelled" />
                        </Tabs>

                        {filteredOrders.length > 0 ? (
                            <Table
                                dataSource={filteredOrders}
                                columns={columns}
                                rowKey="id"
                                pagination={{ pageSize: 10 }}
                                scroll={{ x: 700 }}
                            />
                        ) : (
                            <Empty
                                description="هیچ سفارشی در این بخش وجود ندارد"
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                            >
                                <Button
                                    type="primary"
                                    onClick={() => router.push(`/${locale}/pharmacy`)}
                                >
                                    خرید از داروخانه
                                </Button>
                            </Empty>
                        )}
                    </Card>
                </div>
            </main>

            {/* ============================================================
            ✅ مودال جزئیات سفارش
            ============================================================ */}
            <Modal
                title={`جزئیات سفارش #${selectedOrder?.order_number || ''}`}
                open={modalVisible}
                onCancel={() => setModalVisible(false)}
                footer={[
                    <Button key="close" onClick={() => setModalVisible(false)}>
                        بستن
                    </Button>,
                    // ✅ دکمه پرداخت در مودال (کاربر)
                    selectedOrder?.status === 'payment_pending' && (
                        <Button
                            key="pay"
                            type="primary"
                            icon={<CreditCardOutlined />}
                            onClick={() => handlePayOrder(selectedOrder.id)}
                            loading={payingOrderId === selectedOrder.id}
                            style={{ background: '#22c55e', borderColor: '#22c55e' }}
                            size="large"
                        >
                            پرداخت سفارش
                        </Button>
                    ),
                    // ✅ دکمه‌های ادمین در مودال
                    isAdmin && selectedOrder?.status === 'paid' && (
                        <Button
                            key="processing"
                            type="primary"
                            icon={<SyncOutlined />}
                            onClick={() => updateOrderStatus(selectedOrder.id, 'processing')}
                            loading={updating}
                            style={{ background: '#f59e0b', borderColor: '#f59e0b' }}
                        >
                            شروع آماده‌سازی
                        </Button>
                    ),
                    isAdmin && selectedOrder?.status === 'processing' && (
                        <Button
                            key="ship"
                            type="primary"
                            icon={<SendOutlined />}
                            onClick={() => updateOrderStatus(selectedOrder.id, 'shipped')}
                            loading={updating}
                            style={{ background: '#8b5cf6', borderColor: '#8b5cf6' }}
                        >
                            ارسال دارو
                        </Button>
                    ),
                    isAdmin && selectedOrder?.status === 'shipped' && (
                        <Button
                            key="deliver"
                            type="primary"
                            icon={<CheckOutlined />}
                            onClick={() => updateOrderStatus(selectedOrder.id, 'delivered')}
                            loading={updating}
                            style={{ background: '#10b981', borderColor: '#10b981' }}
                        >
                            تحویل داده شد
                        </Button>
                    ),
                ]}
                width={700}
            >
                {selectedOrder && (
                    <div>
                        {/* وضعیت فعلی */}
                        <div style={{ marginBottom: '16px' }}>
                            <Badge
                                status={ORDER_STATUS[selectedOrder.status]?.color || 'default'}
                                text={
                                    <Text strong style={{ fontSize: '16px' }}>
                                        وضعیت: {ORDER_STATUS[selectedOrder.status]?.label || selectedOrder.status}
                                    </Text>
                                }
                            />
                            {/* اگر در انتظار پرداخت است، پیام ویژه */}
                            {selectedOrder.status === 'payment_pending' && (
                                <div style={{ marginTop: '8px' }}>
                                    <Tag color="warning" icon={<ClockCircleOutlined />}>
                                        ⚠️ این سفارش هنوز پرداخت نشده است
                                    </Tag>
                                </div>
                            )}
                        </div>

                        {/* اطلاعات سفارش */}
                        <Descriptions bordered size="small" column={2}>
                            <Descriptions.Item label="شماره سفارش">
                                {selectedOrder.order_number}
                            </Descriptions.Item>
                            <Descriptions.Item label="تاریخ ثبت">
                                {formatDate(selectedOrder.created_at)}
                            </Descriptions.Item>
                            <Descriptions.Item label="مبلغ کل">
                                <Text strong style={{ color: '#2563eb' }}>
                                    {formatPrice(selectedOrder.total_amount)}
                                </Text>
                            </Descriptions.Item>
                            <Descriptions.Item label="وضعیت پرداخت">
                                <Tag color={selectedOrder.is_paid ? 'green' : 'red'}>
                                    {selectedOrder.is_paid ? 'پرداخت شده' : 'پرداخت نشده'}
                                </Tag>
                            </Descriptions.Item>
                        </Descriptions>

                        <Divider />

                        {/* اطلاعات تحویل */}
                        <Title level={5}>📦 اطلاعات تحویل</Title>
                        <Descriptions bordered size="small" column={1}>
                            <Descriptions.Item label="نام گیرنده">
                                <UserOutlined /> {selectedOrder.recipient_name || '—'}
                            </Descriptions.Item>
                            <Descriptions.Item label="شماره تماس">
                                <PhoneOutlined /> {selectedOrder.recipient_phone || '—'}
                            </Descriptions.Item>
                            <Descriptions.Item label="آدرس تحویل">
                                <EnvironmentOutlined /> {selectedOrder.delivery_address || '—'}
                            </Descriptions.Item>
                            {selectedOrder.delivery_notes && (
                                <Descriptions.Item label="توضیحات">
                                    {selectedOrder.delivery_notes}
                                </Descriptions.Item>
                            )}
                        </Descriptions>

                        <Divider />

                        {/* محصولات */}
                        <Title level={5}>💊 محصولات</Title>
                        {selectedOrder.items?.length > 0 ? (
                            <Table
                                dataSource={selectedOrder.items}
                                columns={[
                                    { title: 'نام محصول', dataIndex: 'drug', key: 'drug', render: (drug) => drug?.name || '—' },
                                    { title: 'تعداد', dataIndex: 'quantity', key: 'quantity', render: (q) => toPersianNumber(q) },
                                    {
                                        title: 'قیمت واحد',
                                        dataIndex: 'unit_price',
                                        key: 'unit_price',
                                        render: (p) => formatPrice(p)
                                    },
                                    {
                                        title: 'جمع',
                                        key: 'total',
                                        render: (_, item) => formatPrice(item.unit_price * item.quantity)
                                    },
                                ]}
                                rowKey="id"
                                pagination={false}
                                size="small"
                            />
                        ) : (
                            <Empty description="هیچ محصولی در این سفارش وجود ندارد" />
                        )}

                        <Divider />

                        {/* تایم‌لاین وضعیت */}
                        <Title level={5}>⏳ روند سفارش</Title>
                        <Timeline items={getStatusTimeline(selectedOrder)} />

                        {/* دکمه‌های مدیریت در پایین مودال */}
                        <div style={{ marginTop: '16px', paddingTop: '16px', borderTop: '1px solid #f0f0f0' }}>
                            {selectedOrder.status === 'payment_pending' ? (
                                <div>
                                    <Text type="warning" style={{ display: 'block', marginBottom: '8px' }}>
                                        ⚠️ این سفارش پرداخت نشده است
                                    </Text>
                                    <Button
                                        type="primary"
                                        size="large"
                                        icon={<CreditCardOutlined />}
                                        onClick={() => handlePayOrder(selectedOrder.id)}
                                        loading={payingOrderId === selectedOrder.id}
                                        style={{ background: '#22c55e', borderColor: '#22c55e' }}
                                        block
                                    >
                                        پرداخت سفارش
                                    </Button>
                                </div>
                            ) : isAdmin ? (
                                <div>
                                    <Text type="secondary" style={{ display: 'block', marginBottom: '8px' }}>
                                        🔑 عملیات ادمین:
                                    </Text>
                                    <Space wrap>
                                        {selectedOrder.status === 'paid' && (
                                            <Button
                                                type="primary"
                                                icon={<SyncOutlined />}
                                                onClick={() => updateOrderStatus(selectedOrder.id, 'processing')}
                                                loading={updating}
                                                style={{ background: '#f59e0b', borderColor: '#f59e0b' }}
                                            >
                                                شروع آماده‌سازی
                                            </Button>
                                        )}
                                        {selectedOrder.status === 'processing' && (
                                            <Button
                                                type="primary"
                                                icon={<SendOutlined />}
                                                onClick={() => updateOrderStatus(selectedOrder.id, 'shipped')}
                                                loading={updating}
                                                style={{ background: '#8b5cf6', borderColor: '#8b5cf6' }}
                                            >
                                                ارسال دارو
                                            </Button>
                                        )}
                                        {selectedOrder.status === 'shipped' && (
                                            <Button
                                                type="primary"
                                                icon={<CheckOutlined />}
                                                onClick={() => updateOrderStatus(selectedOrder.id, 'delivered')}
                                                loading={updating}
                                                style={{ background: '#10b981', borderColor: '#10b981' }}
                                            >
                                                تحویل داده شد
                                            </Button>
                                        )}
                                    </Space>
                                </div>
                            ) : null}
                        </div>
                    </div>
                )}
            </Modal>

        </>
    );
}