// src/components/QueueStatus/QueueStatus.jsx
'use client';

import { useState, useEffect } from 'react';
import { Card, Tag, Typography, Spin, Progress } from 'antd';
import { UserOutlined, ClockCircleOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

export default function QueueStatus({ appointmentId }) {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
    const getToken = () => localStorage.getItem('token');

    useEffect(() => {
        fetchStatus();
        const interval = setInterval(fetchStatus, 15000); // هر 15 ثانیه
        return () => clearInterval(interval);
    }, [appointmentId]);

    const fetchStatus = async () => {
        try {
            const token = getToken();
            const res = await fetch(`${API_URL}/api/waiting/status/${appointmentId}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            if (data.success) {
                setStatus(data.data);
            }
        } catch (error) {
            console.error('Error fetching status:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Spin />;

    if (!status || !status.in_queue) {
        return (
            <Card style={{ borderRadius: '12px', textAlign: 'center' }}>
                <Text>شما در صف نیستید. لطفاً به مطب مراجعه کنید.</Text>
            </Card>
        );
    }

    const progress = status.total_waiting > 0
        ? ((status.total_waiting - status.people_ahead) / status.total_waiting) * 100
        : 0;

    return (
        <Card style={{ borderRadius: '12px' }}>
            <div style={{ textAlign: 'center' }}>
                <Title level={3}>🔄 وضعیت صف</Title>

                <div style={{ margin: '20px 0' }}>
                    <div style={{ fontSize: '48px', fontWeight: 'bold', color: '#2563eb' }}>
                        {status.queue_number}
                    </div>
                    <Text type="secondary">شماره شما در صف</Text>
                </div>

                <div style={{ margin: '20px 0' }}>
                    <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                        {status.people_ahead === 0 ? (
                            <Tag color="success" style={{ fontSize: '18px', padding: '8px 16px' }}>
                                ✅ نوبت شماست!
                            </Tag>
                        ) : (
                            <>
                                <Text>{status.people_ahead} نفر جلوی شما</Text>
                                <br />
                                <Text type="secondary">
                                    <ClockCircleOutlined /> زمان تقریبی: {status.estimated_wait_text}
                                </Text>
                            </>
                        )}
                    </div>
                </div>

                <Progress
                    percent={Math.round(progress)}
                    status={status.people_ahead === 0 ? 'success' : 'active'}
                    strokeColor="#3b82f6"
                />

                <div style={{ marginTop: '16px' }}>
                    <Text type="secondary">پزشک: {status.doctor_name}</Text>
                    <br />
                    <Text type="secondary">ساعت نوبت: {status.appointment_time}</Text>
                </div>
            </div>
        </Card>
    );
}