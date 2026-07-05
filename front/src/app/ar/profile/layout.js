'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

export default function ProfileLayout({ children }) {
  const router = useRouter();

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/fa/login');
    }
  }, [router]);

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        {children}
      </main>
      <Footer />
    </>
  );
}
