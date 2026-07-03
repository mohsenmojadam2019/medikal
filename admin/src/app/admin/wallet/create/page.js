'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
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
  InputNumber,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  WalletOutlined,
  UserOutlined,
  DollarOutlined,
} from '@ant-design/icons';
import { walletService, usersService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;

export default function CreateWalletPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [users, setUsers] = useState([]);

  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const response = await usersService.getAll({ per_page: 100 });
        setUsers(response.data || []);
      } catch (error) {
        console.error('Error fetching users:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست کاربران'));
      }
    };
    fetchUsers();
  }, [t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await walletService.create(values);
      message.success(t('wallet_created', 'کیف پول با موفقیت ایجاد شد'));
      router.push('/admin/wallet');
    } catch (error) {
      console.error('Error creating wallet:', error);
      message.error(t('create_error', 'خطا در ایجاد کیف پول'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

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
                {t('new_wallet', 'کیف پول جدید')}
              </Title>
              <Text type="secondary">
                {t('create_wallet_subtitle', 'ایجاد کیف پول برای کاربر')}
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
          initialValues={{
            is_active: true,
            balance: 0,
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="user_id"
                    label={t('user', 'کاربر')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_user', 'انتخاب کاربر...')}
                      showSearch
                      optionFilterProp="children"
                      options={users.map((u) => ({
                        value: u.id,
                        label: u.name,
                      }))}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="balance"
                    label={t('initial_balance', 'موجودی اولیه (تومان)')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { type: 'number', min: 0, message: t('min_0', 'موجودی باید بیشتر از ۰ باشد') },
                    ]}
                  >
                    <InputNumber
                      prefix={<DollarOutlined />}
                      style={{ width: '100%' }}
                      placeholder={t('balance_placeholder', '۰')}
                      formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                      parser={(value) => value?.replace(/\$\s?|(,*)/g, '')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
              >
                <Input.TextArea
                  rows={3}
                  placeholder={t('description_placeholder', 'توضیحات کیف پول...')}
                />
              </Form.Item>
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
                  <WalletOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('wallet_info', 'اطلاعات کیف پول')}</Text>
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

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('wallet_help', 'کیف پول برای پرداخت‌های آنلاین و نوبت‌ها استفاده می‌شود')}
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
