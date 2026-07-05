'use client';

import { Card } from 'antd';
import { specialtiesData } from '@/lib/data/mockData';
import SpecialtyItem from './SpecialtyItem';

export default function Specialties() {
  return (
    <div className="section">
      <div className="section-header">
        <div className="section-header-left">
          <h2>
            <i className="fas fa-stethoscope" style={{ color: 'var(--primary)' }} /> تخصص‌های پزشکی
          </h2>
          <span className="tag">۳۰ تخصص</span>
          <span className="tag hot">محبوب</span>
        </div>
        <a href="#">مشاهده همه <i className="fas fa-chevron-left" /></a>
      </div>
      <Card>
        <div className="specialties-grid">
          {specialtiesData.map((item) => (
            <SpecialtyItem
              key={item.id}
              icon={item.icon}
              name={item.name}
              count={item.count}
            />
          ))}
        </div>
      </Card>
    </div>
  );
}
