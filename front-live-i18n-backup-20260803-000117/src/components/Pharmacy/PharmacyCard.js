'use client';

import { Button, Tag, message } from 'antd';
import { useState } from 'react';

export default function PharmacyCard({ item }) {
  const [loading, setLoading] = useState(false);

  const handleOrder = () => {
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      if (item.requiresPrescription) {
        message.warning('⚠️ این دارو نیاز به نسخه پزشک دارد. لطفاً نسخه خود را ارسال کنید.');
      } else {
        message.success('✅ سفارش با موفقیت ثبت شد!');
      }
    }, 1000);
  };

  const handleSendPrescription = () => {
    message.info('📤 در حال انتقال به بخش ارسال نسخه...');
  };

  return (
    <div className="pharmacy-card">
      <div className="pharmacy-icon">{item.image}</div>
      <div className="pharmacy-info">
        <h4>{item.name}</h4>
        <Tag color="blue">{item.category}</Tag>
        <div className="pharmacy-stock">
          موجودی: <span className={item.stock < 30 ? 'low-stock' : ''}>{item.stock}</span>
        </div>
        <div className="pharmacy-price">
          {item.price.toLocaleString()} <small>تومان</small>
        </div>
        {item.requiresPrescription && (
          <Tag color="orange" className="prescription-tag">نیاز به نسخه</Tag>
        )}
      </div>
      <div className="pharmacy-actions">
        <Button
          type="primary"
          loading={loading}
          onClick={handleOrder}
          block
        >
          سفارش
        </Button>
        {item.requiresPrescription && (
          <Button
            type="default"
            onClick={handleSendPrescription}
            block
          >
            ارسال نسخه
          </Button>
        )}
      </div>
    </div>
  );
}
