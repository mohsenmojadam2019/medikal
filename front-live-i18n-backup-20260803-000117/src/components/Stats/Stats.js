'use client';

import { statsData } from '@/lib/data/mockData';

const colorMap = {
  blue: 'blue',
  green: 'green',
  purple: 'purple',
  orange: 'orange',
};

export default function Stats() {
  return (
    <div className="stats-row">
      {statsData.map((stat) => (
        <div key={stat.id} className="stat-card">
          <div className={`stat-icon ${colorMap[stat.color]}`}>
            <i className={`fas ${stat.icon}`} />
          </div>
          <div className="stat-info">
            <div className="number">{stat.number}</div>
            <div className="label">{stat.label}</div>
          </div>
        </div>
      ))}
    </div>
  );
}
