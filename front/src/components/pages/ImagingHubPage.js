'use client';

import Link from 'next/link';
import { Alert, Button, Card, Col, Row, Steps, Typography } from 'antd';
import { CalendarOutlined, FileImageOutlined, FileSearchOutlined, SafetyCertificateOutlined, UploadOutlined } from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text, Paragraph } = Typography;

const copyMap = {
  fa: {
    title: 'تصویربرداری پزشکی', subtitle: 'ثبت درخواست، نگهداری نتیجه و اشتراک امن با پزشک',
    notice: 'اتصال PACS و API مدیریت فایل تصویربرداری هنوز در بک‌اند فعال نشده است. این صفحه بدون نمایش خطای ارتباطی، وضعیت واقعی سرویس را نشان می‌دهد.',
    request: 'درخواست تصویربرداری', requestText: 'درخواست‌های ثبت‌شده توسط پزشک از پرونده سلامت قابل پیگیری هستند.',
    archive: 'آرشیو تصاویر', archiveText: 'پس از فعال‌شدن سرویس PACS، تصاویر و گزارش‌ها در این بخش نمایش داده می‌شوند.',
    sharing: 'اشتراک امن', sharingText: 'اشتراک نتیجه با پزشک تنها با دسترسی حساب کاربری انجام خواهد شد.',
    action: 'مشاهده پرونده سلامت', steps: ['ثبت درخواست پزشک', 'انجام تصویربرداری', 'بارگذاری نتیجه', 'بررسی توسط پزشک'],
  },
  en: {
    title: 'Medical imaging', subtitle: 'Track requests, store results and share securely with your doctor',
    notice: 'The PACS and imaging-file APIs are not active in the backend yet. This page now shows the real service status instead of a generic connection error.',
    request: 'Imaging request', requestText: 'Requests created by your doctor can be tracked from your health record.',
    archive: 'Image archive', archiveText: 'Images and reports will appear here when the PACS service is enabled.',
    sharing: 'Secure sharing', sharingText: 'Results will only be shared with authorized healthcare professionals.',
    action: 'Open health records', steps: ['Doctor request', 'Imaging appointment', 'Result upload', 'Doctor review'],
  },
  ar: {
    title: 'التصوير الطبي', subtitle: 'متابعة الطلبات وحفظ النتائج ومشاركتها بأمان مع الطبيب',
    notice: 'واجهة PACS وواجهات ملفات التصوير غير مفعلة في الخادم بعد. تعرض هذه الصفحة حالة الخدمة الحقيقية بدلاً من خطأ اتصال عام.',
    request: 'طلب التصوير', requestText: 'يمكن متابعة الطلبات التي أنشأها الطبيب من السجل الصحي.',
    archive: 'أرشيف الصور', archiveText: 'ستظهر الصور والتقارير هنا بعد تفعيل خدمة PACS.',
    sharing: 'مشاركة آمنة', sharingText: 'تتم مشاركة النتائج فقط مع المختصين المصرح لهم.',
    action: 'فتح السجل الصحي', steps: ['طلب الطبيب', 'موعد التصوير', 'رفع النتيجة', 'مراجعة الطبيب'],
  },
};

export default function ImagingHubPage({ locale = 'fa' }) {
  const copy = copyMap[locale] || copyMap.fa;
  const cards = [
    [CalendarOutlined, copy.request, copy.requestText],
    [FileImageOutlined, copy.archive, copy.archiveText],
    [SafetyCertificateOutlined, copy.sharing, copy.sharingText],
  ];

  return (
    <div className="medikal-platform">
      <Header />
      <main className="medikal-page">
        <div className="medikal-shell">
          <div className="medikal-page-heading">
            <span className="medikal-eyebrow"><FileSearchOutlined /> {copy.subtitle}</span>
            <Title level={1}>{copy.title}</Title>
          </div>

          <Alert className="medikal-status-alert" type="info" showIcon message={copy.notice} />

          <Row gutter={[16, 16]} className="medikal-feature-row">
            {cards.map(([Icon, title, description]) => (
              <Col xs={24} md={8} key={title}>
                <Card className="medikal-feature-card">
                  <span className="medikal-feature-card__icon"><Icon /></span>
                  <Title level={3}>{title}</Title>
                  <Paragraph>{description}</Paragraph>
                </Card>
              </Col>
            ))}
          </Row>

          <Card className="medikal-process-card">
            <Steps
              responsive
              current={0}
              items={copy.steps.map((title, index) => ({ title, icon: index === 2 ? <UploadOutlined /> : undefined }))}
            />
            <div className="medikal-process-card__action">
              <Text>{copy.requestText}</Text>
              <Link href={`/${locale}/profile/medical-records`}><Button type="primary">{copy.action}</Button></Link>
            </div>
          </Card>
        </div>
      </main>
      <Footer />
    </div>
  );
}
