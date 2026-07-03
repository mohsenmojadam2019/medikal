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
  Table,
  InputNumber,
  Popconfirm,
  Alert,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  PlusOutlined,
  DeleteOutlined,
  MedicineBoxOutlined,
  UserOutlined,
} from '@ant-design/icons';
import { prescriptionsService, doctorsService, patientsService, drugsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreatePrescriptionPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);
  const [drugs, setDrugs] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(false);
  const [loadingPatients, setLoadingPatients] = useState(false);
  const [loadingDrugs, setLoadingDrugs] = useState(false);
  const [items, setItems] = useState([]);
  const [selectedDrug, setSelectedDrug] = useState(null);

  useEffect(() => {
    const fetchDoctors = async () => {
      setLoadingDoctors(true);
      try {
        const response = await doctorsService.getAll({ per_page: 100 });
        setDoctors(response.data || []);
      } catch (error) {
        console.error('Error fetching doctors:', error);
      } finally {
        setLoadingDoctors(false);
      }
    };
    fetchDoctors();
  }, []);

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

  useEffect(() => {
    const fetchDrugs = async () => {
      setLoadingDrugs(true);
      try {
        const response = await drugsService.getAll({ per_page: 1000 });
        setDrugs(response.data || []);
      } catch (error) {
        console.error('Error fetching drugs:', error);
      } finally {
        setLoadingDrugs(false);
      }
    };
    fetchDrugs();
  }, []);

  const handleAddDrug = () => {
    if (!selectedDrug) {
      message.warning(t('select_drug', 'لطفاً یک دارو انتخاب کنید'));
      return;
    }

    const formValues = form.getFieldsValue(['dosage', 'frequency', 'duration', 'instructions']);
    if (!formValues.dosage || !formValues.frequency || !formValues.duration) {
      message.warning(t('fill_drug_fields', 'لطفاً دوز، تعداد و مدت مصرف را وارد کنید'));
      return;
    }

    const drug = drugs.find(d => d.id === selectedDrug);
    setItems([
      ...items,
      {
        id: Date.now(),
        drug_id: selectedDrug,
        drug_name: drug?.name || '',
        dosage: formValues.dosage,
        frequency: formValues.frequency,
        duration: formValues.duration,
        instructions: formValues.instructions || '',
      },
    ]);

    form.setFieldsValue({
      dosage: undefined,
      frequency: undefined,
      duration: undefined,
      instructions: undefined,
    });
    setSelectedDrug(null);
    message.success(t('drug_added', 'دارو به نسخه اضافه شد'));
  };

  const handleRemoveDrug = (id) => {
    setItems(items.filter(item => item.id !== id));
  };

  const handleSubmit = async (values) => {
    if (items.length === 0) {
      message.warning(t('no_drugs', 'حداقل یک دارو باید به نسخه اضافه شود'));
      return;
    }

    setLoading(true);
    try {
      const data = {
        ...values,
        items: items.map(item => ({
          drug_id: item.drug_id,
          dosage: item.dosage,
          frequency: item.frequency,
          duration: item.duration,
          instructions: item.instructions,
        })),
      };

      await prescriptionsService.create(data);
      message.success(t('prescription_created', 'نسخه با موفقیت ایجاد شد'));
      router.push('/admin/prescriptions');
    } catch (error) {
      console.error('Error creating prescription:', error);
      message.error(t('create_error', 'خطا در ایجاد نسخه'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  const columns = [
    {
      title: t('drug_name', 'نام دارو'),
      dataIndex: 'drug_name',
      key: 'drug_name',
    },
    {
      title: t('dosage', 'دوز مصرفی'),
      dataIndex: 'dosage',
      key: 'dosage',
    },
    {
      title: t('frequency', 'تعداد در روز'),
      dataIndex: 'frequency',
      key: 'frequency',
    },
    {
      title: t('duration', 'مدت (روز)'),
      dataIndex: 'duration',
      key: 'duration',
    },
    {
      title: t('instructions', 'دستورالعمل'),
      dataIndex: 'instructions',
      key: 'instructions',
      ellipsis: true,
    },
    {
      title: t('actions', 'عملیات'),
      key: 'actions',
      render: (_, record) => (
        <Popconfirm
          title={t('remove_drug_confirm', 'آیا از حذف این دارو اطمینان دارید؟')}
          onConfirm={() => handleRemoveDrug(record.id)}
          okText={t('yes', 'بله')}
          cancelText={t('no', 'خیر')}
        >
          <Button type="text" icon={<DeleteOutlined />} danger size="small" />
        </Popconfirm>
      ),
    },
  ];

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
                {t('new_prescription', 'نسخه جدید')}
              </Title>
              <Text type="secondary">
                {t('create_prescription_subtitle', 'ثبت نسخه الکترونیک جدید')}
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
                      loading={loadingDoctors}
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
                    name="expiry_date"
                    label={t('expiry_date', 'تاریخ انقضا')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <JalaliDatePicker
                      placeholder={t('select_expiry_date', 'انتخاب تاریخ انقضا')}
                      format="jYYYY/jMM/jDD"
                      size="large"
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
                        { value: 'pending', label: t('pending', 'در انتظار تایید') },
                        { value: 'active', label: t('active', 'فعال') },
                        { value: 'cancelled', label: t('cancelled', 'لغو شده') },
                      ]}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="instructions"
                label={t('additional_instructions', 'دستورالعمل تکمیلی')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('instructions_placeholder', 'دستورالعمل مصرف داروها...')}
                />
              </Form.Item>

              <Divider />

              <Title level={4}>{t('add_drug', 'افزودن دارو')}</Title>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item label={t('select_drug', 'انتخاب دارو')}>
                    <Select
                      placeholder={t('search_drug', 'جستجوی دارو...')}
                      loading={loadingDrugs}
                      showSearch
                      optionFilterProp="children"
                      value={selectedDrug}
                      onChange={setSelectedDrug}
                      options={drugs.map((d) => ({
                        value: d.id,
                        label: `${d.name} (${d.form || ''} ${d.strength || ''})`,
                      }))}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={6}>
                  <Form.Item name="dosage" label={t('dosage', 'دوز مصرفی')}>
                    <Input placeholder="۵۰۰mg" />
                  </Form.Item>
                </Col>

                <Col xs={24} md={6}>
                  <Form.Item name="frequency" label={t('frequency', 'تعداد در روز')}>
                    <InputNumber
                      min={1}
                      max={10}
                      style={{ width: '100%' }}
                      placeholder="۳"
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item name="duration" label={t('duration', 'مدت مصرف (روز)')}>
                    <InputNumber
                      min={1}
                      max={365}
                      style={{ width: '100%' }}
                      placeholder="۷"
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item name="instructions" label={t('instructions', 'دستورالعمل')}>
                    <Input placeholder={t('instructions_short', 'دستورالعمل مصرف...')} />
                  </Form.Item>
                </Col>
              </Row>

              <Button
                type="dashed"
                icon={<PlusOutlined />}
                onClick={handleAddDrug}
                block
                style={{ marginBottom: 16 }}
              >
                {t('add_to_prescription', 'افزودن به نسخه')}
              </Button>

              {items.length > 0 && (
                <Table
                  dataSource={items}
                  columns={columns}
                  rowKey="id"
                  pagination={false}
                  size="small"
                  style={{ marginTop: 16 }}
                  summary={() => (
                    <Table.Summary fixed>
                      <Table.Summary.Row>
                        <Table.Summary.Cell index={0} colSpan={5}>
                          <Text strong>
                            {t('total_drugs', 'تعداد داروها')}: {items.length}
                          </Text>
                        </Table.Summary.Cell>
                      </Table.Summary.Row>
                    </Table.Summary>
                  )}
                />
              )}
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
                  <MedicineBoxOutlined style={{ fontSize: 48, color: '#2563eb' }} />
                  <div style={{ marginTop: 8 }}>
                    <Text type="secondary">{t('prescription_info', 'اطلاعات نسخه')}</Text>
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('prescription_help', 'پس از ایجاد، نسخه قابل چاپ و ارسال برای بیمار است')}
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
