'use client';

import { Card, Row, Col, Typography, Space, Divider, Button } from 'antd';
import {
  TeamOutlined, SafetyOutlined, HeartOutlined,
  RocketOutlined, StarOutlined, CalendarOutlined,
  CheckCircleOutlined, ArrowRightOutlined,
  GlobalOutlined, MedicineBoxOutlined,
  UserOutlined, PhoneOutlined
} from '@ant-design/icons';
import Link from 'next/link';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text, Paragraph } = Typography;

export default function AboutPage() {
  const features = [
    {
      icon: <CalendarOutlined style={{ fontSize: 32, color: '#2563eb' }} />,
      title: 'نوبت‌دهی آنلاین',
      desc: 'رزرو نوبت از بهترین پزشکان در کمترین زمان',
      bg: 'rgba(37,99,235,0.06)'
    },
    {
      icon: <TeamOutlined style={{ fontSize: 32, color: '#10b981' }} />,
      title: 'پزشکان مجرب',
      desc: 'بیش از ۵۰۰ پزشک متخصص در ۳۰ تخصص مختلف',
      bg: 'rgba(16,185,129,0.06)'
    },
    {
      icon: <SafetyOutlined style={{ fontSize: 32, color: '#f59e0b' }} />,
      title: 'امنیت و حریم خصوصی',
      desc: 'حفظ کامل اطلاعات بیماران و پزشکان',
      bg: 'rgba(245,158,11,0.06)'
    },
    {
      icon: <HeartOutlined style={{ fontSize: 32, color: '#ec4899' }} />,
      title: 'مراقبت کامل',
      desc: 'پرونده الکترونیک و پیگیری درمان',
      bg: 'rgba(236,72,153,0.06)'
    },
  ];

  const stats = [
    { number: '۵۰۰+', label: 'پزشک متخصص', icon: <UserOutlined /> },
    { number: '۱۲,۴۰۰+', label: 'نوبت رزرو شده', icon: <CalendarOutlined /> },
    { number: '۴.۹', label: 'میانگین امتیاز', icon: <StarOutlined /> },
    { number: '۹۸%', label: 'رضایت بیماران', icon: <HeartOutlined /> },
  ];

  return (
    <>
      <Header />
      <main style={{ 
        minHeight: '100vh', 
        padding: '40px 0',
        backgroundImage: "url('/image/com-1.png')",
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        backgroundAttachment: 'fixed',
        position: 'relative'
      }}>
        {/* اوورلی شیشه‌ای */}
        <div style={{
          position: 'absolute',
          inset: 0,
          background: 'rgba(255,255,255,0.88)',
          backdropFilter: 'blur(6px)',
          WebkitBackdropFilter: 'blur(6px)',
          zIndex: 0
        }} />

        <div className="container" style={{ position: 'relative', zIndex: 1 }}>
          {/* هدر با طراحی جدید */}
          <div style={{ textAlign: 'center', marginBottom: 48 }}>
            <div style={{
              display: 'inline-block',
              background: 'linear-gradient(135deg, rgba(37,99,235,0.1), rgba(139,92,246,0.1))',
              padding: '8px 24px',
              borderRadius: '50px',
              marginBottom: '16px',
              border: '1px solid rgba(37,99,235,0.1)'
            }}>
              <Text style={{ color: '#2563eb', fontWeight: 600, fontSize: '14px' }}>
                <StarOutlined /> درباره ما
              </Text>
            </div>
            <Title level={1} style={{ marginBottom: '8px', fontSize: '42px', fontWeight: 800 }}>
              آشنایی با <span style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>دکتر وب</span>
            </Title>
            <Text type="secondary" style={{ fontSize: 18 }}>
              پلتفرم جامع مدیریت سلامت و نوبت‌دهی هوشمند
            </Text>
          </div>

          {/* بخش اصلی */}
          <Card bordered={false} style={{ 
            borderRadius: 20, 
            marginBottom: 32,
            background: 'rgba(255,255,255,0.92)',
            backdropFilter: 'blur(10px)',
            boxShadow: '0 4px 24px rgba(0,0,0,0.04)'
          }}>
            <Row gutter={[32, 32]} align="middle">
              <Col xs={24} md={12}>
                <div style={{ marginBottom: '16px' }}>
                  <Text style={{ 
                    display: 'inline-block',
                    background: '#2563eb',
                    color: '#fff',
                    padding: '4px 16px',
                    borderRadius: '50px',
                    fontSize: '12px',
                    fontWeight: 600
                  }}>
                    چرا دکتر وب؟
                  </Text>
                </div>
                <Title level={2} style={{ marginBottom: '12px', fontWeight: 700 }}>
                  تحول در صنعت سلامت
                </Title>
                <Paragraph style={{ fontSize: 16, lineHeight: 1.8, color: '#475569' }}>
                  دکتر وب با هدف تحول در صنعت سلامت و ارائه خدمات نوین
                  پزشکی ایجاد شده است. ما با بهره‌گیری از تکنولوژی‌های روز،
                  امکان نوبت‌دهی آسان، مشاوره آنلاین، و مدیریت پرونده
                  الکترونیک را برای بیماران و پزشکان فراهم کرده‌ایم.
                </Paragraph>
                <Paragraph style={{ fontSize: 16, lineHeight: 1.8, color: '#475569' }}>
                  تیم ما متشکل از متخصصین حوزه فناوری و پزشکی است که با
                  همکاری یکدیگر، بهترین تجربه را برای کاربران رقم می‌زنند.
                </Paragraph>
                <Space>
                  <Link href="/doctors">
                    <Button type="primary" size="large" icon={<ArrowRightOutlined />}>
                      شروع کنید
                    </Button>
                  </Link>
                  <Link href="/contact">
                    <Button size="large">
                      تماس با ما
                    </Button>
                  </Link>
                </Space>
              </Col>
              <Col xs={24} md={12} style={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                <div style={{
                  background: 'linear-gradient(135deg, #dbeafe, #ede9fe)',
                  width: '100%',
                  height: 250,
                  borderRadius: 16,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  position: 'relative',
                  overflow: 'hidden'
                }}>
                  <div style={{
                    position: 'absolute',
                    width: 200,
                    height: 200,
                    background: 'radial-gradient(circle, rgba(37,99,235,0.08), transparent)',
                    borderRadius: '50%',
                    top: -50,
                    right: -50
                  }} />
                  <div style={{
                    position: 'absolute',
                    width: 150,
                    height: 150,
                    background: 'radial-gradient(circle, rgba(139,92,246,0.08), transparent)',
                    borderRadius: '50%',
                    bottom: -30,
                    left: -30
                  }} />
                  <RocketOutlined style={{ fontSize: 72, color: '#2563eb', position: 'relative', zIndex: 1 }} />
                </div>
              </Col>
            </Row>
          </Card>

          {/* آمار */}
          <Card bordered={false} style={{ 
            borderRadius: 20, 
            marginBottom: 32,
            background: 'rgba(255,255,255,0.92)',
            backdropFilter: 'blur(10px)',
            boxShadow: '0 4px 24px rgba(0,0,0,0.04)'
          }}>
            <Row gutter={[16, 16]}>
              {stats.map((stat, index) => (
                <Col xs={12} sm={6} key={index}>
                  <div style={{ textAlign: 'center' }}>
                    <div style={{ 
                      fontSize: 32, 
                      color: '#2563eb', 
                      marginBottom: 4,
                      background: 'rgba(37,99,235,0.06)',
                      width: 56,
                      height: 56,
                      borderRadius: '50%',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      margin: '0 auto 8px'
                    }}>
                      {stat.icon}
                    </div>
                    <div style={{ fontSize: 28, fontWeight: 700, color: '#0f172a' }}>
                      {stat.number}
                    </div>
                    <div style={{ color: '#64748b', fontSize: 14 }}>
                      {stat.label}
                    </div>
                  </div>
                </Col>
              ))}
            </Row>
          </Card>

          {/* ویژگی‌ها */}
          <Row gutter={[24, 24]}>
            {features.map((feature, index) => (
              <Col xs={24} sm={12} lg={6} key={index}>
                <Card
                  bordered={false}
                  style={{
                    borderRadius: 20,
                    textAlign: 'center',
                    height: '100%',
                    transition: 'all 0.3s ease',
                    cursor: 'default',
                    background: 'rgba(255,255,255,0.92)',
                    backdropFilter: 'blur(10px)',
                    boxShadow: '0 4px 24px rgba(0,0,0,0.04)',
                    border: 'none'
                  }}
                  hoverable
                >
                  <div style={{
                    width: 72,
                    height: 72,
                    borderRadius: '50%',
                    background: feature.bg,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    margin: '0 auto 16px'
                  }}>
                    {feature.icon}
                  </div>
                  <Title level={4} style={{ marginBottom: 8 }}>{feature.title}</Title>
                  <Text type="secondary" style={{ fontSize: 14 }}>{feature.desc}</Text>
                </Card>
              </Col>
            ))}
          </Row>

          <Divider style={{ margin: '40px 0', borderColor: 'rgba(0,0,0,0.04)' }} />

          {/* رؤیا و رسالت */}
          <Card bordered={false} style={{ 
            borderRadius: 20, 
            textAlign: 'center',
            background: 'rgba(255,255,255,0.92)',
            backdropFilter: 'blur(10px)',
            boxShadow: '0 4px 24px rgba(0,0,0,0.04)'
          }}>
            <Space direction="vertical" size="middle" style={{ width: '100%' }}>
              <div style={{
                width: 64,
                height: 64,
                borderRadius: '50%',
                background: 'rgba(245,158,11,0.1)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                margin: '0 auto'
              }}>
                <StarOutlined style={{ fontSize: 32, color: '#f59e0b' }} />
              </div>
              <Title level={3} style={{ marginBottom: 4 }}>رؤیا و رسالت ما</Title>
              <Paragraph style={{ fontSize: 16, maxWidth: 600, margin: '0 auto', color: '#475569' }}>
                ارائه خدمات سلامت با کیفیت، دسترسی آسان و عادلانه برای همه
                افراد جامعه، با تکیه بر فناوری و نوآوری
              </Paragraph>
              <div style={{ display: 'flex', gap: '8px', justifyContent: 'center', marginTop: '8px' }}>
                <div style={{
                  width: 8,
                  height: 8,
                  borderRadius: '50%',
                  background: '#2563eb'
                }} />
                <div style={{
                  width: 8,
                  height: 8,
                  borderRadius: '50%',
                  background: '#7c3aed'
                }} />
                <div style={{
                  width: 8,
                  height: 8,
                  borderRadius: '50%',
                  background: '#10b981'
                }} />
              </div>
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
