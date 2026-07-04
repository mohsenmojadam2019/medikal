// src/app/admin/referrals/create/page.js

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
  App,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UserOutlined,
  ArrowRightOutlined,
} from '@ant-design/icons';
import { referralsService, doctorsService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateReferralPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp(); // ✅ استفاده از App.useApp()
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [loadingPatients, setLoadingPatients] = useState(false);

  // ===== دریافت لیست پزشکان =====
  useEffect(() => {
    const fetchDoctors = async () => {
      setLoadingDoctors(true);
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        // ✅ بررسی ساختار پاسخ
        if (response.data?.success) {
          const data = response.data.data;
          const list = data?.data || data || [];
          setDoctors(Array.isArray(list) ? list : []);
        } else {
          setDoctors([]);
        }
      } catch (error) {
        console.error('Error fetching doctors:', error);
        setDoctors([]);
      } finally {
        setLoadingDoctors(false);
      }
    };
    fetchDoctors();
  }, []);

  // ===== دریافت لیست بیماران =====
  useEffect(() => {
    const fetchPatients = async () => {
      setLoadingPatients(true);
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        // ✅ بررسی ساختار پاسخ
        if (response.data?.success) {
          const data = response.data.data;
          const list = data?.data || data || [];
          setPatients(Array.isArray(list) ? list : []);
        } else {
          setPatients([]);
        }
      } catch (error) {
        console.error('Error fetching patients:', error);
        setPatients([]);
      } finally {
        setLoadingPatients(false);
      }
    };
    fetchPatients();
  }, []);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await referralsService.create(values);
      message.success(t('referral_created', 'ارجاع با موفقیت ایجاد شد'));
      router.push('/admin/referrals');
    } catch (error) {
      console.error('Error creating referral:', error);
      message.error(t('create_error', 'خطا در ایجاد ارجاع'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  // اگر patients آرایه نیست
  if (!Array.isArray(patients)) {
    console.error('⚠️ Patients is not an array:', patients);
    return (
        <div style={{ padding: 24 }}>
          <Title level={4}>خطا در دریافت اطلاعات</Title>
          <Text type="danger">لطفاً صفحه را مجدداً بارگذاری کنید.</Text>
          <Button onClick={() => window.location.reload()} style={{ marginTop: 16 }}>
            بارگذاری مجدد
          </Button>
        </div>
    );
  }

  // اگر doctors آرایه نیست
  if (!Array.isArray(doctors)) {
    console.error('⚠️ Doctors is not an array:', doctors);
    return (
        <div style={{ padding: 24 }}>
          <Title level={4}>خطا در دریافت اطلاعات</Title>
          <Text type="danger">لطفاً صفحه را مجدداً بارگذاری کنید.</Text>
          <Button onClick={() => window.location.reload()} style={{ marginTop: 16 }}>
            بارگذاری مجدد
          </Button>
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
                  {t('new_referral', 'ارجاع جدید')}
                </Title>
                <Text type="secondary">
                  {t('create_referral_subtitle', 'ارجاع بیمار به پزشک دیگر')}
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
                status: 'pending',
              }}
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
                            label: p.full_name || p.name || `بیمار ${p.id}`,
                          }))}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="from_doctor_id"
                        label={t('from_doctor', 'پزشک مبدأ')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                    >
                      <Select
                          placeholder={t('select_from_doctor', 'انتخاب پزشک مبدأ...')}
                          loading={loadingDoctors}
                          showSearch
                          optionFilterProp="children"
                          options={doctors.map((d) => ({
                            value: d.id,
                            label: d.full_name || d.name || `پزشک ${d.id}`,
                          }))}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Row gutter={[16, 0]}>
                  <Col xs={24} md={12}>
                    <Form.Item
                        name="to_doctor_id"
                        label={t('to_doctor', 'پزشک مقصد')}
                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                    >
                      <Select
                          placeholder={t('select_to_doctor', 'انتخاب پزشک مقصد...')}
                          loading={loadingDoctors}
                          showSearch
                          optionFilterProp="children"
                          options={doctors.map((d) => ({
                            value: d.id,
                            label: d.full_name || d.name || `پزشک ${d.id}`,
                          }))}
                      />
                    </Form.Item>
                  </Col>

                  <Col xs={24} md={12}>
                    <Form.Item
                        name="status"
                        label={t('status', 'وضعیت')}
                    >
                      <Select
                          options={[
                            { value: 'pending', label: t('pending', 'در انتظار') },
                            { value: 'accepted', label: t('accepted', 'پذیرفته شده') },
                            { value: 'rejected', label: t('rejected', 'رد شده') },
                            { value: 'completed', label: t('completed', 'تکمیل شده') },
                          ]}
                      />
                    </Form.Item>
                  </Col>
                </Row>

                <Form.Item
                    name="reason"
                    label={t('reason', 'دلیل ارجاع')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                >
                  <TextArea
                      rows={3}
                      placeholder={t('reason_placeholder', 'دلیل ارجاع بیمار...')}
                  />
                </Form.Item>

                <Form.Item
                    name="notes"
                    label={t('notes', 'یادداشت‌ها')}
                >
                  <TextArea
                      rows={2}
                      placeholder={t('notes_placeholder', 'یادداشت‌های تکمیلی...')}
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
                    <ArrowRightOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                    <div style={{ marginTop: 8 }}>
                      <Text type="secondary">{t('referral_info', 'اطلاعات ارجاع')}</Text>
                    </div>
                  </div>

                  <Divider />

                  <div style={{ textAlign: 'center' }}>
                    <Text type="secondary" style={{ fontSize: 12 }}>
                      {t('referral_help', 'پس از ایجاد، ارجاع برای پزشک مقصد ارسال می‌شود')}
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