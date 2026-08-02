'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import { Avatar, Button, Card, Col, Input, Pagination, Rate, Row, Select, Skeleton, Tag, Typography } from 'antd';
import { CalendarOutlined, EnvironmentOutlined, FilterOutlined, SearchOutlined, UserOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import ServiceState from '@/components/platform/ServiceState';
import { apiFetch, extractCollection, extractPagination, getApiErrorMessage } from '@/lib/api/client';

const { Title, Text } = Typography;

const copyMap = {
  fa: { title: 'پزشکان متخصص', subtitle: 'پزشک مناسب را پیدا کنید و آنلاین نوبت بگیرید', search: 'نام پزشک، تخصص یا مطب', specialty: 'همه تخصص‌ها', results: 'پزشک یافت شد', book: 'رزرو نوبت', profile: 'مشاهده پروفایل', empty: 'پزشکی با این فیلتر پیدا نشد', error: 'دریافت فهرست پزشکان ممکن نشد', retry: 'تلاش دوباره', fee: 'تومان', available: 'نوبت فعال' },
  en: { title: 'Specialist doctors', subtitle: 'Find the right doctor and book online', search: 'Doctor, specialty or clinic', specialty: 'All specialties', results: 'doctors found', book: 'Book appointment', profile: 'View profile', empty: 'No doctors match these filters', error: 'Doctors could not be loaded', retry: 'Try again', fee: 'Toman', available: 'Available' },
  ar: { title: 'الأطباء المتخصصون', subtitle: 'اختر الطبيب المناسب واحجز موعداً عبر الإنترنت', search: 'اسم الطبيب أو التخصص أو العيادة', specialty: 'كل التخصصات', results: 'طبيب', book: 'حجز موعد', profile: 'عرض الملف', empty: 'لا يوجد أطباء مطابقون', error: 'تعذر تحميل قائمة الأطباء', retry: 'إعادة المحاولة', fee: 'تومان', available: 'متاح' },
};

export default function DoctorsDirectoryPage({ locale = 'fa' }) {
  const copy = copyMap[locale] || copyMap.fa;
  const router = useRouter();
  const [doctors, setDoctors] = useState([]);
  const [specialties, setSpecialties] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [specialty, setSpecialty] = useState();
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);

  const query = useMemo(() => {
    const params = new URLSearchParams({ page: String(page), per_page: '12' });
    if (search.trim()) params.set('search', search.trim());
    if (specialty) params.set('specialty_id', String(specialty));
    return params.toString();
  }, [page, search, specialty]);

  const loadDoctors = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const payload = await apiFetch(`/api/doctors?${query}`);
      const items = extractCollection(payload);
      setDoctors(items);
      setTotal(extractPagination(payload, items.length).total);
    } catch (requestError) {
      setDoctors([]);
      setTotal(0);
      setError(getApiErrorMessage(requestError, copy.error));
    } finally {
      setLoading(false);
    }
  }, [query, copy.error]);

  useEffect(() => {
    const timer = setTimeout(loadDoctors, 250);
    return () => clearTimeout(timer);
  }, [loadDoctors]);

  useEffect(() => {
    apiFetch('/api/specialties').then((payload) => setSpecialties(extractCollection(payload))).catch(() => setSpecialties([]));
  }, []);

  const doctorName = (doctor) => doctor.user?.name || doctor.full_name || doctor.name || '—';

  return (
    <div className="medikal-platform">
      <Header />
      <main className="medikal-page">
        <div className="medikal-shell">
          <div className="medikal-page-heading">
            <span className="medikal-eyebrow"><FilterOutlined /> {copy.subtitle}</span>
            <Title level={1}>{copy.title}</Title>
          </div>

          <Card className="medikal-filter-card">
            <Row gutter={[12, 12]}>
              <Col xs={24} md={16}>
                <Input prefix={<SearchOutlined />} value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder={copy.search} allowClear />
              </Col>
              <Col xs={24} md={8}>
                <Select
                  value={specialty}
                  onChange={(value) => { setSpecialty(value); setPage(1); }}
                  placeholder={copy.specialty}
                  allowClear
                  style={{ width: '100%' }}
                  options={specialties.map((item) => ({ value: item.id, label: item.name }))}
                />
              </Col>
            </Row>
          </Card>

          <div className="medikal-results-meta"><strong>{total}</strong> {copy.results}</div>

          {error ? <ServiceState title={copy.error} description={error} retryLabel={copy.retry} onRetry={loadDoctors} /> : null}
          {!error && loading ? (
            <Row gutter={[16, 16]}>{Array.from({ length: 8 }, (_, index) => <Col xs={24} sm={12} lg={6} key={index}><Card><Skeleton active avatar paragraph={{ rows: 3 }} /></Card></Col>)}</Row>
          ) : null}
          {!error && !loading && doctors.length === 0 ? <ServiceState title={copy.empty} description="" retryLabel={copy.retry} onRetry={loadDoctors} /> : null}

          {!error && !loading && doctors.length > 0 ? (
            <Row gutter={[16, 16]}>
              {doctors.map((doctor) => (
                <Col xs={24} sm={12} lg={6} key={doctor.id}>
                  <Card className="medikal-directory-card" hoverable>
                    <div className="medikal-directory-card__head">
                      <Avatar size={76} src={doctor.profile_image || doctor.avatar_url || doctor.user?.avatar_url} icon={<UserOutlined />} />
                      {doctor.is_available !== false ? <Tag color="green">{copy.available}</Tag> : null}
                    </div>
                    <Title level={4}>{doctorName(doctor)}</Title>
                    <Text>{doctor.specialty?.name || doctor.specialty_name || '—'}</Text>
                    <div className="medikal-card-location"><EnvironmentOutlined /> {doctor.clinic_name || doctor.clinic?.name || doctor.city?.name || '—'}</div>
                    <div className="medikal-card-rating"><Rate disabled allowHalf value={Number(doctor.rating || 0)} /><span>{Number(doctor.rating || 0).toFixed(1)}</span></div>
                    <div className="medikal-card-fee">{Number(doctor.fee_value ?? doctor.consultation_fee ?? 0).toLocaleString(locale === 'en' ? 'en-US' : 'fa-IR')} <small>{copy.fee}</small></div>
                    <div className="medikal-card-actions">
                      <Button onClick={() => router.push(`/${locale}/doctors/${doctor.id}`)}>{copy.profile}</Button>
                      <Button type="primary" icon={<CalendarOutlined />} onClick={() => router.push(`/${locale}/appointments/new?doctorId=${doctor.id}`)}>{copy.book}</Button>
                    </div>
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
