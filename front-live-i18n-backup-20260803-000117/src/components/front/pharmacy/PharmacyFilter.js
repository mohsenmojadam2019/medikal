'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Input, Select, Button, Space } from 'antd';
import { SearchOutlined, ReloadOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';

const { Option } = Select;

export default function PharmacyFilter({ onFilterChange, initialValues = {}, showPharmacyFilter = false }) {
    const { t } = useLanguage();
    const [filters, setFilters] = useState({
        search: initialValues.search || '',
        province_id: initialValues.province_id || 'all',
        city_id: initialValues.city_id || 'all',
        clinic_id: initialValues.clinic_id || 'all',
        pharmacy_id: initialValues.pharmacy_id || 'all',
        is_online: initialValues.is_online || 'all',
    });

    const [provinces, setProvinces] = useState([]);
    const [cities, setCities] = useState([]);
    const [clinics, setClinics] = useState([]);
    const [pharmacies, setPharmacies] = useState([]);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    useEffect(() => { fetchFilterData(); }, []);
    useEffect(() => {
        if (filters.province_id !== 'all') fetchCities(filters.province_id);
        else setCities([]);
    }, [filters.province_id]);

    const fetchFilterData = async () => {
        try {
            const [provincesRes, clinicsRes] = await Promise.all([
                fetch(`${API_URL}/api/clinics/provinces`),
                fetch(`${API_URL}/api/clinics`)
            ]);
            const provincesData = await provincesRes.json();
            if (provincesData.success) setProvinces(provincesData.data || []);
            const clinicsData = await clinicsRes.json();
            if (clinicsData.success) setClinics(clinicsData.data.data || clinicsData.data || []);
            if (showPharmacyFilter) {
                const pharmaciesRes = await fetch(`${API_URL}/api/pharmacy/pharmacies?per_page=100`);
                const pharmaciesData = await pharmaciesRes.json();
                if (pharmaciesData.success) setPharmacies(pharmaciesData.data.data || pharmaciesData.data || []);
            }
        } catch (error) { console.error('Error fetching filter data:', error); }
    };

    const fetchCities = async (provinceId) => {
        try {
            const res = await fetch(`${API_URL}/api/clinics/provinces/${provinceId}/cities`);
            const data = await res.json();
            if (data.success) setCities(data.data || []);
        } catch (error) { console.error('Error fetching cities:', error); }
    };

    const handleChange = (key, value) => {
        const newFilters = { ...filters, [key]: value };
        if (key === 'province_id' && value !== filters.province_id) newFilters.city_id = 'all';
        setFilters(newFilters);
    };

    const handleSearch = () => onFilterChange(filters);
    const handleReset = () => {
        const reset = { search: '', province_id: 'all', city_id: 'all', clinic_id: 'all', pharmacy_id: 'all', is_online: 'all' };
        setFilters(reset);
        onFilterChange(reset);
    };

    return (
        <Card style={{ borderRadius: 16, marginBottom: 24 }}>
            <Row gutter={[16, 16]}>
                <Col xs={24} md={showPharmacyFilter ? 4 : 6}>
                    <Input placeholder={t('pharmacy.search')} prefix={<SearchOutlined />} value={filters.search} onChange={(e) => handleChange('search', e.target.value)} onPressEnter={handleSearch} allowClear />
                </Col>
                <Col xs={12} md={showPharmacyFilter ? 3 : 4}>
                    <Select placeholder={t('pharmacy.selectProvince')} style={{ width: '100%' }} value={filters.province_id} onChange={(v) => handleChange('province_id', v)} allowClear>
                        <Option value="all">{t('pharmacy.allProvinces')}</Option>
                        {provinces.map(p => <Option key={p.id} value={p.id}>{p.name}</Option>)}
                    </Select>
                </Col>
                <Col xs={12} md={showPharmacyFilter ? 3 : 4}>
                    <Select placeholder={t('pharmacy.selectCity')} style={{ width: '100%' }} value={filters.city_id} onChange={(v) => handleChange('city_id', v)} disabled={filters.province_id === 'all'} allowClear>
                        <Option value="all">{t('pharmacy.allCities')}</Option>
                        {cities.map(c => <Option key={c.id} value={c.id}>{c.name}</Option>)}
                    </Select>
                </Col>
                <Col xs={12} md={showPharmacyFilter ? 3 : 4}>
                    <Select placeholder={t('pharmacy.selectClinic')} style={{ width: '100%' }} value={filters.clinic_id} onChange={(v) => handleChange('clinic_id', v)} allowClear>
                        <Option value="all">{t('pharmacy.allClinics')}</Option>
                        {clinics.map(c => <Option key={c.id} value={c.id}>{c.name}</Option>)}
                    </Select>
                </Col>
                {showPharmacyFilter && (
                    <Col xs={12} md={3}>
                        <Select placeholder={t('pharmacy.selectPharmacy')} style={{ width: '100%' }} value={filters.pharmacy_id} onChange={(v) => handleChange('pharmacy_id', v)} allowClear>
                            <Option value="all">{t('pharmacy.allPharmacies')}</Option>
                            {pharmacies.map(p => <Option key={p.id} value={p.id}>{p.name}</Option>)}
                        </Select>
                    </Col>
                )}
                <Col xs={12} md={showPharmacyFilter ? 3 : 3}>
                    <Select placeholder={t('pharmacy.status')} style={{ width: '100%' }} value={filters.is_online} onChange={(v) => handleChange('is_online', v)}>
                        <Option value="all">{t('pharmacy.allStatus')}</Option>
                        <Option value="true">{t('pharmacy.online')}</Option>
                        <Option value="false">{t('pharmacy.offline')}</Option>
                    </Select>
                </Col>
                <Col xs={24} md={showPharmacyFilter ? 3 : 3}>
                    <Space style={{ width: '100%' }}>
                        <Button type="primary" onClick={handleSearch} style={{ flex: 1 }}>{t('common.search')}</Button>
                        <Button icon={<ReloadOutlined />} onClick={handleReset} />
                    </Space>
                </Col>
            </Row>
        </Card>
    );
}