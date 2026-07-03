'use client';

import { useState } from 'react';
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
  DatePicker,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  FileSearchOutlined,
  FileExcelOutlined,
  FilePdfOutlined,
} from '@ant-design/icons';
import { reportsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';

const { Title, Text } = Typography;
const { RangePicker } = DatePicker;

export default function CreateReportPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      await reportsService.create(values);
      message.success(t('report_created', 'گزارش با موفقیت ایجاد شد'));
      router.push('/admin/reports');
    } catch (error) {
      console.error('Error creating report:', error);
      message.error(t('create_error', 'خطا در ایجاد گزارش'));
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
                {t('new_report', 'گزارش جدید')}
              </Title>
              <Text type="secondary">
                {t('create_report_subtitle', 'ایجاد گزارش جدید')}
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
            format: 'excel',
          }}
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

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('report_help', 'گزارش پس از ایجاد در لیست گزارشات ذخیره می‌شود')}
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
