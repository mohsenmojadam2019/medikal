'use client';

import { Card } from 'antd';

const specialtiesData = [
  { id: 1, icon: '❤️', name: 'قلب و عروق', count: 42 },
  { id: 2, icon: '🧠', name: 'مغز و اعصاب', count: 38 },
  { id: 3, icon: '🦴', name: 'ارتوپدی', count: 35 },
  { id: 4, icon: '🏥', name: 'داخلی', count: 40 },
  { id: 5, icon: '👶', name: 'اطفال', count: 28 },
  { id: 6, icon: '👩‍⚕️', name: 'زنان و زایمان', count: 32 },
  { id: 7, icon: '🧴', name: 'پوست و مو', count: 25 },
  { id: 8, icon: '👁️', name: 'چشم پزشکی', count: 20 },
  { id: 9, icon: '🦷', name: 'دندانپزشکی', count: 18 },
  { id: 10, icon: '🧪', name: 'آزمایشگاه', count: 15 },
  { id: 11, icon: '💊', name: 'داروخانه', count: 12 },
  { id: 12, icon: '🧘', name: 'روانشناسی', count: 22 },
];

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
            <a key={item.id} href="#" className="specialty-item">
              <div className="specialty-icon">{item.icon}</div>
              <span>{item.name}</span>
              <span className="count">{item.count} پزشک</span>
            </a>
          ))}
        </div>
      </Card>
    </div>
  );
}
