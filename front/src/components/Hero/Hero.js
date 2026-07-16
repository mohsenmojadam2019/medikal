'use client';

import { Button } from 'antd';
import Link from 'next/link';

export default function Hero() {
  return (
    <div className="hero">
      <div className="hero-content">
        <span className="hero-badge">
          <i className="fas fa-bolt" /> نوبت‌دهی هوشمند
        </span>
        <h1>
          نوبت خود را <span>سریع و آسان</span> رزرو کنید
        </h1>
        <p>
          بیش از ۵۰۰ پزشک متخصص در ۳۰ تخصص مختلف، آماده ارائه خدمت به شما هستند.
          نوبت‌دهی آنلاین، پرداخت امن و پرونده الکترونیک.
        </p>
        <div className="hero-actions">
          <Link href="/doctors">
            <Button type="primary" size="large" className="hero-cta">
              <i className="fas fa-arrow-left" /> شروع کنید
            </Button>
          </Link>
          {/*<Button size="large" className="hero-cta-outline">*/}
          {/*  <i className="fas fa-play" /> نحوه کار*/}
          {/*</Button>*/}
        </div>
      </div>
    </div>
  );
}
