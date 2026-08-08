'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

import {
  ArrowLeftOutlined,
  ArrowRightOutlined,
  CheckCircleFilled,
  EnvironmentOutlined,
  ExperimentOutlined,
  FileImageOutlined,
  GlobalOutlined,
  HomeOutlined,
  MedicineBoxOutlined,
  RobotOutlined,
  SearchOutlined,
  ShopOutlined,
  StarFilled,
  UserOutlined,
} from '@ant-design/icons';

import { useLanguage } from '@/lib/context/LanguageContext';
import { getHomeContent } from './homeContent';

import HomeHeader from './HomeHeader';
import AdvertisingSection from './AdvertisingSection';
import PharmacySection from './PharmacySection';
import HomeFooter from './HomeFooter';

import styles from './DoctorWebHome.module.css';

const ICONS = {
  doctors: MedicineBoxOutlined,
  homeDoctor: HomeOutlined,
  pharmacy: ShopOutlined,
  lab: ExperimentOutlined,
  imaging: FileImageOutlined,
  map: EnvironmentOutlined,
  tourism: GlobalOutlined,
  ai: RobotOutlined,
};


const SERVICE_IMAGES = {
  doctors: '/image/services/doctors.png',
  homeDoctor: '/image/services/home-doctor.png',

  pharmacy: '/image/services/pharmacy.png',
  lab: '/image/services/azmayesh.png',
  imaging: '/image/services/tasvirbardari.png',
  map: '/image/services/maps.png',
  tourism: '/image/services/tourism.png',
  ai: '/image/services/ai.png',
  blog: '/image/services/blog.png',
};

const SERVICE_IMAGE_STYLE = {
  display: 'block',
  width: '100%',
  aspectRatio: '400 / 228',
  objectFit: 'cover',
  borderRadius: '14px',
  marginBottom: '14px',
};

const FALLBACK_DOCTORS = [
  {
    id: 1,
    name: 'دکتر محمد رضایی',
    specialty: 'متخصص قلب و عروق',
    rating: 4.9,
  },
  {
    id: 2,
    name: 'دکتر سارا محمدی',
    specialty: 'متخصص پوست و مو',
    rating: 4.8,
  },
  {
    id: 3,
    name: 'دکتر علی احمدی',
    specialty: 'متخصص چشم‌پزشکی',
    rating: 4.9,
  },
  {
    id: 4,
    name: 'دکتر نازنین کریمی',
    specialty: 'متخصص زنان و زایمان',
    rating: 4.7,
  },
];

function normalizeDoctor(doctor) {
  return {
    id: doctor.id,
    name:
      doctor.user?.name ||
      doctor.full_name ||
      doctor.name ||
      'پزشک دکتر وب',
    specialty:
      doctor.specialty?.name ||
      doctor.specialty_name ||
      'پزشک متخصص',
    rating: Number(
      doctor.rating ||
        doctor.average_rating ||
        0,
    ),
    image:
      doctor.profile_image ||
      doctor.avatar_url ||
      doctor.user?.avatar_url ||
      null,
  };
}

