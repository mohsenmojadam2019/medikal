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
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  CreditCardOutlined,
  DollarOutlined,
  UserOutlined,
} from '@ant-design/icons';
import { paymentsService, invoicesService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditPaymentPage() {
  const router = useRouter();
  const params = useParams();
  const paymentId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [payment, setPayment] = useState(null);
  const [invoices, setInvoices] = useState([]);
  const [patients, setPatients] = useState([]);

  useEffect(() => {
    const fetchPatients = async () => {
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        setPatients(response.data || []);
      } catch (error) {
        console.error('Error fetching patients:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست بیماران'));
      }
    };
    fetchPatients();
  }, [t]);

  useEffect(() => {
    const fetchPayment = async () => {
      try {
        const response = await paymentsService.getById(paymentId);
        setPayment(response.data);
        form.setFieldsValue(response.data);
        // دریافت فاکتورهای بیمار
        if (response.data.patient_id) {
          const invoicesRes = await invoicesService.getPatientInvoices(response.data.patient_id);
          setInvoices(invoicesRes.data || []);
        }
      } catch (error) {
        console.error('Error fetching payment:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (paymentId) {
      fetchPayment();
    }
  }, [paymentId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await paymentsService.update(paymentId, values);
      message.success(t('updated', 'پرداخت با موفقیت به‌روزرسانی شد'));
      router.push('/admin/payments');
    } catch (error) {
      console.error('Error updating payment:', error);
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
                {t('edit_payment', 'ویرایش پرداخت')}
              </Title>
              <Text type="secondary">
                {payment?.transaction_id || t('edit_payment_subtitle', 'ویرایش اطلاعات پرداخت')}
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
                    name="patient_id"
                    label={t('patient', 'بیمار')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_patient', 'انتخاب بیمار...')}
                      showSearch
                      optionFilterProp="children"
                      options={patients.map((p) => ({
                        value: p.id,
                        label: `${p.full_name} (${p.national_code || p.phone})`,
                      }))}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="invoice_id"
                    label={t('invoice', 'فاکتور')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_invoice', 'انتخاب فاکتور...')}
                      options={invoices.map((inv) => ({
                        value: inv.id,
                        label: `${inv.invoice_number} - ${Number(inv.total_amount).toLocaleString()} تومان`,
                      }))}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="amount"
                    label={t('amount', 'مبلغ (تومان)')}
                    rules={[
                      { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                      { type: 'number', min: 0, message: t('min_0', 'مبلغ باید بیشتر از ۰ باشد') },
                    ]}
                  >
                    <InputNumber
                      prefix={<DollarOutlined />}
                      style={{ width: '100%' }}
                      placeholder={t('amount_placeholder', '۱۵۰۰۰۰')}
                      formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                      parser={(value) => value?.replace(/\$\s?|(,*)/g, '')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="gateway"
                    label={t('gateway', 'درگاه پرداخت')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      options={[
                        { value: 'zarinpal', label: t('zarinpal', 'زرین‌پال') },
                        { value: 'cash', label: t('cash', 'نقدی') },
                        { value: 'wallet', label: t('wallet', 'کیف پول') },
                        { value: 'paypal', label: t('paypal', 'پی‌پال') },
                      ]}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('description_placeholder', 'توضیحات پرداخت...')}
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
                  <CreditCardOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('payment_info', 'اطلاعات پرداخت')}</Text>
                  </div>
                </div>

                <Divider />

                <Form.Item
                  name="status"
                  label={t('status', 'وضعیت')}
                >
                  <Select
                    options={[
                      { value: 'pending', label: t('pending', 'در انتظار') },
                      { value: 'success', label: t('success', 'موفق') },
                      { value: 'failed', label: t('failed', 'ناموفق') },
                      { value: 'refunded', label: t('refunded', 'عودت داده شده') },
                    ]}
                  />
                </Form.Item>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('payment_edit_help', 'تغییرات روی پرداخت اعمال می‌شود')}
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
