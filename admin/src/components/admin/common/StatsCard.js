import { Card } from 'antd';
import { ArrowUpOutlined, ArrowDownOutlined } from '@ant-design/icons';

const iconColors = {
  blue: { bg: '#dbeafe', color: '#2563eb' },
  green: { bg: '#d1fae5', color: '#059669' },
  yellow: { bg: '#fef3c7', color: '#d97706' },
  red: { bg: '#fee2e2', color: '#dc2626' },
  purple: { bg: '#ede9fe', color: '#7c3aed' },
  cyan: { bg: '#cffafe', color: '#0891b2' },
  pink: { bg: '#fce7f3', color: '#db2777' },
  teal: { bg: '#ccfbf1', color: '#0d9488' },
};

export default function StatsCard({
  title,
  value,
  icon,
  iconColor = 'blue',
  change,
  changeType = 'up',
  progress = 0,
  progressColor = 'blue',
}) {
  const isUp = changeType === 'up';
  const colors = iconColors[iconColor] || iconColors.blue;

  const progressColors = {
    blue: '#2563eb',
    green: '#059669',
    yellow: '#d97706',
    red: '#dc2626',
    purple: '#7c3aed',
    cyan: '#0891b2',
    teal: '#0d9488',
  };

  return (
    <Card
      style={{
        borderRadius: 12,
        borderColor: '#e8e8f0',
        boxShadow: '0 1px 3px rgba(0,0,0,0.06)',
      }}
      hoverable
    >
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          marginBottom: 4,
        }}
      >
        <div
          style={{
            width: 44,
            height: 44,
            borderRadius: 12,
            background: colors.bg,
            color: colors.color,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 20,
          }}
        >
          {icon}
        </div>
        {change && (
          <span
            style={{
              fontSize: 11,
              fontWeight: 600,
              color: isUp ? '#059669' : '#dc2626',
              display: 'inline-flex',
              alignItems: 'center',
              gap: 4,
            }}
          >
            {isUp ? <ArrowUpOutlined /> : <ArrowDownOutlined />} {change}
          </span>
        )}
      </div>

      <div style={{ fontSize: 28, fontWeight: 800, color: '#0f172a', lineHeight: 1.2 }}>
        {value}
      </div>
      <div style={{ fontSize: 13, color: '#64748b', fontWeight: 500 }}>{title}</div>

      {progress > 0 && (
        <div
          style={{
            height: 6,
            background: '#e2e8f0',
            borderRadius: 10,
            overflow: 'hidden',
            marginTop: 4,
          }}
        >
          <div
            style={{
              height: '100%',
              borderRadius: 10,
              width: `${progress}%`,
              background: progressColors[progressColor] || '#2563eb',
              transition: 'width 0.6s ease',
            }}
          />
        </div>
      )}
    </Card>
  );
}
