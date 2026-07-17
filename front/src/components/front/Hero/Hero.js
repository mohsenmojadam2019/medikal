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
                overflow: 'hidden',
            }}
        >
            {/* محتوای اصلی */}
            <div
                className="hero-content"
                style={{
                    position: 'relative',
                    zIndex: 2,
                    color: '#fff',
                    maxWidth: '1200px',
                    margin: '0 auto',
                    padding: '0 24px',
                    width: '100%',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: '40px',
                    flexWrap: 'wrap',
                }}
            >
                {/* متن سمت چپ */}
                <div style={{ flex: 1, minWidth: '280px' }}>
    <span
        className="hero-badge"
        style={{
            display: 'inline-block',
            background: 'rgba(0,0,0,0.3)',
            padding: '6px 18px',
            borderRadius: '50px',
            fontSize: '14px',
            fontWeight: '600',
            marginBottom: '20px',
            border: '1px solid rgba(255,255,255,0.2)',
            backdropFilter: 'blur(10px)',
        }}
    >
        <i className="fas fa-bolt" /> نوبت‌دهی هوشمند
    </span>

                    <div className="hero-actions" style={{ display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
                        <Link href="/fa/doctors">
                            <Button
                                type="primary"
                                size="large"
                                className="hero-cta"
                                style={{
                                    background: 'rgba(255,255,255,0.15)',
                                    border: '1px solid rgba(255,255,255,0.2)',
                                    borderRadius: '12px',
                                    height: '50px',
                                    padding: '0 32px',
                                    fontWeight: '600',
                                    fontSize: '16px',
                                    color: '#fff',
                                    backdropFilter: 'blur(10px)',
                                    boxShadow: '0 4px 16px rgba(0,0,0,0.1)',
                                }}
                            >
                                <i className="fas fa-arrow-left" style={{ marginRight: '8px' }} />
                                شروع کنید
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* باکس‌های متحرک سمت راست */}
                <div
                    style={{
                        flex: '0 0 280px',
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '14px',
                    }}
                >
                    {/* باکس 1 - نوبت‌دهی */}
                    <div
                        className="hero-float-card"
                        style={{
                            background: 'rgba(0,0,0,0.3)',
                            backdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255,255,255,0.15)',
                            borderRadius: '16px',
                            padding: '18px 14px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px',
                            animation: 'floatCard1 4s ease-in-out infinite',
                            transition: 'all 0.3s ease',
                            cursor: 'default',
                        }}
                    >
                        <div
                            style={{
                                fontSize: '30px',
                                width: '46px',
                                height: '46px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(37,99,235,0.3)',
                                borderRadius: '12px',
                                flexShrink: 0,
                            }}
                        >
                            🏥
                        </div>
                        <div>
                            <div style={{ fontSize: '18px', fontWeight: '700', color: '#93c5fd' }}>
                                ۱۲,۴۰۰+
                            </div>
                            <div style={{ fontSize: '12px', color: 'rgba(255,255,255,0.8)' }}>
                                نوبت رزرو شده
                            </div>
                        </div>
                    </div>

                    {/* باکس 2 - پزشکان */}
                    <div
                        className="hero-float-card"
                        style={{
                            background: 'rgba(0,0,0,0.3)',
                            backdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255,255,255,0.15)',
                            borderRadius: '16px',
                            padding: '18px 14px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px',
                            animation: 'floatCard2 4s ease-in-out infinite 0.8s',
                            transition: 'all 0.3s ease',
                            cursor: 'default',
                        }}
                    >
                        <div
                            style={{
                                fontSize: '30px',
                                width: '46px',
                                height: '46px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(16,185,129,0.3)',
                                borderRadius: '12px',
                                flexShrink: 0,
                            }}
                        >
                            👨‍⚕️
                        </div>
                        <div>
                            <div style={{ fontSize: '18px', fontWeight: '700', color: '#6ee7b7' }}>
                                ۵۰۰+
                            </div>
                            <div style={{ fontSize: '12px', color: 'rgba(255,255,255,0.8)' }}>
                                پزشک متخصص
                            </div>
                        </div>
                    </div>

                    {/* باکس 3 - آزمایشگاه */}
                    <div
                        className="hero-float-card"
                        style={{
                            background: 'rgba(0,0,0,0.3)',
                            backdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255,255,255,0.15)',
                            borderRadius: '16px',
                            padding: '18px 14px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px',
                            animation: 'floatCard3 4s ease-in-out infinite 1.6s',
                            transition: 'all 0.3s ease',
                            cursor: 'default',
                        }}
                    >
                        <div
                            style={{
                                fontSize: '30px',
                                width: '46px',
                                height: '46px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(139,92,246,0.3)',
                                borderRadius: '12px',
                                flexShrink: 0,
                            }}
                        >
                            🔬
                        </div>
                        <div>
                            <div style={{ fontSize: '18px', fontWeight: '700', color: '#c4b5fd' }}>
                                ۲۰۰+
                            </div>
                            <div style={{ fontSize: '12px', color: 'rgba(255,255,255,0.8)' }}>
                                آزمایش انجام شده
                            </div>
                        </div>
                    </div>

                    {/* باکس 4 - هوش مصنوعی */}
                    <div
                        className="hero-float-card"
                        style={{
                            background: 'rgba(0,0,0,0.3)',
                            backdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255,255,255,0.15)',
                            borderRadius: '16px',
                            padding: '18px 14px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px',
                            animation: 'floatCard4 4s ease-in-out infinite 2.4s',
                            transition: 'all 0.3s ease',
                            cursor: 'default',
                        }}
                    >
                        <div
                            style={{
                                fontSize: '30px',
                                width: '46px',
                                height: '46px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(245,158,11,0.3)',
                                borderRadius: '12px',
                                flexShrink: 0,
                            }}
                        >
                            🤖
                        </div>
                        <div>
                            <div style={{ fontSize: '18px', fontWeight: '700', color: '#fcd34d' }}>
                                ۲۴/۷
                            </div>
                            <div style={{ fontSize: '12px', color: 'rgba(255,255,255,0.8)' }}>
                                پشتیبانی هوشمند
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* انیمیشن‌های CSS */}
            <style jsx>{`
                @keyframes floatCard1 {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-12px); }
                }
                @keyframes floatCard2 {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-12px); }
                }
                @keyframes floatCard3 {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-12px); }
                }
                @keyframes floatCard4 {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-12px); }
                }

                .hero-float-card:hover {
                    background: rgba(0, 0, 0, 0.4) !important;
                    border-color: rgba(255, 255, 255, 0.3) !important;
                    transform: scale(1.03) !important;
                }

                @media (max-width: 1024px) {
                    .hero-content {
                        flex-direction: column;
                        text-align: center;
                        gap: 30px;
                    }
                    .hero-content h1 {
                        font-size: 36px !important;
                    }
                    .hero-content p {
                        margin: 0 auto 24px !important;
                    }
                    .hero-actions {
                        justify-content: center !important;
                    }
                    .hero-float-card {
                        padding: 14px 12px !important;
                    }
                    .hero-float-card > div:first-child {
                        font-size: 26px !important;
                        width: 40px !important;
                        height: 40px !important;
                    }
                    .hero-float-card > div:last-child > div:first-child {
                        font-size: 16px !important;
                    }
                    .hero-float-card > div:last-child > div:last-child {
                        font-size: 11px !important;
                    }
                }

                @media (max-width: 768px) {
                    .hero {
                        min-height: 80vh !important;
                    }
                    .hero-content h1 {
                        font-size: 28px !important;
                    }
                    .hero-content p {
                        font-size: 15px !important;
                    }
                    .hero-cta {
                        height: 44px !important;
                        font-size: 14px !important;
                        padding: 0 24px !important;
                    }
                    .hero-float-card {
                        padding: 12px 10px !important;
                    }
                    .hero-float-card > div:first-child {
                        font-size: 22px !important;
                        width: 34px !important;
                        height: 34px !important;
                    }
                    .hero-float-card > div:last-child > div:first-child {
                        font-size: 14px !important;
                    }
                    .hero-float-card > div:last-child > div:last-child {
                        font-size: 10px !important;
                    }
                }

                @media (max-width: 480px) {
                    .hero {
                        min-height: 70vh !important;
                    }
                    .hero-content h1 {
                        font-size: 22px !important;
                    }
                    .hero-content p {
                        font-size: 13px !important;
                    }
                    .hero-cta {
                        height: 38px !important;
                        font-size: 13px !important;
                        padding: 0 18px !important;
                    }
                    .hero-float-card {
                        padding: 10px 8px !important;
                    }
                    .hero-float-card > div:first-child {
                        font-size: 18px !important;
                        width: 28px !important;
                        height: 28px !important;
                    }
                    .hero-float-card > div:last-child > div:first-child {
                        font-size: 12px !important;
                    }
                    .hero-float-card > div:last-child > div:last-child {
                        font-size: 9px !important;
                    }
                }
            `}</style>
        </div>
    );
}