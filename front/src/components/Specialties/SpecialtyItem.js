'use client';

import Link from 'next/link';

export default function SpecialtyItem({ icon, name, count }) {
  return (
    <Link href="#" className="specialty-item">
      <div className="specialty-icon">{icon}</div>
      <span>{name}</span>
      <span className="count">{count} پزشک</span>
    </Link>
  );
}
