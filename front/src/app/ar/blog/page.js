'use client';

import { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Spin, Empty, Tag, Input, Pagination, Button, Space, message } from 'antd';
import { SearchOutlined, CalendarOutlined, UserOutlined, EyeOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import dayjs from 'dayjs';

const { Title, Text, Paragraph } = Typography;
const { Search } = Input;

export default function BlogPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchText, setSearchText] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(9);
  const [totalPosts, setTotalPosts] = useState(0);
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

  // دریافت لیست مقالات از API
  const fetchPosts = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/blog/posts`, {
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setPosts(data.data || []);
        setTotalPosts(data.data?.length || 0);
      } else {
        message.error(data.message || 'خطا در دریافت مقالات');
      }
    } catch (error) {
      console.error('Error fetching posts:', error);
      message.error('خطا در ارتباط با سرور');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPosts();
  }, []);

  // فیلتر مقالات
  const filteredPosts = posts.filter(post =>
    post.title?.toLowerCase().includes(searchText.toLowerCase()) ||
    post.summary?.toLowerCase().includes(searchText.toLowerCase())
  );

  const paginatedPosts = filteredPosts.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  );

  const getStatusTag = (status) => {
    const map = {
      published: { color: 'success', label: 'منتشر شده' },
      draft: { color: 'warning', label: 'پیش‌نویس' },
      archived: { color: 'default', label: 'بایگانی' },
    };
    return map[status] || map.draft;
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
            <Title level={2}>📝 {t('nav.blog')}</Title>
            <Text type="secondary">جدیدترین مقالات و مطالب پزشکی</Text>
          </div>

          {/* جستجو */}
          <div style={{ marginBottom: '24px', maxWidth: '400px' }}>
            <Search
              placeholder="جستجوی مقالات..."
              prefix={<SearchOutlined />}
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              enterButton
            />
          </div>

          {/* لیست مقالات */}
          {paginatedPosts.length > 0 ? (
            <>
              <Row gutter={[24, 24]}>
                {paginatedPosts.map((post) => {
                  const status = getStatusTag(post.status);
                  return (
                    <Col xs={24} md={12} lg={8} key={post.id}>
                      <Card
                        hoverable
                        style={{ borderRadius: '12px', height: '100%' }}
                        cover={
                          post.featured_image ? (
                            <img
                              alt={post.title}
                              src={post.featured_image}
                              style={{ height: '200px', objectFit: 'cover' }}
                            />
                          ) : (
                            <div style={{
                              height: '200px',
                              background: 'linear-gradient(135deg, #2563eb, #7c3aed)',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              fontSize: '48px',
                              color: 'white'
                            }}>
                              📝
                            </div>
                          )
                        }
                        onClick={() => router.push(`/${locale}/blog/${post.slug || post.id}`)}
                      >
                        <Card.Meta
                          title={
                            <Space>
                              <Text strong>{post.title}</Text>
                              <Tag color={status.color}>{status.label}</Tag>
                            </Space>
                          }
                          description={
                            <div>
                              <Paragraph ellipsis={{ rows: 2 }}>
                                {post.summary || post.content?.substring(0, 150)}
                              </Paragraph>
                              <div style={{ marginTop: '12px' }}>
                                <Space size="small" wrap>
                                  <Text type="secondary" style={{ fontSize: '12px' }}>
                                    <CalendarOutlined /> {dayjs(post.created_at).format('YYYY/MM/DD')}
                                  </Text>
                                  {post.author && (
                                    <Text type="secondary" style={{ fontSize: '12px' }}>
                                      <UserOutlined /> {post.author.name}
                                    </Text>
                                  )}
                                  {post.views !== undefined && (
                                    <Text type="secondary" style={{ fontSize: '12px' }}>
                                      <EyeOutlined /> {post.views}
                                    </Text>
                                  )}
                                </Space>
                              </div>
                              {post.tags && post.tags.length > 0 && (
                                <div style={{ marginTop: '8px' }}>
                                  {post.tags.slice(0, 3).map((tag) => (
                                    <Tag key={tag.id} color="blue" style={{ fontSize: '11px' }}>
                                      #{tag.name}
                                    </Tag>
                                  ))}
                                </div>
                              )}
                            </div>
                          }
                        />
                      </Card>
                    </Col>
                  );
                })}
              </Row>

              <div style={{ marginTop: '32px', display: 'flex', justifyContent: 'center' }}>
                <Pagination
                  current={currentPage}
                  total={filteredPosts.length}
                  pageSize={pageSize}
                  onChange={(page) => setCurrentPage(page)}
                  showSizeChanger
                  onShowSizeChange={(current, size) => {
                    setPageSize(size);
                    setCurrentPage(1);
                  }}
                />
              </div>
            </>
          ) : (
            <Empty description="هیچ مقاله‌ای یافت نشد" />
          )}
        </div>
      </main>
      <Footer />
    </>
  );
}
