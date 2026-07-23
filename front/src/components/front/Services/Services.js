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
                        >
                            <img
                                src={service.image}
                                alt=""
                                className="service-image"
                            />
                            <div className="services-card-overlay"></div>
                            <div className="services-card-content"></div>
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
                    grid-template-columns: repeat(4, 350px);
                    gap: 35px;
                    justify-content: center;
                }
                .services-card {
                    position: relative;
                    border-radius: 24px;
                    overflow: hidden;
                    width: 350px;
                    height: 460px;
                    text-decoration: none;
                    cursor: pointer;
                    transition: all 0.4s ease;
                    display: block;
                    background: #f0f0f0;
                }
                .services-card:hover {
                    transform: translateY(-12px) scale(1.02);
                    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.25);
                }
                .service-image {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    display: block;
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
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    z-index: 2;
                    padding: 30px;
                    color: #fff;
                }

                @media (max-width: 1600px) {
                    .services-grid {
                        grid-template-columns: repeat(4, 320px);
                        gap: 30px;
                    }
                    .services-card {
                        width: 320px;
                        height: 420px;
                    }
                }

                @media (max-width: 1400px) {
                    .services-grid {
                        grid-template-columns: repeat(4, 280px);
                        gap: 24px;
                    }
                    .services-card {
                        width: 280px;
                        height: 380px;
                    }
                }

                @media (max-width: 1200px) {
                    .services-grid {
                        grid-template-columns: repeat(4, 240px);
                        gap: 20px;
                    }
                    .services-card {
                        width: 240px;
                        height: 320px;
                    }
                }

                @media (max-width: 1024px) {
                    .services-grid {
                        grid-template-columns: repeat(2, 300px);
                        gap: 24px;
                    }
                    .services-card {
                        width: 300px;
                        height: 400px;
                    }
                }

                @media (max-width: 768px) {
                    .services-wrapper {
                        padding: 40px 0 50px;
                    }
                    .services-grid {
                        grid-template-columns: repeat(2, 260px);
                        gap: 16px;
                    }
                    .services-card {
                        width: 260px;
                        height: 350px;
                        border-radius: 16px;
                    }
                    .services-card-content {
                        padding: 20px;
                    }
                }

                @media (max-width: 600px) {
                    .services-grid {
                        grid-template-columns: repeat(2, 200px);
                        gap: 14px;
                    }
                    .services-card {
                        width: 200px;
                        height: 270px;
                        border-radius: 14px;
                    }
                    .services-card-content {
                        padding: 14px;
                    }
                }

                @media (max-width: 450px) {
                    .services-grid {
                        grid-template-columns: repeat(2, 160px);
                        gap: 12px;
                    }
                    .services-card {
                        width: 160px;
                        height: 220px;
                        border-radius: 12px;
                    }
                    .services-card-content {
                        padding: 10px;
                    }
                }
            `}</style>
        </div>
    );
}
