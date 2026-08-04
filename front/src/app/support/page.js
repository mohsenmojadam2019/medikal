'use client';

import { useState } from 'react';
import { Card, Row, Col, Typography, Input, Button, message, Space, Divider, Tag, Form } from 'antd';
import { SendOutlined, PhoneOutlined, MailOutlined, WhatsAppOutlined, InstagramOutlined, GlobalOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';

const { Title, Text, Paragraph } = Typography;
const { TextArea } = Input;

export default function SupportPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [form] = Form.useForm();

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      // ارسال پیام به API
      await new Promise(resolve => setTimeout(resolve, 1000));
      message.success('✅ پیام شما با موفقیت ارسال شد');
      form.resetFields();
    } catch (error) {
      message.error('❌ خطا در ارسال پیام');
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />
          
          <div style={{ marginBottom: '32px' }}>
            <Title level={2}>🎧 {t('nav.support')}</Title>
            <Text type="secondary">پشتیبانی و ارتباط با ما</Text>
          </div>

          <Row gutter={[24, 24]}>
            <Col xs={24} lg={16}>
              <Card style={{ borderRadius: '16px' }}>
                <Title level={4}>ارسال پیام</Title>
                <Form form={form} layout="vertical" onFinish={handleSubmit}>
                  <Form.Item
                    name="subject"
                    label="موضوع"
                    rules={[{ required: true, message: 'لطفاً موضوع را وارد کنید' }]}
                  >
                    <Input placeholder="موضوع پیام..." />
                  </Form.Item>
                  <Form.Item
                    name="message"
                    label="پیام"
                    rules={[{ required: true, message: 'لطفاً پیام را وارد کنید' }]}
                  >
                    <TextArea rows={5} placeholder="متن پیام..." />
                  </Form.Item>
                  <Form.Item>
                    <Button
                      type="primary"
                      htmlType="submit"
                      loading={loading}
                      icon={<SendOutlined />}
                      size="large"
                    >
                      ارسال پیام
                    </Button>
                  </Form.Item>
                </Form>
              </Card>
            </Col>

            <Col xs={24} lg={8}>
              <Card style={{ borderRadius: '16px' }}>
                <Title level={4}>اطلاعات تماس</Title>
                <Divider />
                <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                  <div>
                    <Text strong><PhoneOutlined /> تلفن</Text>
                    <br />
                    <Text>۰۲۱-۱۲۳۴۵۶۷۸</Text>
                  </div>
                  <div>
                    <Text strong><MailOutlined /> ایمیل</Text>
                    <br />
                    <Text>info@clinic-yar.com</Text>
                  </div>
                  <div>
                    <Text strong>ساعات کاری</Text>
                    <br />
                    <Text>شنبه تا پنجشنبه: ۸:۰۰ - ۲۲:۰۰</Text>
                    <br />
                    <Text>جمعه: ۱۰:۰۰ - ۱۸:۰۰</Text>
                  </div>
                </Space>
              </Card>

              <Card style={{ borderRadius: '16px', marginTop: '16px' }}>
                <Title level={4}>شبکه‌های اجتماعی</Title>
                <Divider />
                <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                  <Button block icon={<WhatsAppOutlined />} onClick={() => window.open('https://wa.me/989123456789', '_blank')}>
                    واتساپ
                  </Button>
                  <Button block icon={<GlobalOutlined />} onClick={() => window.open('https://t.me/clinic-yar', '_blank')}>
                    تلگرام
                  </Button>
                  <Button block icon={<InstagramOutlined />} onClick={() => window.open('https://instagram.com/clinic-yar', '_blank')}>
                    اینستاگرام
                  </Button>
                </Space>
              </Card>
            </Col>
          </Row>
        </div>
      </main>
      <Footer />
    </>
  );
}
