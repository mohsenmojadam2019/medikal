'use client';

import { useEffect, useMemo, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Avatar, Button, Card, Col, Input, Row, Skeleton, Tag, Typography } from 'antd';
import {
  CalendarOutlined,
  CustomerServiceOutlined,
  ExperimentOutlined,
  FileImageOutlined,
  FileTextOutlined,
  HeartOutlined,
  MedicineBoxOutlined,
  RobotOutlined,
  SearchOutlined,
  ShopOutlined,
  StarFilled,
  UserOutlined,
} from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import { apiFetch, extractCollection } from '@/lib/api/client';
import { getPlatformContent } from './content';

const { Title, Text, Paragraph } = Typography;

const serviceIcons = {
  doctors: MedicineBoxOutlined,
  pharmacy: ShopOutlined,
  lab: ExperimentOutlined,
  imaging: FileImageOutlined,
  'ai-chat': RobotOutlined,
  appointments: CalendarOutlined,
  records: FileTextOutlined,
  support: CustomerServiceOutlined,
};

const serviceColors = {
  doctors: ['#ede9fe', '#6d28d9'],
  pharmacy: ['#dcfce7', '#059669'],
  lab: ['#dbeafe', '#2563eb'],
  imaging: ['#fce7f3', '#db2777'],
  'ai-chat': ['#f3e8ff', '#9333ea'],
  appointments: ['#ffedd5', '#ea580c'],
  records: ['#e0f2fe', '#0284c7'],
  support: ['#fef3c7', '#d97706'],
};

function normalizeDoctor(doctor) {
  return {
    id: doctor.id,
    name: doctor.user?.name || doctor.full_name || doctor.name || '—',
    specialty: doctor.specialty?.name || doctor.specialty_name || '—',
    rating: Number(doctor.rating || 0),
    image: doctor.profile_image || doctor.avatar_url || doctor.user?.avatar_url,
    fee: Number(doctor.fee_value ?? doctor.consultation_fee ?? 0),
    available: doctor.is_available !== false,
  };
}

function normalizePharmacy(pharmacy) {
  return {
    id: pharmacy.id,
    name: pharmacy.name || '—',
    address: pharmacy.address || pharmacy.city?.name || '',
    logo: pharmacy.logo_thumb || pharmacy.logo_url,
    online: pharmacy.is_online !== false,
  };
}

