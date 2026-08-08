'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import {
  GlobalOutlined,
  HeartFilled,
  SearchOutlined,
  UserOutlined,
} from '@ant-design/icons';

import { useLanguage } from '@/lib/context/LanguageContext';
import { getHomeContent } from './homeContent';
import HomeMegaMenu from './HomeMegaMenu';

import styles from './DoctorWebHome.module.css';

export default function HomeHeader() {
  const router = useRouter();
  const [query, setQuery] = useState('');

  const {
    locale = 'fa',
    direction = 'rtl',
    changeLanguage,
  } = useLanguage();

  const copy = getHomeContent(locale);

  const submitSearch = (event) => {
    event.preventDefault();

    const value = query.trim();

    if (value) {
      router.push(`/search?q=${encodeURIComponent(value)}`);
    }
  };

  return (
    <header className={styles.header}>
      <div className={styles.topBar}>
        <div className={styles.container}>
          <div className={styles.topBarInner}>
            <span>{copy.supportLine}</span>

            <Link href="/advertising">{copy.advertise}</Link>
          </div>
        </div>
      </div>

      <div className={styles.mainHeader}>
        <div className={styles.container}>
          <div className={styles.headerRow}>
            <Link href="/" className={styles.logo}>
              <span className={styles.logoIcon}>
                <HeartFilled />
              </span>

              <span>
                <strong>{copy.brand}</strong>
                <small>{copy.brandCaption}</small>
              </span>
            </Link>

            <form
              className={styles.headerSearch}
              onSubmit={submitSearch}
            >
              <SearchOutlined />

              <input
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder={copy.search}
              />

              <button type="submit">{copy.searchButton}</button>
            </form>

            <div className={styles.headerActions}>
              <label className={styles.languageSelect}>
                <GlobalOutlined />

                <select
                  value={locale}
                  onChange={(event) =>
                    changeLanguage(event.target.value)
                  }
                >
                  <option value="fa">{copy.languageNames.fa}</option>
                  <option value="en">{copy.languageNames.en}</option>
                  <option value="ar">{copy.languageNames.ar}</option>
                </select>
              </label>

              <Link href="/login" className={styles.loginButton}>
                <UserOutlined />
                <span>{copy.login}</span>
              </Link>
            </div>
          </div>
        </div>
      </div>

      <nav className={styles.desktopNav}>
        <div className={styles.container}>
          <div className={styles.navRow}>
            <HomeMegaMenu copy={copy} direction={direction} />

            <Link href="/">{copy.nav.home}</Link>
            <Link href="/doctors">{copy.nav.doctors}</Link>
            <Link href="/pharmacy">{copy.nav.pharmacy}</Link>
            <Link href="/home-doctor">{copy.nav.homeDoctor}</Link>
            <Link href="/medical-tourism">{copy.nav.tourism}</Link>
            <Link href="/map">{copy.nav.map}</Link>
          </div>
        </div>
      </nav>
    </header>
  );
}
