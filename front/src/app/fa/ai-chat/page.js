// /src/app/fa/ai-chat/page.js
'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import {
    Card, Input, Button, Typography, Spin, Avatar, Space,
    Empty, App, Alert, Tag, Tooltip, Upload, Modal,
    Rate
} from 'antd';
import {
    SendOutlined, UserOutlined, RobotOutlined,
    UploadOutlined, FileOutlined,
    LikeOutlined, DislikeOutlined,
    CloseOutlined, ReloadOutlined, PaperClipOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import LoadingSpinner from '@/components/shared/LoadingSpinner';
import Breadcrumb from '@/components/shared/Breadcrumb';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function AiChatPage() {
    const router = useRouter();
    const { locale } = useLanguage();
    const { message: appMessage } = App.useApp();

    const [loading, setLoading] = useState(false);
    const [messages, setMessages] = useState([]);
    const [inputMessage, setInputMessage] = useState('');
    const [sessionId, setSessionId] = useState(null);
    const [isActive, setIsActive] = useState(false);
    const [sending, setSending] = useState(false);
    const [showFeedbackModal, setShowFeedbackModal] = useState(false);
    const [selectedMessageId, setSelectedMessageId] = useState(null);
    const [feedbackRating, setFeedbackRating] = useState(0);
    const [feedbackComment, setFeedbackComment] = useState('');
    const [uploading, setUploading] = useState(false);
    const [fileList, setFileList] = useState([]);
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const [showLoginPrompt, setShowLoginPrompt] = useState(false);

    // ✅ فقط یک ref برای container
    const messagesContainerRef = useRef(null);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    const getToken = () => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('token');
        }
        return null;
    };

    useEffect(() => {
        const token = getToken();
        setIsLoggedIn(!!token);

        if (token) {
            startChat();
        } else {
            setShowLoginPrompt(true);
            setLoading(false);
        }
    }, []);

    // ✅ اسکرول به پایین با ref
    useEffect(() => {
        if (messagesContainerRef.current) {
            messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight;
        }
    }, [messages]);

    const startChat = async () => {
        const token = getToken();
        if (!token) {
            setShowLoginPrompt(true);
            return;
        }

        setLoading(true);
        try {
            const res = await fetch(`${API_URL}/api/v1/chat/start`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ model: 'qwen3:14b' }),
            });

            const data = await res.json();

            if (data.success) {
                setSessionId(data.data.id);
                setIsActive(true);
                setMessages(data.data.messages || []);
                setShowLoginPrompt(false);
            } else {
                appMessage.error(data.message || 'خطا در شروع چت');
            }
        } catch (error) {
            console.error('Error starting chat:', error);
            appMessage.error('خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    };

    const sendMessage = async () => {
        if (!inputMessage.trim()) return;
        if (sending) return;

        const token = getToken();
        if (!token) {
            setShowLoginPrompt(true);
            return;
        }

        if (!sessionId) {
            appMessage.warning('لطفاً ابتدا چت را شروع کنید');
            return;
        }

        const userMessage = {
            id: Date.now(),
            role: 'user',
            content: inputMessage.trim(),
            created_at: new Date().toISOString()
        };

        setMessages(prev => [...prev, userMessage]);
        setInputMessage('');
        setSending(true);

        try {
            const res = await fetch(`${API_URL}/api/v1/chat/send`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: inputMessage.trim(),
                    session_token: sessionId
                }),
            });

            const data = await res.json();

            if (data.success) {
                const aiMessage = {
                    id: data.data.id || Date.now() + 1,
                    role: 'assistant',
                    content: data.data.response || data.data.message || 'پاسخی دریافت نشد',
                    created_at: new Date().toISOString()
                };
                setMessages(prev => [...prev, aiMessage]);
            } else {
                appMessage.error(data.message || 'خطا در دریافت پاسخ');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            appMessage.error('خطا در ارتباط با سرور');
        } finally {
            setSending(false);
        }
    };

    const goToLogin = () => {
        const currentPath = window.location.pathname;
        router.push(`/${locale}/login?redirect=${encodeURIComponent(currentPath)}`);
    };

    const closeChat = async () => {
        if (!sessionId) return;

        try {
            const token = getToken();
            const res = await fetch(`${API_URL}/api/v1/chat/close`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ session_token: sessionId }),
            });

            const data = await res.json();

            if (data.success) {
                setIsActive(false);
                appMessage.success('چت با موفقیت بسته شد');
                router.push(`/${locale}`);
            } else {
                appMessage.error(data.message || 'خطا در بستن چت');
            }
        } catch (error) {
            console.error('Error closing chat:', error);
            appMessage.error('خطا در ارتباط با سرور');
        }
    };

    const submitFeedback = async () => {
        if (!selectedMessageId) return;
        if (feedbackRating === 0) {
            appMessage.warning('لطفاً امتیاز دهید');
            return;
        }

        try {
            const token = getToken();
            const res = await fetch(`${API_URL}/api/v1/chat/feedback`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message_id: selectedMessageId,
                    rating: feedbackRating,
                    comment: feedbackComment,
                }),
            });

            const data = await res.json();

            if (data.success) {
                appMessage.success('بازخورد شما با موفقیت ثبت شد');
                setShowFeedbackModal(false);
                setFeedbackRating(0);
                setFeedbackComment('');
            } else {
                appMessage.error(data.message || 'خطا در ثبت بازخورد');
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            appMessage.error('خطا در ارتباط با سرور');
        }
    };

    const handleUpload = async (file) => {
        const token = getToken();
        if (!token) {
            setShowLoginPrompt(true);
            return false;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch(`${API_URL}/api/v1/chat/files/upload`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
                body: formData,
            });

            const data = await res.json();

            if (data.success) {
                appMessage.success('فایل با موفقیت آپلود شد');
                setFileList(prev => [...prev, data.data]);
                return true;
            } else {
                appMessage.error(data.message || 'خطا در آپلود فایل');
                return false;
            }
        } catch (error) {
            console.error('Error uploading file:', error);
            appMessage.error('خطا در ارتباط با سرور');
            return false;
        } finally {
            setUploading(false);
        }
    };

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
            <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)', paddingTop: '20px' }}>
                <div style={{ maxWidth: '900px', margin: '0 auto', padding: '0 20px 20px' }}>
                    <Breadcrumb />

                    <Title level={2} style={{ marginBottom: '4px' }}>🧠 هوش مصنوعی</Title>
                    <Text type="secondary">پرسش و پاسخ هوشمند پزشکی</Text>

                    <Card
                        style={{ borderRadius: '16px', marginTop: '16px' }}
                        title={
                            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                <RobotOutlined style={{ color: '#2563eb', fontSize: '24px' }} />
                                <div>
                                    <Text strong style={{ fontSize: '16px' }}>دکتر آنلاین</Text>
                                    <br />
                                    <Text type="secondary" style={{ fontSize: '12px' }}>
                                        {isActive ? (
                                            <Tag color="green" style={{ margin: 0 }}>🟢 آنلاین</Tag>
                                        ) : (
                                            <Tag color="red" style={{ margin: 0 }}>⛔ قطع</Tag>
                                        )}
                                    </Text>
                                </div>
                            </div>
                        }
                        extra={
                            <Space>
                                {isLoggedIn && (
                                    <>
                                        <Tooltip title="شروع مجدد">
                                            <Button icon={<ReloadOutlined />} onClick={startChat} />
                                        </Tooltip>
                                        <Tooltip title="بستن چت">
                                            <Button danger icon={<CloseOutlined />} onClick={closeChat} />
                                        </Tooltip>
                                    </>
                                )}
                            </Space>
                        }
                    >
                        {showLoginPrompt ? (
                            <div style={{ textAlign: 'center', padding: '40px 20px' }}>
                                <RobotOutlined style={{ fontSize: '64px', color: '#d9d9d9', marginBottom: '16px' }} />
                                <Title level={3}>برای استفاده از هوش مصنوعی نیاز به ورود دارید</Title>
                                <Text type="secondary" style={{ display: 'block', marginBottom: '24px' }}>
                                    لطفاً وارد حساب کاربری خود شوید تا بتوانید از دکتر آنلاین استفاده کنید.
                                </Text>
                                <Space>
                                    <Button
                                        type="primary"
                                        size="large"
                                        onClick={goToLogin}
                                    >
                                        ورود به حساب
                                    </Button>
                                    <Button
                                        size="large"
                                        onClick={() => router.push(`/${locale}`)}
                                    >
                                        بازگشت به صفحه اصلی
                                    </Button>
                                </Space>
                            </div>
                        ) : (
                            <>
                                <div
                                    ref={messagesContainerRef}
                                    style={{
                                        height: '500px',
                                        overflowY: 'auto',
                                        padding: '16px',
                                        background: '#f8fafc',
                                        borderRadius: '12px',
                                        marginBottom: '16px'
                                    }}
                                >
                                    {messages.length === 0 ? (
                                        <Empty
                                            image={<RobotOutlined style={{ fontSize: '48px', color: '#d9d9d9' }} />}
                                            description="چت را شروع کنید"
                                        >
                                            <Text type="secondary">
                                                سلام! من دکتر آنلاین هستم. هر سوال پزشکی دارید، بپرسید.
                                            </Text>
                                        </Empty>
                                    ) : (
                                        messages.map((msg) => (
                                            <div
                                                key={msg.id}
                                                style={{
                                                    display: 'flex',
                                                    justifyContent: msg.role === 'user' ? 'flex-end' : 'flex-start',
                                                    marginBottom: '16px'
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        maxWidth: '80%',
                                                        display: 'flex',
                                                        alignItems: 'flex-start',
                                                        gap: '8px',
                                                        flexDirection: msg.role === 'user' ? 'row-reverse' : 'row'
                                                    }}
                                                >
                                                    <Avatar
                                                        style={{
                                                            background: msg.role === 'user' ? '#2563eb' : '#7c3aed',
                                                            flexShrink: 0
                                                        }}
                                                        icon={msg.role === 'user' ? <UserOutlined /> : <RobotOutlined />}
                                                    />
                                                    <div
                                                        style={{
                                                            background: msg.role === 'user' ? '#2563eb' : 'white',
                                                            color: msg.role === 'user' ? 'white' : '#1e293b',
                                                            padding: '12px 16px',
                                                            borderRadius: msg.role === 'user' ? '16px 4px 16px 16px' : '4px 16px 16px 16px',
                                                            boxShadow: '0 2px 8px rgba(0,0,0,0.05)',
                                                            wordBreak: 'break-word'
                                                        }}
                                                    >
                                                        <Text style={{ color: 'inherit', whiteSpace: 'pre-wrap' }}>
                                                            {msg.content}
                                                        </Text>
                                                        <div style={{ marginTop: '4px' }}>
                                                            <Text type="secondary" style={{ fontSize: '10px', color: msg.role === 'user' ? 'rgba(255,255,255,0.7)' : '#94a3b8' }}>
                                                                {new Date(msg.created_at).toLocaleTimeString('fa-IR')}
                                                            </Text>
                                                            {msg.role === 'assistant' && isLoggedIn && (
                                                                <Space style={{ marginLeft: '8px' }} size="small">
                                                                    <Tooltip title="پاسخ مفید بود">
                                                                        <Button
                                                                            type="text"
                                                                            size="small"
                                                                            icon={<LikeOutlined />}
                                                                            onClick={() => {
                                                                                setSelectedMessageId(msg.id);
                                                                                setShowFeedbackModal(true);
                                                                            }}
                                                                        />
                                                                    </Tooltip>
                                                                    <Tooltip title="پاسخ مفید نبود">
                                                                        <Button
                                                                            type="text"
                                                                            size="small"
                                                                            icon={<DislikeOutlined />}
                                                                            onClick={() => {
                                                                                setSelectedMessageId(msg.id);
                                                                                setShowFeedbackModal(true);
                                                                            }}
                                                                        />
                                                                    </Tooltip>
                                                                </Space>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>

                                <div style={{ display: 'flex', gap: '8px', alignItems: 'flex-end' }}>
                                    <div style={{ flex: 1 }}>
                                        <TextArea
                                            value={inputMessage}
                                            onChange={(e) => setInputMessage(e.target.value)}
                                            onPressEnter={(e) => {
                                                if (!e.shiftKey) {
                                                    e.preventDefault();
                                                    sendMessage();
                                                }
                                            }}
                                            placeholder="پیام خود را بنویسید..."
                                            autoSize={{ minRows: 1, maxRows: 4 }}
                                            disabled={!isActive || sending || !isLoggedIn}
                                            style={{ borderRadius: '12px' }}
                                        />
                                    </div>
                                    <Space>
                                        <Upload
                                            customRequest={({ file, onSuccess, onError }) => {
                                                handleUpload(file).then((success) => {
                                                    if (success) {
                                                        onSuccess();
                                                    } else {
                                                        onError();
                                                    }
                                                });
                                            }}
                                            showUploadList={false}
                                            disabled={!isActive || sending || uploading || !isLoggedIn}
                                        >
                                            <Tooltip title="آپلود فایل">
                                                <Button
                                                    icon={uploading ? <Spin size="small" /> : <PaperClipOutlined />}
                                                    disabled={!isActive || sending || uploading || !isLoggedIn}
                                                />
                                            </Tooltip>
                                        </Upload>
                                        <Button
                                            type="primary"
                                            icon={<SendOutlined />}
                                            onClick={sendMessage}
                                            loading={sending}
                                            disabled={!inputMessage.trim() || !isActive || !isLoggedIn}
                                            style={{ borderRadius: '12px' }}
                                        />
                                    </Space>
                                </div>

                                {fileList.length > 0 && (
                                    <div style={{ marginTop: '12px', display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                                        {fileList.map((file) => (
                                            <Tag key={file.id} icon={<FileOutlined />}>
                                                {file.filename || file.name}
                                            </Tag>
                                        ))}
                                    </div>
                                )}

                                <Alert
                                    message="توجه پزشکی"
                                    description="پاسخ‌های ارائه شده توسط هوش مصنوعی صرفاً جنبه اطلاع‌رسانی دارند و جایگزین تشخیص پزشک نمی‌شوند. در صورت اورژانس با ۱۱۵ تماس بگیرید."
                                    type="warning"
                                    showIcon
                                    style={{ marginTop: '12px', borderRadius: '12px' }}
                                />
                            </>
                        )}
                    </Card>
                </div>
            </main>
            <Footer />

            <Modal
                title="بازخورد شما"
                open={showFeedbackModal}
                onCancel={() => setShowFeedbackModal(false)}
                onOk={submitFeedback}
                okText="ثبت بازخورد"
                cancelText="انصراف"
            >
                <div style={{ padding: '12px 0' }}>
                    <Text strong>این پاسخ چقدر مفید بود؟</Text>
                    <div style={{ marginTop: '8px' }}>
                        <Rate
                            value={feedbackRating}
                            onChange={setFeedbackRating}
                            style={{ fontSize: '28px' }}
                        />
                    </div>
                    <div style={{ marginTop: '16px' }}>
                        <Text strong>نظر شما (اختیاری)</Text>
                        <TextArea
                            value={feedbackComment}
                            onChange={(e) => setFeedbackComment(e.target.value)}
                            placeholder="نظر خود را بنویسید..."
                            rows={3}
                            style={{ marginTop: '8px', borderRadius: '12px' }}
                        />
                    </div>
                </div>
            </Modal>
        </>
    );
}