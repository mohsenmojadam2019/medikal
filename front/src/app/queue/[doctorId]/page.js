'use client';

import { useParams } from 'next/navigation';
import QueueDisplay from '@/components/QueueDisplay/QueueDisplay';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

export default function QueuePage() {
    const params = useParams();
    const doctorId = params.doctorId;

    return (
        <>
            <Header />
            <main style={{ padding: '20px' }}>
                <QueueDisplay doctorId={doctorId} />
            </main>
            <Footer />
        </>
    );
}