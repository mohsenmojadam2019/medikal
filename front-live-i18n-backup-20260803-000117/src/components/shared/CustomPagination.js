'use client';

import { Pagination, Select, Space, Typography } from 'antd';
import { useLanguage } from '@/lib/context/LanguageContext';

const { Text } = Typography;
const { Option } = Select;

export default function CustomPagination({
  current,
  total,
  pageSize,
  onChange,
  onShowSizeChange,
  showSizeChanger = true,
}) {
  const { t } = useLanguage();

  return (
    <div style={{ 
      display: 'flex', 
      justifyContent: 'space-between', 
      alignItems: 'center',
      flexWrap: 'wrap',
      gap: '16px',
      marginTop: '24px'
    }}>
      <Text type="secondary">
        {t('common.showing')} {((current - 1) * pageSize) + 1} - {Math.min(current * pageSize, total)} {t('common.of')} {total} {t('common.items')}
      </Text>
      <Pagination
        current={current}
        total={total}
        pageSize={pageSize}
        onChange={onChange}
        showSizeChanger={showSizeChanger}
        onShowSizeChange={onShowSizeChange}
        showQuickJumper
        showTotal={(total) => `${t('common.total')} ${total} ${t('common.items')}`}
      />
    </div>
  );
}
