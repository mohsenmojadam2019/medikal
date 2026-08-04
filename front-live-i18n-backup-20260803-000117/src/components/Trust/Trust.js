'use client';

import { trustData } from '@/lib/data/mockData';

export default function Trust() {
  return (
    <div className="section">
      <div className="trust-grid">
        {trustData.map((item) => (
          <div key={item.id} className="trust-item">
            <div className="icon"><i className={`fas ${item.icon}`} /></div>
            <h4>{item.title}</h4>
            <p>{item.desc}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
