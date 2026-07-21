'use client';

import { useState, useEffect } from 'react';
import { 
  Card, Row, Col, Typography, Spin, Empty, Tag, 
  Button, Space, message, Upload, Modal, List, 
  Avatar, Statistic, Divider, Image, Progress,
  Alert
} from 'antd';
import { 
  CameraOutlined, UploadOutlined, 
  EyeOutlined, DownloadOutlined, 
  DeleteOutlined, FileImageOutlined,
  PlusOutlined, SearchOutlined,
  ClockCircleOutlined, CheckCircleOutlined,
  CloseCircleOutlined, MedicineBoxOutlined,
  LoginOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import Link from 'next/link';

const { Title, Text } = Typography;

export default function ImagingPage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const [loading, setLoading] = useState(true);
  const [images, setImages] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [stats, setStats] = useState({
    total: 0,
    xray: 0,
    ct: 0,
    mri: 0,
    ultrasound: 0,
  });

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('token');
    }
    return null;
  };

  // بررسی لاگین
  useEffect(() => {
    const token = getToken();
    setIsLoggedIn(!!token);
    
    if (token) {
      fetchImages();
    } else {
      setLoading(false);
    }
  }, []);

  // دریافت تصاویر بیمار (فقط با لاگین)
  const fetchImages = async () => {
    const token = getToken();
    if (!token) {
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      // دریافت اطلاعات بیمار فعلی
      const userRes = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const userData = await userRes.json();
      
      if (userData.success && userData.data) {
        const patientId = userData.data.id;
        
        const res = await fetch(`${API_URL}/api/pacs/patient/${patientId}/images`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        });
        const data = await res.json();
        if (data.success) {
          setImages(data.data || []);
          // محاسبه آمار
          const imageList = data.data || [];
          setStats({
            total: imageList.length,
            xray: imageList.filter(i => i.image_type === 'xray').length,
            ct: imageList.filter(i => i.image_type === 'ct').length,
            mri: imageList.filter(i => i.image_type === 'mri').length,
            ultrasound: imageList.filter(i => i.image_type === 'ultrasound').length,
          });
        }
      }
    } catch (error) {
      console.error('Error fetching images:', error);
      message.error('خطا در دریافت تصاویر');
    } finally {
      setLoading(false);
    }
  };

  // آپلود تصویر (نیاز به لاگین)
  const handleUpload = async (file) => {
    const token = getToken();
    if (!token) {
      message.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
      router.push(`/${locale}/login?redirect=/${locale}/imaging`);
      return false;
    }

    setUploading(true);
    const formData = new FormData();
    formData.append('image', file);

    try {
      // دریافت patient_id
      const userRes = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const userData = await userRes.json();
      
      if (userData.success && userData.data) {
        formData.append('patient_id', userData.data.id);
        formData.append('image_type', 'other');
        formData.append('description', file.name);
      }

      const res = await fetch(`${API_URL}/api/pacs/upload`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      });

      const data = await res.json();
      if (data.success) {
        message.success('تصویر با موفقیت آپلود شد');
        fetchImages();
        return true;
      } else {
        message.error(data.message || 'خطا در آپلود تصویر');
        return false;
      }
    } catch (error) {
      console.error('Error uploading image:', error);
      message.error('خطا در ارتباط با سرور');
      return false;
    } finally {
      setUploading(false);
    }
  };

  // حذف تصویر (نیاز به لاگین)
  const handleDelete = async (id) => {
    const token = getToken();
    if (!token) {
      message.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
      return;
    }

    try {
      const res = await fetch(`${API_URL}/api/pacs/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        message.success('تصویر با موفقیت حذف شد');
        fetchImages();
        setModalVisible(false);
      } else {
        message.error(data.message || 'خطا در حذف تصویر');
      }
    } catch (error) {
      console.error('Error deleting image:', error);
      message.error('خطا در ارتباط با سرور');
    }
  };

  // دریافت آیکون بر اساس نوع
  const getTypeIcon = (type) => {
    const icons = {
      xray: '🦴',
      ct: '🧠',
      mri: '🔬',
      ultrasound: '👶',
      other: '📷',
    };
    return icons[type] || '📷';
  };

  const getTypeLabel = (type) => {
    const labels = {
      xray: 'رادیولوژی',
      ct: 'سی‌تی اسکن',
      mri: 'ام‌آرآی',
      ultrasound: 'سونوگرافی',
      other: 'سایر',
    };
    return labels[type] || type;
  };

  const getStatusTag = (status) => {
    const map = {
      pending: { color: 'warning', label: 'در انتظار بررسی' },
      verified: { color: 'success', label: 'تایید شده' },
      rejected: { color: 'error', label: 'رد شده' },
    };
    return map[status] || map.pending;
  };

  if (loading) {
    return (
      <>
        <Header />
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px', textAlign: 'center' }}>
          <Spin size="large" />
          <p style={{ marginTop: '16px' }}>در حال بارگذاری تصاویر...</p>
        </div>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main style={{ 
        minHeight: 'calc(100vh - 200px)',
        background: '#f8fafc'
      }}>
        <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '0 20px' }}>
          <Breadcrumb />
          
          <div style={{ 
            marginBottom: '32px', 
            display: 'flex', 
            justifyContent: 'space-between', 
            alignItems: 'center',
            flexWrap: 'wrap',
            gap: '16px'
          }}>
            <div>
              <Title level={2}>📷 تصویربرداری پزشکی</Title>
              <Text type="secondary">مدیریت تصاویر پزشکی شامل رادیولوژی، ام‌آرآی، سی‌تی اسکن و سونوگرافی</Text>
            </div>
            {isLoggedIn && (
              <Upload
                accept="image/*"
                beforeUpload={handleUpload}
                showUploadList={false}
                disabled={uploading}
              >
                <Button 
                  type="primary" 
                  icon={<UploadOutlined />} 
                  size="large"
                  loading={uploading}
                >
                  آپلود تصویر جدید
                </Button>
              </Upload>
            )}
          </div>

          {/* اگر لاگین نباشه */}
          {!isLoggedIn ? (
            <Card style={{ borderRadius: '16px', textAlign: 'center', padding: '40px 20px' }}>
              <CameraOutlined style={{ fontSize: 64, color: '#d9d9d9', marginBottom: '16px' }} />
              <Title level={3}>برای مشاهده تصاویر پزشکی خود وارد شوید</Title>
              <Text type="secondary" style={{ display: 'block', marginBottom: '24px' }}>
                با ورود به حساب کاربری می‌توانید تصاویر پزشکی خود را مشاهده و مدیریت کنید.
              </Text>
              <Space>
                <Link href={`/${locale}/login?redirect=/${locale}/imaging`}>
                  <Button type="primary" size="large" icon={<LoginOutlined />}>
                    ورود به حساب
                  </Button>
                </Link>
                <Link href={`/${locale}/register`}>
                  <Button size="large">
                    ثبت‌نام
                  </Button>
                </Link>
              </Space>
            </Card>
          ) : (
            <>
              {/* آمار - فقط برای کاربران لاگین */}
              <Row gutter={[16, 16]} style={{ marginBottom: '24px' }}>
                <Col xs={12} sm={6}>
                  <Card>
                    <Statistic 
                      title="کل تصاویر" 
                      value={stats.total} 
                      prefix={<FileImageOutlined />}
                    />
                  </Card>
                </Col>
                <Col xs={12} sm={6}>
                  <Card>
                    <Statistic 
                      title="رادیولوژی" 
                      value={stats.xray} 
                      prefix="🦴"
                    />
                  </Card>
                </Col>
                <Col xs={12} sm={6}>
                  <Card>
                    <Statistic 
                      title="سی‌تی اسکن" 
                      value={stats.ct} 
                      prefix="🧠"
                    />
                  </Card>
                </Col>
                <Col xs={12} sm={6}>
                  <Card>
                    <Statistic 
                      title="ام‌آرآی" 
                      value={stats.mri} 
                      prefix="🔬"
                    />
                  </Card>
                </Col>
              </Row>

              {/* لیست تصاویر - فقط برای کاربران لاگین */}
              <Card style={{ borderRadius: '16px' }}>
                {images.length > 0 ? (
                  <List
                    grid={{ gutter: 16, xs: 1, sm: 2, md: 3, lg: 4 }}
                    dataSource={images}
                    renderItem={(item) => (
                      <List.Item>
                        <Card
                          hoverable
                          style={{ borderRadius: '12px' }}
                          cover={
                            <div style={{ 
                              height: 200, 
                              background: '#f0f0f0',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              fontSize: 64,
                              color: '#d9d9d9'
                            }}>
                              {getTypeIcon(item.image_type)}
                            </div>
                          }
                          actions={[
                            <Button 
                              type="text" 
                              icon={<EyeOutlined />}
                              onClick={() => {
                                setSelectedImage(item);
                                setModalVisible(true);
                              }}
                            />,
                            <Button 
                              type="text" 
                              icon={<DownloadOutlined />}
                              onClick={() => message.info('در حال دانلود...')}
                            />,
                            <Button 
                              type="text" 
                              danger
                              icon={<DeleteOutlined />}
                              onClick={() => handleDelete(item.id)}
                            />,
                          ]}
                        >
                          <Card.Meta
                            title={
                              <Space>
                                <Text ellipsis>{item.filename || 'تصویر پزشکی'}</Text>
                              </Space>
                            }
                            description={
                              <div>
                                <Tag color="blue">{getTypeLabel(item.image_type)}</Tag>
                                <Tag color={getStatusTag(item.status).color}>
                                  {getStatusTag(item.status).label}
                                </Tag>
                                <div style={{ marginTop: '4px' }}>
                                  <Text type="secondary" style={{ fontSize: '12px' }}>
                                    {item.created_at ? new Date(item.created_at).toLocaleDateString('fa-IR') : ''}
                                  </Text>
                                </div>
                              </div>
                            }
                          />
                        </Card>
                      </List.Item>
                    )}
                  />
                ) : (
                  <Empty 
                    description="هیچ تصویر پزشکی ثبت نشده است"
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                  >
                    <Upload
                      accept="image/*"
                      beforeUpload={handleUpload}
                      showUploadList={false}
                    >
                      <Button type="primary" icon={<UploadOutlined />}>
                        اولین تصویر را آپلود کنید
                      </Button>
                    </Upload>
                  </Empty>
                )}
              </Card>
            </>
          )}
        </div>
      </main>

      {/* مودال مشاهده تصویر - فقط برای کاربران لاگین */}
      {isLoggedIn && (
        <Modal
          title="مشاهده تصویر"
          open={modalVisible}
          onCancel={() => setModalVisible(false)}
          footer={[
            <Button key="close" onClick={() => setModalVisible(false)}>
              بستن
            </Button>,
            <Button 
              key="download" 
              type="primary" 
              icon={<DownloadOutlined />}
              onClick={() => message.info('در حال دانلود...')}
            >
              دانلود
            </Button>,
            <Button 
              key="delete" 
              danger 
              icon={<DeleteOutlined />}
              onClick={() => selectedImage && handleDelete(selectedImage.id)}
            >
              حذف
            </Button>,
          ]}
          width={600}
        >
          {selectedImage && (
            <div>
              <div style={{ 
                height: 400, 
                background: '#f0f0f0',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: 128,
                color: '#d9d9d9',
                borderRadius: '12px'
              }}>
                {getTypeIcon(selectedImage.image_type)}
              </div>
              <Divider />
              <Row gutter={[16, 16]}>
                <Col span={12}>
                  <Text type="secondary">نوع تصویر</Text>
                  <div><Tag color="blue">{getTypeLabel(selectedImage.image_type)}</Tag></div>
                </Col>
                <Col span={12}>
                  <Text type="secondary">وضعیت</Text>
                  <div>
                    <Tag color={getStatusTag(selectedImage.status).color}>
                      {getStatusTag(selectedImage.status).label}
                    </Tag>
                  </div>
                </Col>
                <Col span={24}>
                  <Text type="secondary">تاریخ ثبت</Text>
                  <div>{selectedImage.created_at ? new Date(selectedImage.created_at).toLocaleDateString('fa-IR') : ''}</div>
                </Col>
                {selectedImage.description && (
                  <Col span={24}>
                    <Text type="secondary">توضیحات</Text>
                    <div>{selectedImage.description}</div>
                  </Col>
                )}
              </Row>
            </div>
          )}
        </Modal>
      )}

      <Footer />
    </>
  );
}