export default function PlatformHome({ locale = 'fa' }) {
  const router = useRouter();
  const copy = useMemo(() => getPlatformContent(locale), [locale]);
  const [query, setQuery] = useState('');
  const [doctors, setDoctors] = useState([]);
  const [pharmacies, setPharmacies] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    Promise.allSettled([
      apiFetch('/api/doctors?per_page=4'),
      apiFetch('/api/pharmacy/pharmacies?per_page=4'),
    ]).then(([doctorResult, pharmacyResult]) => {
      if (!mounted) return;
      if (doctorResult.status === 'fulfilled') {
        setDoctors(extractCollection(doctorResult.value).slice(0, 4).map(normalizeDoctor));
      }
      if (pharmacyResult.status === 'fulfilled') {
        setPharmacies(extractCollection(pharmacyResult.value).slice(0, 4).map(normalizePharmacy));
      }
    }).finally(() => mounted && setLoading(false));
    return () => { mounted = false; };
  }, []);

  const submitSearch = () => {
    const value = query.trim();
    if (!value) return;
    router.push(`/search?q=${encodeURIComponent(value)}`);
  };

  return (
    <div className="medikal-platform" dir={copy.dir}>
      <Header />
      <main>
        <section className="medikal-hero">
          <div className="medikal-shell medikal-hero__grid">
            <div className="medikal-hero__copy">
              <span className="medikal-eyebrow"><HeartOutlined /> {copy.tagline}</span>
              <Title level={1}>{copy.heroTitle}</Title>
              <Paragraph>{copy.heroText}</Paragraph>
              <div className="medikal-hero__actions">
                <Button type="primary" size="large" icon={<CalendarOutlined />} onClick={() => router.push(`/doctors`)}>
                  {copy.primaryAction}
                </Button>
                <Button size="large" icon={<RobotOutlined />} onClick={() => router.push(`/ai-chat`)}>
                  {copy.secondaryAction}
                </Button>
              </div>
            </div>
            <div className="medikal-hero__panel" aria-hidden="true">
              <div className="medikal-health-orbit medikal-health-orbit--one"><MedicineBoxOutlined /></div>
              <div className="medikal-health-orbit medikal-health-orbit--two"><ExperimentOutlined /></div>
              <div className="medikal-health-orbit medikal-health-orbit--three"><RobotOutlined /></div>
              <div className="medikal-health-card">
                <div className="medikal-health-card__pulse"><HeartOutlined /></div>
                <strong>{copy.brand}</strong>
                <span>{copy.sections.health}</span>
              </div>
            </div>
          </div>
          <div className="medikal-shell medikal-search-card">
            <Input
              size="large"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              onPressEnter={submitSearch}
              prefix={<SearchOutlined />}
              placeholder={copy.searchPlaceholder}
              suffix={<Button type="primary" icon={<SearchOutlined />} onClick={submitSearch} aria-label="search" />}
            />
          </div>
        </section>

        <section className="medikal-shell medikal-section">
          <div className="medikal-section__heading">
            <div><Title level={2}>{copy.servicesTitle}</Title><Text>{copy.servicesSubtitle}</Text></div>
          </div>
          <div className="medikal-service-grid">
            {copy.services.map(([key, title, description]) => {
              const Icon = serviceIcons[key];
              const [background, color] = serviceColors[key];
              return (
                <Link href={`/${key}`} key={key} className="medikal-service-card">
                  <span className="medikal-service-card__icon" style={{ background, color }}><Icon /></span>
                  <strong>{title}</strong>
                  <span>{description}</span>
                </Link>
              );
            })}
          </div>
        </section>

        <section className="medikal-shell medikal-section">
          <div className="medikal-section__heading">
            <Title level={2}>{copy.sections.doctors}</Title>
            <Link href={`/doctors`}>{copy.viewAll}</Link>
          </div>
          <Row gutter={[16, 16]}>
            {loading ? [1, 2, 3, 4].map((item) => <Col xs={12} md={6} key={item}><Card><Skeleton active avatar paragraph={{ rows: 2 }} /></Card></Col>) : null}
            {!loading && doctors.length === 0 ? (
              <Col span={24}><div className="medikal-inline-state">{copy.unavailable}</div></Col>
            ) : doctors.map((doctor) => (
              <Col xs={12} md={6} key={doctor.id}>
                <Card className="medikal-doctor-mini" hoverable onClick={() => router.push(`/doctors/${doctor.id}`)}>
                  <Avatar size={68} src={doctor.image} icon={<UserOutlined />} />
                  <strong>{doctor.name}</strong>
                  <span>{doctor.specialty}</span>
                  <div><StarFilled /> {doctor.rating.toFixed(1)} {doctor.available && <Tag color="green">Online</Tag>}</div>
                </Card>
              </Col>
            ))}
          </Row>
        </section>

        <section className="medikal-shell medikal-section">
          <div className="medikal-section__heading">
            <Title level={2}>{copy.sections.pharmacies}</Title>
            <Link href={`/pharmacy`}>{copy.viewAll}</Link>
          </div>
          <div className="medikal-pharmacy-strip">
            {loading ? [1, 2, 3, 4].map((item) => <Card key={item}><Skeleton active avatar paragraph={{ rows: 1 }} /></Card>) : null}
            {!loading && pharmacies.length === 0 ? <div className="medikal-inline-state">{copy.unavailable}</div> : pharmacies.map((pharmacy) => (
              <Link href={`/pharmacy/${pharmacy.id}`} className="medikal-pharmacy-mini" key={pharmacy.id}>
                <Avatar size={54} src={pharmacy.logo} icon={<ShopOutlined />} />
                <div><strong>{pharmacy.name}</strong><span>{pharmacy.address}</span></div>
                <Tag color={pharmacy.online ? 'green' : 'default'}>{pharmacy.online ? 'Online' : 'Offline'}</Tag>
              </Link>
            ))}
          </div>
        </section>

        <section className="medikal-shell medikal-safety-card">
          <div className="medikal-safety-card__icon"><HeartOutlined /></div>
          <div><Title level={3}>{copy.safetyTitle}</Title><Text>{copy.safetyText}</Text></div>
        </section>
      </main>
      <Footer />
    </div>
  );
}
