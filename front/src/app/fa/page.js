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
import Hero from '@/components/front/Hero/Hero.js';
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

  const fetchSpecialties = async () => {
    try {
      const res = await fetch(`${API_URL}/api/specialties`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();

      if (data.success) {
        let specialtiesData = [];
        if (Array.isArray(data.data)) {
          specialtiesData = data.data;
        } else if (data.data && Array.isArray(data.data.data)) {
          specialtiesData = data.data.data;
        }

        const filtered = specialtiesData.filter(s => s.is_active !== false);

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

    let cart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');

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
          <Hero />

          {/* Quick Access Cards - 4 باکس شیک متحرک */}
          {/* Quick Access Cards - نسخه فوق‌العاده شیک و مدرن */}
          <section className="quick-access">
            <div className="container">
              <div className="quick-section-header">
                <div className="quick-section-title">
                  <span className="quick-section-badge">🚀 دسترسی سریع</span>
                  <h2>خدمات ما</h2>
                  <p>با انتخاب هر یک از خدمات، به سرعت به بخش مورد نظر هدایت شوید</p>
                </div>
              </div>

              <div className="quick-grid">
                {/* Card 1 - نوبت‌دهی مطب */}
                <Link href={`/${locale}/appointments/new`} className="quick-card card-1">
                  <div className="quick-card-glow"></div>
                  <div className="quick-card-inner">
                    <div className="quick-icon-wrapper">
                      <div className="quick-icon-bg"></div>
                      <div className="quick-icon-pulse"></div>
                      <span className="quick-icon">🏥</span>
                    </div>
                    <div className="quick-card-content">
                      <h3>نوبت‌دهی مطب</h3>
                      <p>دریافت نوبت حضوری از پزشکان متخصص</p>
                      <div className="quick-features">
                        <span>✓ رزرو آنلاین</span>
                        <span>✓ انتخاب پزشک</span>
                      </div>
                    </div>
                    <div className="quick-footer">
                      <span className="quick-arrow">→</span>
                      <span className="quick-badge">همین حالا</span>
                    </div>
                  </div>
                  <div className="quick-shine"></div>
                  <div className="quick-border-animation"></div>
                </Link>

                {/* Card 2 - داروخانه */}
                <Link href={`/${locale}/pharmacy`} className="quick-card card-2">
                  <div className="quick-card-glow"></div>
                  <div className="quick-card-inner">
                    <div className="quick-icon-wrapper">
                      <div className="quick-icon-bg"></div>
                      <div className="quick-icon-pulse"></div>
                      <span className="quick-icon">💊</span>
                    </div>
                    <div className="quick-card-content">
                      <h3>داروخانه</h3>
                      <p>خرید آنلاین دارو با ارسال سریع</p>
                      <div className="quick-features">
                        <span>✓ ارسال فوری</span>
                        <span>✓ قیمت مناسب</span>
                      </div>
                    </div>
                    <div className="quick-footer">
                      <span className="quick-arrow">→</span>
                      <span className="quick-badge">۲۴ ساعته</span>
                    </div>
                  </div>
                  <div className="quick-shine"></div>
                  <div className="quick-border-animation"></div>
                </Link>

                {/* Card 3 - آزمایشگاه */}
                <Link href={`/${locale}/lab`} className="quick-card card-3">
                  <div className="quick-card-glow"></div>
                  <div className="quick-card-inner">
                    <div className="quick-icon-wrapper">
                      <div className="quick-icon-bg"></div>
                      <div className="quick-icon-pulse"></div>
                      <span className="quick-icon">🔬</span>
                    </div>
                    <div className="quick-card-content">
                      <h3>آزمایشگاه</h3>
                      <p>رزرو آزمایش و دریافت نتیجه آنلاین</p>
                      <div className="quick-features">
                        <span>✓ نتایج دقیق</span>
                        <span>✓ پاسخ سریع</span>
                      </div>
                    </div>
                    <div className="quick-footer">
                      <span className="quick-arrow">→</span>
                      <span className="quick-badge">دقیق</span>
                    </div>
                  </div>
                  <div className="quick-shine"></div>
                  <div className="quick-border-animation"></div>
                </Link>

                {/* Card 4 - هوش مصنوعی */}
                <Link href={`/${locale}/ai-chat`} className="quick-card card-4">
                  <div className="quick-card-glow"></div>
                  <div className="quick-card-inner">
                    <div className="quick-icon-wrapper">
                      <div className="quick-icon-bg"></div>
                      <div className="quick-icon-pulse"></div>
                      <span className="quick-icon">🤖</span>
                    </div>
                    <div className="quick-card-content">
                      <h3>هوش مصنوعی</h3>
                      <p>مشاوره هوشمند و پاسخ به سوالات پزشکی</p>
                      <div className="quick-features">
                        <span>✓ پاسخ‌دهی سریع</span>
                        <span>✓ ۲۴/۷ در دسترس</span>
                      </div>
                    </div>
                    <div className="quick-footer">
                      <span className="quick-arrow">→</span>
                      <span className="quick-badge">جدید</span>
                    </div>
                  </div>
                  <div className="quick-shine"></div>
                  <div className="quick-border-animation"></div>
                </Link>
              </div>
            </div>
          </section>

          {/* بخش پزشکان برتر */}
          {/* بخش پزشکان برتر - نسخه حرفه‌ای با عکس و آواتار */}
          <section className="container section" style={{ marginBottom: '48px' }}>
            <div className="section-header">
              <div className="section-header-left">
                <h2>
                  <i className="fas fa-star" style={{ color: '#f59e0b' }} /> پزشکان برتر
                </h2>
                <span className="tag">پرامتیاز</span>
                <span className="tag hot">{doctors.length} پزشک</span>
              </div>
              <Link href={`/${locale}/doctors`} className="view-all-link">
                مشاهده همه <i className="fas fa-chevron-left" />
              </Link>
            </div>

            {doctors.length > 0 ? (
                <div className="doctors-grid">
                  {doctors.slice(0, 5).map((doctor, index) => (
                      <div key={doctor.id} className="doctor-card">
                        {/* نشان ویژه برای دکتر اول */}
                        {index === 0 && (
                            <div className="doctor-featured-badge">
                              <span>⭐ ویژه</span>
                            </div>
                        )}

                        {/* نشان جدید برای دکتر دوم */}
                        {index === 1 && (
                            <div className="doctor-featured-badge new">
                              <span>🆕 جدید</span>
                            </div>
                        )}

                        {/* تصویر/آواتار دکتر با افکت حباب */}
                        <div className="doctor-image-wrapper">
                          <div className="doctor-image-ring"></div>
                          {doctor.profile_image ? (
                              <img
                                  src={doctor.profile_image}
                                  alt={doctor.user?.name || doctor.full_name || 'پزشک'}
                                  className="doctor-image"
                                  onError={(e) => {
                                    e.target.style.display = 'none';
                                    e.target.parentElement.querySelector('.doctor-avatar-fallback').style.display = 'flex';
                                  }}
                              />
                          ) : null}
                          <div className="doctor-avatar-fallback" style={{ display: doctor.profile_image ? 'none' : 'flex' }}>
                            {doctor.user?.name?.charAt(0) || doctor.full_name?.charAt(0) || '👨‍⚕️'}
                          </div>
                          {/* وضعیت آنلاین/آفلاین */}
                          <div className={`doctor-status-dot ${doctor.is_available ? 'online' : 'offline'}`}>
                            <span className="status-tooltip">{doctor.is_available ? 'آنلاین' : 'آفلاین'}</span>
                          </div>
                        </div>

                        {/* اطلاعات پزشک */}
                        <div className="doctor-info-content">
                          <h3 className="doctor-name">
                            {doctor.user?.name || doctor.full_name || 'پزشک'}
                          </h3>
                          <div className="doctor-specialty">
                            <i className="fas fa-stethoscope"></i>
                            {doctor.specialty?.name || 'تخصص'}
                          </div>
                          <div className="doctor-clinic">
                            <i className="fas fa-map-marker-alt"></i>
                            {doctor.clinic_name || 'آدرس مطب'}
                          </div>

                          {/* امتیاز و نظرات */}
                          <div className="doctor-rating-section">
                            <div className="doctor-stars">
                              <Rate
                                  disabled
                                  defaultValue={parseFloat(doctor.rating) || 0}
                                  allowHalf
                                  style={{ fontSize: '14px', color: '#f59e0b' }}
                              />
                              <span className="rating-number">{parseFloat(doctor.rating).toFixed(1) || '۰'}</span>
                            </div>
                            <span className="reviews-count">
                <i className="fas fa-comment"></i>
                              {doctor.total_reviews || 0} نظر
              </span>
                          </div>

                          {/* هزینه */}
                          <div className="doctor-fee-section">
              <span className="fee-amount">
                {parseInt(doctor.consultation_fee || 0).toLocaleString()}
              </span>
                            <span className="fee-label">تومان</span>
                          </div>

                          {/* دکمه‌های اقدام */}
                          <div className="doctor-actions">
                            <Button
                                type="primary"
                                className="btn-book"
                                onClick={() => handleBookAppointment(doctor.id)}
                                icon={<i className="fas fa-calendar-check"></i>}
                            >
                              رزرو نوبت
                            </Button>
                            <Button
                                className="btn-profile"
                                onClick={() => router.push(`/${locale}/doctors/${doctor.id}`)}
                                icon={<i className="fas fa-user"></i>}
                            >
                              پروفایل
                            </Button>
                          </div>
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
        <style jsx>{`
          /* ===== Quick Access Cards ===== */
          .quick-access {
            padding: 60px 0 70px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
          }

          /* پس‌زمینه دکوراتیو */
          .quick-access::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatBg 20s ease-in-out infinite;
          }

          .quick-access::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatBg 25s ease-in-out infinite reverse;
          }

          @keyframes floatBg {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.1); }
          }

          /* هدر بخش */
          .quick-section-header {
            text-align: center;
            margin-bottom: 40px;
          }

          .quick-section-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(139, 92, 246, 0.1));
            color: #2563eb;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
          }

          .quick-section-title h2 {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
          }

          .quick-section-title p {
            color: #64748b;
            font-size: 16px;
            margin: 0;
          }

          .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            position: relative;
            z-index: 1;
          }

          .quick-card {
            position: relative;
            display: block;
            background: #ffffff;
            border-radius: 24px;
            padding: 0;
            text-decoration: none;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            cursor: pointer;
          }

          .quick-card-inner {
            padding: 32px 24px 28px;
            position: relative;
            z-index: 2;
            background: #ffffff;
          }

          /* Glow Effect on Hover */
          .quick-card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.03) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.6s ease;
            pointer-events: none;
            z-index: 0;
          }

          .quick-card:hover .quick-card-glow {
            opacity: 1;
          }

          /* Border Animation */
          .quick-border-animation {
            position: absolute;
            inset: 0;
            border-radius: 24px;
            padding: 2px;
            background: linear-gradient(135deg, transparent 40%, rgba(37, 99, 235, 0.3) 50%, transparent 60%);
            background-size: 200% 200%;
            opacity: 0;
            transition: opacity 0.5s ease;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
          }

          .quick-card:hover .quick-border-animation {
            opacity: 1;
            animation: borderSpin 3s ease-in-out infinite;
          }

          @keyframes borderSpin {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
          }

          /* Shine Effect */
          .quick-shine {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, transparent 60%);
            opacity: 0;
            transform: rotate(25deg) translateY(-100%);
            transition: all 0.8s ease;
            pointer-events: none;
            z-index: 1;
          }

          .quick-card:hover .quick-shine {
            opacity: 0.6;
            transform: rotate(25deg) translateY(100%);
          }

          /* Card Colors */
          .card-1 { border-color: rgba(37, 99, 235, 0.15); }
          .card-1 .quick-icon-bg { background: linear-gradient(135deg, #2563eb, #3b82f6); }
          .card-1 .quick-card-glow { background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, transparent 60%); }
          .card-1:hover { border-color: #2563eb; box-shadow: 0 16px 48px rgba(37, 99, 235, 0.2); }
          .card-1 .quick-border-animation { background: linear-gradient(135deg, transparent 40%, #2563eb 50%, transparent 60%); }

          .card-2 { border-color: rgba(16, 185, 129, 0.15); }
          .card-2 .quick-icon-bg { background: linear-gradient(135deg, #10b981, #34d399); }
          .card-2 .quick-card-glow { background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 60%); }
          .card-2:hover { border-color: #10b981; box-shadow: 0 16px 48px rgba(16, 185, 129, 0.2); }
          .card-2 .quick-border-animation { background: linear-gradient(135deg, transparent 40%, #10b981 50%, transparent 60%); }

          .card-3 { border-color: rgba(139, 92, 246, 0.15); }
          .card-3 .quick-icon-bg { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
          .card-3 .quick-card-glow { background: radial-gradient(circle, rgba(139, 92, 246, 0.08) 0%, transparent 60%); }
          .card-3:hover { border-color: #8b5cf6; box-shadow: 0 16px 48px rgba(139, 92, 246, 0.2); }
          .card-3 .quick-border-animation { background: linear-gradient(135deg, transparent 40%, #8b5cf6 50%, transparent 60%); }

          .card-4 { border-color: rgba(245, 158, 11, 0.15); }
          .card-4 .quick-icon-bg { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
          .card-4 .quick-card-glow { background: radial-gradient(circle, rgba(245, 158, 11, 0.08) 0%, transparent 60%); }
          .card-4:hover { border-color: #f59e0b; box-shadow: 0 16px 48px rgba(245, 158, 11, 0.2); }
          .card-4 .quick-border-animation { background: linear-gradient(135deg, transparent 40%, #f59e0b 50%, transparent 60%); }

          .quick-card:hover {
            transform: translateY(-12px) scale(1.01);
          }

          /* Icon */
          .quick-icon-wrapper {
            position: relative;
            width: 80px;
            height: 80px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
          }

          .quick-icon-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            opacity: 0.12;
            transition: all 0.4s ease;
            transform: rotate(0deg) scale(1);
          }

          .quick-card:hover .quick-icon-bg {
            transform: rotate(10deg) scale(1.1);
            opacity: 0.2;
          }

          .quick-icon-pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            border: 2px solid transparent;
            opacity: 0;
            transition: all 0.5s ease;
          }

          .card-1 .quick-icon-pulse { border-color: rgba(37, 99, 235, 0.3); }
          .card-2 .quick-icon-pulse { border-color: rgba(16, 185, 129, 0.3); }
          .card-3 .quick-icon-pulse { border-color: rgba(139, 92, 246, 0.3); }
          .card-4 .quick-icon-pulse { border-color: rgba(245, 158, 11, 0.3); }

          .quick-card:hover .quick-icon-pulse {
            opacity: 1;
            animation: pulseRing 1.5s ease-out infinite;
          }

          @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.3); opacity: 0; }
          }

          .quick-icon {
            position: relative;
            font-size: 38px;
            z-index: 1;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
          }

          .quick-card:hover .quick-icon {
            transform: scale(1.15) rotate(-8deg);
          }

          /* Content */
          .quick-card-content {
            margin-bottom: 16px;
          }

          .quick-card-content h3 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
            line-height: 1.3;
          }

          .quick-card-content p {
            font-size: 14px;
            color: #64748b;
            margin: 0 0 10px 0;
            line-height: 1.6;
          }

          .quick-features {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
          }

          .quick-features span {
            font-size: 12px;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
          }

          .quick-card:hover .quick-features span {
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
          }

          .card-2:hover .quick-features span { background: rgba(16, 185, 129, 0.08); color: #10b981; }
          .card-3:hover .quick-features span { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }
          .card-4:hover .quick-features span { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }

          /* Footer */
          .quick-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 2px solid #f1f5f9;
            transition: border-color 0.3s ease;
          }

          .quick-card:hover .quick-footer {
            border-color: #e2e8f0;
          }

          .quick-arrow {
            font-size: 22px;
            color: #94a3b8;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 300;
          }

          .quick-card:hover .quick-arrow {
            color: #2563eb;
            transform: translateX(-8px);
          }

          .card-2:hover .quick-arrow { color: #10b981; }
          .card-3:hover .quick-arrow { color: #8b5cf6; }
          .card-4:hover .quick-arrow { color: #f59e0b; }

          .quick-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 16px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
          }

          .card-1:hover .quick-badge {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
          }
          .card-2:hover .quick-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
          }
          .card-3:hover .quick-badge {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
          }
          .card-4:hover .quick-badge {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
          }

          /* Responsive */
          @media (max-width: 1024px) {
            .quick-grid {
              grid-template-columns: repeat(2, 1fr);
              gap: 20px;
            }
            .quick-section-title h2 {
              font-size: 28px;
            }
          }

          @media (max-width: 768px) {
            .quick-access {
              padding: 40px 0 50px;
            }
            .quick-grid {
              grid-template-columns: repeat(2, 1fr);
              gap: 16px;
            }
            .quick-card-inner {
              padding: 24px 18px 20px;
            }
            .quick-icon-wrapper {
              width: 64px;
              height: 64px;
            }
            .quick-icon {
              font-size: 30px;
            }
            .quick-card-content h3 {
              font-size: 17px;
            }
            .quick-card-content p {
              font-size: 13px;
            }
            .quick-features span {
              font-size: 11px;
              padding: 2px 8px;
            }
            .quick-section-title h2 {
              font-size: 24px;
            }
          }

          @media (max-width: 480px) {
            .quick-grid {
              grid-template-columns: repeat(2, 1fr);
              gap: 12px;
            }
            .quick-card-inner {
              padding: 18px 14px 16px;
            }
            .quick-icon-wrapper {
              width: 52px;
              height: 52px;
              margin-bottom: 12px;
            }
            .quick-icon {
              font-size: 24px;
            }
            .quick-card-content h3 {
              font-size: 15px;
            }
            .quick-card-content p {
              font-size: 12px;
              margin-bottom: 8px;
            }
            .quick-features {
              gap: 6px;
            }
            .quick-features span {
              font-size: 10px;
              padding: 1px 6px;
            }
            .quick-footer {
              padding-top: 10px;
            }
            .quick-arrow {
              font-size: 18px;
            }
            .quick-badge {
              font-size: 10px;
              padding: 3px 10px;
            }
            .quick-section-title h2 {
              font-size: 20px;
            }
            .quick-section-title p {
              font-size: 14px;
            }
          }

          /* ===== پزشکان برتر - نسخه حرفه‌ای ===== */
          .container.section {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 24px;
          }

          /* هدر بخش */
          .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 12px;
          }

          .section-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
          }

          .section-header-left h2 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
          }

          .section-header-left h2 i {
            font-size: 24px;
          }

          .tag {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
          }

          .tag.hot {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
          }

          .view-all-link {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #2563eb;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
          }

          .view-all-link:hover {
            color: #1d4ed8;
            transform: translateX(-4px);
          }

          .view-all-link i {
            font-size: 14px;
            transition: transform 0.3s ease;
          }

          .view-all-link:hover i {
            transform: translateX(-4px);
          }

          /* گرید پزشکان */
          .doctors-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px;
          }

          /* کارت پزشک */
          .doctor-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px 20px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
          }

          .doctor-card:hover {
            transform: translateY(-8px);
            border-color: #2563eb;
            box-shadow: 0 16px 48px rgba(37, 99, 235, 0.12);
          }

          .doctor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #7c3aed);
            opacity: 0;
            transition: opacity 0.4s ease;
          }

          .doctor-card:hover::before {
            opacity: 1;
          }

          /* نشان ویژه */
          .doctor-featured-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #78350f;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            animation: featuredPulse 2s ease-in-out infinite;
          }

          .doctor-featured-badge.new {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: #fff;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
            animation: featuredPulse 2s ease-in-out infinite 0.5s;
          }

          @keyframes featuredPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
          }

          /* تصویر پزشک */
          .doctor-image-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 16px;
          }

          .doctor-image-ring {
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            opacity: 0.15;
            transition: all 0.4s ease;
          }

          .doctor-card:hover .doctor-image-ring {
            opacity: 0.3;
            transform: scale(1.05);
          }

          .doctor-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
          }

          .doctor-card:hover .doctor-image {
            transform: scale(1.05);
            border-color: #2563eb;
          }

          .doctor-avatar-fallback {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #fff;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.2);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
          }

          .doctor-card:hover .doctor-avatar-fallback {
            transform: scale(1.05);
          }

          /* وضعیت آنلاین */
          .doctor-status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid #fff;
            z-index: 5;
            transition: all 0.3s ease;
          }

          .doctor-status-dot.online {
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
          }

          .doctor-status-dot.offline {
            background: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
          }

          .doctor-status-dot .status-tooltip {
            position: absolute;
            bottom: calc(100% + 8px);
            right: 50%;
            transform: translateX(50%) scale(0.8);
            background: #0f172a;
            color: #fff;
            padding: 2px 10px;
            border-radius: 6px;
            font-size: 10px;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
          }

          .doctor-status-dot:hover .status-tooltip {
            opacity: 1;
            transform: translateX(50%) scale(1);
          }

          /* اطلاعات پزشک */
          .doctor-info-content {
            margin-top: 4px;
          }

          .doctor-name {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
          }

          .doctor-specialty {
            font-size: 14px;
            color: #2563eb;
            font-weight: 500;
            margin-bottom: 4px;
          }

          .doctor-specialty i {
            margin-left: 6px;
            font-size: 12px;
          }

          .doctor-clinic {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 12px;
          }

          .doctor-clinic i {
            margin-left: 6px;
            font-size: 12px;
          }

          /* امتیاز */
          .doctor-rating-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 12px;
          }

          .doctor-stars {
            display: flex;
            align-items: center;
            gap: 4px;
          }

          .rating-number {
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
          }

          .reviews-count {
            font-size: 13px;
            color: #94a3b8;
          }

          .reviews-count i {
            margin-left: 4px;
          }

          /* هزینه */
          .doctor-fee-section {
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: inline-block;
          }

          .fee-amount {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
          }

          .fee-label {
            font-size: 13px;
            color: #64748b;
            margin-right: 4px;
          }

          /* دکمه‌ها */
          .doctor-actions {
            display: flex;
            gap: 8px;
            flex-direction: column;
          }

          .btn-book {
            width: 100%;
            border-radius: 12px;
            height: 40px;
            font-weight: 600;
            font-size: 14px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
            transition: all 0.3s ease;
          }

          .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
          }

          .btn-book i {
            margin-left: 6px;
          }

          .btn-profile {
            width: 100%;
            border-radius: 12px;
            height: 40px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid #e2e8f0;
            color: #475569;
            background: transparent;
            transition: all 0.3s ease;
          }

          .btn-profile:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.04);
          }

          .btn-profile i {
            margin-left: 6px;
          }

          /* Responsive */
          @media (max-width: 1200px) {
            .doctors-grid {
              grid-template-columns: repeat(4, 1fr);
              gap: 20px;
            }
          }

          @media (max-width: 992px) {
            .doctors-grid {
              grid-template-columns: repeat(3, 1fr);
              gap: 20px;
            }
            .section-header-left h2 {
              font-size: 24px;
            }
          }

          @media (max-width: 768px) {
            .doctors-grid {
              grid-template-columns: repeat(2, 1fr);
              gap: 16px;
            }
            .doctor-card {
              padding: 18px 14px 16px;
            }
            .doctor-image-wrapper {
              width: 80px;
              height: 80px;
            }
            .doctor-avatar-fallback {
              font-size: 32px;
            }
            .doctor-name {
              font-size: 15px;
            }
            .doctor-specialty {
              font-size: 13px;
            }
            .doctor-clinic {
              font-size: 12px;
            }
            .fee-amount {
              font-size: 16px;
            }
            .section-header {
              flex-direction: column;
              align-items: flex-start;
            }
            .section-header-left h2 {
              font-size: 20px;
            }
          }

          @media (max-width: 480px) {
            .doctors-grid {
              grid-template-columns: 1fr 1fr;
              gap: 12px;
            }
            .doctor-image-wrapper {
              width: 64px;
              height: 64px;
            }
            .doctor-avatar-fallback {
              font-size: 28px;
            }
            .doctor-name {
              font-size: 13px;
            }
            .doctor-specialty {
              font-size: 11px;
            }
            .doctor-clinic {
              font-size: 11px;
            }
            .doctor-rating-section {
              flex-direction: column;
              gap: 4px;
            }
            .doctor-stars {
              font-size: 12px;
            }
            .fee-amount {
              font-size: 14px;
            }
            .btn-book, .btn-profile {
              font-size: 12px;
              height: 34px;
            }
            .doctor-featured-badge {
              font-size: 9px;
              padding: 2px 8px;
            }
            .doctor-status-dot {
              width: 14px;
              height: 14px;
              bottom: 0;
              right: 0;
            }
          }
        `}</style>
      </>
  );
}