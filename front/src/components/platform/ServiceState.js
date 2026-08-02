'use client';

import { Button, Empty, Typography } from 'antd';
import { ReloadOutlined, WifiOutlined } from '@ant-design/icons';

const { Text } = Typography;

export default function ServiceState({ title, description, retryLabel, onRetry, compact = false }) {
  return (
    <div className={compact ? 'medikal-service-state compact' : 'medikal-service-state'}>
      <Empty
        image={<WifiOutlined className="medikal-service-state__icon" />}
        description={<><strong>{title}</strong><Text type="secondary">{description}</Text></>}
      >
        {onRetry ? <Button icon={<ReloadOutlined />} onClick={onRetry}>{retryLabel}</Button> : null}
      </Empty>
    </div>
  );
}
