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
import Services from "@/components/front/Services/Services";
// import Services from "@/components/front/Services/Services";

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
          <Services />
          {/* بخش پزشکان برتر */}
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
              <Link href={`/${locale}/pharmacy`} className="view-all-link">
                مشاهده همه <i className="fas fa-chevron-left" />
              </Link>
            </div>

            {drugs.length > 0 ? (
                <div className="pharmacy-scroll-wrapper">
                  <div className="pharmacy-scroll-container">
                    {drugs.map((drug) => (
                        <div key={drug.id} className="pharmacy-card">
                          <div className="pharmacy-card-inner">
                            <div className="pharmacy-card-icon">💊</div>
                            <div className="pharmacy-card-info">
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
                            <div className="pharmacy-card-actions">
                              <Button
                                  type="primary"
                                  disabled={drug.stock === 0}
                                  onClick={() => handleAddToCart(drug)}
                                  block
                                  size="small"
                              >
                                سفارش
                              </Button>
                              {drug.requires_prescription && (
                                  <Button
                                      type="default"
                                      onClick={() => router.push(`/${locale}/pharmacy`)}
                                      block
                                      size="small"
                                  >
                                    ارسال نسخه
                                  </Button>
                              )}
                            </div>
                          </div>
                        </div>
                    ))}
                  </div>
                </div>
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
          /* ===== Quick Access Cards - نسخه کامل و فوق‌العاده شیک ===== */
          .quick-access {
            padding: 70px 0 80px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
          }

          .quick-access::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.04) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatBg1 25s ease-in-out infinite;
          }

          .quick-access::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -5%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.04) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatBg2 30s ease-in-out infinite reverse;
          }

          @keyframes floatBg1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -20px) scale(1.1); }
            66% { transform: translate(-20px, 30px) scale(0.9); }
          }

          @keyframes floatBg2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-30px, 40px) scale(1.15); }
            66% { transform: translate(30px, -20px) scale(0.85); }
          }

          .quick-section-header {
            text-align: center;
            margin-bottom: 50px;
          }

          .quick-section-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(139, 92, 246, 0.08));
            color: #2563eb;
            padding: 6px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
            border: 1px solid rgba(37, 99, 235, 0.1);
            animation: badgePulse 3s ease-in-out infinite;
          }

          @keyframes badgePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
            50% { box-shadow: 0 0 20px rgba(37, 99, 235, 0.08); }
          }

          .badge-icon {
            display: inline-block;
            animation: iconBounce 2s ease-in-out infinite;
          }

          @keyframes iconBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2) rotate(-5deg); }
          }

          .quick-section-title h2 {
            font-size: 38px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
          }

          .quick-section-title h2 .highlight {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
          }

          .quick-section-title p {
            color: #64748b;
            font-size: 17px;
            margin: 0;
            max-width: 500px;
            margin: 0 auto;
          }

          .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 28px;
            position: relative;
            z-index: 1;
          }

          /* ===== کارت‌های جدید با طراحی مدرن ===== */
          .quick-card {
            position: relative;
            display: block;
            background: #ffffff;
            border-radius: 28px;
            padding: 0;
            text-decoration: none;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            border: 2px solid transparent;
            cursor: pointer;
            min-height: 340px;
          }

          .quick-card-inner {
            padding: 32px 28px 28px;
            position: relative;
            z-index: 2;
            background: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
          }

          /* ===== افکت Shimmer - نور عبوری ===== */
          .quick-card-shimmer {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.4) 0%, transparent 60%);
            opacity: 0;
            transform: rotate(25deg) translateY(-100%);
            transition: all 0.8s ease;
            pointer-events: none;
            z-index: 3;
          }

          .quick-card:hover .quick-card-shimmer {
            opacity: 1;
            transform: rotate(25deg) translateY(100%);
          }

          /* ===== افکت Orb - دایره نورانی ===== */
          .quick-card-orb {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            opacity: 0;
            transition: all 0.8s ease;
            pointer-events: none;
            z-index: 0;
            top: -60px;
            right: -60px;
          }

          .quick-card:hover .quick-card-orb {
            opacity: 1;
            transform: scale(1.5);
          }

          /* ===== الگوی پس‌زمینه ===== */
          .quick-card-bg-pattern {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: 0.04;
          }

          /* ===== Particle Effects ===== */
          .quick-card-particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
          }

          .quick-card-particles span {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            animation: particleFloat 8s ease-in-out infinite;
          }

          .quick-card-particles span:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
          .quick-card-particles span:nth-child(2) { top: 20%; right: 15%; animation-delay: 1.5s; }
          .quick-card-particles span:nth-child(3) { bottom: 30%; left: 5%; animation-delay: 3s; }
          .quick-card-particles span:nth-child(4) { bottom: 20%; right: 10%; animation-delay: 4.5s; }
          .quick-card-particles span:nth-child(5) { top: 50%; left: 50%; animation-delay: 6s; }

          @keyframes particleFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0; }
            50% { transform: translate(30px, -30px) scale(2); opacity: 1; }
          }

          /* ===== Glow Effect ===== */
          .quick-card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            opacity: 0;
            transition: opacity 0.8s ease;
            pointer-events: none;
            z-index: 0;
          }

          .quick-card:hover .quick-card-glow {
            opacity: 1;
          }

          /* ===== Hover Line ===== */
          .quick-hover-line {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            z-index: 3;
          }

          .quick-card:hover .quick-hover-line {
            transform: scaleX(1);
          }

          /* ===== Border Animation ===== */
          .quick-border-animation {
            position: absolute;
            inset: 0;
            border-radius: 28px;
            padding: 2px;
            background: linear-gradient(135deg, transparent 30%, rgba(37, 99, 235, 0.2) 50%, transparent 70%);
            background-size: 300% 300%;
            opacity: 0;
            transition: opacity 0.6s ease;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
            z-index: 3;
          }

          .quick-card:hover .quick-border-animation {
            opacity: 1;
            animation: borderSpin 4s ease-in-out infinite;
          }

          @keyframes borderSpin {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
          }

          /* ===== Shine Effect ===== */
          .quick-shine {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, transparent 50%);
            opacity: 0;
            transform: rotate(35deg) translateY(-100%);
            transition: all 0.9s ease;
            pointer-events: none;
            z-index: 3;
          }

          .quick-card:hover .quick-shine {
            opacity: 0.5;
            transform: rotate(35deg) translateY(100%);
          }

          /* ===== رنگ‌های هر کارت ===== */
          .card-1 {
            border-color: rgba(37, 99, 235, 0.1);
          }
          .card-1 .quick-icon-bg {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
          }
          .card-1 .quick-card-glow {
            background: radial-gradient(circle, rgba(37, 99, 235, 0.06) 0%, transparent 60%);
          }
          .card-1:hover {
            border-color: #2563eb;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.15);
          }
          .card-1 .quick-hover-line {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
          }
          .card-1 .quick-card-orb {
            background: radial-gradient(circle, rgba(37, 99, 235, 0.08), transparent 70%);
          }
          .card-1 .quick-card-bg-pattern {
            background-image: radial-gradient(circle at 20% 50%, #2563eb 1px, transparent 1px);
            background-size: 20px 20px;
          }
          .card-1 .quick-card-particles span {
            background: rgba(37, 99, 235, 0.15);
          }
          .card-1 .quick-border-animation {
            background: linear-gradient(135deg, transparent 30%, #2563eb 50%, transparent 70%);
          }

          .card-2 {
            border-color: rgba(16, 185, 129, 0.1);
          }
          .card-2 .quick-icon-bg {
            background: linear-gradient(135deg, #10b981, #34d399);
          }
          .card-2 .quick-card-glow {
            background: radial-gradient(circle, rgba(16, 185, 129, 0.06) 0%, transparent 60%);
          }
          .card-2:hover {
            border-color: #10b981;
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.15);
          }
          .card-2 .quick-hover-line {
            background: linear-gradient(90deg, #10b981, #34d399);
          }
          .card-2 .quick-card-orb {
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08), transparent 70%);
          }
          .card-2 .quick-card-bg-pattern {
            background-image: radial-gradient(circle at 20% 50%, #10b981 1px, transparent 1px);
            background-size: 20px 20px;
          }
          .card-2 .quick-card-particles span {
            background: rgba(16, 185, 129, 0.15);
          }
          .card-2 .quick-border-animation {
            background: linear-gradient(135deg, transparent 30%, #10b981 50%, transparent 70%);
          }

          .card-3 {
            border-color: rgba(139, 92, 246, 0.1);
          }
          .card-3 .quick-icon-bg {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
          }
          .card-3 .quick-card-glow {
            background: radial-gradient(circle, rgba(139, 92, 246, 0.06) 0%, transparent 60%);
          }
          .card-3:hover {
            border-color: #8b5cf6;
            box-shadow: 0 20px 60px rgba(139, 92, 246, 0.15);
          }
          .card-3 .quick-hover-line {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
          }
          .card-3 .quick-card-orb {
            background: radial-gradient(circle, rgba(139, 92, 246, 0.08), transparent 70%);
          }
          .card-3 .quick-card-bg-pattern {
            background-image: radial-gradient(circle at 20% 50%, #8b5cf6 1px, transparent 1px);
            background-size: 20px 20px;
          }
          .card-3 .quick-card-particles span {
            background: rgba(139, 92, 246, 0.15);
          }
          .card-3 .quick-border-animation {
            background: linear-gradient(135deg, transparent 30%, #8b5cf6 50%, transparent 70%);
          }

          .card-4 {
            border-color: rgba(245, 158, 11, 0.1);
          }
          .card-4 .quick-icon-bg {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
          }
          .card-4 .quick-card-glow {
            background: radial-gradient(circle, rgba(245, 158, 11, 0.06) 0%, transparent 60%);
          }
          .card-4:hover {
            border-color: #f59e0b;
            box-shadow: 0 20px 60px rgba(245, 158, 11, 0.15);
          }
          .card-4 .quick-hover-line {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
          }
          .card-4 .quick-card-orb {
            background: radial-gradient(circle, rgba(245, 158, 11, 0.08), transparent 70%);
          }
          .card-4 .quick-card-bg-pattern {
            background-image: radial-gradient(circle at 20% 50%, #f59e0b 1px, transparent 1px);
            background-size: 20px 20px;
          }
          .card-4 .quick-card-particles span {
            background: rgba(245, 158, 11, 0.15);
          }
          .card-4 .quick-border-animation {
            background: linear-gradient(135deg, transparent 30%, #f59e0b 50%, transparent 70%);
          }

          .quick-card:hover {
            transform: translateY(-16px) scale(1.01);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.12);
          }

          /* ===== قسمت بالای کارت ===== */
          .quick-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
          }

          /* ===== Badge برچسب ===== */
          .quick-card-badge-wrapper {
            flex-shrink: 0;
          }

          .quick-card-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
          }

          .card-1 .quick-card-badge {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
          }

          .card-2 .quick-card-badge.hot {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
          }

          .card-3 .quick-card-badge.new {
            background: linear-gradient(135deg, #ede9fe, #c4b5fd);
            color: #7c3aed;
          }

          .card-4 .quick-card-badge.ai {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            color: #0284c7;
          }

          /* ===== آیکون ===== */
          .quick-icon-wrapper {
            position: relative;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
          }

          .quick-icon-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            opacity: 0.1;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            transform: rotate(0deg) scale(1);
          }

          .quick-card:hover .quick-icon-bg {
            transform: rotate(10deg) scale(1.1);
            opacity: 0.2;
          }

          .quick-icon-ripple {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            border: 2px solid transparent;
            opacity: 0;
            transition: all 0.6s ease;
          }

          .quick-card:hover .quick-icon-ripple {
            opacity: 1;
            animation: rippleExpand 1.8s ease-out infinite;
          }

          @keyframes rippleExpand {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.4); opacity: 0; }
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

          .quick-card:hover .quick-icon-pulse {
            opacity: 1;
            animation: pulseRing 2s ease-out infinite;
          }

          @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.5); opacity: 0; }
          }

          .quick-icon {
            position: relative;
            font-size: 36px;
            z-index: 1;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
          }

          .quick-card:hover .quick-icon {
            transform: scale(1.15) rotate(-8deg);
          }

          /* ===== محتوای کارت ===== */
          .quick-card-content {
            flex: 1;
            margin-bottom: 16px;
          }

          .card-number {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 2px;
            margin-bottom: 8px;
            font-family: 'Monaco', 'Menlo', monospace;
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
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 50px;
            transition: all 0.4s ease;
          }

          .quick-features span svg {
            stroke: #94a3b8;
            transition: stroke 0.4s ease;
          }

          .quick-card:hover .quick-features span {
            background: rgba(37, 99, 235, 0.06);
            color: #2563eb;
          }

          .quick-card:hover .quick-features span svg {
            stroke: #2563eb;
          }

          .card-2:hover .quick-features span {
            background: rgba(16, 185, 129, 0.06);
            color: #10b981;
          }
          .card-2:hover .quick-features span svg { stroke: #10b981; }

          .card-3:hover .quick-features span {
            background: rgba(139, 92, 246, 0.06);
            color: #8b5cf6;
          }
          .card-3:hover .quick-features span svg { stroke: #8b5cf6; }

          .card-4:hover .quick-features span {
            background: rgba(245, 158, 11, 0.06);
            color: #f59e0b;
          }
          .card-4:hover .quick-features span svg { stroke: #f59e0b; }

          /* ===== فوتر کارت ===== */
          .quick-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 2px solid #f1f5f9;
            transition: border-color 0.4s ease;
          }

          .quick-card:hover .quick-footer {
            border-color: #e2e8f0;
          }

          .quick-arrow svg {
            stroke: #94a3b8;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
          }

          .quick-card:hover .quick-arrow svg {
            stroke: #2563eb;
            transform: translateX(-6px);
          }

          .card-2:hover .quick-arrow svg { stroke: #10b981; }
          .card-3:hover .quick-arrow svg { stroke: #8b5cf6; }
          .card-4:hover .quick-arrow svg { stroke: #f59e0b; }

          .quick-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 16px;
            border-radius: 50px;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.4s ease;
            letter-spacing: 0.3px;
          }

          .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #94a3b8;
            transition: all 0.4s ease;
          }

          .quick-card:hover .quick-badge {
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
          }
          .quick-card:hover .badge-dot { background: #2563eb; }

          .card-2:hover .quick-badge {
            background: rgba(16, 185, 129, 0.08);
            color: #10b981;
          }
          .card-2:hover .badge-dot { background: #10b981; }

          .card-3:hover .quick-badge {
            background: rgba(139, 92, 246, 0.08);
            color: #8b5cf6;
          }
          .card-3:hover .badge-dot { background: #8b5cf6; }

          .card-4:hover .quick-badge {
            background: rgba(245, 158, 11, 0.08);
            color: #f59e0b;
          }
          .card-4:hover .badge-dot { background: #f59e0b; }

          /* ===== مشاهده همه ===== */
          .quick-view-all {
            text-align: center;
            margin-top: 48px;
          }

          .view-all-services {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2563eb;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            padding: 10px 28px;
            border-radius: 50px;
            border: 2px solid rgba(37, 99, 235, 0.15);
            transition: all 0.4s ease;
            background: rgba(37, 99, 235, 0.02);
          }

          .view-all-services:hover {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
            transform: translateX(-4px);
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.2);
          }

          .view-all-services svg {
            transition: transform 0.4s ease;
          }

          .view-all-services:hover svg {
            transform: translateX(-4px);
          }

          /* ===== Responsive ===== */
          @media (max-width: 1024px) {
            .quick-grid {
              grid-template-columns: repeat(2, 1fr);
              gap: 24px;
            }
            .quick-section-title h2 {
              font-size: 32px;
            }
            .quick-card-inner {
              padding: 30px 24px 26px;
            }
          }

          @media (max-width: 768px) {
            .quick-access {
              padding: 50px 0 60px;
            }
            .quick-grid {
              grid-template-columns: 1fr 1fr;
              gap: 18px;
            }
            .quick-card-inner {
              padding: 24px 18px 20px;
            }
            .quick-icon-wrapper {
              width: 60px;
              height: 60px;
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
              padding: 3px 10px;
            }
            .quick-section-title h2 {
              font-size: 28px;
            }
            .quick-section-title p {
              font-size: 15px;
            }
          }

          @media (max-width: 480px) {
            .quick-access {
              padding: 40px 0 50px;
            }
            .quick-grid {
              grid-template-columns: 1fr 1fr;
              gap: 14px;
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
              font-size: 13px;
            }
          }

          /* ===== پزشکان برتر ===== */
          .container.section {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 24px;
          }

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

          .doctors-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px;
          }

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

          /* ===== بنرها ===== */
          .banners-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
          }

          .banner-card {
            padding: 32px 24px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
          }

          .banner-card:hover {
            transform: translateY(-6px);
          }

          .banner-card .icon {
            font-size: 42px;
            margin-bottom: 12px;
          }

          .banner-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
          }

          .banner-card p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
          }

          .banner-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
          }

          .banner-link:hover {
            transform: translateX(-4px);
          }

          .b1 {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-color: rgba(37, 99, 235, 0.1);
          }
          .b1:hover { border-color: #2563eb; }

          .b2 {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-color: rgba(16, 185, 129, 0.1);
          }
          .b2:hover { border-color: #10b981; }

          .b3 {
            background: linear-gradient(135deg, #faf5ff, #ede9fe);
            border-color: rgba(139, 92, 246, 0.1);
          }
          .b3:hover { border-color: #8b5cf6; }

          /* ===== اعتماد ===== */
          .trust-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
          }

          .trust-item {
            text-align: center;
            padding: 20px 12px;
            border-radius: 16px;
            background: #f8fafc;
            transition: all 0.3s ease;
            border: 2px solid transparent;
          }

          .trust-item:hover {
            background: #fff;
            border-color: #e2e8f0;
            transform: translateY(-4px);
          }

          .trust-item .icon {
            font-size: 28px;
            color: #2563eb;
            margin-bottom: 8px;
          }

          .trust-item h4 {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px 0;
          }

          .trust-item p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
          }

          /* ===== داروخانه آنلاین - اسکرول افقی ===== */
          .pharmacy-scroll-wrapper {
            position: relative;
            width: 100%;
            overflow: hidden;
            padding: 8px 0;
          }

          .pharmacy-scroll-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 8px 4px 16px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #2563eb #e2e8f0;
          }

          .pharmacy-scroll-container::-webkit-scrollbar {
            height: 6px;
          }

          .pharmacy-scroll-container::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
          }

          .pharmacy-scroll-container::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #2563eb, #7c3aed);
            border-radius: 10px;
          }

          .pharmacy-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
          }

          .pharmacy-card {
            flex: 0 0 220px;
            min-width: 200px;
            background: #ffffff;
            border-radius: 16px;
            padding: 20px 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
          }

          .pharmacy-card:hover {
            transform: translateY(-4px);
            border-color: #10b981;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.12);
          }

          .pharmacy-card-inner {
            display: flex;
            flex-direction: column;
            height: 100%;
          }

          .pharmacy-card-icon {
            font-size: 40px;
            text-align: center;
            margin-bottom: 12px;
          }

          .pharmacy-card-info {
            flex: 1;
          }

          .pharmacy-card-info h4 {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
            line-height: 1.3;
            text-align: center;
          }

          .pharmacy-card-info .ant-tag {
            margin: 2px 4px 2px 0;
            font-size: 11px;
          }

          .prescription-tag {
            font-size: 10px !important;
          }

          .pharmacy-stock {
            font-size: 13px;
            color: #64748b;
            text-align: center;
            margin-top: 6px;
          }

          .pharmacy-stock .low-stock {
            color: #ef4444;
            font-weight: 600;
          }

          .pharmacy-price {
            text-align: center;
            margin-top: 8px;
            padding: 6px 0;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
          }

          .pharmacy-price small {
            font-size: 12px;
            font-weight: 400;
            color: #64748b;
          }

          .pharmacy-card-actions {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
          }

          .pharmacy-card-actions .ant-btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            height: 36px;
          }

          .pharmacy-card-actions .ant-btn-primary {
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
          }

          .pharmacy-card-actions .ant-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
          }

          .pharmacy-card-actions .ant-btn-primary:disabled {
            background: #d1d5db;
          }

          /* فلش‌های ناوبری اسکرول */
          .pharmacy-scroll-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: #475569;
          }

          .pharmacy-scroll-nav:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.2);
          }

          .pharmacy-scroll-nav.prev {
            left: -12px;
          }

          .pharmacy-scroll-nav.next {
            right: -12px;
          }

          /* ===== دکمه شناور پشتیبانی ===== */
          .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
          }

          .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.4);
          }

          /* ===== Responsive ===== */
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
            .banners-grid {
              grid-template-columns: repeat(2, 1fr);
            }
            .trust-grid {
              grid-template-columns: repeat(3, 1fr);
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
            .banners-grid {
              grid-template-columns: 1fr;
            }
            .trust-grid {
              grid-template-columns: repeat(2, 1fr);
            }
            .pharmacy-scroll-nav {
              display: none;
            }
            .pharmacy-card {
              flex: 0 0 170px;
              min-width: 160px;
              padding: 16px 12px;
            }
            .pharmacy-card-icon {
              font-size: 32px;
            }
            .pharmacy-card-info h4 {
              font-size: 13px;
            }
            .pharmacy-price {
              font-size: 15px;
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
            .pharmacy-card {
              flex: 0 0 150px;
              min-width: 140px;
              padding: 14px 10px;
            }
            .pharmacy-card-icon {
              font-size: 28px;
            }
            .pharmacy-card-info h4 {
              font-size: 12px;
            }
            .pharmacy-price {
              font-size: 13px;
            }
            .pharmacy-stock {
              font-size: 11px;
            }
            .trust-grid {
              grid-template-columns: repeat(2, 1fr);
            }
            .floating-btn {
              width: 48px;
              height: 48px;
              font-size: 20px;
              bottom: 20px;
              right: 20px;
            }
          }
        `}</style>
      </>
  );
}