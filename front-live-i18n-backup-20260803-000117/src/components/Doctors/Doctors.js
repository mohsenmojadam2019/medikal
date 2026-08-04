'use client';

import { doctorsData } from '@/lib/data/mockData';
import DoctorCard from './DoctorCard';

export default function Doctors() {
  return (
    <div className="section">
      <div className="section-header">
        <div className="section-header-left">
          <h2>
            <i className="fas fa-star" style={{ color: 'var(--warning)' }} /> پزشکان برتر
          </h2>
          <span className="tag">پرامتیاز</span>
        </div>
        <a href="#">مشاهده همه <i className="fas fa-chevron-left" /></a>
      </div>
      <div className="doctors-grid">
        {doctorsData.map((doctor) => (
          <DoctorCard key={doctor.id} doctor={doctor} />
        ))}
      </div>
    </div>
  );
}
