// /src/app/fa/pharmacy/payment/callback/page.js
'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Result, Button, Spin, message } from 'antd';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

export default function PharmacyPaymentCallbackPage() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const { locale } = useLanguage();
    const [loading, setLoading] = useState(true);
    const [paymentStatus, setPaymentStatus] = useState(null);
    const [orderNumber, setOrderNumber] = useState('');

    useEffect(() => {
        // دریافت پارامترها از URL
        const success = searchParams.get('success');
        const orderNumber = searchParams.get('order_number');
        const status = searchParams.get('status');

        setOrderNumber(orderNumber || '');

        console.log('📞 Payment callback page:', { success, orderNumber, status });

        // اگر success=true بود، پرداخت موفق است
        if (success === 'true' || success === '1' || status === 'paid') {
            setPaymentStatus('success');
            message.success('پرداخت با موفقیت انجام شد');

            // حذف اطلاعات سبد خرید
            localStorage.removeItem('pharmacyCart');
            localStorage.removeItem('pharmacyCheckoutData');
            localStorage.removeItem('pendingOrder');

            // بعد از 3 ثانیه به صفحه سفارشات برو
            setTimeout(() => {
                router.push(`/${locale}/profile/pharmacy-orders`);
            }, 3000);
        } else {
            setPaymentStatus('error');
            message.error('پرداخت ناموفق بود');

            setTimeout(() => {
                router.push(`/${locale}/pharmacy`);
            }, 3000);
        }

        setLoading(false);
    }, [router, searchParams, locale]);

    // نمایش لودینگ
    if (loading) {
        return (
            <>
                <Header />
                <div style={{
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    height: '60vh',
                    flexDirection: 'column'
                }}>
                    <Spin size="large" />
                    <div style={{ marginTop: '20px' }}>
                        <p>در حال تایید پرداخت...</p>
                    </div>
                </div>
                <Footer />
            </>
        );
    }

    // نمایش نتیجه پرداخت
    return (
        <>
            <div style={{
                maxWidth: '600px',
                margin: '0 auto',
                padding: '40px 20px',
                minHeight: 'calc(100vh - 200px)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            }}>
                {paymentStatus === 'success' ? (
                    <Result
                        status="success"
                        title="پرداخت با موفقیت انجام شد!"
                        subTitle={`
                            سفارش ${orderNumber ? '#' + orderNumber : ''} با موفقیت ثبت و پرداخت شد.
                            به زودی سفارش شما آماده ارسال خواهد شد.
                        `}
                        extra={[
                            <Button
                                type="primary"
                                key="orders"
                                onClick={() => router.push(`/${locale}/profile/pharmacy-orders`)}
                            >
                                مشاهده سفارشات
                            </Button>,
                            <Button
                                key="continue"
                                onClick={() => router.push(`/${locale}/pharmacy`)}
                            >
                                ادامه خرید
                            </Button>
                        ]}
                    />
                ) : (
                    <Result
                        status="error"
                        title="پرداخت ناموفق بود"
                        subTitle={`
                            متأسفانه پرداخت سفارش ${orderNumber ? '#' + orderNumber : ''} با مشکل مواجه شد.
                            لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.
                        `}
                        extra={[
                            <Button
                                type="primary"
                                key="retry"
                                onClick={() => router.push(`/${locale}/pharmacy`)}
                            >
                                تلاش مجدد
                            </Button>,
                            <Button
                                key="support"
                                onClick={() => router.push(`/${locale}/contact`)}
                            >
                                تماس با پشتیبانی
                            </Button>
                        ]}
                    />
                )}
            </div>
        </>
    );
}