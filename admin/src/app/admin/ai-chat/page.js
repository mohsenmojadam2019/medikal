'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Statistic, Table, Tag, Spin, Alert, Button } from 'antd';
import {
    MessageOutlined,
    UserOutlined,
    WarningOutlined,
    CheckCircleOutlined,
    ReloadOutlined,
} from '@ant-design/icons';
import axios from 'axios';
import { useAuth } from '@/context/AuthContext';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';
import dynamic from 'next/dynamic';

const Line = dynamic(() => import('react-chartjs-2').then((mod) => mod.Line), {
    ssr: false,
});

export default function AiChatDashboard() {
    const { token } = useAuth();
    const { t } = useLanguage();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [stats, setStats] = useState({
        total_sessions: 0,
        total_messages: 0,
        emergencies: 0,
        active_sessions: 0,
        feedback_helpful: 0,
        daily_stats: {},
        recent_messages: [],
    });

    const fetchStats = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get('/api/v1/admin/chat/analytics', {
                headers: { Authorization: `Bearer ${token}` },
            });
            if (response.data.success) {
                setStats(response.data.data);
            } else {
                setError('خطا در دریافت آمار');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchStats();
    }, []);

    const columns = [
        {
            title: 'کاربر',
            dataIndex: 'user_name',
            key: 'user_name',
        },
        {
            title: 'پیام',
            dataIndex: 'content',
            key: 'content',
            ellipsis: true,
        },
        {
            title: 'دسته‌بندی',
            dataIndex: 'category',
            key: 'category',
            render: (category) => <Tag color="blue">{category || 'عمومی'}</Tag>,
        },
        {
            title: 'وضعیت',
            dataIndex: 'is_emergency',
            key: 'is_emergency',
            render: (isEmergency) => (
                <Tag color={isEmergency ? 'red' : 'green'}>
                    {isEmergency ? '⚠️ اورژانسی' : 'عادی'}
                </Tag>
            ),
        },
        {
            title: 'زمان',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('YYYY/MM/DD HH:mm'),
        },
    ];

    const chartData = {
        labels: Object.keys(stats.daily_stats || {}).map((date) =>
            dayjs(date).format('YYYY/MM/DD')
        ),
        datasets: [
            {
                label: 'تعداد پیام‌ها',
                data: Object.values(stats.daily_stats || {}).map((d) => d.messages || 0),
                borderColor: 'rgb(53, 162, 235)',
                backgroundColor: 'rgba(53, 162, 235, 0.5)',
                tension: 0.4,
            },
            {
                label: 'جلسات جدید',
                data: Object.values(stats.daily_stats || {}).map((d) => d.sessions || 0),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                tension: 0.4,
            },
        ],
    };

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                rtl: true,
            },
            title: {
                display: true,
                text: 'آمار روزانه فعالیت چت‌بات',
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    };

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
                    <Button onClick={fetchStats} icon={<ReloadOutlined />}>
                        تلاش مجدد
                    </Button>
                }
            />
        );
    }

    return (
        <div>
            <h1 className="text-2xl font-bold mb-6">📊 داشبورد هوش مصنوعی</h1>

            <Row gutter={[16, 16]}>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="جلسات فعال"
                            value={stats.active_sessions || 0}
                            prefix={<UserOutlined />}
                            valueStyle={{ color: '#3f8600' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="کل پیام‌ها"
                            value={stats.total_messages || 0}
                            prefix={<MessageOutlined />}
                            valueStyle={{ color: '#1890ff' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="موارد اورژانسی"
                            value={stats.emergencies || 0}
                            prefix={<WarningOutlined />}
                            valueStyle={{ color: '#cf1322' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="بازخورد مفید"
                            value={stats.feedback_helpful || 0}
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
            </Row>

            <Row gutter={[16, 16]} className="mt-6">
                <Col xs={24} lg={16}>
                    <Card title="نمودار فعالیت روزانه">
                        <Line data={chartData} options={chartOptions} />
                    </Card>
                </Col>
                <Col xs={24} lg={8}>
                    <Card title="آمار کلی">
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span>کل جلسات:</span>
                                <span className="font-bold">{stats.total_sessions || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>کل پیام‌ها:</span>
                                <span className="font-bold">{stats.total_messages || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>موارد اورژانسی:</span>
                                <span className="font-bold text-red-600">{stats.emergencies || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>بازخورد مفید:</span>
                                <span className="font-bold text-green-600">{stats.feedback_helpful || 0}</span>
                            </div>
                        </div>
                    </Card>
                </Col>
            </Row>

            <Card title="آخرین فعالیت‌ها" className="mt-6">
                <Table
                    columns={columns}
                    dataSource={stats.recent_messages || []}
                    rowKey="id"
                    pagination={{ pageSize: 5 }}
                    scroll={{ x: true }}
                />
            </Card>
        </div>
    );
}
