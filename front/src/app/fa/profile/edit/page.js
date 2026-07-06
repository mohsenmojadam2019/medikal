'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card, Form, Input, Button, Typography, Spin,
  App, Select, Space, Divider, Alert, Row, Col,
  Upload, Avatar
} from 'antd';
import {
  UserOutlined, PhoneOutlined, MailOutlined,
  HomeOutlined, IdcardOutlined, SaveOutlined,
  ArrowLeftOutlined, UploadOutlined,
  SafetyOutlined
} from '@ant-design/icons';
import { useLanguage } from '@/lib/context/LanguageContext';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import Breadcrumb from '@/components/shared/Breadcrumb';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function EditProfilePage() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const { message: appMessage } = App.useApp();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [user, setUser] = useState(null);
  const [avatarLoading, setAvatarLoading] = useState(false);

  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
  const getToken = () => localStorage.getItem('token');

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.push(`/${locale}/login`);
      return;
    }
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const token = getToken();

      // دریافت اطلاعات کاربر
      const userRes = await fetch(`${API_URL}/api/auth/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const userData = await userRes.json();

      if (userData.success) {
        setUser(userData.data);
      }

      // دریافت اطلاعات بیمار
      const patientRes = await fetch(`${API_URL}/api/patients/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      const patientData = await patientRes.json();

      console.log('📋 Patient data:', patientData);

      // پر کردن فرم
      form.setFieldsValue({
        name: userData?.data?.name || '',
        email: userData?.data?.email || '',
        mobile: userData?.data?.mobile || '',
        national_code: patientData?.data?.national_code || '',
        address: patientData?.data?.address || '',
        insurance_type: patientData?.data?.insurance_type || '',
        insurance_number: patientData?.data?.insurance_number || '',
      });

    } catch (error) {
      console.error('Error fetching profile:', error);
      appMessage.error('خطا در دریافت اطلاعات');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (values) => {
    setSubmitting(true);
    try {
      const token = getToken();

      // آپدیت اطلاعات کاربر
      const userRes = await fetch(`${API_URL}/api/profile`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: values.name,
          email: values.email,
          mobile: values.mobile,
        }),
      });
      const userData = await userRes.json();

      if (!userData.success) {
        appMessage.error(userData.message || 'خطا در به‌روزرسانی اطلاعات کاربر');
        setSubmitting(false);
        return;
      }

      // آپدیت اطلاعات بیمار
      const patientRes = await fetch(`${API_URL}/api/patients/update`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          national_code: values.national_code,
          address: values.address,
          insurance_type: values.insurance_type,
          insurance_number: values.insurance_number,
        }),
      });
      const patientData = await patientRes.json();

      if (patientData.success) {
        appMessage.success('اطلاعات با موفقیت به‌روزرسانی شد');
        setTimeout(() => router.push(`/${locale}/profile`), 1500);
      } else {
        appMessage.error(patientData.message || 'خطا در به‌روزرسانی اطلاعات');
      }
    } catch (error) {
      console.error('Error updating profile:', error);
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setSubmitting(false);
    }
  };

  const handleAvatarUpload = async (file) => {
    setAvatarLoading(true);
    try {
      const token = getToken();
      const formData = new FormData();
      formData.append('avatar', file);

      const res = await fetch(`${API_URL}/api/profile/avatar`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      });
      const data = await res.json();
      if (data.success) {
        appMessage.success('عکس با موفقیت آپلود شد');
        fetchProfile();
      } else {
        appMessage.error(data.message || 'خطا در آپلود عکس');
      }
    } catch (error) {
      console.error('Error uploading avatar:', error);
      appMessage.error('خطا در ارتباط با سرور');
    } finally {
      setAvatarLoading(false);
    }
    return false;
  };

  if (loading) {
    return (
        <>
          <Header />
          <LoadingSpinner />
          <Footer />
        </>
    );
  }

  return (
      <>
        <Header />
        <main style={{ background: '#f8fafc', minHeight: 'calc(100vh - 200px)' }}>
          <div style={{ maxWidth: '800px', margin: '0 auto', padding: '24px 20px' }}>
            <Breadcrumb
                items={[
                  { title: 'خانه', href: `/${locale}` },
                  { title: 'پروفایل', href: `/${locale}/profile` },
                  { title: 'ویرایش پروفایل' },
                ]}
            />

            <Card style={{ borderRadius: '16px' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                <Title level={2} style={{ marginBottom: 0 }}>
                  ✏️ ویرایش پروفایل
                </Title>
                <Button
                    icon={<ArrowLeftOutlined />}
                    onClick={() => router.push(`/${locale}/profile`)}
                >
                  بازگشت
                </Button>
              </div>

              <Divider />

              {/* بخش عکس پروفایل */}
              <div style={{ textAlign: 'center', marginBottom: '24px' }}>
                <Avatar
                    size={100}
                    src={user?.avatar}
                    style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)' }}
                    icon={<UserOutlined />}
                />
                <div style={{ marginTop: '8px' }}>
                  <Upload
                      showUploadList={false}
                      beforeUpload={handleAvatarUpload}
                      accept="image/*"
                  >
                    <Button
                        icon={<UploadOutlined />}
                        loading={avatarLoading}
                    >
                      تغییر عکس
                    </Button>
                  </Upload>
                </div>
              </div>

              <Form
                  form={form}
                  layout="vertical"
                  onFinish={handleSubmit}
              >
                <Row gutter={[16, 16]}>
                  <Col xs={24}>
                    <Form.Item
                        name="name"
                        label="نام و نام خانوادگی"
                        rules={[{ required: true, message: 'لطفاً نام خود را وارد کنید' }]}
                    >
                      <Input
                          prefix={<UserOutlined />}
                          placeholder="نام و نام خانوادگی"
                          size="large"
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="mobile"
                        label="شماره موبایل"
                        rules={[
                          { required: true, message: 'لطفاً شماره موبایل را وارد کنید' },
                          { pattern: /^09[0-9]{9}$/, message: 'شماره موبایل معتبر نیست' }
                        ]}
                    >
                      <Input
                          prefix={<PhoneOutlined />}
                          placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                          size="large"
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="email"
                        label="ایمیل"
                        rules={[
                          { type: 'email', message: 'ایمیل معتبر نیست' }
                        ]}
                    >
                      <Input
                          prefix={<MailOutlined />}
                          placeholder="example@email.com"
                          size="large"
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24}>
                    <Form.Item
                        name="national_code"
                        label="کد ملی"
                        rules={[
                          { required: true, message: 'لطفاً کد ملی را وارد کنید' },
                          { pattern: /^[0-9]{10}$/, message: 'کد ملی باید ۱۰ رقم باشد' }
                        ]}
                    >
                      <Input
                          prefix={<IdcardOutlined />}
                          placeholder="۱۲۳۴۵۶۷۸۹۰"
                          size="large"
                          maxLength={10}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24}>
                    <Form.Item
                        name="address"
                        label="آدرس"
                        rules={[{ required: true, message: 'لطفاً آدرس خود را وارد کنید' }]}
                    >
                      <Input.TextArea
                          placeholder="آدرس کامل خود را وارد کنید..."
                          rows={3}
                          size="large"
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="insurance_type"
                        label="نوع بیمه"
                    >
                      <Select
                          placeholder="انتخاب نوع بیمه"
                          size="large"
                          allowClear
                      >
                        <Option value="tamin_ejtemaei">تامین اجتماعی</Option>
                        <Option value="tamin_tekamili">بیمه تکمیلی</Option>
                        <Option value="asal">بیمه آسایش</Option>
                        <Option value="iran">بیمه ایران</Option>
                        <Option value="dana">بیمه دانا</Option>
                        <Option value="saman">بیمه سامان</Option>
                        <Option value="other">سایر</Option>
                      </Select>
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="insurance_number"
                        label="شماره بیمه"
                    >
                      <Input
                          prefix={<SafetyOutlined />}
                          placeholder="شماره بیمه خود را وارد کنید"
                          size="large"
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24}>
                    <Alert
                        message="تکمیل اطلاعات"
                        description="تکمیل اطلاعات زیر برای ثبت سفارش داروخانه الزامی است: نام، موبایل، کد ملی و آدرس"
                        type="info"
                        showIcon
                        style={{ marginBottom: '16px' }}
                    />
                  </Col>

                  <Col xs={24}>
                    <Space style={{ width: '100%', justifyContent: 'flex-end' }}>
                      <Button
                          size="large"
                          onClick={() => router.push(`/${locale}/profile`)}
                      >
                        انصراف
                      </Button>
                      <Button
                          type="primary"
                          size="large"
                          htmlType="submit"
                          loading={submitting}
                          icon={<SaveOutlined />}
                      >
                        ذخیره اطلاعات
                      </Button>
                    </Space>
                  </Col>
                </Row>
              </Form>
            </Card>
          </div>
        </main>
        <Footer />
      </>
  );
}
