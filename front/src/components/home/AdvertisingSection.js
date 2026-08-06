'use client';

import { useRef } from 'react';
import Link from 'next/link';
import {
  ArrowLeftOutlined,
  ArrowRightOutlined,
  PlayCircleOutlined,
  MessageOutlined,
} from '@ant-design/icons';

import { useLanguage } from '@/lib/context/LanguageContext';
import { getHomeContent } from './homeContent';

import styles from './AdvertisingSection.module.css';

async function trackAdvertisement(campaignId, eventType) {
  try {
    await fetch('/backend-api/api/advertising/events', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        campaign_id: campaignId,
        event_type: eventType,
        placement: 'homepage_banner',
      }),
      keepalive: true,
    });
  } catch {
    // در صورت قطع بودن API، نمایش بنرها متوقف نمی‌شود.
  }
}

export default function AdvertisingSection() {
  const sliderRef = useRef(null);

  const {
    locale = 'fa',
    direction = 'rtl',
  } = useLanguage();

  const copy = getHomeContent(locale);

  const ads = copy.ads?.length
      ? copy.ads
      : getHomeContent('fa').ads;

  const scrollSlider = (side) => {
    const slider = sliderRef.current;

    if (!slider) {
      return;
    }

    const distance = Math.min(
        Math.max(slider.clientWidth * 0.8, 300),
        430,
    );

    const multiplier = side === 'next' ? 1 : -1;

    slider.scrollBy({
      left:
          direction === 'rtl'
              ? distance * multiplier * -1
              : distance * multiplier,
      behavior: 'smooth',
    });
  };

  return (
      <section
          className={styles.section}
          aria-labelledby="medical-advertising-title"
      >
        <div className={styles.sectionHeading}>
          <div className={styles.headingText}>
          <span className={styles.sectionEyebrow}>
            تبلیغات پزشکی
          </span>

            <h2 id="medical-advertising-title">
              {copy.section.ads}
            </h2>

            <p>{copy.section.adsDescription}</p>
          </div>

          <div className={styles.headingActions}>
            <Link
                href="/advertising"
                className={styles.registerLink}
            >
              ثبت تبلیغ مرکز درمانی
            </Link>

            <div
                className={styles.sliderControls}
                aria-label="کنترل اسلایدر تبلیغات"
            >
              <button
                  type="button"
                  className={styles.sliderButton}
                  onClick={() => scrollSlider('previous')}
                  aria-label="بنر قبلی"
              >
                {direction === 'rtl' ? (
                    <ArrowRightOutlined />
                ) : (
                    <ArrowLeftOutlined />
                )}
              </button>

              <button
                  type="button"
                  className={styles.sliderButton}
                  onClick={() => scrollSlider('next')}
                  aria-label="بنر بعدی"
              >
                {direction === 'rtl' ? (
                    <ArrowLeftOutlined />
                ) : (
                    <ArrowRightOutlined />
                )}
              </button>
            </div>
          </div>
        </div>

        <div
            ref={sliderRef}
            className={styles.slider}
            dir={direction}
        >
          {ads.map((ad) => {
            const videoHref =
                ad.videoHref ||
                `/advertising?campaign=${encodeURIComponent(
                    ad.campaignId,
                )}&action=video`;

            return (
                <article
                    key={ad.campaignId}
                    className={styles.card}
                    onMouseEnter={() =>
                        trackAdvertisement(
                            ad.campaignId,
                            'impression',
                        )
                    }
                >
                  <Link
                      href={ad.href}
                      className={styles.bannerLink}
                      onClick={() =>
                          trackAdvertisement(
                              ad.campaignId,
                              'banner_click',
                          )
                      }
                      aria-label={ad.title}
                  >
                    <img
                        src={ad.image}
                        alt={ad.title}
                        width="400"
                        height="228"
                        loading="lazy"
                        decoding="async"
                    />
                  </Link>

                  <div className={styles.actions}>
                    <Link
                        href={ad.href}
                        className={styles.consultButton}
                        onClick={() =>
                            trackAdvertisement(
                                ad.campaignId,
                                'consultation_click',
                            )
                        }
                    >
                      <MessageOutlined />
                      دریافت مشاوره
                    </Link>

                    <Link
                        href={videoHref}
                        className={styles.videoButton}
                        onClick={() =>
                            trackAdvertisement(
                                ad.campaignId,
                                'video_click',
                            )
                        }
                    >
                      <PlayCircleOutlined />
                      کلیپ معرفی
                    </Link>
                  </div>
                </article>
            );
          })}
        </div>
      </section>
  );
}