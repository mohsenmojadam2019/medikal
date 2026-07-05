'use client';

const statsData = [
  { id: 1, icon: 'fa-user-md', number: '۵۰۰+', label: 'پزشک متخصص', color: 'blue' },
  { id: 2, icon: 'fa-calendar-check', number: '۱۲,۴۰۰+', label: 'نوبت رزرو شده', color: 'green' },
  { id: 3, icon: 'fa-star', number: '۴.۹', label: 'میانگین امتیاز', color: 'purple' },
  { id: 4, icon: 'fa-clock', number: '۹۸٪', label: 'رضایت بیماران', color: 'orange' },
];

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
