'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Spin,
  Tag,
  Badge,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  BellOutlined,
  EyeOutlined,
} from '@ant-design/icons';
import { notificationsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditNotificationPage() {
  const router = useRouter();
  const params = useParams();
  const notificationId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [notification, setNotification] = useState(null);

  useEffect(() => {
    const fetchNotification = async () => {
      try {
        const response = await notificationsService.getById(notificationId);
        setNotification(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching notification:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (notificationId) {
      fetchNotification();
    }
  }, [notificationId, form, t]);

  const handleMarkAsRead = async () => {
    setLoading(true);
    try {
      await notificationsService.markAsRead(notificationId);
      message.success(t('marked_as_read', 'اعلان به عنوان خوانده شده علامت‌گذاری شد'));
      router.push('/admin/notifications');
    } catch (error) {
      console.error('Error marking as read:', error);
      message.error(t('error', 'خطا در علامت‌گذاری'));
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

  const priorityMap = {
    low: { color: 'default', label: 'معمولی' },
    medium: { color: 'blue', label: 'متوسط' },
    high: { color: 'orange', label: 'بالا' },
    urgent: { color: 'red', label: 'فوری' },
  };

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
                {t('notification_details', 'جزئیات اعلان')}
              </Title>
              <Text type="secondary">
                {t('notification_details_subtitle', 'مشاهده جزئیات اعلان')}
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
        <Form
          form={form}
          layout="vertical"
          size="large"
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Form.Item
                name="title"
                label={t('title', 'عنوان')}
              >
                <Input
                  prefix={<BellOutlined />}
                  disabled
                />
              </Form.Item>

              <Form.Item
                name="message"
                label={t('message', 'متن')}
              >
                <TextArea
                  rows={4}
                  disabled
                />
              </Form.Item>

              <Form.Item
                name="priority"
                label={t('priority', 'اولویت')}
              >
                <Select
                  disabled
                  options={[
                    { value: 'low', label: t('low', 'معمولی') },
                    { value: 'medium', label: t('medium', 'متوسط') },
                    { value: 'high', label: t('high', 'بالا') },
                    { value: 'urgent', label: t('urgent', 'فوری') },
                  ]}
                />
              </Form.Item>
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
                  <BellOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('notification_info', 'اطلاعات اعلان')}</Text>
                  </div>
                </div>

                <Divider />

                <div>
                  <Text type="secondary">{t('status', 'وضعیت')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    <Badge
                      status={notification?.read_at ? 'success' : 'warning'}
                      text={notification?.read_at ? t('read', 'خوانده شده') : t('unread', 'خوانده نشده')}
                    />
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('priority', 'اولویت')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    <Tag color={priorityMap[notification?.priority]?.color || 'default'}>
                      {priorityMap[notification?.priority]?.label || notification?.priority}
                    </Tag>
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('date', 'تاریخ')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {notification?.created_at ? dayjs(notification.created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                  </div>
                </div>

                <Divider />

                <div style={{ display: 'flex', gap: 12, justifyContent: 'center' }}>
                  {!notification?.read_at && (
                    <Button
                      type="primary"
                      icon={<EyeOutlined />}
                      onClick={handleMarkAsRead}
                      loading={loading}
                      style={{
                        background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                        border: 'none',
                      }}
                    >
                      {t('mark_as_read', 'علامت‌گذاری به عنوان خوانده شده')}
                    </Button>
                  )}
                  <Button onClick={handleBack}>
                    {t('back', 'بازگشت')}
                  </Button>
                </div>
              </Card>
            </Col>
          </Row>
        </Form>
      </Card>
    </div>
  );
}
