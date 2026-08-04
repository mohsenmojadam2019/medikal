'use client';

import { useState } from 'react';
import { message } from 'antd';

export default function Offers() {
  const [copied, setCopied] = useState(false);
  const code = 'WELCOME20';

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      message.success('✅ کد تخفیف کپی شد!');
      setTimeout(() => setCopied(false), 2000);
    } catch {
      message.error('خطا در کپی کردن');
    }
  };

  return (
    <div className="section">
      <div className="section-header">
        <div className="section-header-left">
          <h2>
            <i className="fas fa-gift" style={{ color: 'var(--warning)' }} /> پیشنهادات ویژه
          </h2>
          <span className="tag hot">تخفیف</span>
        </div>
        <a href="#">مشاهده همه <i className="fas fa-chevron-left" /></a>
      </div>
      <div className="offer-card">
        <div className="offer-icon">🎁</div>
        <div className="offer-content">
          <h4>تخفیف ۲۰٪ برای ویزیت اول</h4>
          <p>برای اولین نوبت خود از هر پزشک، ۲۰٪ تخفیف دریافت کنید. کد تخفیف را کپی کنید.</p>
        </div>
        <div className="offer-code" onClick={handleCopy} style={{ cursor: 'pointer' }}>
          {copied ? '✅ کپی شد!' : code}
        </div>
      </div>
    </div>
  );
}
