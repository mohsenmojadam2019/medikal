'use client';

export default function TopBar() {
  return (
    <div className="header-top">
      <div className="container header-top-inner">
        <div className="left">
          <span>
            <i className="fas fa-phone" /> ۰۲۱-۱۲۳۴۵۶۷۸
          </span>
          <span>
            <i className="fas fa-clock" /> ۸:۰۰ - ۲۲:۰۰
          </span>
          <span>
            <i className="fas fa-map-marker-alt" /> تهران، خیابان ولیعصر
          </span>
        </div>
        <div className="right">
          <a href="#">
            <i className="fas fa-headset" /> پشتیبانی
          </a>
          <a href="#">
            <i className="fas fa-download" /> دانلود اپلیکیشن
          </a>
          <a href="#">
            <i className="fas fa-question-circle" /> راهنما
          </a>
        </div>
      </div>
    </div>
  );
}
