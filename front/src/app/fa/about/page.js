'use client';

import { Card, Row, Col, Typography, Space, Divider } from 'antd';
import {
  TeamOutlined, SafetyOutlined, HeartOutlined,
  RocketOutlined, StarOutlined, CalendarOutlined
} from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text, Paragraph } = Typography;

export default function AboutPage() {
  const features = [
    {
      icon: <CalendarOutlined style={{ fontSize: 32, color: '#2563eb' }} />,
      title: 'نوبت‌دهی آنلاین',
      desc: 'رزرو نوبت از بهترین پزشکان در کمترین زمان'
    },
    {
      icon: <TeamOutlined style={{ fontSize: 32, color: '#10b981' }} />,
      title: 'پزشکان مجرب',
      desc: 'بیش از ۵۰۰ پزشک متخصص در ۳۰ تخصص مختلف'
    },
    {
      icon: <SafetyOutlined style={{ fontSize: 32, color: '#f59e0b' }} />,
      title: 'امنیت و حریم خصوصی',
      desc: 'حفظ کامل اطلاعات بیماران و پزشکان'
    },
    {
      icon: <HeartOutlined style={{ fontSize: 32, color: '#ec4899' }} />,
      title: 'مراقبت کامل',
      desc: 'پرونده الکترونیک و پیگیری درمان'
    },
  ];

  return (
      <>
        <Header />
        <main style={{ minHeight: '100vh', padding: '40px 0', background: '#f8fafc' }}>
          <div className="container">
            <div style={{ textAlign: 'center', marginBottom: 48 }}>
              <Title level={1}>درباره ما</Title>
              <Text type="secondary" style={{ fontSize: 18 }}>
                دکتر وب، پلتفرم جامع مدیریت سلامت
              </Text>
            </div>

            <Card bordered={false} style={{ borderRadius: 16, marginBottom: 32 }}>
              <Row gutter={[24, 24]}>
                <Col xs={24} md={12}>
                  <Title level={3}>چرا دکتر وب؟</Title>
                  <Paragraph style={{ fontSize: 16, lineHeight: 1.8 }}>
                    دکتر وب با هدف تحول در صنعت سلامت و ارائه خدمات نوین
                    پزشکی ایجاد شده است. ما با بهره‌گیری از تکنولوژی‌های روز،
                    امکان نوبت‌دهی آسان، مشاوره آنلاین، و مدیریت پرونده
                    الکترونیک را برای بیماران و پزشکان فراهم کرده‌ایم.
                  </Paragraph>
                  <Paragraph style={{ fontSize: 16, lineHeight: 1.8 }}>
                    تیم ما متشکل از متخصصین حوزه فناوری و پزشکی است که با
                    همکاری یکدیگر، بهترین تجربه را برای کاربران رقم می‌زنند.
                  </Paragraph>
                </Col>
                <Col xs={24} md={12} style={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                  <div style={{
                    background: '#dbeafe',
                    width: '100%',
                    height: 200,
                    borderRadius: 12,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}>
                    <RocketOutlined style={{ fontSize: 64, color: '#2563eb' }} />
                  </div>
                </Col>
              </Row>
            </Card>

            <Row gutter={[24, 24]}>
              {features.map((feature, index) => (
                  <Col xs={24} sm={12} lg={6} key={index}>
                    <Card
                        bordered={false}
                        style={{
                          borderRadius: 16,
                          textAlign: 'center',
                          height: '100%',
                          transition: 'transform 0.2s',
                          cursor: 'default'
                        }}
                        hoverable
                    >
                      <div style={{ marginBottom: 16 }}>{feature.icon}</div>
                      <Title level={4}>{feature.title}</Title>
                      <Text type="secondary">{feature.desc}</Text>
                    </Card>
                  </Col>
              ))}
            </Row>

            <Divider />

            <Card bordered={false} style={{ borderRadius: 16, textAlign: 'center' }}>
              <Space direction="vertical" size="middle">
                <StarOutlined style={{ fontSize: 48, color: '#f59e0b' }} />
                <Title level={3}>رؤیا و رسالت ما</Title>
                <Paragraph style={{ fontSize: 16, maxWidth: 600, margin: '0 auto' }}>
                  ارائه خدمات سلامت با کیفیت، دسترسی آسان و عادلانه برای همه
                  افراد جامعه، با تکیه بر فناوری و نوآوری
                </Paragraph>
              </Space>
            </Card>
          </div>
        </main>
        <Footer />

        <style jsx>{`
        .container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 24px;
        }

        @media (max-width: 768px) {
          main {
            padding: 20px 0;
          }
          .container {
            padding: 0 16px;
          }
        }
      `}</style>
      </>
  );
}