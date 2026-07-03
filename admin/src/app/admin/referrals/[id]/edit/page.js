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

export default function EditReferralPage() {
  const router = useRouter();
  const params = useParams();
  const referralId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [referral, setReferral] = useState(null);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);

  useEffect(() => {
    const fetchDoctors = async () => {
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        setDoctors(response.data || []);
      } catch (error) {
        console.error('Error fetching doctors:', error);
      }
    };
    const fetchPatients = async () => {
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        setPatients(response.data || []);
      } catch (error) {
        console.error('Error fetching patients:', error);
      }
    };
    fetchDoctors();
    fetchPatients();
  }, []);

  useEffect(() => {
    const fetchReferral = async () => {
      try {
        const response = await referralsService.getById(referralId);
        setReferral(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching referral:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (referralId) {
      fetchReferral();
    }
  }, [referralId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await referralsService.update(referralId, values);
      message.success(t('updated', 'ارجاع با موفقیت به‌روزرسانی شد'));
      router.push('/admin/referrals');
    } catch (error) {
      console.error('Error updating referral:', error);
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
                {t('edit_referral', 'ویرایش ارجاع')}
              </Title>
              <Text type="secondary">
                {t('edit_referral_subtitle', 'ویرایش اطلاعات ارجاع')}
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
                    name="from_doctor_id"
                    label={t('from_doctor', 'پزشک مبدأ')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_from_doctor', 'انتخاب پزشک مبدأ...')}
                      showSearch
                      optionFilterProp="children"
                      options={doctors.map((d) => ({
                        value: d.id,
                        label: `${d.full_name} (${d.specialty?.name || ''})`,
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
                      showSearch
                      optionFilterProp="children"
                      options={doctors.map((d) => ({
                        value: d.id,
                        label: `${d.full_name} (${d.specialty?.name || ''})`,
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
                    {t('referral_edit_help', 'تغییرات روی ارجاع اعمال می‌شود')}
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
