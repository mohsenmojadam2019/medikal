'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  Rate,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UserOutlined,
  StarOutlined,
} from '@ant-design/icons';
import { ratingsService, doctorsService, patientsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreateRatingPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);

  useEffect(() => {
    const fetchDoctors = async () => {
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        setDoctors(response.data || []);
      } catch (error) {
        console.error('Error fetching doctors:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست پزشکان'));
      }
    };
    fetchDoctors();
  }, [t]);

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

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await ratingsService.create(values);
      message.success(t('rating_created', 'نظر با موفقیت ثبت شد'));
      router.push('/admin/ratings');
    } catch (error) {
      console.error('Error creating rating:', error);
      message.error(t('create_error', 'خطا در ثبت نظر'));
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
                {t('new_rating', 'ثبت نظر جدید')}
              </Title>
              <Text type="secondary">
                {t('create_rating_subtitle', 'ثبت نظر و امتیاز جدید')}
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
            rating: 5,
            is_approved: false,
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
                    name="doctor_id"
                    label={t('doctor', 'پزشک')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_doctor', 'انتخاب پزشک...')}
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

              <Form.Item
                name="rating"
                label={t('rating', 'امتیاز')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Rate allowHalf count={5} />
              </Form.Item>

              <Form.Item
                name="comment"
                label={t('comment', 'نظر')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <TextArea
                  rows={4}
                  placeholder={t('comment_placeholder', 'متن نظر...')}
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
                  <StarOutlined style={{ fontSize: 48, color: '#f59e0b' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('rating_info', 'اطلاعات نظر')}</Text>
                  </div>
                </div>

                <Divider />

                <Form.Item
                  name="is_approved"
                  label={t('status', 'وضعیت')}
                  valuePropName="checked"
                >
                  <Select
                    options={[
                      { value: true, label: t('approved', 'تایید شده') },
                      { value: false, label: t('pending_approval', 'در انتظار تایید') },
                    ]}
                  />
                </Form.Item>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('rating_help', 'پس از تایید، نظر در سایت نمایش داده می‌شود')}
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
