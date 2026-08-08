'use client';

import Link from 'next/link';
import {
  InstagramOutlined,
  LinkedinOutlined,
  MailOutlined,
  PhoneOutlined,
  SafetyCertificateOutlined,
  SendOutlined,
  WhatsAppOutlined,
} from '@ant-design/icons';

import { useLanguage } from '@/lib/context/LanguageContext';
import { getHomeContent } from './homeContent';

import styles from './DoctorWebHome.module.css';

export default function HomeFooter() {
  const { locale = 'fa' } = useLanguage();
  const copy = getHomeContent(locale);

  return (
    <footer className={styles.footer}>
      <div className={styles.container}>
        <div className={styles.footerGrid}>
          <div className={styles.footerBrand}>
            <Link href="/" className={styles.footerLogo}>
              {copy.brand}
            </Link>

            <p>{copy.footer.description}</p>

            <div className={styles.footerTrust}>
              <span>
                <SafetyCertificateOutlined />
                {copy.footer.secure}
              </span>

              <span>{copy.footer.alwaysOn}</span>
            </div>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.services}</h3>
            <Link href="/doctors">{copy.nav.doctors}</Link>
            <Link href="/home-doctor">{copy.nav.homeDoctor}</Link>
            <Link href="/pharmacy">{copy.nav.pharmacy}</Link>
            <Link href="/medical-tourism">{copy.nav.tourism}</Link>
            <Link href="/map">{copy.nav.map}</Link>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.company}</h3>
            <Link href="/about">{locale === 'en' ? 'About us' : locale === 'ar' ? 'من نحن' : 'درباره ما'}</Link>
            <Link href="/contact">{locale === 'en' ? 'Contact us' : locale === 'ar' ? 'اتصل بنا' : 'تماس با ما'}</Link>
            <Link href="/cooperation">{locale === 'en' ? 'Work with us' : locale === 'ar' ? 'تعاون معنا' : 'همکاری با ما'}</Link>
            <Link href="/advertising">{copy.advertise}</Link>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.support}</h3>
            <Link href="/faq">{locale === 'en' ? 'FAQ' : locale === 'ar' ? 'الأسئلة الشائعة' : 'سؤالات متداول'}</Link>
            <Link href="/help">{locale === 'en' ? 'User guide' : locale === 'ar' ? 'دليل الاستخدام' : 'راهنمای استفاده'}</Link>
            <Link href="/privacy">{locale === 'en' ? 'Privacy' : locale === 'ar' ? 'الخصوصية' : 'حریم خصوصی'}</Link>
            <Link href="/terms">{locale === 'en' ? 'Terms' : locale === 'ar' ? 'الشروط والأحكام' : 'قوانین و مقررات'}</Link>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.contact}</h3>

            <a href="tel:02191000000">
              <PhoneOutlined />
              ۰۲۱-۹۱۰۰۰۰۰۰
            </a>

            <a href="mailto:info@doctorweb.ir">
              <MailOutlined />
              info@doctorweb.ir
            </a>

            <span>{copy.footer.location}</span>

            <div className={styles.socialLinks}>
              <a href="#" aria-label="Instagram">
                <InstagramOutlined />
              </a>

              <a href="#" aria-label="WhatsApp">
                <WhatsAppOutlined />
              </a>

              <a href="#" aria-label="Telegram">
                <SendOutlined />
              </a>

              <a href="#" aria-label="LinkedIn">
                <LinkedinOutlined />
              </a>
            </div>
          </div>
        </div>

        <div className={styles.footerBottom}>
          <span>{copy.footer.copyright}</span>

          <div>
            <Link href="/privacy">{locale === 'en' ? 'Privacy' : locale === 'ar' ? 'الخصوصية' : 'حریم خصوصی'}</Link>
            <Link href="/terms">{locale === 'en' ? 'Terms' : locale === 'ar' ? 'الشروط' : 'قوانین'}</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
