'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Input, Space, message } from 'antd';
import { SearchOutlined, TeamOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text } = Typography;
const { Search } = Input;

export default function SpecialtiesPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [specialties, setSpecialties] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filteredSpecialties, setFilteredSpecialties] = useState([]);
  const [searchText, setSearchText] = useState('');
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  // دریافت لیست تخصص‌ها از API
  const fetchSpecialties = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/specialties`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setSpecialties(data.data || []);
        setFilteredSpecialties(data.data || []);
      } else {
        message.error(data.message || 'خطا در دریافت لیست تخصص‌ها');
      }
    } catch (error) {
      console.error('Error fetching specialties:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSpecialties();
  }, []);

  // فیلتر کردن تخصص‌ها
  useEffect(() => {
    if (searchText) {
      const filtered = specialties.filter(item =>
        item.name?.toLowerCase().includes(searchText.toLowerCase()) ||
        item.description?.toLowerCase().includes(searchText.toLowerCase())
      );
      setFilteredSpecialties(filtered);
    } else {
      setFilteredSpecialties(specialties);
    }
  }, [searchText, specialties]);

  const specialtyIcons = {
    'قلب و عروق': '❤️',
    'مغز و اعصاب': '🧠',
    'ارتوپدی': '🦴',
    'داخلی': '🏥',
    'اطفال': '👶',
    'زنان و زایمان': '👩‍⚕️',
    'پوست و مو': '🧴',
    'چشم پزشکی': '👁️',
    'دندانپزشکی': '🦷',
    'آزمایشگاه': '🧪',
    'داروخانه': '💊',
    'روانشناسی': '🧘',
    'جراحی عمومی': '🔪',
    'اورولوژی': '🫘',
    'گوش و حلق و بینی': '👂',
    'رادیولوژی': '📷',
    'فیزیوتراپی': '💪',
    'تغذیه': '🥗',
    'طب کار': '👔',
    'طب سوزنی': '📌',
  };

  const getSpecialtyIcon = (name) => {
    return specialtyIcons[name] || '🔬';
  };

  if (loading) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
          <Spin size="large" />
          <p style={{ marginTop: '16px' }}>{t('common.loading')}</p>
        </div>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main style={{ minHeight: 'calc(100vh - 200px)' }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          {/* هدر صفحه */}
          <div style={{ marginBottom: '32px' }}>
            <Title level={2}>🔬 {t('nav.specialties')}</Title>
            <Text type="secondary">لیست تخصص‌های پزشکی موجود</Text>
          </div>

          {/* جستجو */}
          <div style={{ marginBottom: '24px', maxWidth: '400px' }}>
            <Search
              placeholder="جستجوی تخصص..."
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              enterButton
            />
          </div>

          {/* لیست تخصص‌ها */}
          {filteredSpecialties.length > 0 ? (
            <Row gutter={[16, 16]}>
              {filteredSpecialties.map((specialty) => (
                <Col xs={24} sm={12} md={8} lg={6} key={specialty.id}>
                  <Card
                    hoverable
                    style={{ 
                      borderRadius: '12px', 
                      textAlign: 'center',
                      height: '100%',
                      cursor: 'pointer'
                    }}
                    onClick={() => router.push(`/${locale}/doctors?specialty=${specialty.id}`)}
                  >
                    <div style={{ fontSize: '48px', marginBottom: '12px' }}>
                      {getSpecialtyIcon(specialty.name)}
                    </div>
                    <Title level={4} style={{ marginBottom: '4px' }}>
                      {specialty.name}
                    </Title>
                    <Text type="secondary" style={{ fontSize: '12px' }}>
                      {specialty.description || 'توضیحاتی برای این تخصص'}
                    </Text>
                    <div style={{ marginTop: '12px' }}>
                      <Tag color="blue">
                        <TeamOutlined /> {specialty.doctors_count || 0} پزشک
                      </Tag>
                    </div>
                  </Card>
                </Col>
              ))}
            </Row>
          ) : (
            <Empty description="هیچ تخصصی یافت نشد" />
          )}
        </div>
      </main>
      <Footer />
    </>
  );
}
