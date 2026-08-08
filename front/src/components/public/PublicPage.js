'use client';

import { useState } from 'react';
import { EnvironmentOutlined, GlobalOutlined, HomeOutlined, NotificationOutlined, QuestionCircleOutlined, SafetyCertificateOutlined, TeamOutlined } from '@ant-design/icons';
import HomeHeader from '@/components/home/HomeHeader';
import HomeFooter from '@/components/home/HomeFooter';
import { useLanguage } from '@/lib/context/LanguageContext';
import { PUBLIC_PAGES } from './publicPages';
import styles from './PublicPage.module.css';

const icons = { home: HomeOutlined, global: GlobalOutlined, notification: NotificationOutlined, team: TeamOutlined, environment: EnvironmentOutlined, question: QuestionCircleOutlined, safety: SafetyCertificateOutlined };
const labels = {
  fa: { badge: 'دکتر وب؛ همراه سلامت شما', details: 'اطلاعات درخواست', name: 'نام و نام خانوادگی', phone: 'شماره تماس', email: 'ایمیل (اختیاری)', subject: 'موضوع درخواست', message: 'توضیحات', send: 'ثبت درخواست', sending: 'در حال ارسال...', success: 'درخواست شما با موفقیت ثبت شد. به‌زودی با شما تماس می‌گیریم.', error: 'ثبت درخواست انجام نشد. لطفاً دوباره تلاش کنید.' },
  en: { badge: 'Doctor Web, your health partner', details: 'Request details', name: 'Full name', phone: 'Phone number', email: 'Email (optional)', subject: 'Request subject', message: 'Details', send: 'Submit request', sending: 'Submitting...', success: 'Your request was submitted. We will contact you soon.', error: 'Could not submit the request. Please try again.' },
  ar: { badge: 'دكتور ويب، شريكك الصحي', details: 'بيانات الطلب', name: 'الاسم الكامل', phone: 'رقم الهاتف', email: 'البريد الإلكتروني (اختياري)', subject: 'موضوع الطلب', message: 'التفاصيل', send: 'إرسال الطلب', sending: 'جارٍ الإرسال...', success: 'تم تسجيل طلبك وسنتواصل معك قريباً.', error: 'تعذر إرسال الطلب. يرجى المحاولة مرة أخرى.' },
};

export default function PublicPage({ pageKey }) {
  const { locale = 'fa' } = useLanguage();
  const config = PUBLIC_PAGES[pageKey];
  const copy = config[locale] || config.fa;
  const ui = labels[locale] || labels.fa;
  const Icon = icons[config.icon] || SafetyCertificateOutlined;
  const [status, setStatus] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault(); setBusy(true); setStatus('');
    const form = new FormData(event.currentTarget);
    try {
      const response = await fetch('/backend-api/api/public-inquiries', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ type: config.type, locale, name: form.get('name'), phone: form.get('phone'), email: form.get('email'), subject: form.get('subject'), message: form.get('message') }) });
      if (!response.ok) throw new Error('request_failed');
      event.currentTarget.reset(); setStatus('success');
    } catch { setStatus('error'); } finally { setBusy(false); }
  }

  return <div className={styles.page}>
    <HomeHeader />
    <main>
      <section className={styles.hero}><div className={styles.container}>
        <span className={styles.eyebrow}>{ui.badge}</span><h1>{copy[1]}</h1><p>{copy[2]}</p>
      </div></section>
      <section className={`${styles.content} ${styles.container}`}>
        <div className={styles.grid}>{copy[3].map((item) => <article className={styles.card} key={item}><span className={styles.cardIcon}><Icon /></span><h2>{item}</h2><p>{copy[2]}</p></article>)}</div>
        {pageKey === 'help' && <div className={styles.steps}>{copy[3].map((item) => <div className={styles.step} key={item}><strong>{item}</strong></div>)}</div>}
        {config.form && <form className={styles.form} onSubmit={submit}><h2>{ui.details}</h2><div className={styles.fields}>
          <label className={styles.field}><span>{ui.name}</span><input name="name" required maxLength={120} /></label>
          <label className={styles.field}><span>{ui.phone}</span><input name="phone" required inputMode="tel" maxLength={30} /></label>
          <label className={styles.field}><span>{ui.email}</span><input name="email" type="email" maxLength={190} /></label>
          <label className={styles.field}><span>{ui.subject}</span><input name="subject" required maxLength={190} /></label>
          <label className={`${styles.field} ${styles.wide}`}><span>{ui.message}</span><textarea name="message" required maxLength={3000} /></label>
        </div><button className={styles.submit} disabled={busy}>{busy ? ui.sending : ui.send}</button>{status && <p className={styles.message}>{ui[status]}</p>}</form>}
      </section>
    </main>
    <HomeFooter />
  </div>;
}
