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
  UserOutlined,
  DollarOutlined,
  FileInvoiceOutlined,
} from '@ant-design/icons';
import { invoicesService, patientsService, appointmentsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditInvoicePage() {
  const router = useRouter();
  const params = useParams();
  const invoiceId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [invoice, setInvoice] = useState(null);
  const [patients, setPatients] = useState([]);
  const [appointments, setAppointments] = useState([]);

  useEffect(() => {
    const fetchPatients = async () => {
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        setPatients(response.data || []);
      } catch (error) {
        console.error('Error fetching patients:', error);
      }
    };
    fetchPatients();
  }, []);

  useEffect(() => {
    const fetchInvoice = async () => {
      try {
        const response = await invoicesService.getById(invoiceId);
        setInvoice(response.data);
        form.setFieldsValue(response.data);
        // دریافت نوبت‌های بیمار
        if (response.data.patient_id) {
          const appointmentsRes = await appointmentsService.getByPatient(response.data.patient_id);
          setAppointments(appointmentsRes.data || []);
        }
      } catch (error) {
        console.error('Error fetching invoice:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (invoiceId) {
      fetchInvoice();
    }
  }, [invoiceId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await invoicesService.update(invoiceId, values);
      message.success(t('updated', 'فاکتور با موفقیت به‌روزرسانی شد'));
      router.push('/admin/invoices');
    } catch (error) {
      console.error('Error updating invoice:', error);
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
                {t('edit_invoice', 'ویرایش فاکتور')}
              </Title>
              <Text type="secondary">
                {invoice?.invoice_number || t('edit_invoice_subtitle', 'ویرایش اطلاعات فاکتور')}
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
                    name="appointment_id"
                    label={t('appointment', 'نوبت')}
                  >
                    <Select
                      placeholder={t('select_appointment', 'انتخاب نوبت...')}
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

                <Form.Item
                  name="status"
                  label={t('status', 'وضعیت')}
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
                    {t('invoice_edit_help', 'تغییرات روی فاکتور اعمال می‌شود')}
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
