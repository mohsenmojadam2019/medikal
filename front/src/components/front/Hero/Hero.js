'use client';

import { Button } from 'antd';
import Link from 'next/link';

export default function Hero() {
  return (
      <div
          className="hero hero-primary"
          style={{
            backgroundImage: "url('/image/bac-2.png')",
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            minHeight: '90vh',
            display: 'flex',
            alignItems: 'center',
            position: 'relative',
          }}
      >
        <div
            style={{
              position: 'absolute',
              inset: 0,
              background: 'rgba(0,0,0,0.35)', // تیره شدن عکس
            }}
        />

        <div
            className="hero-content"
            style={{
              position: 'relative',
              zIndex: 2,
              color: '#fff',
              maxWidth: '700px',
            }}
        >
        <span className="hero-badge">
          <i className="fas fa-bolt" /> نوبت‌دهی هوشمند
        </span>

          <h1>
            نوبت خود را <span>سریع و آسان</span> رزرو کنید
          </h1>

          <p>
            بیش از ۵۰۰ پزشک متخصص در ۳۰ تخصص مختلف، آماده ارائه خدمت به شما هستند.
          </p>

          <div className="hero-actions">
            <Link href="/doctors">
              <Button type="primary" size="large" className="hero-cta">
                شروع کنید
              </Button>
            </Link>


          </div>
        </div>
      </div>
  );
}