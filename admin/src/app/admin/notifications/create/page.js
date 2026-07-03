'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
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
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  SendOutlined,
  BellOutlined,
} from '@ant-design/icons';
import { notificationsService, usersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateNotificationPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [users, setUsers] = useState([]);

  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const response = await usersService.getAll({ per_page: 100 });
        setUsers(response.data || []);
      } catch (error) {
        console.error('Error fetching users:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست کاربران'));
      }
    };
    fetchUsers();
  }, [t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      if (values.send_to === 'all') {
        await notificationsService.sendToAll(values.title, values.message, values.priority);
      } else if (values.send_to === 'doctors') {
        await notificationsService.sendToDoctors(values.title, values.message, values.priority);
      } else if (values.send_to === 'patients') {
        await notificationsService.sendToPatients(values.title, values.message, values.priority);
      } else if (values.send_to === 'user' && values.user_id) {
        await notificationsService.sendToUser(values.user_id, values.title, values.message, values.priority);
      }
      message.success(t('notification_sent', 'اعلان با موفقیت ارسال شد'));
      router.push('/admin/notifications');
    } catch (error) {
      console.error('Error sending notification:', error);
      message.error(t('send_error', 'خطا در ارسال اعلان'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
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
                {t('new_notification', 'اعلان جدید')}
              </Title>
              <Text type="secondary">
                {t('create_notification_subtitle', 'ارسال اعلان جدید')}
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
          onFinish={handleSubmit}
          size="large"
          initialValues={{
            send_to: 'all',
            priority: 'medium',
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Form.Item
                name="send_to"
                label={t('send_to', 'ارسال به')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Select
                  options={[
                    { value: 'all', label: t('all_users', 'همه کاربران') },
                    { value: 'doctors', label: t('all_doctors', 'همه پزشکان') },
                    { value: 'patients', label: t('all_patients', 'همه بیماران') },
                    { value: 'user', label: t('specific_user', 'کاربر خاص') },
                  ]}
                />
              </Form.Item>

              <Form.Item
                noStyle
                shouldUpdate={(prevValues, currentValues) => prevValues.send_to !== currentValues.send_to}
              >
                {({ getFieldValue }) => {
                  const sendTo = getFieldValue('send_to');
                  if (sendTo === 'user') {
                    return (
                      <Form.Item
                        name="user_id"
                        label={t('select_user', 'انتخاب کاربر')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                      >
                        <Select
                          placeholder={t('select_user', 'انتخاب کاربر...')}
                          showSearch
                          optionFilterProp="children"
                          options={users.map((u) => ({
                            value: u.id,
                            label: u.name,
                          }))}
                        />
                      </Form.Item>
                    );
                  }
                  return null;
                }}
              </Form.Item>

              <Form.Item
                name="title"
                label={t('title', 'عنوان')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  prefix={<BellOutlined />}
                  placeholder={t('title_placeholder', 'عنوان اعلان...')}
                />
              </Form.Item>

              <Form.Item
                name="message"
                label={t('message', 'متن')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <TextArea
                  rows={4}
                  placeholder={t('message_placeholder', 'متن اعلان...')}
                />
              </Form.Item>

              <Form.Item
                name="priority"
                label={t('priority', 'اولویت')}
              >
                <Select
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

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('notification_help', 'اعلان به‌صورت لحظه‌ای برای گیرنده ارسال می‌شود')}
                  </Text>
                </div>
              </Card>
            </Col>
          </Row>

          <Divider />
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
              {t('send', 'ارسال')}
            </Button>
          </div>
        </Form>
      </Card>
    </div>
  );
}
