'use client';

import { useState, useRef, useEffect } from 'react';
import {
    Button,
    Input,
    Avatar,
    Spin,
    message,
    Upload,
    Space,
    Tag,
    Typography,
} from 'antd';
import {
    SendOutlined,
    RobotOutlined,
    UserOutlined,
    UploadOutlined,
    PaperClipOutlined,
    CloseOutlined,
    FileImageOutlined,
    WarningOutlined,
} from '@ant-design/icons';
import axios from 'axios';

const { TextArea } = Input;
const { Text } = Typography;

export default function ChatWidget() {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [sessionToken, setSessionToken] = useState(null);
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);
    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    useEffect(() => {
        startChatSession();
    }, []);

    const startChatSession = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.post(
                `${API_URL}/api/v1/chat/start`,
                {},
                {
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                }
            );
            if (response.data.success) {
                setSessionToken(response.data.data.session.session_token);
                setMessages([
                    {
                        role: 'assistant',
                        content: 'سلام! من "دکتر آنلاین" هستم. چطور می‌توانم به شما کمک کنم؟',
                        timestamp: new Date(),
                    },
                ]);
            }
        } catch (error) {
            message.error('خطا در شروع چت');
            setMessages([
                {
                    role: 'assistant',
                    content: 'سلام! من "دکتر آنلاین" هستم. لطفاً سوال پزشکی خود را بپرسید.',
                    timestamp: new Date(),
                },
            ]);
        }
    };

    const sendMessage = async () => {
        if (!input.trim() && files.length === 0) return;

        const userMessage = {
            role: 'user',
            content: input.trim(),
            timestamp: new Date(),
        };
        setMessages(prev => [...prev, userMessage]);
        const messageContent = input.trim();
        setInput('');
        setLoading(true);

        try {
            const token = localStorage.getItem('token');
            const formData = new FormData();
            formData.append('message', messageContent);
            if (sessionToken) {
                formData.append('session_token', sessionToken);
            }

            files.forEach(file => {
                formData.append('files[]', file);
            });

            const response = await axios.post(
                `${API_URL}/api/v1/chat/send`,
                formData,
                {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );

            if (response.data.success) {
                setMessages(prev => [
                    ...prev,
                    {
                        role: 'assistant',
                        content: response.data.data.message.content,
                        timestamp: new Date(),
                        suggestions: response.data.data.suggestions || [],
                        analysis: response.data.data.analysis || null,
                    },
                ]);
                setFiles([]);
            } else {
                message.error('خطا در دریافت پاسخ');
            }
        } catch (error) {
            if (error.response?.data?.is_emergency) {
                message.error('⚠️ وضعیت اورژانسی تشخیص داده شد! لطفاً با 115 تماس بگیرید.');
                setMessages(prev => [
                    ...prev,
                    {
                        role: 'assistant',
                        content: error.response.data.message,
                        timestamp: new Date(),
                        isEmergency: true,
                    },
                ]);
            } else if (error.response?.data?.is_medical === false) {
                message.warning('لطفاً سوالات پزشکی خود را بپرسید.');
                setMessages(prev => [
                    ...prev,
                    {
                        role: 'assistant',
                        content: error.response.data.message || 'من فقط به سوالات پزشکی پاسخ می‌دهم.',
                        timestamp: new Date(),
                    },
                ]);
            } else {
                message.error(error.response?.data?.message || 'خطا در ارتباط با سرور');
                setMessages(prev => [
                    ...prev,
                    {
                        role: 'assistant',
                        content: 'متاسفانه خطایی رخ داد. لطفاً دوباره تلاش کنید.',
                        timestamp: new Date(),
                    },
                ]);
            }
        } finally {
            setLoading(false);
        }
    };

    const handleFileUpload = async (file) => {
        setUploading(true);
        try {
            const token = localStorage.getItem('token');
            const formData = new FormData();
            formData.append('file', file);
            if (sessionToken) {
                formData.append('session_token', sessionToken);
            }

            const response = await axios.post(
                `${API_URL}/api/v1/chat/files/upload`,
                formData,
                {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );

            if (response.data.success) {
                setFiles(prev => [...prev, file]);
                message.success('فایل با موفقیت آپلود شد');
            }
        } catch (error) {
            message.error('خطا در آپلود فایل');
        } finally {
            setUploading(false);
        }
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    const formatTime = (date) => {
        return new Date(date).toLocaleTimeString('fa-IR', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <div className="flex flex-col h-[550px]">
            {/* Header */}
            <div className="flex items-center justify-between p-4 border-b bg-gradient-to-r from-blue-50 to-blue-100 rounded-t-xl">
                <div className="flex items-center gap-3">
                    <Avatar
                        icon={<RobotOutlined />}
                        style={{ backgroundColor: '#1890ff' }}
                        size="large"
                    />
                    <div>
                        <div className="font-bold text-lg">دکتر آنلاین</div>
                        <div className="text-xs text-gray-500 flex items-center gap-1">
                            <span className="w-2 h-2 bg-green-500 rounded-full inline-block"></span>
                            پاسخ‌دهی هوشمند
                        </div>
                    </div>
                </div>
                <Tag color="green" className="text-xs">
                    🟢 آنلاین
                </Tag>
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                {messages.map((msg, index) => (
                    <div
                        key={index}
                        className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                    >
                        <div
                            className={`max-w-[85%] ${
                                msg.role === 'user'
                                    ? 'bg-blue-500 text-white rounded-2xl rounded-tr-none'
                                    : msg.isEmergency
                                        ? 'bg-red-50 border border-red-200 text-red-700 rounded-2xl rounded-tl-none'
                                        : 'bg-white text-gray-800 rounded-2xl rounded-tl-none shadow-sm'
                            } p-3`}
                        >
                            <div className="whitespace-pre-wrap text-sm">{msg.content}</div>
                            {msg.suggestions && msg.suggestions.length > 0 && (
                                <div className="mt-2 pt-2 border-t border-gray-200">
                                    <div className="text-xs font-medium text-gray-500 mb-1">💡 پیشنهادات:</div>
                                    {msg.suggestions.map((suggestion, i) => (
                                        <div key={i} className="text-sm">• {suggestion}</div>
                                    ))}
                                </div>
                            )}
                            {msg.isEmergency && (
                                <div className="mt-2 pt-2 border-t border-red-200">
                                    <Text type="danger" strong className="text-sm">
                                        <WarningOutlined /> لطفاً فوراً با 115 تماس بگیرید
                                    </Text>
                                </div>
                            )}
                            <div className={`text-xs mt-1 ${msg.role === 'user' ? 'text-blue-200' : 'text-gray-400'}`}>
                                {formatTime(msg.timestamp)}
                            </div>
                        </div>
                    </div>
                ))}
                {loading && (
                    <div className="flex justify-start">
                        <div className="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm">
                            <Spin size="small" />
                            <span className="mr-2 text-sm text-gray-400">در حال تایپ...</span>
                        </div>
                    </div>
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* File Upload Preview */}
            {files.length > 0 && (
                <div className="flex flex-wrap gap-2 p-2 border-t bg-gray-50">
                    {files.map((file, index) => (
                        <div
                            key={index}
                            className="flex items-center gap-2 bg-white px-3 py-1 rounded-lg shadow-sm border"
                        >
                            <FileImageOutlined />
                            <span className="text-sm truncate max-w-32">{file.name}</span>
                            <Button
                                type="text"
                                size="small"
                                icon={<CloseOutlined />}
                                onClick={() => setFiles(files.filter((_, i) => i !== index))}
                            />
                        </div>
                    ))}
                </div>
            )}

            {/* Input Area */}
            <div className="flex items-end gap-2 p-3 border-t bg-white rounded-b-xl">
                <Button
                    type="text"
                    icon={<PaperClipOutlined />}
                    onClick={() => fileInputRef.current?.click()}
                    loading={uploading}
                    className="flex-shrink-0"
                >
                    <input
                        ref={fileInputRef}
                        type="file"
                        className="hidden"
                        onChange={(e) => {
                            if (e.target.files?.[0]) {
                                handleFileUpload(e.target.files[0]);
                            }
                            e.target.value = '';
                        }}
                        accept="image/*,.pdf,.doc,.docx"
                    />
                </Button>
                <TextArea
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    onKeyDown={handleKeyPress}
                    placeholder="سوال پزشکی خود را بپرسید..."
                    autoSize={{ minRows: 1, maxRows: 3 }}
                    disabled={loading}
                    className="flex-1"
                />
                <Button
                    type="primary"
                    icon={<SendOutlined />}
                    onClick={sendMessage}
                    loading={loading}
                    disabled={!input.trim() && files.length === 0}
                    className="flex-shrink-0"
                />
            </div>
        </div>
    );
}
