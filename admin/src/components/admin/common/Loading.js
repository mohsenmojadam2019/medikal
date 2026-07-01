import { Spin } from 'antd';

export default function Loading({ fullScreen = false, text = 'در حال بارگذاری...' }) {
  const style = fullScreen
    ? {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: '100vh',
        flexDirection: 'column',
        gap: 16,
      }
    : {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: 400,
        flexDirection: 'column',
        gap: 16,
      };

  return (
    <div style={style}>
      <Spin size="large" />
      <span style={{ color: '#64748b', fontSize: 14 }}>{text}</span>
    </div>
  );
}
