// /src/components/Header/SearchBox.js
'use client';

import { Input, message } from 'antd';
import { SearchOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useState } from 'react';
import { useLanguage } from '@/lib/context/LanguageContext';

export default function SearchBox() {
    const router = useRouter();
    const { locale } = useLanguage();
    const [loading, setLoading] = useState(false);
    const [value, setValue] = useState('');
    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    const handleSearch = async (query) => {
        if (!query.trim()) {
            message.warning('لطفاً عبارت جستجو را وارد کنید');
            return;
        }

        setLoading(true);
        try {
            const token = localStorage.getItem('token');

            // ✅ جستجوی داروها از API درست
            const res = await fetch(`${API_URL}/api/drugs/active`, {
                headers: {
                    'Authorization': token ? `Bearer ${token}` : '',
                    'Content-Type': 'application/json',
                },
            });

            const data = await res.json();
            console.log('📦 All drugs:', data);

            if (data.success) {
                let allDrugs = data.data?.data || data.data || [];
                const searchTerm = query.toLowerCase().trim();

                // ✅ فیلتر در فرانت‌اند
                const filtered = allDrugs.filter(drug => {
                    const name = (drug.generic_name || drug.name || '').toLowerCase();
                    const category = (drug.category || '').toLowerCase();
                    const code = (drug.code || '').toLowerCase();
                    return name.includes(searchTerm) ||
                        category.includes(searchTerm) ||
                        code.includes(searchTerm);
                });

                console.log('🔍 Filtered results:', filtered.length);

                // ذخیره و هدایت
                localStorage.setItem('searchResults', JSON.stringify(filtered));
                localStorage.setItem('searchQuery', query);
                router.push(`/${locale}/search?q=${encodeURIComponent(query)}`);
            } else {
                message.error('خطا در دریافت اطلاعات');
            }
        } catch (error) {
            console.error('Search error:', error);
            message.error('خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="search-box">
            <Input.Search
                size="large"
                placeholder="جستجوی دارو..."
                prefix={<SearchOutlined />}
                suffix={<span className="search-shortcut">Ctrl + K</span>}
                value={value}
                onChange={(e) => setValue(e.target.value)}
                onSearch={handleSearch}
                loading={loading}
                enterButton="جستجو"
                style={{ borderRadius: '12px' }}
            />
        </div>
    );
}