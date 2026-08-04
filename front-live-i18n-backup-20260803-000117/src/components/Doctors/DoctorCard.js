'use client';

import { useState } from 'react';
import { Button, message } from 'antd';
import { HeartOutlined, HeartFilled } from '@ant-design/icons';

export default function DoctorCard({ doctor }) {
  const [isFavorite, setIsFavorite] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleBook = () => {
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      message.success('✅ نوبت با موفقیت رزرو شد! پیامک تأیید برای شما ارسال خواهد شد.');
    }, 1000);
  };

  return (
    <div className="doctor-card">
      {doctor.featured && <span className="featured">{doctor.featured}</span>}
      <button
        className="wishlist-btn"
        onClick={() => setIsFavorite(!isFavorite)}
      >
        {isFavorite ? <HeartFilled style={{ color: '#ef4444' }} /> : <HeartOutlined />}
      </button>

      <div className="doctor-top">
        <div className="doctor-avatar">{doctor.avatar}</div>
        <div className="doctor-info">
          <h3>{doctor.name}</h3>
          <div className="specialty">{doctor.specialty}</div>
          <div className="clinic">
            <i className="fas fa-map-marker-alt" /> {doctor.clinic}
          </div>
        </div>
      </div>

      <div className="doctor-meta">
        <div className="doctor-rating">
          <span className="stars">
            <i className="fas fa-star" /> {doctor.rating}
          </span>
          <span className="count">({doctor.reviews} نظر)</span>
        </div>
        <span className={`doctor-availability ${!doctor.available ? 'busy' : ''}`}>
          <i className="fas fa-circle" /> {doctor.available ? 'نوبت دارد' : 'نوبت محدود'}
        </span>
      </div>

      <div className="doctor-price">
        {doctor.fee.toLocaleString()} <small>تومان</small>
      </div>

      <div className="doctor-actions">
        <Button
          type="primary"
          className="btn-book"
          loading={loading}
          onClick={handleBook}
        >
          رزرو نوبت
        </Button>
        <Button className="btn-book outline">پروفایل</Button>
      </div>
    </div>
  );
}
