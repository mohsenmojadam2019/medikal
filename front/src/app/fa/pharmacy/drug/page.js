'use client';

import { useState, useEffect, useCallback } from 'react';
import {
    Card, Row, Col, Typography, Spin, Empty, Tag,
    Button, Input, Space, App, Select, Pagination,
    Tooltip
} from 'antd';
import {
    SearchOutlined, MedicineBoxOutlined,
    EyeOutlined, ShoppingCartOutlined
} from '@ant-design/icons';
import { useRouter, useSearchParams } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function DrugsPage() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const { t, locale } = useLanguage();
    const { message: appMessage } = App.useApp();

    const [drugs, setDrugs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [pageSize, setPageSize] = useState(12);
    const [totalItems, setTotalItems] = useState(0);

    const [searchTerm, setSearchTerm] = useState('');
    const [selectedPharmacy, setSelectedPharmacy] = useState(searchParams?.get('pharmacy_id') || 'all');
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [requiresPrescription, setRequiresPrescription] = useState('all');
    const [pharmacies, setPharmacies] = useState([]);
    const [categories, setCategories] = useState([]);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    const fetchDrugs = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (selectedPharmacy !== 'all') params.append('pharmacy_id', selectedPharmacy);
            if (selectedCategory !== 'all') params.append('category', selectedCategory);
            if (requiresPrescription !== 'all') params.append('requires_prescription', requiresPrescription === 'required' ? '1' : '0');
            params.append('page', currentPage);
            params.append('per_page', pageSize);

            const res = await fetch(`${API_URL}/api/drugs/active?${params}`);
            const data = await res.json();
            if (data.success) {
                const drugsData = data.data.data || data.data || [];
                setDrugs(drugsData);
                setTotalItems(data.data.total || drugsData.length || 0);
                const uniqueCategories = [...new Set(drugsData.map(d => d.category).filter(Boolean))];
                setCategories(uniqueCategories);
            } else {
                appMessage.error(data.message || t('pharmacy.errorFetchingDrugs'));
            }
        } catch (error) {
            console.error('Error fetching drugs:', error);
            appMessage.error(t('pharmacy.serverError'));
        } finally {
            setLoading(false);
        }
    }, [searchTerm, selectedPharmacy, selectedCategory, requiresPrescription, currentPage, pageSize, appMessage, t]);

    const fetchPharmacies = useCallback(async () => {
        try {
            const res = await fetch(`${API_URL}/api/pharmacy/pharmacies?per_page=100`);
            const data = await res.json();
            if (data.success) setPharmacies(data.data.data || data.data || []);
        } catch (error) {
            console.error('Error fetching pharmacies:', error);
        }
    }, [API_URL]);

    useEffect(() => {
        fetchDrugs();
        fetchPharmacies();
    }, [fetchDrugs, fetchPharmacies]);

    const handleSearch = () => { setCurrentPage(1); fetchDrugs(); };

    if (loading && drugs.length === 0) return <><Header /><LoadingSpinner /><Footer /></>;

    return (
        <>
            <Header />
            <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc', padding: '24px 20px' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
                    <Breadcrumb items={[
                        { title: t('pharmacy.title'), href: `/${locale}/pharmacy` },
                        { title: t('pharmacy.drugs') }
                    ]} />
                    <Title level={2}>💊 {t('pharmacy.drugs')}</Title>

                    <Card style={{ borderRadius: 16, marginBottom: 24 }}>
                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={6}>
                                <Input placeholder={t('pharmacy.searchDrugs')} prefix={<SearchOutlined />} value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} onPressEnter={handleSearch} allowClear />
                            </Col>
                            <Col xs={12} md={5}>
                                <Select placeholder={t('pharmacy.selectPharmacy')} style={{ width: '100%' }} value={selectedPharmacy} onChange={setSelectedPharmacy} allowClear>
                                    <Option value="all">{t('pharmacy.allPharmacies')}</Option>
                                    {pharmacies.map(p => <Option key={p.id} value={p.id}>{p.name}</Option>)}
                                </Select>
                            </Col>
                            <Col xs={12} md={5}>
                                <Select placeholder={t('pharmacy.selectCategory')} style={{ width: '100%' }} value={selectedCategory} onChange={setSelectedCategory} allowClear>
                                    <Option value="all">{t('pharmacy.allCategories')}</Option>
                                    {categories.map(cat => <Option key={cat} value={cat}>{cat}</Option>)}
                                </Select>
                            </Col>
                            <Col xs={12} md={5}>
                                <Select placeholder={t('pharmacy.prescription')} style={{ width: '100%' }} value={requiresPrescription} onChange={setRequiresPrescription}>
                                    <Option value="all">{t('pharmacy.all')}</Option>
                                    <Option value="required">{t('pharmacy.withPrescription')}</Option>
                                    <Option value="not_required">{t('pharmacy.withoutPrescription')}</Option>
                                </Select>
                            </Col>
                            <Col xs={24} md={3}>
                                <Button type="primary" block onClick={handleSearch}>{t('common.search')}</Button>
                            </Col>
                        </Row>
                    </Card>

                    <Text type="secondary">{totalItems} {t('pharmacy.drugsFound')}</Text>

                    {drugs.length > 0 ? (
                        <>
                            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                                {drugs.map((drug) => (
                                    <Col xs={24} sm={12} md={8} lg={6} key={drug.id}>
                                        <Card
                                            hoverable
                                            style={{ borderRadius: 12, height: '100%' }}
                                            cover={
                                                <div style={{
                                                    height: 140, background: 'linear-gradient(135deg, #f0f5ff, #e0e7ff)',
                                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                    position: 'relative', borderRadius: '12px 12px 0 0'
                                                }}>
                                                    <MedicineBoxOutlined style={{ fontSize: 56, color: '#2563eb' }} />
                                                    {drug.requires_prescription && <Tag color="orange" style={{ position: 'absolute', top: 8, left: 8, fontSize: 10 }}>{t('pharmacy.requiresPrescription')}</Tag>}
                                                    {drug.stock === 0 && <Tag color="red" style={{ position: 'absolute', top: 8, right: 8, fontSize: 10 }}>{t('pharmacy.outOfStock')}</Tag>}
                                                    {drug.pharmacy && <Tag color="blue" style={{ position: 'absolute', bottom: 8, right: 8, fontSize: 10 }}>{drug.pharmacy.name}</Tag>}
                                                </div>
                                            }
                                        >
                                            <Tooltip title={drug.name}>
                                                <Text strong style={{ fontSize: 14 }}>{drug.name.length > 20 ? drug.name.substring(0, 20) + '...' : drug.name}</Text>
                                            </Tooltip>
                                            {drug.generic_name && <Text type="secondary" style={{ fontSize: 12, display: 'block' }}>{drug.generic_name}</Text>}
                                            <div><Tag color="blue" style={{ fontSize: 10 }}>{drug.category || 'عمومی'}</Tag></div>
                                            <div><Text strong style={{ color: '#2563eb', fontSize: 16 }}>{drug.price?.toLocaleString() || 0}</Text><Text type="secondary" style={{ fontSize: 12 }}> تومان</Text></div>
                                            <Text type="secondary" style={{ fontSize: 12 }}>{t('pharmacy.stock')}: {drug.stock > 0 ? `${drug.stock} عدد` : t('pharmacy.outOfStock')}</Text>
                                        </Card>
                                    </Col>
                                ))}
                            </Row>
                            <div style={{ marginTop: 32, textAlign: 'center' }}>
                                <Pagination current={currentPage} total={totalItems} pageSize={pageSize} onChange={(page) => setCurrentPage(page)} showSizeChanger onShowSizeChange={(_, size) => { setPageSize(size); setCurrentPage(1); }} />
                            </div>
                        </>
                    ) : (
                        <Empty description={t('pharmacy.noDrugs')}>
                            <Button type="primary" onClick={() => { setSearchTerm(''); setSelectedPharmacy('all'); setSelectedCategory('all'); setRequiresPrescription('all'); setCurrentPage(1); fetchDrugs(); }}>{t('pharmacy.resetFilters')}</Button>
                        </Empty>
                    )}
                </div>
            </main>
            <Footer />
        </>
    );
}