'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import Link from 'next/link';
import { Avatar, Button, Card, Col, Input, Pagination, Row, Skeleton, Tag, Typography } from 'antd';
import { EnvironmentOutlined, PhoneOutlined, SearchOutlined, ShopOutlined } from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import ServiceState from '@/components/platform/ServiceState';
import { apiFetch, extractCollection, extractPagination, getApiErrorMessage } from '@/lib/api/client';

const { Title, Text } = Typography;

const copyMap = {
  fa: { title: 'داروخانه آنلاین', subtitle: 'داروخانه‌های فعال را پیدا کنید و محصولات موجود را ببینید', search: 'نام یا آدرس داروخانه', results: 'داروخانه یافت شد', open: 'مشاهده داروخانه', online: 'آنلاین', empty: 'داروخانه‌ای پیدا نشد', error: 'دریافت فهرست داروخانه‌ها ممکن نشد', retry: 'تلاش دوباره' },
  en: { title: 'Online pharmacy', subtitle: 'Find active pharmacies and browse available products', search: 'Pharmacy name or address', results: 'pharmacies found', open: 'Open pharmacy', online: 'Online', empty: 'No pharmacy found', error: 'Pharmacies could not be loaded', retry: 'Try again' },
  ar: { title: 'الصيدلية الإلكترونية', subtitle: 'اعثر على الصيدليات المتاحة وتصفح المنتجات', search: 'اسم الصيدلية أو العنوان', results: 'صيدلية', open: 'عرض الصيدلية', online: 'متصلة', empty: 'لم يتم العثور على صيدليات', error: 'تعذر تحميل الصيدليات', retry: 'إعادة المحاولة' },
};

export default function PharmacyDirectoryPage({ locale = 'fa' }) {
  const copy = copyMap[locale] || copyMap.fa;
  const [pharmacies, setPharmacies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);

  const query = useMemo(() => {
    const params = new URLSearchParams({ page: String(page), per_page: '12' });
    if (search.trim()) params.set('search', search.trim());
    return params.toString();
  }, [page, search]);

  const loadPharmacies = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const payload = await apiFetch(`/api/pharmacy/pharmacies?${query}`);
      const items = extractCollection(payload);
      setPharmacies(items);
      setTotal(extractPagination(payload, items.length).total);
    } catch (requestError) {
      setPharmacies([]);
      setTotal(0);
      setError(getApiErrorMessage(requestError, copy.error));
    } finally {
      setLoading(false);
    }
  }, [query, copy.error]);

  useEffect(() => {
    const timer = setTimeout(loadPharmacies, 250);
    return () => clearTimeout(timer);
  }, [loadPharmacies]);

  return (
    <div className="medikal-platform">
      <Header />
      <main className="medikal-page">
        <div className="medikal-shell">
          <div className="medikal-page-heading">
            <span className="medikal-eyebrow"><ShopOutlined /> {copy.subtitle}</span>
            <Title level={1}>{copy.title}</Title>
          </div>

          <Card className="medikal-filter-card">
            <Input
              prefix={<SearchOutlined />}
              value={search}
              onChange={(event) => { setSearch(event.target.value); setPage(1); }}
              placeholder={copy.search}
              allowClear
            />
          </Card>

          <div className="medikal-results-meta"><strong>{total}</strong> {copy.results}</div>
          {error ? <ServiceState title={copy.error} description={error} retryLabel={copy.retry} onRetry={loadPharmacies} /> : null}
          {!error && loading ? <Row gutter={[16, 16]}>{Array.from({ length: 8 }, (_, index) => <Col xs={24} sm={12} lg={6} key={index}><Card><Skeleton active avatar paragraph={{ rows: 3 }} /></Card></Col>)}</Row> : null}
          {!error && !loading && pharmacies.length === 0 ? <ServiceState title={copy.empty} description="" retryLabel={copy.retry} onRetry={loadPharmacies} /> : null}

          {!error && !loading && pharmacies.length > 0 ? (
            <Row gutter={[16, 16]}>
              {pharmacies.map((pharmacy) => (
                <Col xs={24} sm={12} lg={6} key={pharmacy.id}>
                  <Card className="medikal-directory-card medikal-pharmacy-card" hoverable>
                    <div className="medikal-directory-card__head">
                      <Avatar size={76} src={pharmacy.logo_medium || pharmacy.logo_thumb || pharmacy.logo_url} icon={<ShopOutlined />} />
                      <Tag color="green">{copy.online}</Tag>
                    </div>
                    <Title level={4}>{pharmacy.name || '—'}</Title>
                    <div className="medikal-card-location"><EnvironmentOutlined /> {pharmacy.address || pharmacy.city?.name || '—'}</div>
                    {pharmacy.phone ? <div className="medikal-card-location"><PhoneOutlined /> {pharmacy.phone}</div> : null}
                    <Link href={`/${locale}/pharmacy/${pharmacy.id}`}><Button type="primary" block>{copy.open}</Button></Link>
                  </Card>
                </Col>
              ))}
            </Row>
          ) : null}

          {total > 12 ? <Pagination current={page} pageSize={12} total={total} onChange={setPage} showSizeChanger={false} className="medikal-pagination" /> : null}
        </div>
      </main>
      <Footer />
    </div>
  );
}
