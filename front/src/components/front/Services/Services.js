// /src/components/front/Services/Services.js
'use client';

import Link from 'next/link';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function Services() {
    const { locale } = useLanguage();

    const services = [
        {
            id: 1,
            link: `/${locale}/appointments/new`,
            image: '/image/services/moshavere.png',
        },
        {
            id: 2,
            link: `/${locale}/pharmacy`,
            image: '/image/services/daroo.png',
        },
        {
            id: 3,
            link: `/${locale}/lab`,
            image: '/image/services/azmayeshgah.png',
        },
        {
            id: 4,
            link: `/${locale}/ai-chat`,
            image: '/image/services/ai.png',
        }
    ];

    return (
        <div className="services-wrapper">
            <div className="services-container">
                <div className="services-grid">
                    {services.map((service) => (
                        <Link
                            key={service.id}
                            href={service.link}
                            className="services-card"
                            style={{
                                backgroundImage: `url('${service.image}')`,
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                                backgroundRepeat: 'no-repeat'
                            }}
                        >
                            <div className="services-card-overlay"></div>
                            <div className="services-card-content">
                                {/* بدون دکمه */}
                            </div>
                        </Link>
                    ))}
                </div>
            </div>

            <style jsx>{`
                .services-wrapper {
                    padding: 60px 0 80px;
                    background: #f8fafc;
                }
                .services-container {
                    max-width: 1600px;
                    margin: 0 auto;
                    padding: 0 24px;
                }
                .services-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 40px;
                }
                .services-card {
                    position: relative;
                    border-radius: 28px;
                    overflow: hidden;
                    height: 228px;
                    text-decoration: none;
                    cursor: pointer;
                    transition: all 0.4s ease;
                    display: block;
                    max-width: 400px;
                    width: 100%;
                    margin: 0 auto;
                }
                .services-card:hover {
                    transform: translateY(-12px);
                    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.25);
                }
                .services-card-overlay {
                    position: absolute;
                    inset: 0;
                    z-index: 1;
                    transition: all 0.4s ease;
                }

                .services-card:hover .services-card-overlay {
                    background: linear-gradient(180deg,
                    rgba(0,0,0,0.02) 0%,
                    rgba(0,0,0,0.3) 40%,
                    rgba(0,0,0,0.65) 100%
                    );
                }

                .services-card-content {
                    position: relative;
                    z-index: 2;
                    padding: 40px;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: flex-end;
                    color: #fff;
                }

                @media (max-width: 1440px) {
                    .services-card {
                        height: 228px;
                        max-width: 400px;
                    }
                    .services-grid {
                        gap: 32px;
                    }
                }

                @media (max-width: 1200px) {
                    .services-card {
                        height: 228px;
                        max-width: 400px;
                    }
                    .services-grid {
                        gap: 28px;
                    }
                }

                @media (max-width: 1024px) {
                    .services-grid {
                        grid-template-columns: repeat(2, 1fr);
                        gap: 28px;
                    }
                    .services-card {
                        height: 228px;
                        max-width: 400px;
                    }
                    .services-card-content {
                        padding: 32px;
                    }
                }

                @media (max-width: 768px) {
                    .services-wrapper {
                        padding: 40px 0 50px;
                    }
                    .services-grid {
                        grid-template-columns: 1fr 1fr;
                        gap: 18px;
                    }
                    .services-card {
                        height: 228px;
                        max-width: 400px;
                        border-radius: 20px;
                    }
                    .services-card-content {
                        padding: 24px;
                    }
                }

                @media (max-width: 480px) {
                    .services-wrapper {
                        padding: 30px 0 40px;
                    }
                    .services-grid {
                        grid-template-columns: 1fr 1fr;
                        gap: 14px;
                    }
                    .services-card {
                        height: 228px;
                        max-width: 400px;
                        border-radius: 16px;
                    }
                    .services-card-content {
                        padding: 18px;
                    }
                }

                @media (max-width: 400px) {
                    .services-card {
                        height: 180px;
                        max-width: 100%;
                    }
                }
            `}</style>
        </div>
    );
}