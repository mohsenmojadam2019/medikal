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
  InputNumber,
  Switch,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  MedicineBoxOutlined,
  TagOutlined,
  DollarOutlined,
  StockOutlined,
} from '@ant-design/icons';
import { drugsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditDrugPage() {
  const router = useRouter();
  const params = useParams();
  const drugId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [drug, setDrug] = useState(null);

  useEffect(() => {
    const fetchDrug = async () => {
      try {
        const response = await drugsService.getById(drugId);
        setDrug(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching drug:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (drugId) {
      fetchDrug();
    }
  }, [drugId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await drugsService.update(drugId, values);
      message.success(t('updated', 'دارو با موفقیت به‌روزرسانی شد'));
      router.push('/admin/drugs');
    } catch (error) {
      console.error('Error updating drug:', error);
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
                {t('edit_drug', 'ویرایش دارو')}
              </Title>
              <Text type="secondary">
                {drug?.name || t('edit_drug_subtitle', 'ویرایش اطلاعات دارو')}
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
                    label={t('drug_name', 'نام دارو')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<MedicineBoxOutlined />}
                      placeholder={t('drug_name_placeholder', 'مثال: ایبوپروفن')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="generic_name"
                    label={t('generic_name', 'نام ژنریک')}
                  >
                    <Input
                      placeholder={t('generic_name_placeholder', 'مثال: Ibuprofen')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="category"
                    label={t('category', 'دسته‌بندی')}
                  >
                    <Input
                      prefix={<TagOutlined />}
                      placeholder={t('category_placeholder', 'مثال: مسکن')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="form"
                    label={t('form', 'فرم')}
                  >
                    <Select
                      placeholder={t('select_form', 'انتخاب فرم...')}
                      options={[
                        { value: 'قرص', label: 'قرص' },
                        { value: 'کپسول', label: 'کپسول' },
                        { value: 'شربت', label: 'شربت' },
                        { value: 'پماد', label: 'پماد' },
                        { value: 'آمپول', label: 'آمپول' },
                        { value: 'قطره', label: 'قطره' },
                      ]}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="strength"
                    label={t('strength', 'قدرت دارو')}
                  >
                    <Input
                      placeholder={t('strength_placeholder', 'مثال: ۴۰۰mg')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="manufacturer"
                    label={t('manufacturer', 'سازنده')}
                  >
                    <Input
                      placeholder={t('manufacturer_placeholder', 'نام سازنده...')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="price"
                    label={t('price', 'قیمت (تومان)')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { type: 'number', min: 0, message: t('min_0', 'قیمت باید بیشتر از ۰ باشد') },
                    ]}
                  >
                    <InputNumber
                      prefix={<DollarOutlined />}
                      style={{ width: '100%' }}
                      placeholder={t('price_placeholder', '۲۵۰۰۰')}
                      formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                      parser={(value) => value?.replace(/\$\s?|(,*)/g, '')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="stock"
                    label={t('stock', 'موجودی')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { type: 'number', min: 0, message: t('min_0', 'موجودی باید بیشتر از ۰ باشد') },
                    ]}
                  >
                    <InputNumber
                      prefix={<StockOutlined />}
                      style={{ width: '100%' }}
                      placeholder={t('stock_placeholder', '۱۰۰')}
                      min={0}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="metadata"
                label={t('metadata', 'اطلاعات تکمیلی')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('metadata_placeholder', 'اطلاعات اضافی درباره دارو...')}
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
                  <MedicineBoxOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('drug_settings', 'تنظیمات دارو')}</Text>
                  </div>
                </div>

                <Divider />

                <Form.Item
                  name="requires_prescription"
                  label={t('requires_prescription', 'نیاز به نسخه')}
                  valuePropName="checked"
                >
                  <Switch
                    checkedChildren={t('yes', 'بله')}
                    unCheckedChildren={t('no', 'خیر')}
                  />
                </Form.Item>

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
                    {t('drug_edit_help', 'تغییرات روی اطلاعات دارو اعمال می‌شود')}
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
