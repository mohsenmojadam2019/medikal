'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Spin,
  Switch,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UserOutlined,
  PhoneOutlined,
  MailOutlined,
} from '@ant-design/icons';
import { usersService, rolesService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

export default function EditUserPage() {
  const router = useRouter();
  const params = useParams();
  const userId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [roles, setRoles] = useState([]);

  useEffect(() => {
    const fetchRoles = async () => {
      try {
        const response = await rolesService.getAll();
        setRoles(response.data || []);
      } catch (error) {
        console.error('Error fetching roles:', error);
        message.error(t('fetch_error', 'خطا در دریافت نقش‌ها'));
      }
    };
    fetchRoles();
  }, [t]);

  useEffect(() => {
    const fetchUser = async () => {
      try {
        const response = await usersService.getById(userId);
        setUser(response.data);
        form.setFieldsValue({
          ...response.data,
          role: response.data.roles?.[0]?.name,
        });
      } catch (error) {
        console.error('Error fetching user:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (userId) {
      fetchUser();
    }
  }, [userId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const submitData = { ...values };
      // اگر رمز عبور وارد نشده، حذفش کن
      if (!values.password) {
        delete submitData.password;
      }
      await usersService.update(userId, submitData);
      message.success(t('user_updated', 'کاربر با موفقیت به‌روزرسانی شد'));
      router.push('/admin/users');
    } catch (error) {
      console.error('Error updating user:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  if (fetchLoading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: 400 }}>
        <Spin size="large" />
      </div>
    );
  }

  return (
    <div>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: 24,
        }}
      >
        <div>
          <Space>
            <Button
              type="text"
              icon={<ArrowLeftOutlined />}
              onClick={handleBack}
              style={{ fontSize: 18 }}
            />
            <div>
              <Title level={2} style={{ margin: 0 }}>
                {t('edit_user', 'ویرایش کاربر')}
              </Title>
              <Text type="secondary">
                {user?.name || t('edit_user_subtitle', 'ویرایش اطلاعات کاربر')}
              </Text>
            </div>
          </Space>
        </div>
      </div>

      <Card
        style={{
          borderRadius: 12,
          borderColor: '#e8e8f0',
        }}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          size="large"
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="name"
                    label={t('name', 'نام و نام خانوادگی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<UserOutlined />}
                      placeholder={t('name_placeholder', 'نام کامل...')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="mobile"
                    label={t('mobile', 'شماره موبایل')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { pattern: /^09[0-9]{9}$/, message: t('mobile_invalid', 'شماره موبایل نامعتبر است') },
                    ]}
                  >
                    <Input
                      prefix={<PhoneOutlined />}
                      placeholder={t('mobile_placeholder', '۰۹۱۲۳۴۵۶۷۸۹')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="email"
                    label={t('email', 'ایمیل')}
                    rules={[
                      { type: 'email', message: t('email_invalid', 'ایمیل نامعتبر است') },
                    ]}
                  >
                    <Input
                      prefix={<MailOutlined />}
                      placeholder={t('email_placeholder', 'user@clinic.com')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="role"
                    label={t('role', 'نقش')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_role', 'انتخاب نقش...')}
                      options={roles.map((role) => ({
                        value: role.name,
                        label: role.name,
                      }))}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="password"
                label={t('password', 'رمز عبور جدید')}
                extra={t('password_extra', 'در صورت تمایل به تغییر رمز عبور، وارد کنید')}
              >
                <Input.Password placeholder="********" />
              </Form.Item>

              <div style={{ marginTop: 8 }}>
                <Text type="secondary">
                  {t('last_login', 'آخرین ورود')}: {user?.last_login_at ? dayjs(user.last_login_at).format('jYYYY/jMM/jDD HH:mm') : t('never', 'هرگز')}
                </Text>
              </div>
            </Col>

            <Col xs={24} lg={8}>
              <Card
                style={{
                  borderRadius: 12,
                  borderColor: '#e8e8f0',
                  background: '#f8fafc',
                }}
              >
                <div style={{ textAlign: 'center', padding: '16px 0' }}>
                  <UserOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('user_info', 'اطلاعات کاربر')}</Text>
                  </div>
                </div>

                <Divider />

                <Form.Item
                  name="is_active"
                  label={t('status', 'وضعیت')}
                  valuePropName="checked"
                >
                  <Switch
                    checkedChildren={t('active', 'فعال')}
                    unCheckedChildren={t('inactive', 'غیرفعال')}
                  />
                </Form.Item>

                <Divider />

                <div>
                  <Text type="secondary">{t('created_at', 'تاریخ ایجاد')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {user?.created_at ? dayjs(user.created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('user_edit_help', 'تغییرات روی اطلاعات کاربر اعمال می‌شود')}
                  </Text>
                </div>
              </Card>
            </Col>
          </Row>

          <Divider />
          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <Button onClick={handleBack} size="large">
              {t('cancel', 'انصراف')}
            </Button>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              icon={<SaveOutlined />}
              size="large"
              style={{
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                border: 'none',
              }}
            >
              {t('save', 'ذخیره')}
            </Button>
          </div>
        </Form>
      </Card>
    </div>
  );
}
