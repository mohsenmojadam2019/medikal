'use client';

import { Rate } from 'antd';

export default function TestimonialCard({ testimonial }) {
  return (
    <div className="testimonial-card">
      <Rate disabled defaultValue={testimonial.stars} allowHalf className="stars" />
      <blockquote>{testimonial.text}</blockquote>
      <div className="author">
        <div className="avatar">{testimonial.avatar}</div>
        <div>
          <div className="name">{testimonial.name}</div>
          <div className="role">{testimonial.role}</div>
        </div>
      </div>
    </div>
  );
}
