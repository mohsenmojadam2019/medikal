'use client';

import Link from 'next/link';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function Footer() {
  const { t, locale } = useLanguage();

  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-grid">
          {/* بخش برند */}
          <div className="footer-brand">
            <Link href={`/${locale}`} className="logo" style={{ marginBottom: '8px' }}>
              <div className="logo-icon">
                <i className="fas fa-user-md" />
              </div>
              <div>
                <span className="logo-text">کلینیک<span>‌یار</span></span>
                <span className="logo-sub">سیستم مدیریت جامع سلامت</span>
              </div>
            </Link>
            <p>پلتفرم جامع نوبت‌دهی، مدیریت پرونده الکترونیک و خدمات سلامت برای مطب‌ها و کلینیک‌ها.</p>
            <div className="footer-social">
              <a href="#"><i className="fab fa-instagram" /></a>
              <a href="#"><i className="fab fa-telegram" /></a>
              <a href="#"><i className="fab fa-whatsapp" /></a>
              <a href="#"><i className="fab fa-linkedin-in" /></a>
              <a href="#"><i className="fab fa-youtube" /></a>
            </div>
          </div>

          {/* ستون خدمات */}
          <div className="footer-col">
            <h4>{t('footer.services')}</h4>
            <ul>
              <li><Link href={`/${locale}/appointments`}>نوبت‌دهی آنلاین</Link></li>
              <li><Link href={`/${locale}/doctors`}>پزشکان</Link></li>
              <li><Link href={`/${locale}/specialties`}>تخصص‌ها</Link></li>
              <li><Link href={`/${locale}/lab`}>آزمایشگاه</Link></li>
              <li><Link href={`/${locale}/pharmacy`}>داروخانه</Link></li>
            </ul>
          </div>

          {/* ستون اطلاعات */}
          <div className="footer-col">
            <h4>{t('footer.info')}</h4>
            <ul>
              <li><Link href={`/${locale}/about`}>درباره ما</Link></li>
              <li><Link href={`/${locale}/contact`}>تماس با ما</Link></li>
              <li><Link href={`/${locale}/terms`}>قوانین و مقررات</Link></li>
              <li><Link href={`/${locale}/privacy`}>حریم خصوصی</Link></li>
              <li><Link href={`/${locale}/faq`}>سوالات متداول</Link></li>
            </ul>
          </div>

          {/* ستون پشتیبانی */}
          <div className="footer-col">
            <h4>{t('footer.support')}</h4>
            <ul>
              <li><Link href={`/${locale}/help`}>راهنما</Link></li>
              <li><Link href={`/${locale}/report`}>گزارش تخلف</Link></li>
              <li><Link href={`/${locale}/suggestions`}>پیشنهادات</Link></li>
              <li><Link href={`/${locale}/collaborate`}>همکاری با ما</Link></li>
              <li><Link href={`/${locale}/blog`}>بلاگ</Link></li>
            </ul>
          </div>

          {/* ستون ارتباط با ما */}
          <div className="footer-col">
            <h4>{t('footer.contact')}</h4>
            <ul>
              <li><i className="fas fa-phone" style={{ color: 'var(--primary)' }} /> ۰۲۱-۱۲۳۴۵۶۷۸</li>
              <li><i className="fas fa-mobile-alt" style={{ color: 'var(--primary)' }} /> ۰۹۱۲-۳۴۵۶۷۸۹</li>
              <li><i className="fas fa-envelope" style={{ color: 'var(--primary)' }} /> info@clinic-yar.com</li>
              <li><i className="fas fa-map-marker-alt" style={{ color: 'var(--primary)' }} /> تهران، خیابان ولیعصر، پلاک ۱۲۳</li>
            </ul>
          </div>
        </div>

        {/* بخش پایین فوتر */}
        <div className="footer-bottom">
          <span>© ۱۴۰۴ دکتر وب. تمامی حقوق محفوظ است.</span>
          <div className="payments">
            <span>زرین‌پال</span>
            <span>آسان‌پرداخت</span>
            <span>شتاب</span>
            <span>شاپرک</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
