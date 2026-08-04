'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { Avatar, Card, Col, Empty, Row, Skeleton, Tag, Typography } from 'antd';
import { MedicineBoxOutlined, SearchOutlined, ShopOutlined, UserOutlined } from '@ant-design/icons';
import { useSearchParams } from 'next/navigation';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import { apiFetch, extractCollection } from '@/lib/api/client';

import { useLanguage } from '@/lib/context/LanguageContext';
const { Title, Text } = Typography;
const copyMap = {
  fa: { title: 'نتایج جستجو', doctors: 'پزشکان', products: 'دارو و محصولات', empty: 'نتیجه‌ای پیدا نشد', searching: 'در حال جستجو…' },
  en: { title: 'Search results', doctors: 'Doctors', products: 'Medicine and products', empty: 'No results found', searching: 'Searching…' },
  ar: { title: 'نتائج البحث', doctors: 'الأطباء', products: 'الأدوية والمنتجات', empty: 'لم يتم العثور على نتائج', searching: 'جاري البحث…' },
};

export default function SearchResultsPage() {
  const { locale } = useLanguage();
  const copy = copyMap[locale] || copyMap.fa;
  const params = useSearchParams();
  const query = params.get('q')?.trim() || '';
  const [doctors, setDoctors] = useState([]);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(Boolean(query));

  useEffect(() => {
    if (!query) { setLoading(false); return; }
    let mounted = true;
    Promise.allSettled([
      apiFetch(`/api/doctors?search=${encodeURIComponent(query)}&per_page=8`),
      apiFetch(`/api/products?search=${encodeURIComponent(query)}&per_page=8`),
    ]).then(([doctorResult, productResult]) => {
      if (!mounted) return;
      setDoctors(doctorResult.status === 'fulfilled' ? extractCollection(doctorResult.value) : []);
      setProducts(productResult.status === 'fulfilled' ? extractCollection(productResult.value) : []);
    }).finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, [query]);

  return (
    <div className="medikal-platform">
      <Header />
      <main className="medikal-page"><div className="medikal-shell">
        <div className="medikal-page-heading"><span className="medikal-eyebrow"><SearchOutlined /> {query || copy.title}</span><Title level={1}>{copy.title}</Title></div>
        {loading ? <Row gutter={[16,16]}>{[1,2,3,4].map((item) => <Col xs={24} md={6} key={item}><Card><Skeleton active avatar /></Card></Col>)}</Row> : null}
        {!loading && !doctors.length && !products.length ? <Empty description={copy.empty} /> : null}
        {!loading && doctors.length ? <section className="medikal-section"><div className="medikal-section__heading"><Title level={2}>{copy.doctors}</Title></div><Row gutter={[16,16]}>{doctors.map((doctor) => <Col xs={12} md={6} key={doctor.id}><Link href={`/doctors/${doctor.id}`}><Card className="medikal-doctor-mini"><Avatar size={64} src={doctor.profile_image || doctor.user?.avatar_url} icon={<UserOutlined />} /><strong>{doctor.user?.name || doctor.full_name || doctor.name}</strong><Text>{doctor.specialty?.name || '—'}</Text></Card></Link></Col>)}</Row></section> : null}
        {!loading && products.length ? <section className="medikal-section"><div className="medikal-section__heading"><Title level={2}>{copy.products}</Title></div><Row gutter={[16,16]}>{products.map((product) => <Col xs={12} md={6} key={product.id}><Card className="medikal-product-card"><ShopOutlined /><Title level={5}>{product.generic_name || product.name}</Title><Text>{product.brand?.name || product.manufacturer}</Text>{product.requires_prescription ? <Tag color="orange"><MedicineBoxOutlined /> Rx</Tag> : null}</Card></Col>)}</Row></section> : null}
      </div></main>
      <Footer />
    </div>
  );
}
