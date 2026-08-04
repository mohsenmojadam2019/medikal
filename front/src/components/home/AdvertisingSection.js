'use client';

import Link from 'next/link';
import {
  PlayCircleOutlined,
  SafetyCertificateOutlined,
} from '@ant-design/icons';

import { useLanguage } from '@/lib/context/LanguageContext';
import { getHomeContent } from './homeContent';

import styles from './DoctorWebHome.module.css';

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
    // سیستم تبلیغات بعداً به بک‌اند متصل می‌شود.
  }
}

export default function AdvertisingSection() {
  const { locale = 'fa' } = useLanguage();
  const copy = getHomeContent(locale);

  const ads = copy.ads?.length
    ? copy.ads
    : getHomeContent('fa').ads;

  return (
    <section className={styles.section}>
      <div className={styles.sectionHeading}>
        <div>
          <span className={styles.sectionEyebrow}>
            تبلیغات پزشکی
          </span>

          <h2>{copy.section.ads}</h2>
          <p>{copy.section.adsDescription}</p>
        </div>

        <Link href="/advertising">
          ثبت تبلیغ مرکز درمانی
        </Link>
      </div>

      <div className={styles.adsGrid}>
        {ads.map((ad) => (
          <article
            key={ad.campaignId}
            className={`${styles.adCard} ${
              styles[`adTheme${ad.theme}`]
            }`}
            onMouseEnter={() =>
              trackAdvertisement(ad.campaignId, 'impression')
            }
          >
            <img src={ad.image} alt={ad.title} />

            <div className={styles.adOverlay} />

            <div className={styles.adContent}>
              <div className={styles.adMeta}>
                <span>{ad.badge}</span>

                <small>
                  <SafetyCertificateOutlined />
                  {ad.sponsor}
                </small>
              </div>

              <h3>{ad.title}</h3>
              <p>{ad.description}</p>

              <div className={styles.adActions}>
                <Link
                  href={ad.href}
                  onClick={() =>
                    trackAdvertisement(ad.campaignId, 'click')
                  }
                >
                  {ad.button}
                </Link>

                <button type="button">
                  <PlayCircleOutlined />
                  کلیپ معرفی
                </button>
              </div>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
