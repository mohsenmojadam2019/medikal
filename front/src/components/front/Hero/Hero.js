
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
                <div style={{ flex: 1, minWidth: '280px' }}>


                    <div style={{ display: 'flex', gap: '12px', flexWrap: 'wrap' }}>


                    </div>
                </div>

                <div
                    style={{
                        flex: '0 0 300px',
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '12px',
                    }}
                >
                    <Link href="/fa/appointments/new" style={{ textDecoration: 'none' }}>
                        <div
                            style={{
                                background: 'rgba(255,255,255,0.12)',
                                backdropFilter: 'blur(20px)',
                                border: '1px solid rgba(255,255,255,0.2)',
                                borderRadius: '16px',
                                padding: '16px 12px',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                textAlign: 'center',
                                gap: '6px',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer',
                                animation: 'floatBtn1 4s ease-in-out infinite',
                            }}
                        >
                            <div style={{
                                fontSize: '32px',
                                width: '52px',
                                height: '52px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(37,99,235,0.3)',
                                borderRadius: '12px',
                            }}>
                                🩺
                            </div>
                            <div style={{ fontWeight: 700, fontSize: '14px', color: '#fff' }}>رزرو پزشک</div>
                            <div style={{ fontSize: '11px', color: 'rgba(255,255,255,0.7)' }}>نوبت‌دهی آنلاین</div>
                        </div>
                    </Link>

                    <Link href="/fa/lab" style={{ textDecoration: 'none' }}>
                        <div
                            style={{
                                background: 'rgba(255,255,255,0.12)',
                                backdropFilter: 'blur(20px)',
                                border: '1px solid rgba(255,255,255,0.2)',
                                borderRadius: '16px',
                                padding: '16px 12px',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                textAlign: 'center',
                                gap: '6px',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer',
                                animation: 'floatBtn2 4s ease-in-out infinite 0.4s',
                            }}
                        >
                            <div style={{
                                fontSize: '32px',
                                width: '52px',
                                height: '52px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(245,158,11,0.3)',
                                borderRadius: '12px',
                            }}>
                                🔬
                            </div>
                            <div style={{ fontWeight: 700, fontSize: '14px', color: '#fff' }}>آزمایشگاه</div>
                            <div style={{ fontSize: '11px', color: 'rgba(255,255,255,0.7)' }}>انجام آزمایش</div>
                        </div>
                    </Link>

                    <Link href="/fa/imaging" style={{ textDecoration: 'none' }}>
                        <div
                            style={{
                                background: 'rgba(255,255,255,0.12)',
                                backdropFilter: 'blur(20px)',
                                border: '1px solid rgba(255,255,255,0.2)',
                                borderRadius: '16px',
                                padding: '16px 12px',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                textAlign: 'center',
                                gap: '6px',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer',
                                animation: 'floatBtn3 4s ease-in-out infinite 0.8s',
                            }}
                        >
                            <div style={{
                                fontSize: '32px',
                                width: '52px',
                                height: '52px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(236,72,153,0.3)',
                                borderRadius: '12px',
                            }}>
                                📷
                            </div>
                            <div style={{ fontWeight: 700, fontSize: '14px', color: '#fff' }}>تصویربرداری</div>
                            <div style={{ fontSize: '11px', color: 'rgba(255,255,255,0.7)' }}>سونوگرافی، ام‌آرآی</div>
                        </div>
                    </Link>

                    <Link href="/fa/pharmacy" style={{ textDecoration: 'none' }}>
                        <div
                            style={{
                                background: 'rgba(255,255,255,0.12)',
                                backdropFilter: 'blur(20px)',
                                border: '1px solid rgba(255,255,255,0.2)',
                                borderRadius: '16px',
                                padding: '16px 12px',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                textAlign: 'center',
                                gap: '6px',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer',
                                animation: 'floatBtn4 4s ease-in-out infinite 1.2s',
                            }}
                        >
                            <div style={{
                                fontSize: '32px',
                                width: '52px',
                                height: '52px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(16,185,129,0.3)',
                                borderRadius: '12px',
                            }}>
                                💊
                            </div>
                            <div style={{ fontWeight: 700, fontSize: '14px', color: '#fff' }}>داروخانه</div>
                            <div style={{ fontSize: '11px', color: 'rgba(255,255,255,0.7)' }}>خرید آنلاین دارو</div>
                        </div>
                    </Link>

                    <Link href="/fa/ai-chat" style={{ textDecoration: 'none' }}>
                        <div
                            style={{
                                background: 'rgba(255,255,255,0.12)',
                                backdropFilter: 'blur(20px)',
                                border: '1px solid rgba(255,255,255,0.2)',
                                borderRadius: '16px',
                                padding: '16px 12px',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                textAlign: 'center',
                                gap: '6px',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer',
                                animation: 'floatBtn5 4s ease-in-out infinite 1.6s',
                            }}
                        >
                            <div style={{
                                fontSize: '32px',
                                width: '52px',
                                height: '52px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: 'rgba(139,92,246,0.3)',
                                borderRadius: '12px',
                            }}>
                                🤖
                            </div>
                            <div style={{ fontWeight: 700, fontSize: '14px', color: '#fff' }}>چت AI</div>
                            <div style={{ fontSize: '11px', color: 'rgba(255,255,255,0.7)' }}>هوش مصنوعی پزشکی</div>
                        </div>
                    </Link>
                </div>
            </div>

            <style jsx>{`
@keyframes floatBtn1 {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes floatBtn2 {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes floatBtn3 {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes floatBtn4 {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes floatBtn5 {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

[style*="animation: floatBtn"]:hover {
    background: rgba(255,255,255,0.25) !important;
    border-color: rgba(255,255,255,0.4) !important;
    transform: scale(1.05) !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4) !important;
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
    div[style*="flex: 0 0 300px"] {
        flex: 1 1 100% !important;
        grid-template-columns: repeat(3, 1fr) !important;
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
    div[style*="flex: 0 0 300px"] {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 8px !important;
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
    div[style*="flex: 0 0 300px"] {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 6px !important;
    }
}
`}</style>
        </div>
    );
}
