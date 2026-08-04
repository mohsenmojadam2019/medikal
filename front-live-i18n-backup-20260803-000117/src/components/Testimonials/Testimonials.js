'use client';

import { testimonialsData } from '@/lib/data/mockData';
import TestimonialCard from './TestimonialCard';

export default function Testimonials() {
  return (
    <div className="section">
      <div className="section-header">
        <div className="section-header-left">
          <h2>
            <i className="fas fa-quote-right" style={{ color: 'var(--secondary)' }} /> نظرات بیماران
          </h2>
          <span className="tag">تجربه کاربران</span>
        </div>
        <a href="#">مشاهده همه <i className="fas fa-chevron-left" /></a>
      </div>
      <div className="testimonials-grid">
        {testimonialsData.map((item) => (
          <TestimonialCard key={item.id} testimonial={item} />
        ))}
      </div>
    </div>
  );
}
