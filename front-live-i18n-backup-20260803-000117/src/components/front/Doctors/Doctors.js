'use client';

import { Button, Card, Rate, Tag } from 'antd';

const doctorsData = [
  {
    id: 1,
    name: 'دکتر محمد رضایی',
    specialty: 'متخصص قلب و عروق',
    clinic: 'بیمارستان قلب تهران',
    rating: 4.9,
    reviews: 324,
    available: true,
    fee: 350000,
    featured: 'ویژه',
    avatar: 'م',
  },
  {
    id: 2,
    name: 'دکتر سارا حسینی',
    specialty: 'متخصص مغز و اعصاب',
    clinic: 'کلینیک مغز و اعصاب',
    rating: 4.8,
    reviews: 256,
    available: true,
    fee: 400000,
    featured: null,
    avatar: 'س',
  },
  {
    id: 3,
    name: 'دکتر علی کریمی',
    specialty: 'جراح ارتوپد',
    clinic: 'بیمارستان ارتوپدی',
    rating: 4.7,
    reviews: 189,
    available: false,
    fee: 450000,
    featured: null,
    avatar: 'ع',
  },
  {
    id: 4,
    name: 'دکتر ندا محمدی',
    specialty: 'متخصص داخلی',
    clinic: 'کلینیک داخلی تهران',
    rating: 4.9,
    reviews: 412,
    available: true,
    fee: 300000,
    featured: 'جدید',
    avatar: 'ن',
  },
];

export default function Doctors() {
  return (
    <div className="section">
      <div className="section-header">
        <div className="section-header-left">
          <h2>
            <i className="fas fa-star" style={{ color: 'var(--warning)' }} /> پزشکان برتر
          </h2>
          <span className="tag">پرامتیاز</span>
        </div>
        <a href="#">مشاهده همه <i className="fas fa-chevron-left" /></a>
      </div>
      <div className="doctors-grid">
        {doctorsData.map((doctor) => (
          <div key={doctor.id} className="doctor-card">
            {doctor.featured && <span className="featured">{doctor.featured}</span>}
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
                <Rate disabled defaultValue={doctor.rating} allowHalf style={{ fontSize: '14px' }} />
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
              <Button type="primary" className="btn-book">رزرو نوبت</Button>
              <Button className="btn-book outline">پروفایل</Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
