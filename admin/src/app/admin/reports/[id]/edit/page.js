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
  FileSearchOutlined,
} from '@ant-design/icons';
import { reportsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditReportPage() {
  const router = useRouter();
  const params = useParams();
  const reportId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [report, setReport] = useState(null);

  useEffect(() => {
    const fetchReport = async () => {
      try {
        const response = await reportsService.getById(reportId);
        setReport(response.data);
        form.setFieldsValue({
          ...response.data,
          from_date: response.data.from_date,
          to_date: response.data.to_date,
        });
      } catch (error) {
        console.error('Error fetching report:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (reportId) {
      fetchReport();
    }
  }, [reportId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await reportsService.update(reportId, values);
      message.success(t('report_updated', 'گزارش با موفقیت به‌روزرسانی شد'));
      router.push('/admin/reports');
    } catch (error) {
      console.error('Error updating report:', error);
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
                {t('edit_report', 'ویرایش گزارش')}
              </Title>
              <Text type="secondary">
                {report?.title || t('edit_report_subtitle', 'ویرایش اطلاعات گزارش')}
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
              <Form.Item
                name="title"
                label={t('title', 'عنوان گزارش')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  prefix={<FileSearchOutlined />}
                  placeholder={t('title_placeholder', 'عنوان گزارش...')}
                />
              </Form.Item>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="type"
                    label={t('type', 'نوع گزارش')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_type', 'انتخاب نوع...')}
                      options={[
                        { value: 'appointments', label: t('appointments', 'نوبت‌ها') },
                        { value: 'patients', label: t('patients', 'بیماران') },
                        { value: 'doctors', label: t('doctors', 'پزشکان') },
                        { value: 'revenue', label: t('revenue', 'درآمد') },
                      ]}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="format"
                    label={t('format', 'فرمت خروجی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      options={[
                        { value: 'excel', label: t('excel', 'Excel') },
                        { value: 'pdf', label: t('pdf', 'PDF') },
                      ]}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="from_date"
                    label={t('from_date', 'از تاریخ')}
                  >
                    <JalaliDatePicker
                      placeholder={t('select_from_date', 'انتخاب از تاریخ')}
                      format="jYYYY/jMM/jDD"
                      size="large"
                      value={form.getFieldValue('from_date')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="to_date"
                    label={t('to_date', 'تا تاریخ')}
                  >
                    <JalaliDatePicker
                      placeholder={t('select_to_date', 'انتخاب تا تاریخ')}
                      format="jYYYY/jMM/jDD"
                      size="large"
                      value={form.getFieldValue('to_date')}
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
                  placeholder={t('description_placeholder', 'توضیحات گزارش...')}
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
                  <FileSearchOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('report_info', 'اطلاعات گزارش')}</Text>
                  </div>
                </div>

                <Divider />

                <div>
                  <Text type="secondary">{t('created_at', 'تاریخ ایجاد')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {report?.created_at ? dayjs(report.created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('status', 'وضعیت')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {report?.status || t('draft', 'پیش‌نویس')}
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('report_edit_help', 'تغییرات روی گزارش اعمال می‌شود')}
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