export default function DoctorWebHome() {
  const router = useRouter();

  const {
    locale = 'fa',
    direction = 'rtl',
  } = useLanguage();

  const copy = getHomeContent(locale);
  const ui = {
    fa: { search: 'جستجو', quick: 'دسترسی سریع', treatment: 'پزشکان و درمان', profile: 'مشاهده پروفایل', verified: 'پزشکان معتبر', verifiedSub: 'تأیید اطلاعات حرفه‌ای', tehran: 'فعلاً ویژه تهران', homeText: 'نوع پزشک، آدرس، زمان مراجعه و توضیحات بیمار را ثبت کنید تا درخواست شما بررسی شود.', homeItems: ['انتخاب پزشک و زمان مراجعه', 'ثبت آدرس و موقعیت', 'بارگذاری مدارک پزشکی', 'پیگیری وضعیت درخواست'], homeCta: 'ثبت درخواست پزشک در منزل', emergency: 'این سرویس برای شرایط اورژانسی نیست.', mapEyebrow: 'جست‌وجوی مراکز', mapText: 'ابتدا شهر یا موقعیت خود را انتخاب کنید و سپس کلینیک‌ها، داروخانه‌ها، آزمایشگاه‌ها و مراکز تصویربرداری را مشاهده کنید.', mapCta: 'ورود به نقشه مراکز', international: 'بیماران بین‌المللی', tourismText: 'اطلاعات تماس، مدارک پزشکی و برنامه سفر خود را ثبت کنید تا کارشناس دکتر وب با شما تماس بگیرد.', tourismItems: ['ثبت مدارک پزشکی', 'انتخاب زبان ارتباطی', 'پیگیری توسط کارشناس', 'هماهنگی خدمات سفر'], tourismCta: 'ثبت درخواست درمان', popular: 'انتخاب سریع' },
    en: { search: 'Search', quick: 'Quick access', treatment: 'Doctors & care', profile: 'View profile', verified: 'Verified doctors', verifiedSub: 'Professional credentials checked', tehran: 'Currently available in Tehran', homeText: 'Provide the required specialist, address, preferred time, and patient details so we can review your request.', homeItems: ['Choose doctor and time', 'Provide address and location', 'Upload medical records', 'Track your request'], homeCta: 'Request a doctor at home', emergency: 'This service is not for emergencies.', mapEyebrow: 'Find healthcare centers', mapText: 'Choose your city or location to discover clinics, pharmacies, laboratories, and imaging centers.', mapCta: 'Open healthcare map', international: 'International patients', tourismText: 'Submit your contact details, medical records, and travel plan so a Doctor Web coordinator can contact you.', tourismItems: ['Upload medical records', 'Choose communication language', 'Coordinator follow-up', 'Travel service coordination'], tourismCta: 'Submit treatment request', popular: 'Quick selection' },
    ar: { search: 'بحث', quick: 'وصول سريع', treatment: 'الأطباء والعلاج', profile: 'عرض الملف', verified: 'أطباء موثقون', verifiedSub: 'تم التحقق من المؤهلات', tehran: 'متاح حالياً في طهران', homeText: 'أدخل تخصص الطبيب والعنوان والموعد ومعلومات المريض لمراجعة طلبك.', homeItems: ['اختيار الطبيب والموعد', 'إدخال العنوان والموقع', 'رفع التقارير الطبية', 'متابعة الطلب'], homeCta: 'طلب طبيب في المنزل', emergency: 'هذه الخدمة ليست للحالات الطارئة.', mapEyebrow: 'البحث عن المراكز', mapText: 'اختر مدينتك أو موقعك لعرض العيادات والصيدليات والمختبرات ومراكز التصوير.', mapCta: 'فتح خريطة المراكز', international: 'المرضى الدوليون', tourismText: 'أرسل معلومات الاتصال والتقارير الطبية وخطة السفر ليتواصل معك منسق دكتور ويب.', tourismItems: ['رفع التقارير الطبية', 'اختيار لغة التواصل', 'متابعة المنسق', 'تنسيق خدمات السفر'], tourismCta: 'إرسال طلب العلاج', popular: 'اختيار سريع' },
  }[locale] || {};
  const [query, setQuery] = useState('');
  const [doctors, setDoctors] = useState(FALLBACK_DOCTORS);

  const ArrowIcon =
    direction === 'rtl'
      ? ArrowLeftOutlined
      : ArrowRightOutlined;

  useEffect(() => {
    fetch('/backend-api/api/doctors?per_page=8')
      .then((response) => response.json())
      .then((response) => {
        const source =
          response?.data?.data ||
          response?.data ||
          response;

        if (Array.isArray(source) && source.length) {
          setDoctors(source.slice(0, 8).map(normalizeDoctor));
        }
      })
      .catch(() => {
        setDoctors(FALLBACK_DOCTORS);
      });
  }, []);

  const submitSearch = (event) => {
    event.preventDefault();

    const value = query.trim();

    if (value) {
      router.push(`/search?q=${encodeURIComponent(value)}`);
    }
  };

  const services = copy.services?.length
    ? copy.services
    : getHomeContent('fa').services;

  return (
    <div className={styles.page}>
      <HomeHeader />

      <main>
        <section className={styles.heroSection}>
          <div className={styles.container}>
            <div className={styles.hero}>
              <div className={styles.heroContent}>
                <span className={styles.heroBadge}>
                  <CheckCircleFilled />
                  {copy.hero.badge}
                </span>

                <h1>
                  {copy.hero.title}
                  <strong>{copy.hero.accent}</strong>
                </h1>

                <p>{copy.hero.description}</p>

                <form
                  className={styles.heroSearch}
                  onSubmit={submitSearch}
                >
                  <SearchOutlined />

                  <input
                    value={query}
                    onChange={(event) =>
                      setQuery(event.target.value)
                    }
                    placeholder={copy.search}
                  />

                  <button type="submit">{ui.search}</button>
                </form>

                <div className={styles.heroButtons}>
                  <Link href="/doctors">
                    {copy.hero.primary}
                    <ArrowIcon />
                  </Link>

                  <Link href="/home-doctor">
                    <HomeOutlined />
                    {copy.hero.secondary}
                  </Link>
                </div>

                <div className={styles.heroTrust}>
                  <span>
                    <CheckCircleFilled />
                    {copy.hero.verified}
                  </span>

                  <span>
                    <CheckCircleFilled />
                    {copy.hero.support}
                  </span>

                  <span>
                    <CheckCircleFilled />
                    {copy.hero.privacy}
                  </span>
                </div>
              </div>

              <div className={styles.heroImage}>

                <img
                  src="/image/bac-2.png"
                  alt="پزشک دکتر وب"
                />

                <div className={styles.heroFloatingCard}>
                  <CheckCircleFilled />

                  <span>
                    {ui.verified}
                    <small>{ui.verifiedSub}</small>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <div className={styles.container}>
          <section className={styles.section}>
            <div className={styles.sectionHeading}>
              <div>
                <span className={styles.sectionEyebrow}>
                  {ui.quick}
                </span>

                <h2>{copy.section.services}</h2>
                <p>{copy.section.servicesDescription}</p>
              </div>
            </div>

            <div className={styles.servicesGrid}>
              {services.map((service) => {
                const Icon =
                  ICONS[service.key] ||
                  MedicineBoxOutlined;
                const image = SERVICE_IMAGES[service.key];

                return (
                  <Link
                    key={service.key}
                    href={service.href}
                    className={styles.serviceCard}
                  >
                    {image ? (
                      <img
                        src={image}
                        alt={service.title}
                        width={400}
                        height={228}
                        loading="lazy"
                        decoding="async"
                        style={SERVICE_IMAGE_STYLE}
                      />
                    ) : (
                      <span className={styles.serviceIcon}>
                        <Icon />
                      </span>
                    )}

                    <strong>{service.title}</strong>
                    <p>{service.description}</p>

                    <ArrowIcon />
                  </Link>
                );
              })}
            </div>
          </section>

          <AdvertisingSection />

          <section className={styles.section}>
            <div className={styles.sectionHeading}>
              <div>
                <span className={styles.sectionEyebrow}>
                  {ui.treatment}
                </span>

                <h2>{copy.section.doctors}</h2>
                <p>{copy.section.doctorsDescription}</p>
              </div>

              <Link href="/doctors">
                {copy.section.viewAll}
              </Link>
            </div>

            <div className={styles.doctorsGrid}>
              {doctors.map((doctor) => (
                <article
                  key={doctor.id}
                  className={styles.doctorCard}
                >
                  <div className={styles.doctorAvatar}>
                    {doctor.image ? (
                      <img src={doctor.image} alt={doctor.name} />
                    ) : (
                      <UserOutlined />
                    )}
                  </div>

                  <h3>{doctor.name}</h3>
                  <p>{doctor.specialty}</p>

                  <div className={styles.doctorRating}>
                    <StarFilled />
                    <span>{doctor.rating || '—'}</span>
                  </div>

                  <Link href={`/doctors/${doctor.id}`}>
                    {ui.profile}
                  </Link>
                </article>
              ))}
            </div>
          </section>

          <section className={styles.homeDoctorBanner}>
            <div>
              <span className={styles.bannerLabel}>
                {ui.tehran}
              </span>

              <h2>{copy.section.homeDoctor}</h2>

              <p>{ui.homeText}</p>

              <ul>{ui.homeItems.map((item) => <li key={item}><CheckCircleFilled />{item}</li>)}</ul>

              <Link href="/home-doctor">
                {ui.homeCta}
              </Link>

              <small>
                {ui.emergency}
              </small>
            </div>

            <div className={styles.homeDoctorPhoto}>
              <img
                src="/image/services/inhome.png?v=10"
                alt={copy.section.homeDoctor}
              />
            </div>
          </section>

          <PharmacySection />

          <section className={styles.mapBanner}>
            <div>
              <span className={styles.sectionEyebrow}>
                {ui.mapEyebrow}
              </span>

              <h2>{copy.section.map}</h2>

              <p>{ui.mapText}</p>

              <Link href="/map">
                <EnvironmentOutlined />
                {ui.mapCta}
              </Link>
            </div>

            <div className={styles.fakeMap}>
              <span className={styles.mapPinOne}>
                <MedicineBoxOutlined />
              </span>

              <span className={styles.mapPinTwo}>
                <ShopOutlined />
              </span>

              <span className={styles.mapPinThree}>
                <ExperimentOutlined />
              </span>
            </div>
          </section>

          <section className={styles.tourismBanner}>
            <div className={styles.tourismPhoto}>
              <img
                src="/image/services/torist.png?v=30"
                alt={copy.section.tourism}
              />
            </div>

            <div>
              <span className={styles.sectionEyebrow}>
                {ui.international}
              </span>

              <h2>{copy.section.tourism}</h2>

              <p>{ui.tourismText}</p>

              <div className={styles.tourismFeatures}>{ui.tourismItems.map((item) => <span key={item}><CheckCircleFilled />{item}</span>)}</div>

              <Link href="/medical-tourism">
                {ui.tourismCta}
              </Link>
            </div>
          </section>

          <section className={styles.section}>
            <div className={styles.sectionHeading}>
              <div>
                <span className={styles.sectionEyebrow}>
                  {ui.popular}
                </span>

                <h2>{copy.section.specialties}</h2>
              </div>
            </div>

            <div className={styles.specialtiesGrid}>
              {copy.specialties.map((specialty) => (
                <Link
                  key={specialty}
                  href={`/search?q=${encodeURIComponent(
                    specialty,
                  )}`}
                >
                  <MedicineBoxOutlined />
                  <strong>{specialty}</strong>
                  <ArrowIcon />
                </Link>
              ))}
            </div>
          </section>
        </div>
      </main>

      <HomeFooter />
    </div>
  );
}

