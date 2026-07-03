'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Spin,
  Avatar,
  Timeline,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UserOutlined,
  SendOutlined,
} from '@ant-design/icons';
import { chatService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditChatPage() {
  const router = useRouter();
  const params = useParams();
  const chatId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [messages, setMessages] = useState([]);
  const [chat, setChat] = useState(null);

  useEffect(() => {
    const fetchMessages = async () => {
      try {
        const response = await chatService.getMessages(chatId);
        setMessages(response.data || []);
        setChat(response.data?.[0]);
      } catch (error) {
        console.error('Error fetching messages:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (chatId) {
      fetchMessages();
    }
  }, [chatId, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await chatService.sendMessage(chatId, values.message);
      message.success(t('reply_sent', 'پاسخ با موفقیت ارسال شد'));
      router.push('/admin/chat');
    } catch (error) {
      console.error('Error sending reply:', error);
      message.error(t('send_error', 'خطا در ارسال پاسخ'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  if (fetchLoading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: 400 }}>
        <Spin size="large" />
      </div>
    );
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
          <Space>
            <Button
              type="text"
              icon={<ArrowLeftOutlined />}
              onClick={handleBack}
              style={{ fontSize: 18 }}
            />
            <div>
              <Title level={2} style={{ margin: 0 }}>
                {t('chat_details', 'جزئیات مکالمه')}
              </Title>
              <Text type="secondary">
                {t('chat_details_subtitle', 'مشاهده و پاسخ به پیام‌ها')}
              </Text>
            </div>
          </Space>
        </div>
      </div>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Row gutter={[24, 0]}>
          <Col xs={24} lg={16}>
            <div style={{ maxHeight: 400, overflowY: 'auto', padding: '8px 0' }}>
              {messages.length === 0 ? (
                <div style={{ textAlign: 'center', padding: 40, color: '#94a3b8' }}>
                  {t('no_messages_yet', 'هنوز پیامی ارسال نشده است')}
                </div>
              ) : (
                <Timeline>
                  {messages.map((msg) => (
                    <Timeline.Item
                      key={msg.id}
                      color={msg.sender_id === chat?.sender_id ? 'blue' : 'green'}
                    >
                      <div>
                        <Space>
                          <Avatar
                            icon={<UserOutlined />}
                            size="small"
                            style={{ backgroundColor: msg.sender_id === chat?.sender_id ? '#2563eb' : '#10b981' }}
                          />
                          <Text strong>{msg.sender?.name || '—'}</Text>
                          <Text type="secondary" style={{ fontSize: 12 }}>
                            {msg.created_at ? dayjs(msg.created_at).format('jYYYY/jMM/jDD HH:mm') : ''}
                          </Text>
                        </Space>
                        <div style={{ marginTop: 4, padding: '8px 12px', background: '#f8fafc', borderRadius: 8 }}>
                          {msg.message}
                        </div>
                      </div>
                    </Timeline.Item>
                  ))}
                </Timeline>
              )}
            </div>

            <Divider />

            <Form
              form={form}
              layout="vertical"
              onFinish={handleSubmit}
              size="large"
            >
              <Form.Item
                name="message"
                label={t('your_reply', 'پاسخ شما')}
                rules={[{ required: true, message: t('required', 'لطفاً پاسخ را وارد کنید') }]}
              >
                <TextArea
                  rows={3}
                  placeholder={t('reply_placeholder', 'پاسخ خود را بنویسید...')}
                />
              </Form.Item>

              <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                <Button onClick={handleBack} size="large">
                  {t('cancel', 'انصراف')}
                </Button>
                <Button
                  type="primary"
                  htmlType="submit"
                  loading={loading}
                  icon={<SendOutlined />}
                  size="large"
                  style={{
                    background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                    border: 'none',
                  }}
                >
                  {t('send_reply', 'ارسال پاسخ')}
                </Button>
              </div>
            </Form>
          </Col>

          <Col xs={24} lg={8}>
            <Card
              style={{
                borderRadius: 12,
                borderColor: '#e8e8f0',
                background: '#f8fafc',
              }}
            >
              <div style={{ textAlign: 'center', padding: '16px 0' }}>
                <UserOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                <div style={{ marginTop: 8 }}>
                  <Text type="secondary">{t('conversation_info', 'اطلاعات مکالمه')}</Text>
                </div>
              </div>

              <Divider />

              <div>
                <Text type="secondary">{t('total_messages', 'تعداد پیام‌ها')}</Text>
                <div style={{ fontWeight: 500, marginTop: 4 }}>{messages.length}</div>
              </div>

              <div style={{ marginTop: 12 }}>
                <Text type="secondary">{t('last_message', 'آخرین پیام')}</Text>
                <div style={{ fontWeight: 500, marginTop: 4 }}>
                  {messages.length > 0 ? dayjs(messages[messages.length - 1].created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                </div>
              </div>

              <Divider />

              <div style={{ textAlign: 'center' }}>
                <Text type="secondary" style={{ fontSize: 12 }}>
                  {t('chat_help', 'پاسخ شما به‌صورت لحظه‌ای ارسال می‌شود')}
                </Text>
              </div>
            </Card>
          </Col>
        </Row>
      </Card>
    </div>
  );
}
