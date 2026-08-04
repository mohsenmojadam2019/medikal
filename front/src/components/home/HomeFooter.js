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
              دکتر وب
            </Link>

            <p>{copy.footer.description}</p>

            <div className={styles.footerTrust}>
              <span>
                <SafetyCertificateOutlined />
                اطلاعات امن
              </span>

              <span>پشتیبانی شبانه‌روزی</span>
            </div>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.services}</h3>
            <Link href="/doctors">پزشکان</Link>
            <Link href="/home-doctor">پزشک در منزل</Link>
            <Link href="/pharmacy">داروخانه آنلاین</Link>
            <Link href="/medical-tourism">توریست درمانی</Link>
            <Link href="/map">نقشه مراکز</Link>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.company}</h3>
            <Link href="/about">درباره ما</Link>
            <Link href="/contact">تماس با ما</Link>
            <Link href="/cooperation">همکاری با ما</Link>
            <Link href="/advertising">تبلیغات در دکتر وب</Link>
          </div>

          <div className={styles.footerColumn}>
            <h3>{copy.footer.support}</h3>
            <Link href="/faq">سؤالات متداول</Link>
            <Link href="/help">راهنمای استفاده</Link>
            <Link href="/privacy">حریم خصوصی</Link>
            <Link href="/terms">قوانین و مقررات</Link>
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

            <span>تهران، ایران</span>

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
            <Link href="/privacy">حریم خصوصی</Link>
            <Link href="/terms">قوانین</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
