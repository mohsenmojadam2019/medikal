// src/app/admin/reports/page.js

'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Table,
  Tag,
  App,
} from 'antd';
import {
  ArrowLeftOutlined,
  FileSearchOutlined,
  DownloadOutlined,
  PrinterOutlined,
  FileExcelOutlined,
  FilePdfOutlined,
} from '@ant-design/icons';
import { reportsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

const { Title, Text } = Typography;

export default function ReportsPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [reportTypes, setReportTypes] = useState([]);
  const [reportData, setReportData] = useState([]);
  const [generated, setGenerated] = useState(false);

  // ===== دریافت انواع گزارش =====
  useEffect(() => {
    const fetchReportTypes = async () => {
      try {
        const response = await reportsService.getTypes();
        if (response.data?.success) {
          const data = response.data.data;
          const types = Object.keys(data).map((key) => ({
            value: key,
            label: data[key].label,
            icon: data[key].icon,
            filters: data[key].filters,
            formats: data[key].formats,
          }));
          setReportTypes(types);
        }
      } catch (error) {
        console.error('Error fetching report types:', error);
        message.error(t('fetch_error', 'خطا در دریافت انواع گزارش'));
        setReportTypes([]);
      }
    };
    fetchReportTypes();
  }, [t]);

  // ===== تولید گزارش =====
  const handleGenerate = async (values) => {
    setLoading(true);
    setGenerated(false);
    try {
      const response = await reportsService.generate(values);
      if (response.data?.success) {
        const data = response.data.data;
        setReportData(Array.isArray(data) ? data : []);
        setGenerated(true);
        message.success(t('report_generated', 'گزارش با موفقیت تولید شد'));
      } else {
        message.error(response.data?.message || t('generate_error', 'خطا در تولید گزارش'));
      }
    } catch (error) {
      console.error('Error generating report:', error);
      message.error(t('generate_error', 'خطا در تولید گزارش'));
      setReportData([]);
    } finally {
      setLoading(false);
    }
  };

  // ===== خروجی Excel =====
  const handleExportExcel = async () => {
    try {
      const values = form.getFieldsValue();
      const response = await reportsService.exportExcel(values);

      // دانلود فایل
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `report-${Date.now()}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();

      message.success(t('exported', 'گزارش با موفقیت خروجی گرفته شد'));
    } catch (error) {
      console.error('Error exporting report:', error);
      message.error(t('export_error', 'خطا در خروجی گرفتن گزارش'));
    }
  };

  // ===== خروجی PDF =====
  const handleExportPdf = async () => {
    try {
      const values = form.getFieldsValue();
      const response = await reportsService.exportPdf(values);

      // دانلود فایل
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `report-${Date.now()}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();

      message.success(t('exported', 'گزارش با موفقیت خروجی گرفته شد'));
    } catch (error) {
      console.error('Error exporting report:', error);
      message.error(t('export_error', 'خطا در خروجی گرفتن گزارش'));
    }
  };

  // ===== فرمت تاریخ =====
  const formatJalaliDate = (date) => {
    if (!date) return '—';
    try {
      return moment(date).format('jYYYY/jMM/jDD');
    } catch (error) {
      return '—';
    }
  };

  // ===== ستون‌های جدول =====
  const columns = [
    {
      title: t('id', 'شناسه'),
      dataIndex: 'id',
      key: 'id',
      width: 70,
    },
    {
      title: t('title', 'عنوان'),
      dataIndex: 'title',
      key: 'title',
    },
    {
      title: t('value', 'مقدار'),
      dataIndex: 'value',
      key: 'value',
      render: (value) => value || '—',
    },
    {
      title: t('date', 'تاریخ'),
      dataIndex: 'date',
      key: 'date',
      render: (date) => date ? formatJalaliDate(date) : '—',
    },
  ];

  // اگر reportData آرایه نیست
  if (!Array.isArray(reportData)) {
    console.error('⚠️ ReportData is not an array:', reportData);
    setReportData([]);
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
            <Title level={2} style={{ margin: 0 }}>
              {t('reports_management', 'گزارشات')}
            </Title>
            <Text type="secondary">
              {t('reports_subtitle', 'گزارشات مالی و آماری')}
            </Text>
          </div>
        </div>

        <Card
            style={{
              borderRadius: 12,
              borderColor: '#e8e8f0',
              marginBottom: 16,
            }}
        >
          <Form
              form={form}
              layout="vertical"
              onFinish={handleGenerate}
              size="large"
              initialValues={{
                format: 'excel',
              }}
          >
            <Row gutter={[16, 0]}>
              <Col xs={24} md={12} lg={8}>
                <Form.Item
                    name="type"
                    label={t('report_type', 'نوع گزارش')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                >
                  <Select
                      placeholder={t('select_report_type', 'انتخاب نوع گزارش...')}
                      options={reportTypes.map((type) => ({
                        value: type.value,
                        label: `${type.icon} ${type.label}`,
                      }))}
                  />
                </Form.Item>
              </Col>

              <Col xs={24} md={12} lg={8}>
                <Form.Item
                    name="from_date"
                    label={t('from_date', 'از تاریخ')}
                >
                  <JalaliDatePicker
                      placeholder={t('select_from_date', 'انتخاب از تاریخ')}
                      size="middle"
                      onChange={(date) => form.setFieldsValue({ from_date: date })}
                  />
                </Form.Item>
              </Col>

              <Col xs={24} md={12} lg={8}>
                <Form.Item
                    name="to_date"
                    label={t('to_date', 'تا تاریخ')}
                >
                  <JalaliDatePicker
                      placeholder={t('select_to_date', 'انتخاب تا تاریخ')}
                      size="middle"
                      onChange={(date) => form.setFieldsValue({ to_date: date })}
                  />
                </Form.Item>
              </Col>
            </Row>

            <Row gutter={[16, 0]}>
              <Col xs={24} lg={24}>
                <Space>
                  <Button
                      type="primary"
                      htmlType="submit"
                      loading={loading}
                      icon={<FileSearchOutlined />}
                      style={{
                        background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                        border: 'none',
                      }}
                  >
                    {t('generate', 'تولید گزارش')}
                  </Button>
                  <Button
                      icon={<FileExcelOutlined />}
                      onClick={handleExportExcel}
                      disabled={!generated}
                      style={{ color: '#10b981' }}
                  >
                    {t('excel', 'خروجی Excel')}
                  </Button>
                  <Button
                      icon={<FilePdfOutlined />}
                      onClick={handleExportPdf}
                      disabled={!generated}
                      style={{ color: '#ef4444' }}
                  >
                    {t('pdf', 'خروجی PDF')}
                  </Button>
                  <Button
                      icon={<PrinterOutlined />}
                      onClick={() => window.print()}
                      disabled={!generated}
                  >
                    {t('print', 'چاپ')}
                  </Button>
                </Space>
              </Col>
            </Row>
          </Form>
        </Card>

        {generated && (
            <Card
                style={{
                  borderRadius: 12,
                  borderColor: '#e8e8f0',
                }}
                title={
                  <Space>
                    <FileSearchOutlined style={{ color: '#2563eb' }} />
                    <Text strong>{t('report_result', 'نتیجه گزارش')}</Text>
                    <Tag color="blue">{reportData.length} {t('records', 'رکورد')}</Tag>
                  </Space>
                }
            >
              <Table
                  columns={columns}
                  dataSource={reportData}
                  rowKey="id"
                  pagination={{
                    pageSize: 20,
                    showSizeChanger: true,
                    showTotal: (total) => `${t('total', 'مجموع')} ${total} ${t('records', 'رکورد')}`,
                  }}
                  locale={{
                    emptyText: t('no_data', 'هیچ داده‌ای یافت نشد'),
                  }}
              />
            </Card>
        )}
      </div>
  );
}