'use client';

import Link from 'next/link';

export default function Footer() {
  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-grid">
          <div className="footer-brand">
            <Link href="/" className="logo" style={{ marginBottom: '8px' }}>
              <div className="logo-icon">
                <i className="fas fa-user-md" />
              </div>
              <div>
                <span className="logo-text">کلینیک<span>‌یار</span></span>
                <span className="logo-sub">سیستم مدیریت جامع سلامت</span>
              </div>
            </Link>
            <p>
              پلتفرم جامع نوبت‌دهی، مدیریت پرونده الکترونیک و خدمات سلامت برای مطب‌ها و کلینیک‌ها.
            </p>
            <div className="footer-social">
              <a href="#"><i className="fab fa-instagram" /></a>
              <a href="#"><i className="fab fa-telegram" /></a>
              <a href="#"><i className="fab fa-whatsapp" /></a>
              <a href="#"><i className="fab fa-linkedin-in" /></a>
              <a href="#"><i className="fab fa-youtube" /></a>
            </div>
          </div>
          <div className="footer-col">
            <h4>خدمات</h4>
            <ul>
              <li><a href="#">نوبت‌دهی آنلاین</a></li>
              <li><a href="#">پزشکان</a></li>
              <li><a href="#">تخصص‌ها</a></li>
              <li><a href="#">آزمایشگاه</a></li>
              <li><a href="#">داروخانه</a></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>اطلاعات</h4>
            <ul>
              <li><a href="#">درباره ما</a></li>
              <li><a href="#">تماس با ما</a></li>
              <li><a href="#">قوانین و مقررات</a></li>
              <li><a href="#">حریم خصوصی</a></li>
              <li><a href="#">سوالات متداول</a></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>پشتیبانی</h4>
            <ul>
              <li><a href="#">راهنما</a></li>
              <li><a href="#">گزارش تخلف</a></li>
              <li><a href="#">پیشنهادات</a></li>
              <li><a href="#">همکاری با ما</a></li>
              <li><a href="#">بلاگ</a></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>ارتباط با ما</h4>
            <ul>
              <li><i className="fas fa-phone" style={{ color: 'var(--primary)' }} /> ۰۲۱-۱۲۳۴۵۶۷۸</li>
              <li><i className="fas fa-mobile-alt" style={{ color: 'var(--primary)' }} /> ۰۹۱۲-۳۴۵۶۷۸۹</li>
              <li><i className="fas fa-envelope" style={{ color: 'var(--primary)' }} /> info@clinic-yar.com</li>
              <li><i className="fas fa-map-marker-alt" style={{ color: 'var(--primary)' }} /> تهران، خیابان ولیعصر، پلاک ۱۲۳</li>
            </ul>
          </div>
        </div>

        <div className="footer-bottom">
          <span>© ۱۴۰۴ کلینیک‌یار. تمامی حقوق محفوظ است.</span>
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
