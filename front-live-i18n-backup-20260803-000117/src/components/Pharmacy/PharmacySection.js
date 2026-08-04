'use client';

import { Card } from 'antd';
import { pharmacyData } from '@/lib/data/mockData';
import PharmacyCard from './PharmacyCard';

export default function PharmacySection() {
  return (
    <div className="section">
      <div className="section-header">
        <div className="section-header-left">
          <h2>
            <i className="fas fa-pills" style={{ color: 'var(--success)' }} /> داروخانه آنلاین
          </h2>
          <span className="tag">داروهای موجود</span>
          <span className="tag hot">ارسال نسخه</span>
        </div>
        <a href="#">مشاهده همه <i className="fas fa-chevron-left" /></a>
      </div>
      <Card>
        <div className="pharmacy-grid">
          {pharmacyData.map((item) => (
            <PharmacyCard key={item.id} item={item} />
          ))}
        </div>
      </Card>
    </div>
  );
}
