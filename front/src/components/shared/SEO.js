'use client';

import Head from 'next/head';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function SEO({
  title,
  description,
  keywords,
  image,
  type = 'website',
  url,
}) {
  const { locale } = useLanguage();
  const router = useRouter();
  const currentUrl = url || `http://localhost:3000${router.pathname}`;
  
  const siteTitle = title ? `${title} | کلینیک‌یار` : 'کلینیک‌یار | سیستم مدیریت جامع سلامت';
  const siteDescription = description || 'سیستم جامع نوبت‌دهی و مدیریت سلامت';
  const siteKeywords = keywords || 'پزشک, نوبت, سلامت, درمان, کلینیک';

  return (
    <Head>
      <title>{siteTitle}</title>
      <meta name="description" content={siteDescription} />
      <meta name="keywords" content={siteKeywords} />
      <meta property="og:title" content={siteTitle} />
      <meta property="og:description" content={siteDescription} />
      <meta property="og:type" content={type} />
      <meta property="og:url" content={currentUrl} />
      <meta property="og:locale" content={locale === 'fa' ? 'fa_IR' : 'en_US'} />
      {image && <meta property="og:image" content={image} />}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={siteTitle} />
      <meta name="twitter:description" content={siteDescription} />
      <link rel="canonical" href={currentUrl} />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta charSet="UTF-8" />
    </Head>
  );
}
