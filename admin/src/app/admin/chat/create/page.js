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
  Upload,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  SendOutlined,
  UserOutlined,
  PaperClipOutlined,
} from '@ant-design/icons';
import { chatService, usersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateChatPage() {
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
      await chatService.sendMessage(values.receiver_id, values.message);
      message.success(t('message_sent', 'پیام با موفقیت ارسال شد'));
      router.push('/admin/chat');
    } catch (error) {
      console.error('Error sending message:', error);
      message.error(t('send_error', 'خطا در ارسال پیام'));
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
                {t('new_message', 'پیام جدید')}
              </Title>
              <Text type="secondary">
                {t('create_message_subtitle', 'ارسال پیام جدید')}
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
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="receiver_id"
                    label={t('receiver', 'گیرنده')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_receiver', 'انتخاب گیرنده...')}
                      showSearch
                      optionFilterProp="children"
                      options={users.map((u) => ({
                        value: u.id,
                        label: u.name,
                      }))}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="message"
                label={t('message', 'متن پیام')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <TextArea
                  rows={6}
                  placeholder={t('message_placeholder', 'متن پیام...')}
                />
              </Form.Item>

              <Form.Item
                name="attachment"
                label={t('attachment', 'پیوست')}
              >
                <Upload>
                  <Button icon={<PaperClipOutlined />}>
                    {t('upload_file', 'آپلود فایل')}
                  </Button>
                </Upload>
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
                  <SendOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('message_info', 'اطلاعات پیام')}</Text>
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('message_help', 'پیام به‌صورت لحظه‌ای برای گیرنده ارسال می‌شود')}
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
