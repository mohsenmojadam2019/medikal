'use client';

import { useState, useEffect } from 'react';
import { Card, Collapse, Typography, Spin, Empty, Input, Space, Tag, message } from 'antd';
import { SearchOutlined, QuestionCircleOutlined } from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text } = Typography;
const { Panel } = Collapse;
const { Search } = Input;

export default function FAQPage() {
  const { t, locale } = useLanguage();
  const [faqs, setFaqs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchText, setSearchText] = useState('');
  const [filteredFaqs, setFilteredFaqs] = useState([]);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  // دریافت لیست سوالات متداول از API
  const fetchFaqs = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/faqs`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setFaqs(data.data || []);
        setFilteredFaqs(data.data || []);
      } else {
        message.error(data.message || 'خطا در دریافت سوالات متداول');
      }
    } catch (error) {
      console.error('Error fetching faqs:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchFaqs();
  }, []);

  // فیلتر کردن سوالات
  useEffect(() => {
    if (searchText) {
      const filtered = faqs.filter(item =>
        item.question?.toLowerCase().includes(searchText.toLowerCase()) ||
        item.answer?.toLowerCase().includes(searchText.toLowerCase()) ||
        item.category?.toLowerCase().includes(searchText.toLowerCase())
      );
      setFilteredFaqs(filtered);
    } else {
      setFilteredFaqs(faqs);
    }
  }, [searchText, faqs]);

  // گروه‌بندی بر اساس دسته‌بندی
  const groupedFaqs = filteredFaqs.reduce((groups, faq) => {
    const category = faq.category || 'عمومی';
    if (!groups[category]) {
      groups[category] = [];
    }
    groups[category].push(faq);
    return groups;
  }, {});

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
          <div style={{ marginBottom: '32px', textAlign: 'center' }}>
            <QuestionCircleOutlined style={{ fontSize: '48px', color: '#2563eb' }} />
            <Title level={2} style={{ marginTop: '16px' }}>
              سوالات متداول
            </Title>
            <Text type="secondary">پاسخ به سوالات رایج شما</Text>
          </div>

          {/* جستجو */}
          <div style={{ marginBottom: '24px', maxWidth: '500px', margin: '0 auto 24px' }}>
            <Search
              placeholder="جستجوی سوال..."
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              enterButton
              size="large"
            />
          </div>

          {/* لیست سوالات */}
          {filteredFaqs.length > 0 ? (
            <div>
              {Object.entries(groupedFaqs).map(([category, items]) => (
                <div key={category} style={{ marginBottom: '24px' }}>
                  <Title level={4}>
                    <Tag color="blue">{category}</Tag>
                  </Title>
                  <Card style={{ borderRadius: '12px' }}>
                    <Collapse accordion>
                      {items.map((faq) => (
                        <Panel
                          header={
                            <Space>
                              <Text strong>{faq.question}</Text>
                              {faq.is_popular && <Tag color="gold">پرطرفدار</Tag>}
                            </Space>
                          }
                          key={faq.id}
                        >
                          <Text>{faq.answer}</Text>
                        </Panel>
                      ))}
                    </Collapse>
                  </Card>
                </div>
              ))}
            </div>
          ) : (
            <Empty description="هیچ سوالی یافت نشد" />
          )}
        </div>
      </main>
      <Footer />
    </>
  );
}
