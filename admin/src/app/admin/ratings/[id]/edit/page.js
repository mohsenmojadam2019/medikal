'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
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
  Spin,
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

export default function EditRatingPage() {
  const router = useRouter();
  const params = useParams();
  const ratingId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [rating, setRating] = useState(null);
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
    const fetchPatients = async () => {
      try {
        const response = await patientsService.getAll({ per_page: 100 });
        setPatients(response.data || []);
      } catch (error) {
        console.error('Error fetching patients:', error);
        message.error(t('fetch_error', 'خطا در دریافت لیست بیماران'));
      }
    };
    fetchDoctors();
    fetchPatients();
  }, [t]);

  useEffect(() => {
    const fetchRating = async () => {
      try {
        const response = await ratingsService.getById(ratingId);
        setRating(response.data);
        form.setFieldsValue(response.data);
      } catch (error) {
        console.error('Error fetching rating:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (ratingId) {
      fetchRating();
    }
  }, [ratingId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await ratingsService.update(ratingId, values);
      message.success(t('updated', 'نظر با موفقیت به‌روزرسانی شد'));
      router.push('/admin/ratings');
    } catch (error) {
      console.error('Error updating rating:', error);
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
                {t('edit_rating', 'ویرایش نظر')}
              </Title>
              <Text type="secondary">
                {t('edit_rating_subtitle', 'ویرایش نظر و امتیاز')}
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
                    {t('rating_edit_help', 'تغییرات روی نظر اعمال می‌شود')}
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
