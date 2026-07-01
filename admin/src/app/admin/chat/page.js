'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card,
  Input,
  Button,
  Space,
  Typography,
  Avatar,
  List,
  Badge,
  message,
  Row,
  Col,
  Spin,
  Empty,
  Form,
  Tag,
  Tooltip,
  Divider,
} from 'antd';
import {
  SendOutlined,
  UserOutlined,
  SearchOutlined,
  PaperClipOutlined,
  SmileOutlined,
  PhoneOutlined,
  VideoCameraOutlined,
  MoreOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
} from '@ant-design/icons';
import { chatService, usersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import { useAuth } from '@/context/AuthContext';
import Loading from '@/components/admin/common/Loading';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function ChatPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { user: currentUser } = useAuth();
  const [loading, setLoading] = useState(false);
  const [conversations, setConversations] = useState([]);
  const [messages, setMessages] = useState([]);
  const [selectedUser, setSelectedUser] = useState(null);
  const [searchText, setSearchText] = useState('');
  const [messageText, setMessageText] = useState('');
  const [sending, setSending] = useState(false);
  const [users, setUsers] = useState([]);
  const [loadingMessages, setLoadingMessages] = useState(false);
  const messagesEndRef = useRef(null);
  const [isMobile, setIsMobile] = useState(false);
  const [showConversations, setShowConversations] = useState(true);

  // ===== تشخیص موبایل =====
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth < 768);
      if (window.innerWidth < 768) {
        setShowConversations(true);
      }
    };
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // ===== دریافت لیست کاربران =====
  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const response = await usersService.getAll({ per_page: 100 });
        setUsers(response.data || []);
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    };
    fetchUsers();
  }, []);

  // ===== دریافت مکالمات =====
  const fetchConversations = async () => {
    setLoading(true);
    try {
      const response = await chatService.getConversations();
      setConversations(response.data || []);
    } catch (error) {
      console.error('Error fetching conversations:', error);
      message.error(t('fetch_error', 'خطا در دریافت مکالمات'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchConversations();
    // WebSocket connection will be handled here
  }, []);

  // ===== دریافت پیام‌ها =====
  const fetchMessages = async (userId) => {
    if (!userId) return;
    
    setLoadingMessages(true);
    try {
      const response = await chatService.getMessages(userId);
      setMessages(response.data || []);
      // Mark as read
      await chatService.markAsRead(userId);
    } catch (error) {
      console.error('Error fetching messages:', error);
      message.error(t('fetch_error', 'خطا در دریافت پیام‌ها'));
    } finally {
      setLoadingMessages(false);
    }
  };

  // ===== انتخاب کاربر =====
  const handleSelectUser = (user) => {
    setSelectedUser(user);
    setShowConversations(false);
    fetchMessages(user.id);
  };

  // ===== ارسال پیام =====
  const handleSendMessage = async () => {
    if (!messageText.trim() || !selectedUser) return;

    setSending(true);
    try {
      await chatService.sendMessage(selectedUser.id, messageText);
      setMessageText('');
      // Refresh messages
      await fetchMessages(selectedUser.id);
      // Update conversation list
      await fetchConversations();
    } catch (error) {
      console.error('Error sending message:', error);
      message.error(t('send_error', 'خطا در ارسال پیام'));
    } finally {
      setSending(false);
    }
  };

  // ===== اسکرول به پایین =====
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  // ===== دریافت وضعیت کاربر =====
  const getUserStatus = (userId) => {
    // This will be handled by WebSocket
    return 'online';
  };

  // ===== فرمت زمان =====
  const formatTime = (date) => {
    if (!date) return '';
    const now = dayjs();
    const msgDate = dayjs(date);
    if (now.diff(msgDate, 'day') === 0) {
      return msgDate.format('HH:mm');
    } else if (now.diff(msgDate, 'day') === 1) {
      return t('yesterday', 'دیروز');
    } else {
      return msgDate.format('jYYYY/jMM/jDD');
    }
  };

  // ===== کامپوننت مکالمه =====
  const ConversationItem = ({ conversation }) => {
    const otherUser = conversation.other_user;
    const isActive = selectedUser?.id === otherUser?.id;

    return (
      <div
        style={{
          padding: '12px 16px',
          cursor: 'pointer',
          background: isActive ? '#dbeafe' : 'transparent',
          borderRadius: 8,
          transition: 'all 0.2s',
          display: 'flex',
          alignItems: 'center',
          gap: 12,
        }}
        onClick={() => handleSelectUser(otherUser)}
        onMouseEnter={(e) => {
          if (!isActive) {
            e.currentTarget.style.background = '#f1f5f9';
          }
        }}
        onMouseLeave={(e) => {
          if (!isActive) {
            e.currentTarget.style.background = 'transparent';
          }
        }}
      >
        <Badge
          dot
          color={getUserStatus(otherUser?.id) === 'online' ? '#10b981' : '#94a3b8'}
          offset={[-4, 4]}
        >
          <Avatar
            src={otherUser?.avatar}
            icon={<UserOutlined />}
            style={{ backgroundColor: '#2563eb' }}
          />
        </Badge>
        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text strong style={{ fontSize: 14 }}>
              {otherUser?.name || '—'}
            </Text>
            <Text type="secondary" style={{ fontSize: 11 }}>
              {conversation.last_message_at ? formatTime(conversation.last_message_at) : ''}
            </Text>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text
              type="secondary"
              style={{
                fontSize: 13,
                overflow: 'hidden',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
                maxWidth: 150,
              }}
            >
              {conversation.last_message || t('no_messages', 'بدون پیام')}
            </Text>
            {conversation.unread_count > 0 && (
              <Badge count={conversation.unread_count} size="small" />
            )}
          </div>
        </div>
      </div>
    );
  };

  // ===== کامپوننت پیام =====
  const MessageItem = ({ message }) => {
    const isOwn = message.sender_id === currentUser?.id;

    return (
      <div
        style={{
          display: 'flex',
          justifyContent: isOwn ? 'flex-end' : 'flex-start',
          marginBottom: 12,
        }}
      >
        {!isOwn && (
          <Avatar
            src={message.sender?.avatar}
            icon={<UserOutlined />}
            size={32}
            style={{ marginLeft: 8, backgroundColor: '#2563eb' }}
          />
        )}
        <div
          style={{
            maxWidth: '70%',
            padding: '10px 14px',
            borderRadius: 12,
            background: isOwn ? '#2563eb' : '#f1f5f9',
            color: isOwn ? '#ffffff' : '#1e293b',
            wordWrap: 'break-word',
          }}
        >
          <div style={{ fontSize: 14 }}>{message.message}</div>
          <div
            style={{
              fontSize: 10,
              marginTop: 4,
              color: isOwn ? 'rgba(255,255,255,0.7)' : '#94a3b8',
              display: 'flex',
              alignItems: 'center',
              gap: 4,
            }}
          >
            {formatTime(message.created_at)}
            {isOwn && message.read_at && (
              <CheckCircleOutlined style={{ fontSize: 10 }} />
            )}
            {isOwn && !message.read_at && (
              <ClockCircleOutlined style={{ fontSize: 10 }} />
            )}
          </div>
        </div>
        {isOwn && (
          <Avatar
            src={currentUser?.avatar}
            icon={<UserOutlined />}
            size={32}
            style={{ marginRight: 8, backgroundColor: '#10b981' }}
          />
        )}
      </div>
    );
  };

  // ===== فیلتر مکالمات =====
  const filteredConversations = conversations.filter((conv) => {
    if (!searchText) return true;
    return conv.other_user?.name?.toLowerCase().includes(searchText.toLowerCase());
  });

  if (loading) {
    return <Loading text={t('loading_chat', 'در حال بارگذاری مکالمات...')} />;
  }

  return (
    <div style={{ height: 'calc(100vh - 120px)' }}>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 16,
        }}
      >
        <div>
          <Title level={2} style={{ margin: 0 }}>
            {t('chat', 'پیام‌ها')}
          </Title>
          <Text type="secondary">
            {t('chat_subtitle', 'ارتباط با بیماران و پزشکان')}
          </Text>
        </div>
      </div>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
          height: 'calc(100% - 60px)',
          overflow: 'hidden',
        }}
        bodyStyle={{ padding: 0, height: '100%' }}
      >
        <Row style={{ height: '100%' }}>
          {/* ===== لیست مکالمات ===== */}
          <Col
            xs={showConversations ? 24 : 0}
            sm={8}
            md={8}
            lg={8}
            style={{
              height: '100%',
              borderLeft: '1px solid #e8e8f0',
              display: showConversations ? 'flex' : 'none',
              flexDirection: 'column',
            }}
          >
            <div style={{ padding: 16, borderBottom: '1px solid #e8e8f0' }}>
              <Input
                placeholder={t('search_conversations', 'جستجوی مکالمات...')}
                prefix={<SearchOutlined />}
                value={searchText}
                onChange={(e) => setSearchText(e.target.value)}
                allowClear
              />
            </div>

            <div style={{ flex: 1, overflowY: 'auto', padding: '8px 0' }}>
              {filteredConversations.length === 0 ? (
                <Empty
                  image={Empty.PRESENTED_IMAGE_SIMPLE}
                  description={t('no_conversations', 'هیچ مکالمه‌ای وجود ندارد')}
                  style={{ marginTop: 40 }}
                />
              ) : (
                filteredConversations.map((conv) => (
                  <ConversationItem key={conv.other_user?.id} conversation={conv} />
                ))
              )}
            </div>
          </Col>

          {/* ===== پنجره چت ===== */}
          <Col
            xs={showConversations ? 0 : 24}
            sm={16}
            md={16}
            lg={16}
            style={{
              height: '100%',
              display: 'flex',
              flexDirection: 'column',
            }}
          >
            {selectedUser ? (
              <>
                {/* ===== هدر چت ===== */}
                <div
                  style={{
                    padding: '12px 16px',
                    borderBottom: '1px solid #e8e8f0',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    background: '#f8fafc',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                    {isMobile && (
                      <Button
                        type="text"
                        icon={<SearchOutlined />}
                        onClick={() => setShowConversations(true)}
                      />
                    )}
                    <Badge
                      dot
                      color={getUserStatus(selectedUser.id) === 'online' ? '#10b981' : '#94a3b8'}
                      offset={[-4, 4]}
                    >
                      <Avatar
                        src={selectedUser.avatar}
                        icon={<UserOutlined />}
                        style={{ backgroundColor: '#2563eb' }}
                      />
                    </Badge>
                    <div>
                      <div style={{ fontWeight: 600 }}>{selectedUser.name}</div>
                      <div style={{ fontSize: 12, color: '#64748b' }}>
                        {getUserStatus(selectedUser.id) === 'online' 
                          ? t('online', 'آنلاین') 
                          : t('offline', 'آفلاین')}
                      </div>
                    </div>
                  </div>
                  <Space>
                    <Tooltip title={t('call', 'تماس صوتی')}>
                      <Button type="text" icon={<PhoneOutlined />} />
                    </Tooltip>
                    <Tooltip title={t('video_call', 'تماس تصویری')}>
                      <Button type="text" icon={<VideoCameraOutlined />} />
                    </Tooltip>
                    <Tooltip title={t('more', 'بیشتر')}>
                      <Button type="text" icon={<MoreOutlined />} />
                    </Tooltip>
                  </Space>
                </div>

                {/* ===== پیام‌ها ===== */}
                <div
                  style={{
                    flex: 1,
                    overflowY: 'auto',
                    padding: '16px 20px',
                    background: '#ffffff',
                  }}
                >
                  {loadingMessages ? (
                    <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}>
                      <Spin />
                    </div>
                  ) : messages.length === 0 ? (
                    <Empty
                      image={Empty.PRESENTED_IMAGE_SIMPLE}
                      description={t('no_messages_yet', 'هنوز پیامی ارسال نشده است')}
                      style={{ marginTop: 60 }}
                    />
                  ) : (
                    <>
                      {messages.map((msg) => (
                        <MessageItem key={msg.id} message={msg} />
                      ))}
                      <div ref={messagesEndRef} />
                    </>
                  )}
                </div>

                {/* ===== ورودی پیام ===== */}
                <div
                  style={{
                    padding: '12px 16px',
                    borderTop: '1px solid #e8e8f0',
                    background: '#ffffff',
                  }}
                >
                  <div style={{ display: 'flex', gap: 8 }}>
                    <Button type="text" icon={<PaperClipOutlined />} />
                    <Button type="text" icon={<SmileOutlined />} />
                    <TextArea
                      value={messageText}
                      onChange={(e) => setMessageText(e.target.value)}
                      placeholder={t('type_message', 'پیام خود را بنویسید...')}
                      autoSize={{ minRows: 1, maxRows: 4 }}
                      onPressEnter={(e) => {
                        if (!e.shiftKey) {
                          e.preventDefault();
                          handleSendMessage();
                        }
                      }}
                      style={{ borderRadius: 8 }}
                    />
                    <Button
                      type="primary"
                      icon={<SendOutlined />}
                      onClick={handleSendMessage}
                      loading={sending}
                      disabled={!messageText.trim()}
                      style={{
                        background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                        border: 'none',
                      }}
                    />
                  </div>
                </div>
              </>
            ) : (
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  height: '100%',
                  flexDirection: 'column',
                  color: '#94a3b8',
                }}
              >
                <UserOutlined style={{ fontSize: 48, marginBottom: 16 }} />
                <Text type="secondary">
                  {t('select_conversation', 'یک مکالمه را انتخاب کنید')}
                </Text>
              </div>
            )}
          </Col>
        </Row>
      </Card>
    </div>
  );
}
