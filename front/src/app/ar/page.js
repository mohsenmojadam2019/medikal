'use client';

import { useEffect } from 'react';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Hero from '@/components/front/Hero/Hero';
import Stats from '@/components/front/Stats/Stats';
import Specialties from '@/components/front/Specialties/Specialties';
import Doctors from '@/components/front/Doctors/Doctors';

export default function HomePage() {
  useEffect(() => {
    const handleKeyDown = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) searchInput.focus();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, []);

  return (
    <>
      <Header />
      <main>
        <div className="container">
          <Hero />
          <Stats />
          <Specialties />
          <Doctors />
        </div>
      </main>
      <Footer />

      <button
        className="floating-btn"
        title="پشتیبانی آنلاین"
        onClick={() => alert('پشتیبانی آنلاین: در حال حاضر در دسترس است.')}
      >
        <i className="fas fa-comment-dots" />
      </button>
    </>
  );
}
