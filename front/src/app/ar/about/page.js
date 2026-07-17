'use client';

import { Card, Row, Col, Typography, Statistic, Space, Divider } from 'antd';
import { TeamOutlined, CalendarOutlined, UserOutlined, HeartOutlined, MedicineBoxOutlined, SafetyOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text, Paragraph } = Typography;

export default function AboutPage() {
  const { t } = useLanguage();

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          {/* هدر صفحه */}
          <div style={{ textAlign: 'center', marginBottom: '48px' }}>
            <Title level={2}>🏥 درباره دکتر وب</Title>
            <Text type="secondary">پلتفرم جامع مدیریت سلامت و نوبت‌دهی</Text>
          </div>

          <Row gutter={[24, 24]}>
            <Col xs={24} lg={12}>
              <Card style={{ borderRadius: '16px', height: '100%' }}>
                <Title level={4}>داستان ما</Title>
                <Paragraph>
                  دکتر وب با هدف ساده‌سازی فرآیند مدیریت سلامت و ارتباط بین پزشکان و بیماران ایجاد شده است.
                  ما معتقدیم که دسترسی آسان به خدمات پزشکی حق همه افراد است.
                </Paragraph>
                <Paragraph>
                  با استفاده از پلتفرم دکتر وب، بیماران می‌توانند به راحتی نوبت خود را رزرو کنند،
                  با پزشکان خود در ارتباط باشند و پرونده الکترونیک خود را مدیریت کنند.
                </Paragraph>
                <Divider />
                <Title level={5}>چشم‌انداز</Title>
                <Paragraph>
                  تبدیل دکتر وب به مرجع اصلی خدمات سلامت دیجیتال و بهبود کیفیت زندگی مردم از طریق فناوری.
                </Paragraph>
                <Title level={5}>رسالت</Title>
                <Paragraph>
                  ارائه پلتفرمی امن، کارآمد و کاربرپسند برای مدیریت تمام نیازهای پزشکی کاربران.
                </Paragraph>
              </Card>
            </Col>

            <Col xs={24} lg={12}>
              <Card style={{ borderRadius: '16px', height: '100%' }}>
                <Title level={4}>ارزش‌های ما</Title>
                <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                  <Card size="small" style={{ background: '#f0f5ff' }}>
                    <Space>
                      <SafetyOutlined style={{ fontSize: '24px', color: '#2563eb' }} />
                      <div>
                        <Text strong>امنیت و حریم خصوصی</Text>
                        <br />
                        <Text type="secondary">اطلاعات شما نزد ما در امنیت کامل است</Text>
                      </div>
                    </Space>
                  </Card>
                  <Card size="small" style={{ background: '#f0fdf4' }}>
                    <Space>
                      <HeartOutlined style={{ fontSize: '24px', color: '#10b981' }} />
                      <div>
                        <Text strong>مراقبت باکیفیت</Text>
                        <br />
                        <Text type="secondary">ارائه بهترین خدمات پزشکی</Text>
                      </div>
                    </Space>
                  </Card>
                  <Card size="small" style={{ background: '#fef3c7' }}>
                    <Space>
                      <MedicineBoxOutlined style={{ fontSize: '24px', color: '#f59e0b' }} />
                      <div>
                        <Text strong>پزشکان متخصص</Text>
                        <br />
                        <Text type="secondary">همکاری با بهترین پزشکان متخصص</Text>
                      </div>
                    </Space>
                  </Card>
                  <Card size="small" style={{ background: '#f3e8ff' }}>
                    <Space>
                      <CalendarOutlined style={{ fontSize: '24px', color: '#7c3aed' }} />
                      <div>
                        <Text strong>نوبت‌دهی آسان</Text>
                        <br />
                        <Text type="secondary">رزرو نوبت در هر زمان و مکان</Text>
                      </div>
                    </Space>
                  </Card>
                </Space>
              </Card>
            </Col>
          </Row>

          {/* آمار */}
          <div style={{ marginTop: '48px' }}>
            <Title level={3} style={{ textAlign: 'center', marginBottom: '32px' }}>
              آمار و ارقام
            </Title>
            <Row gutter={[16, 16]}>
              <Col xs={12} sm={6}>
                <Card style={{ textAlign: 'center' }}>
                  <Statistic title="پزشکان" value={500} prefix={<UserOutlined />} />
                </Card>
              </Col>
              <Col xs={12} sm={6}>
                <Card style={{ textAlign: 'center' }}>
                  <Statistic title="بیماران" value={12000} prefix={<TeamOutlined />} />
                </Card>
              </Col>
              <Col xs={12} sm={6}>
                <Card style={{ textAlign: 'center' }}>
                  <Statistic title="نوبت‌ها" value={12400} prefix={<CalendarOutlined />} />
                </Card>
              </Col>
              <Col xs={12} sm={6}>
                <Card style={{ textAlign: 'center' }}>
                  <Statistic title="امتیاز" value={4.9} prefix={<HeartOutlined />} />
                </Card>
              </Col>
            </Row>
          </div>
        </div>
      </main>
      <Footer />
    </>
  );
}
