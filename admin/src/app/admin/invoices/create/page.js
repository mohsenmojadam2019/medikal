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
  UserOutlined,
  DollarOutlined,
  FileInvoiceOutlined,
} from '@ant-design/icons';
import { invoicesService, patientsService, appointmentsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateInvoicePage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [patients, setPatients] = useState([]);
  const [appointments, setAppointments] = useState([]);
  const [loadingPatients, setLoadingPatients] = useState(false);
  const [loadingAppointments, setLoadingAppointments] = useState(false);

  // ===== دریافت لیست بیماران =====
  useEffect(() => {
    const fetchPatients = async () => {
      setLoadingPatients(true);
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        setPatients(response.data || []);
      } catch (error) {
        console.error('Error fetching patients:', error);
      } finally {
        setLoadingPatients(false);
      }
    };
    fetchPatients();
  }, []);

  // ===== دریافت لیست نوبت‌ها بر اساس بیمار انتخاب شده =====
  useEffect(() => {
    const fetchAppointments = async () => {
      const patientId = form.getFieldValue('patient_id');
      if (!patientId) {
        setAppointments([]);
        return;
      }

      setLoadingAppointments(true);
      try {
        const response = await appointmentsService.getByPatient(patientId);
        setAppointments(response.data || []);
      } catch (error) {
        console.error('Error fetching appointments:', error);
      } finally {
        setLoadingAppointments(false);
      }
    };

    fetchAppointments();
  }, [form.getFieldValue('patient_id')]);

  // ===== محاسبه مبلغ کل =====
  const calculateTotal = () => {
    const amount = form.getFieldValue('amount') || 0;
    const tax = form.getFieldValue('tax') || 0;
    const discount = form.getFieldValue('discount') || 0;
    return amount + tax - discount;
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await invoicesService.create(values);
      message.success(t('invoice_created', 'فاکتور با موفقیت ایجاد شد'));
      router.push('/admin/invoices');
    } catch (error) {
      console.error('Error creating invoice:', error);
      message.error(t('create_error', 'خطا در ایجاد فاکتور'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  const totalAmount = calculateTotal();

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
                {t('new_invoice', 'فاکتور جدید')}
              </Title>
              <Text type="secondary">
                {t('create_invoice_subtitle', 'ایجاد فاکتور جدید')}
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
            status: 'draft',
            tax: 0,
            discount: 0,
          }}
          onValuesChange={() => form.setFieldsValue({ total_amount: calculateTotal() })}
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
                      loading={loadingPatients}
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
                    name="appointment_id"
                    label={t('appointment', 'نوبت')}
                  >
                    <Select
                      placeholder={t('select_appointment', 'انتخاب نوبت...')}
                      loading={loadingAppointments}
                      options={appointments.map((a) => ({
                        value: a.id,
                        label: `${a.code} - ${dayjs(a.date).format('jYYYY/jMM/jDD')}`,
                      }))}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={8}>
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

                <Col xs={24} md={8}>
                  <Form.Item
                    name="tax"
                    label={t('tax', 'مالیات (تومان)')}
                  >
                    <InputNumber
                      prefix={<DollarOutlined />}
                      style={{ width: '100%' }}
                      placeholder={t('tax_placeholder', '۰')}
                      min={0}
                      formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                      parser={(value) => value?.replace(/\$\s?|(,*)/g, '')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={8}>
                  <Form.Item
                    name="discount"
                    label={t('discount', 'تخفیف (تومان)')}
                  >
                    <InputNumber
                      prefix={<DollarOutlined />}
                      style={{ width: '100%' }}
                      placeholder={t('discount_placeholder', '۰')}
                      min={0}
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
                <TextArea
                  rows={3}
                  placeholder={t('description_placeholder', 'توضیحات فاکتور...')}
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
                  <FileInvoiceOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('invoice_summary', 'خلاصه فاکتور')}</Text>
                  </div>
                </div>

                <Divider />

                <div>
                  <Text type="secondary">{t('patient', 'بیمار')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {form.getFieldValue('patient_id') ? patients.find(p => p.id === form.getFieldValue('patient_id'))?.full_name || '—' : t('not_selected', 'انتخاب نشده')}
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('total_amount', 'مبلغ کل')}</Text>
                  <div style={{ fontSize: 20, fontWeight: 700, color: '#2563eb', marginTop: 4 }}>
                    {Number(totalAmount).toLocaleString()} تومان
                  </div>
                </div>

                <Divider />

                <Form.Item
                  name="status"
                  label={t('status', 'وضعیت')}
                  initialValue="draft"
                >
                  <Select
                    options={[
                      { value: 'draft', label: t('draft', 'پیش‌نویس') },
                      { value: 'issued', label: t('issued', 'صادر شده') },
                      { value: 'paid', label: t('paid', 'پرداخت شده') },
                      { value: 'cancelled', label: t('cancelled', 'لغو شده') },
                    ]}
                  />
                </Form.Item>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('invoice_help', 'پس از ایجاد، فاکتور قابل چاپ و ارسال برای بیمار است')}
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
