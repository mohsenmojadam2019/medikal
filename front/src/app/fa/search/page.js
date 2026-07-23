// /src/app/fa/search/page.js
'use client';

import { useState, useEffect } from 'react';
import { useSearchParams } from 'next/navigation';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Button, message } from 'antd';
import { MedicineBoxOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text } = Typography;

export default function SearchPage() {
    const searchParams = useSearchParams();
    const { locale } = useLanguage();
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState([]);
    const [query, setQuery] = useState('');

    useEffect(() => {
        const q = searchParams.get('q') || '';
        setQuery(q);

        // دریافت نتایج از localStorage
        const savedResults = localStorage.getItem('searchResults');
        if (savedResults) {
            try {
                const parsed = JSON.parse(savedResults);
                setResults(parsed);
            } catch (error) {
                console.error('Error parsing search results:', error);
                setResults([]);
            }
        }
        setLoading(false);
    }, [searchParams]);

    const addToCart = (drug) => {
        let cart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');
        const existing = cart.find(item => item.id === drug.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                id: drug.id,
                name: drug.generic_name || drug.name || 'دارو',
                price: parseFloat(drug.price) || 0,
                quantity: 1,
                stock: drug.stock || 0,
            });
        }
        localStorage.setItem('pharmacyCart', JSON.stringify(cart));
        message.success(`${drug.generic_name || drug.name} به سبد خرید اضافه شد`);
    };

    if (loading) {
        return (
            <>
                <Header />
                <div style={{ display: 'flex', justifyContent: 'center', padding: '60px' }}>
                    <Spin size="large" />
                </div>
                <Footer />
            </>
        );
    }

    return (
        <>
            <Header />
            <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '24px 20px' }}>
                    <Title level={2}>
                        نتایج جستجو برای: "{query}"
                    </Title>
                    <Text type="secondary">{results.length} نتیجه یافت شد</Text>

                    <Row gutter={[16, 16]} style={{ marginTop: '24px' }}>
                        {results.length > 0 ? (
                            results.map((item) => (
                                <Col xs={24} sm={12} md={8} lg={6} key={item.id}>
                                    <Card
                                        hoverable
                                        style={{ borderRadius: '12px', height: '100%' }}
                                    >
                                        <div style={{ textAlign: 'center', padding: '16px 0' }}>
                                            <MedicineBoxOutlined style={{ fontSize: '48px', color: '#2563eb' }} />
                                        </div>
                                        <Card.Meta
                                            title={item.generic_name || item.name || 'بدون نام'}
                                            description={
                                                <div>
                                                    <Tag color="blue">{item.category || 'عمومی'}</Tag>
                                                    {item.requires_prescription && (
                                                        <Tag color="orange">نیاز به نسخه</Tag>
                                                    )}
                                                    <div style={{ marginTop: '8px' }}>
                                                        <Text strong style={{ color: '#2563eb' }}>
                                                            {parseFloat(item.price).toLocaleString()} تومان
                                                        </Text>
                                                    </div>
                                                    <div style={{ marginTop: '8px' }}>
                                                        <Tag color={item.stock > 0 ? 'green' : 'red'}>
                                                            {item.stock > 0 ? `موجود (${item.stock})` : 'ناموجود'}
                                                        </Tag>
                                                    </div>
                                                    <Button
                                                        type="primary"
                                                        block
                                                        style={{ marginTop: '12px' }}
                                                        onClick={() => addToCart(item)}
                                                        disabled={item.stock === 0}
                                                    >
                                                        افزودن به سبد خرید
                                                    </Button>
                                                </div>
                                            }
                                        />
                                    </Card>
                                </Col>
                            ))
                        ) : (
                            <Col span={24}>
                                <Empty description="هیچ نتیجه‌ای برای جستجوی شما یافت نشد" />
                            </Col>
                        )}
                    </Row>
                </div>
            </main>
            <Footer />
        </>
    );
}