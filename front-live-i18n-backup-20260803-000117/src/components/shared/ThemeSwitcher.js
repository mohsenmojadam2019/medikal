'use client';

import { Switch, Space, Typography } from 'antd';
import { MoonOutlined, SunOutlined } from '@ant-design/icons';
import { useTheme } from '@/lib/context/ThemeContext';

const { Text } = Typography;

export default function ThemeSwitcher() {
  const { theme, toggleTheme, isDark } = useTheme();

  return (
    <Space>
      <SunOutlined style={{ color: isDark ? '#94a3b8' : '#f59e0b' }} />
      <Switch
        checked={isDark}
        onChange={toggleTheme}
        checkedChildren={<MoonOutlined />}
        unCheckedChildren={<SunOutlined />}
      />
      <MoonOutlined style={{ color: isDark ? '#e2e8f0' : '#94a3b8' }} />
    </Space>
  );
}
