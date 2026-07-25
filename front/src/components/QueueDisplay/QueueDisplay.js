// src/components/QueueDisplay/QueueDisplay.jsx
'use client';

import { useState, useEffect } from 'react';
import { Card, List, Tag, Typography, Spin } from 'antd';
import { CheckCircleOutlined, ClockCircleOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

export default function QueueDisplay({ doctorId }) {
    const [queue, setQueue] = useState([]);
    const [current, setCurrent] = useState(null);
    const [loading, setLoading] = useState(true);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    useEffect(() => {
        fetchQueue();
        const interval = setInterval(fetchQueue, 10000); // هر 10 ثانیه به‌روزرسانی
        return () => clearInterval(interval);
    }, [doctorId]);

    const fetchQueue = async () => {
        try {
            const res = await fetch(`${API_URL}/api/waiting/queue/${doctorId}`);
            const data = await res.json();
            if (data.success) {
                setQueue(data.data.queue || []);
                setCurrent(data.data.current_patient);
            }
        } catch (error) {
            console.error('Error fetching queue:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', padding: '40px' }}>
                <Spin size="large" />
            </div>
        );
    }

    return (
        <div style={{
            maxWidth: '800px',
            margin: '0 auto',
            padding: '20px',
            background: '#f8fafc',
            minHeight: '100vh'
        }}>
            <Card style={{ borderRadius: '16px', marginBottom: '20px' }}>
                <div style={{ textAlign: 'center' }}>
                    <Title level={2}>📋 صف انتظار</Title>
                    <Text type="secondary">تعداد افراد در صف: {queue.length} نفر</Text>
                    {current && (
                        <div style={{
                            marginTop: '12px',
                            padding: '12px',
                            background: '#dbeafe',
                            borderRadius: '8px'
                        }}>
                            <Tag color="processing" icon={<ClockCircleOutlined />}>
                                در حال ویزیت
                            </Tag>
                            <Text strong style={{ fontSize: '18px' }}>
                                {current.patient_name}
                            </Text>
                            <Text> - شماره {current.queue_number}</Text>
                        </div>
                    )}
                </div>
            </Card>

            <List
                dataSource={queue}
                renderItem={(item, index) => (
                    <List.Item
                        style={{
                            padding: '16px 20px',
                            background: item.is_current ? '#dbeafe' : 'white',
                            borderRadius: '8px',
                            marginBottom: '8px',
                            border: item.is_current ? '2px solid #3b82f6' : '1px solid #e5e7eb'
                        }}
                    >
                        <div style={{ display: 'flex', alignItems: 'center', width: '100%' }}>
                            <div style={{
                                width: '40px',
                                height: '40px',
                                borderRadius: '50%',
                                background: item.is_current ? '#3b82f6' : '#e5e7eb',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: item.is_current ? 'white' : '#6b7280',
                                fontWeight: 'bold',
                                fontSize: '16px'
                            }}>
                                {item.queue_number}
                            </div>
                            <div style={{ flex: 1, marginLeft: '16px' }}>
                                <Text strong style={{ fontSize: '16px' }}>
                                    {item.patient_name}
                                </Text>
                            </div>
                            <div>
                                {item.is_completed ? (
                                    <Tag color="success" icon={<CheckCircleOutlined />}>
                                        انجام شده
                                    </Tag>
                                ) : item.is_current ? (
                                    <Tag color="processing" icon={<ClockCircleOutlined />}>
                                        در حال ویزیت
                                    </Tag>
                                ) : (
                                    <Tag color="default">در انتظار</Tag>
                                )}
                            </div>
                        </div>
                    </List.Item>
                )}
            />
        </div>
    );
}