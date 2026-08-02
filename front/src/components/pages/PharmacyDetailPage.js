'use client';

import { useCallback, useEffect, useState } from 'react';
import Link from 'next/link';
import { Avatar, Button, Card, Col, Empty, Row, Skeleton, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EnvironmentOutlined, PhoneOutlined, ShopOutlined } from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import ServiceState from '@/components/platform/ServiceState';
import { apiFetch, getApiErrorMessage } from '@/lib/api/client';

const { Title, Text, Paragraph } = Typography;
const copyMap = {
  fa: { back: 'بازگشت به داروخانه‌ها', products: 'محصولات موجود', empty: 'محصول فعالی ثبت نشده است', error: 'اطلاعات داروخانه دریافت نشد', retry: 'تلاش دوباره', price: 'تومان' },
  en: { back: 'Back to pharmacies', products: 'Available products', empty: 'No active products', error: 'Pharmacy information could not be loaded', retry: 'Try again', price: 'Toman' },
  ar: { back: 'العودة إلى الصيدليات', products: 'المنتجات المتاحة', empty: 'لا توجد منتجات فعالة', error: 'تعذر تحميل معلومات الصيدلية', retry: 'إعادة المحاولة', price: 'تومان' },
};

export default function PharmacyDetailPage({ locale = 'fa', pharmacyId }) {
  const copy = copyMap[locale] || copyMap.fa;
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true); setError('');
    try {
      const payload = await apiFetch(`/api/pharmacy/pharmacies/${pharmacyId}`);
      setData(payload.data || null);
    } catch (requestError) {
      setData(null); setError(getApiErrorMessage(requestError, copy.error));
    } finally { setLoading(false); }
  }, [pharmacyId, copy.error]);

  useEffect(() => { load(); }, [load]);

  const pharmacy = data?.pharmacy || data;
  const products = Array.isArray(data?.products) ? data.products : [];

  return (
    <div className="medikal-platform">
      <Header />
      <main className="medikal-page"><div className="medikal-shell">
        <Link href={`/${locale}/pharmacy`} className="medikal-back-link"><ArrowLeftOutlined /> {copy.back}</Link>
        {loading ? <Card><Skeleton active avatar paragraph={{ rows: 6 }} /></Card> : null}
        {error ? <ServiceState title={copy.error} description={error} retryLabel={copy.retry} onRetry={load} /> : null}
        {!loading && !error && pharmacy ? (
          <>
            <Card className="medikal-detail-hero">
              <Avatar size={96} src={data?.logo_large || data?.logo_url || pharmacy.logo_url} icon={<ShopOutlined />} />
              <div><Title level={1}>{pharmacy.name}</Title><Paragraph><EnvironmentOutlined /> {pharmacy.address || '—'}</Paragraph>{pharmacy.phone ? <Text><PhoneOutlined /> {pharmacy.phone}</Text> : null}</div>
              <Tag color="green">Online</Tag>
            </Card>
            <div className="medikal-section__heading"><Title level={2}>{copy.products}</Title></div>
            {products.length ? <Row gutter={[16,16]}>{products.map((product) => <Col xs={12} md={6} key={product.id}><Card className="medikal-product-card"><Title level={5}>{product.generic_name || product.name}</Title><Text>{product.manufacturer || product.brand?.name}</Text><strong>{Number(product.price || 0).toLocaleString(locale === 'en' ? 'en-US' : 'fa-IR')} {copy.price}</strong></Card></Col>)}</Row> : <Empty description={copy.empty} />}
          </>
        ) : null}
      </div></main>
      <Footer />
    </div>
  );
}
