// /src/app/fa/page.js
'use client';

import { useState, useEffect } from 'react';
import {
  Card, Row, Col, Button, Typography, Spin, Empty, Tag, Rate, message, Space,
  Statistic, Divider, Skeleton, Input
} from 'antd';
import {
  EnvironmentOutlined, StarOutlined,
  UserOutlined, CalendarOutlined, ClockCircleOutlined,
  ShoppingCartOutlined, PhoneOutlined,
  MailOutlined, TeamOutlined,
  DollarOutlined, MedicineBoxOutlined, SafetyOutlined,
  HomeOutlined
} from '@ant-design/icons';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;

export default function HomePage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [specialties, setSpecialties] = useState([]);
  const [doctors, setDoctors] = useState([]);
  const [drugs, setDrugs] = useState([]);
  const [stats, setStats] = useState({
    doctors: 0,
    appointments: 0,
    rating: 0,
    satisfaction: 0
  });
  const [loading, setLoading] = useState(true);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  // دریافت تخصص‌ها از API
  // دریافت تخصص‌ها از API
  const fetchSpecialties = async () => {
    try {
      const res = await fetch(`${API_URL}/api/specialties`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      console.log('📦 Specialties response:', data);

      if (data.success) {
        let specialtiesData = [];
        if (Array.isArray(data.data)) {
          specialtiesData = data.data;
        } else if (data.data && Array.isArray(data.data.data)) {
          specialtiesData = data.data.data;
        }

        // ✅ فیلتر کردن تخصص‌های فعال
        const filtered = specialtiesData.filter(s => s.is_active !== false);

        // ✅ اگر تخصصی وجود نداره، از داده‌های پیش‌فرض استفاده کن
        if (filtered.length === 0) {
          setSpecialties([
            { id: 1, name: 'داخلی', icon: 'fa-stethoscope' },
            { id: 2, name: 'قلب و عروق', icon: 'fa-heart' },
            { id: 3, name: 'کودکان', icon: 'fa-child' },
            { id: 4, name: 'زنان و زایمان', icon: 'fa-female' },
            { id: 5, name: 'ارتوپدی', icon: 'fa-bone' },
            { id: 6, name: 'مغز و اعصاب', icon: 'fa-brain' },
            { id: 7, name: 'پوست و مو', icon: 'fa-hand' },
            { id: 8, name: 'چشم پزشکی', icon: 'fa-eye' },
            { id: 9, name: 'گوش و حلق و بینی', icon: 'fa-ear' },
            { id: 10, name: 'روانپزشکی', icon: 'fa-user-check' },
          ]);
        } else {
          setSpecialties(filtered);
        }
      }
    } catch (error) {
      console.error('Error fetching specialties:', error);
      // داده‌های پیش‌فرض
      setSpecialties([
        { id: 1, name: 'داخلی', icon: 'fa-stethoscope' },
        { id: 2, name: 'قلب و عروق', icon: 'fa-heart' },
        { id: 3, name: 'کودکان', icon: 'fa-child' },
        { id: 4, name: 'زنان و زایمان', icon: 'fa-female' },
        { id: 5, name: 'ارتوپدی', icon: 'fa-bone' },
        { id: 6, name: 'مغز و اعصاب', icon: 'fa-brain' },
        { id: 7, name: 'پوست و مو', icon: 'fa-hand' },
        { id: 8, name: 'چشم پزشکی', icon: 'fa-eye' },
      ]);
    }
  };

  // دریافت پزشکان برتر از API
  const fetchTopDoctors = async () => {
    try {
      const res = await fetch(`${API_URL}/api/doctors/public`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        const doctorsData = data.data?.data || data.data || [];
        const sorted = [...doctorsData].sort((a, b) => {
          const ratingA = parseFloat(a.rating) || 0;
          const ratingB = parseFloat(b.rating) || 0;
          return ratingB - ratingA;
        });
        setDoctors(sorted.slice(0, 4));
      }
    } catch (error) {
      console.error('Error fetching doctors:', error);
    }
  };

  // دریافت داروهای فعال از API
  const fetchDrugs = async () => {
    try {
      const res = await fetch(`${API_URL}/api/drugs/active`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        const drugsData = data.data?.data || data.data || [];
        setDrugs(drugsData.slice(0, 6));
      }
    } catch (error) {
      console.error('Error fetching drugs:', error);
    }
  };

  // دریافت آمار از API
  const fetchStats = async () => {
    try {
      const res = await fetch(`${API_URL}/api/landing/stats`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setStats(data.data || {});
      }
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  useEffect(() => {
    setLoading(true);
    Promise.all([
      fetchSpecialties(),
      fetchTopDoctors(),
      fetchDrugs(),
      fetchStats(),
    ]).finally(() => setLoading(false));
  }, []);

  const handleBookAppointment = (doctorId) => {
    localStorage.setItem('selectedDoctorId', String(doctorId));
    router.push(`/${locale}/appointments/new?doctorId=${doctorId}`);
  };

  const handleAddToCart = (drug) => {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }

    // دریافت سبد خرید فعلی
    let cart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');

    // بررسی اینکه دارو قبلاً در سبد هست
    const existing = cart.find(item => item.id === drug.id);
    if (existing) {
      existing.quantity += 1;
    } else {
      cart.push({
        id: drug.id,
        name: drug.generic_name || drug.name || 'دارو',
        price: parseFloat(drug.price) || 0,
        quantity: 1,
        stock: drug.stock || 0,
      });
    }

    localStorage.setItem('pharmacyCart', JSON.stringify(cart));
    message.success(`${drug.generic_name || drug.name || 'دارو'} به سبد خرید اضافه شد`);
    router.push(`/${locale}/pharmacy`);
  };

  if (loading) {
    return (
        <>
          <Header />
          <div className="container" style={{ padding: '40px 0' }}>
            <Skeleton active avatar paragraph={{ rows: 8 }} />
            <Divider />
            <Row gutter={[16, 16]}>
              {[1, 2, 3, 4].map(i => (
                  <Col xs={24} sm={12} lg={6} key={i}>
                    <Skeleton active avatar paragraph={{ rows: 4 }} />
                  </Col>
              ))}
            </Row>
            <Divider />
            <Row gutter={[16, 16]}>
              {[1, 2, 3, 4].map(i => (
                  <Col xs={24} sm={12} lg={6} key={i}>
                    <Skeleton active avatar paragraph={{ rows: 4 }} />
                  </Col>
              ))}
            </Row>
          </div>
          <Footer />
        </>
    );
  }

  return (
      <>
        <Header />
        <main>
          {/* Hero Section */}
          <div className="container" style={{ marginBottom: '40px' }}>
            <div className="hero hero-primary">
              <div className="hero-content">
              <span className="hero-badge">
                <i className="fas fa-bolt" /> نوبت‌دهی هوشمند
              </span>
                <h1>
                  نوبت خود را <span>سریع و آسان</span> رزرو کنید
                </h1>
                <p>
                  بیش از {stats.doctors || 500} پزشک متخصص در {specialties.length || 30} تخصص مختلف،
                  آماده ارائه خدمت به شما هستند. نوبت‌دهی آنلاین، پرداخت امن و پرونده الکترونیک.
                </p>
                <div className="hero-actions">
                  <Link href={`/${locale}/doctors`}>
                    <Button type="primary" size="large" className="hero-cta">
                      <i className="fas fa-arrow-left" /> شروع کنید
                    </Button>
                  </Link>
                  <Button size="large" className="hero-cta-outline" onClick={() => {
                    document.getElementById('specialties-section').scrollIntoView({ behavior: 'smooth' });
                  }}>
                    <i className="fas fa-play" /> نحوه کار
                  </Button>
                </div>
              </div>
            </div>
          </div>

          {/* آمار */}
          <section className="container" style={{ marginBottom: '48px' }}>
            <div className="stats-row">
              <div className="stat-card">
                <div className="stat-icon blue">
                  <UserOutlined />
                </div>
                <div className="stat-info">
                  <div className="number">{stats.doctors || 500}+</div>
                  <div className="label">پزشک متخصص</div>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon green">
                  <CalendarOutlined />
                </div>
                <div className="stat-info">
                  <div className="number">{stats.appointments || 12400}+</div>
                  <div className="label">نوبت رزرو شده</div>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon purple">
                  <StarOutlined />
                </div>
                <div className="stat-info">
                  <div className="number">{stats.rating || 4.9}</div>
                  <div className="label">میانگین امتیاز</div>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon orange">
                  <TeamOutlined />
                </div>
                <div className="stat-info">
                  <div className="number">{stats.satisfaction || 98}%</div>
                  <div className="label">رضایت بیماران</div>
                </div>
              </div>
            </div>
          </section>

          {/* بخش تخصص‌ها */}
          {/* بخش تخصص‌ها */}
          <section className="container section" id="specialties-section" style={{ marginBottom: '48px' }}>
            <div className="section-header">
              <div className="section-header-left">
                <h2>
                  <i className="fas fa-stethoscope" style={{ color: '#2563eb' }} /> تخصص‌های پزشکی
                </h2>
                <span className="tag">{specialties.length} تخصص</span>
                <span className="tag hot">محبوب</span>
              </div>
              <Link href={`/${locale}/specialties`}>
                مشاهده همه <i className="fas fa-chevron-left" />
              </Link>
            </div>

            {specialties.length > 0 ? (
                <Card>
                  <div className="specialties-grid">
                    {specialties.slice(0, 12).map((specialty) => (
                        <Link
                            key={specialty.id}
                            href={`/${locale}/doctors?specialty=${specialty.id}`}
                            className="specialty-item"
                        >
                          <div className="specialty-icon">
                            {specialty.icon ? (
                                <i className={`fas ${specialty.icon}`} style={{ fontSize: '28px', color: '#2563eb' }} />
                            ) : (
                                <i className="fas fa-stethoscope" style={{ fontSize: '28px', color: '#2563eb' }} />
                            )}
                          </div>
                          <span>{specialty.name}</span>
                          <span className="count">{specialty.doctors_count || 0} پزشک</span>
                        </Link>
                    ))}
                  </div>
                </Card>
            ) : (
                <Empty description="هیچ تخصصی یافت نشد" />
            )}
          </section>
          {/* بخش پزشکان برتر */}
          <section className="container section" style={{ marginBottom: '48px' }}>
            <div className="section-header">
              <div className="section-header-left">
                <h2>
                  <i className="fas fa-star" style={{ color: '#f59e0b' }} /> پزشکان برتر
                </h2>
                <span className="tag">پرامتیاز</span>
              </div>
              <Link href={`/${locale}/doctors`}>
                مشاهده همه <i className="fas fa-chevron-left" />
              </Link>
            </div>

            {doctors.length > 0 ? (
                <div className="doctors-grid">
                  {doctors.map((doctor, index) => (
                      <div key={doctor.id} className="doctor-card">
                        {index === 0 && <span className="featured">ویژه</span>}
                        <div className="doctor-top">
                          <div className="doctor-avatar">
                            {doctor.user?.name?.charAt(0) || doctor.full_name?.charAt(0) || '👨‍⚕️'}
                          </div>
                          <div className="doctor-info">
                            <h3>{doctor.user?.name || doctor.full_name || 'پزشک'}</h3>
                            <div className="specialty">{doctor.specialty?.name || 'تخصص'}</div>
                            <div className="clinic">
                              <HomeOutlined /> {doctor.clinic_name || 'آدرس مطب'}
                            </div>
                          </div>
                        </div>
                        <div className="doctor-meta">
                          <div className="doctor-rating">
                            <Rate disabled defaultValue={parseFloat(doctor.rating) || 0} allowHalf style={{ fontSize: '14px' }} />
                            <span className="count">({doctor.total_reviews || 0} نظر)</span>
                          </div>
                          <span className={`doctor-availability ${!doctor.is_available ? 'busy' : ''}`}>
                      <i className="fas fa-circle" /> {doctor.is_available ? 'نوبت دارد' : 'نوبت محدود'}
                    </span>
                        </div>
                        <div className="doctor-price">
                          {parseInt(doctor.consultation_fee || 0).toLocaleString()} <small>تومان</small>
                        </div>
                        <div className="doctor-actions">
                          <Button
                              type="primary"
                              className="btn-book"
                              onClick={() => handleBookAppointment(doctor.id)}
                          >
                            رزرو نوبت
                          </Button>
                          <Button
                              className="btn-book outline"
                              onClick={() => router.push(`/${locale}/doctors/${doctor.id}`)}
                          >
                            پروفایل
                          </Button>
                        </div>
                      </div>
                  ))}
                </div>
            ) : (
                <Empty description="هیچ پزشکی یافت نشد" />
            )}
          </section>

          {/* بنرها */}
          <section className="container section" style={{ marginBottom: '48px' }}>
            <div className="banners-grid">
              <div className="banner-card b1">
                <div className="icon">📱</div>
                <h3>نوبت‌دهی آنلاین</h3>
                <p>۲۴ ساعته، ۷ روز هفته</p>
                <Link href={`/${locale}/appointments`} className="banner-link">
                  بیشتر بدانید <i className="fas fa-arrow-left" />
                </Link>
              </div>
              <div className="banner-card b2">
                <div className="icon">💳</div>
                <h3>پرداخت امن</h3>
                <p>زرین‌پال | آسان‌پرداخت | درگاه ملی</p>
                <Link href={`/${locale}/wallet`} className="banner-link">
                  بیشتر بدانید <i className="fas fa-arrow-left" />
                </Link>
              </div>
              <div className="banner-card b3">
                <div className="icon">📋</div>
                <h3>پرونده الکترونیک</h3>
                <p>دسترسی به سوابق پزشکی در هر زمان</p>
                <Link href={`/${locale}/records`} className="banner-link">
                  بیشتر بدانید <i className="fas fa-arrow-left" />
                </Link>
              </div>
            </div>
          </section>

          {/* بخش داروخانه */}
          <section className="container section" style={{ marginBottom: '48px' }}>
            <div className="section-header">
              <div className="section-header-left">
                <h2>
                  <i className="fas fa-pills" style={{ color: '#10b981' }} /> داروخانه آنلاین
                </h2>
                <span className="tag">داروهای موجود</span>
                <span className="tag hot">ارسال نسخه</span>
              </div>
              <Link href={`/${locale}/pharmacy`}>
                مشاهده همه <i className="fas fa-chevron-left" />
              </Link>
            </div>

            {drugs.length > 0 ? (
                <Card>
                  <div className="pharmacy-grid">
                    {drugs.map((drug) => (
                        <div key={drug.id} className="pharmacy-card">
                          <div className="pharmacy-icon">💊</div>
                          <div className="pharmacy-info">
                            <h4>{drug.generic_name || drug.name || 'بدون نام'}</h4>
                            <Tag color="blue">{drug.category || 'عمومی'}</Tag>
                            {drug.requires_prescription && (
                                <Tag color="orange" className="prescription-tag">نیاز به نسخه</Tag>
                            )}
                            <div className="pharmacy-stock">
                              موجودی: <span className={drug.stock < 30 ? 'low-stock' : ''}>{drug.stock || 0}</span>
                            </div>
                            <div className="pharmacy-price">
                              {parseFloat(drug.price).toLocaleString() || 0} <small>تومان</small>
                            </div>
                          </div>
                          <div className="pharmacy-actions">
                            <Button
                                type="primary"
                                disabled={drug.stock === 0}
                                onClick={() => handleAddToCart(drug)}
                                block
                            >
                              سفارش
                            </Button>
                            {drug.requires_prescription && (
                                <Button
                                    type="default"
                                    onClick={() => router.push(`/${locale}/pharmacy`)}
                                    block
                                >
                                  ارسال نسخه
                                </Button>
                            )}
                          </div>
                        </div>
                    ))}
                  </div>
                </Card>
            ) : (
                <Empty description="هیچ دارویی یافت نشد" />
            )}
          </section>

          {/* پیشنهادات ویژه */}
          <section className="container section" style={{ marginBottom: '48px' }}>
            <div className="section-header">
              <div className="section-header-left">
                <h2>
                  <i className="fas fa-gift" style={{ color: '#f59e0b' }} /> پیشنهادات ویژه
                </h2>
                <span className="tag hot">تخفیف</span>
              </div>
              <Link href={`/${locale}/offers`}>
                مشاهده همه <i className="fas fa-chevron-left" />
              </Link>
            </div>
            <div className="offer-card">
              <div className="offer-icon">🎁</div>
              <div className="offer-content">
                <h4>تخفیف ۲۰٪ برای ویزیت اول</h4>
                <p>برای اولین نوبت خود از هر پزشک، ۲۰٪ تخفیف دریافت کنید. کد تخفیف را کپی کنید.</p>
              </div>
              <div className="offer-code" onClick={() => {
                navigator.clipboard.writeText('WELCOME20').then(() => {
                  message.success('✅ کد تخفیف کپی شد!');
                });
              }}>
                WELCOME20
              </div>
            </div>
          </section>

          {/* اعتماد */}
          <section className="container section" style={{ marginBottom: '48px' }}>
            <div className="trust-grid">
              <div className="trust-item">
                <div className="icon"><CalendarOutlined /></div>
                <h4>نوبت‌دهی سریع</h4>
                <p>بدون معطلی و انتظار</p>
              </div>
              <div className="trust-item">
                <div className="icon"><PhoneOutlined /></div>
                <h4>پشتیبانی ۲۴/۷</h4>
                <p>همیشه در دسترس</p>
              </div>
              <div className="trust-item">
                <div className="icon"><ClockCircleOutlined /></div>
                <h4>یادآوری هوشمند</h4>
                <p>پیامک و ایمیل</p>
              </div>
              <div className="trust-item">
                <div className="icon"><DollarOutlined /></div>
                <h4>پرداخت امن</h4>
                <p>درگاه معتبر بانکی</p>
              </div>
              <div className="trust-item">
                <div className="icon"><MedicineBoxOutlined /></div>
                <h4>لغو آسان</h4>
                <p>تا ۲۴ ساعت قبل</p>
              </div>
            </div>
          </section>
        </main>
        <Footer />

        {/* دکمه شناور پشتیبانی */}
        <button
            className="floating-btn"
            title="پشتیبانی آنلاین"
            onClick={() => message.info('پشتیبانی آنلاین: در حال حاضر در دسترس است.')}
        >
          <i className="fas fa-comment-dots" />
        </button>
      </>
  );
}