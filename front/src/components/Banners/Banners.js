'use client';

import { bannersData } from '@/lib/data/mockData';

export default function Banners() {
  return (
    <div className="section">
      <div className="banners-grid">
        {bannersData.map((banner) => (
          <div key={banner.id} className={`banner-card ${banner.className}`}>
            <div className="icon">{banner.icon}</div>
            <h3>{banner.title}</h3>
            <p>{banner.desc}</p>
            <a href={banner.link} className="banner-link">
              بیشتر بدانید <i className="fas fa-arrow-left" />
            </a>
          </div>
        ))}
      </div>
    </div>
  );
}
