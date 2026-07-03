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
  Table,
  InputNumber,
  Popconfirm,
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
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditPrescriptionPage() {
  const router = useRouter();
  const params = useParams();
  const prescriptionId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [prescription, setPrescription] = useState(null);
  const [doctors, setDoctors] = useState([]);
  const [patients, setPatients] = useState([]);
  const [drugs, setDrugs] = useState([]);
  const [items, setItems] = useState([]);
  const [selectedDrug, setSelectedDrug] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [doctorsRes, patientsRes, drugsRes] = await Promise.all([
          doctorsService.getAll({ per_page: 100 }),
          patientsService.getAll({ per_page: 100 }),
          drugsService.getAll({ per_page: 1000 }),
        ]);
        setDoctors(doctorsRes.data || []);
        setPatients(patientsRes.data || []);
        setDrugs(drugsRes.data || []);
      } catch (error) {
        console.error('Error fetching data:', error);
      }
    };
    fetchData();
  }, []);

  useEffect(() => {
    const fetchPrescription = async () => {
      try {
        const response = await prescriptionsService.getById(prescriptionId);
        setPrescription(response.data);
        form.setFieldsValue(response.data);
        setItems(response.data.items || []);
      } catch (error) {
        console.error('Error fetching prescription:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (prescriptionId) {
      fetchPrescription();
    }
  }, [prescriptionId, form, t]);

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

      await prescriptionsService.update(prescriptionId, data);
      message.success(t('updated', 'نسخه با موفقیت به‌روزرسانی شد'));
      router.push('/admin/prescriptions');
    } catch (error) {
      console.error('Error updating prescription:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
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
                {t('edit_prescription', 'ویرایش نسخه')}
              </Title>
              <Text type="secondary">
                {prescription?.code || t('edit_prescription_subtitle', 'ویرایش اطلاعات نسخه')}
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
                      value={form.getFieldValue('expiry_date')}
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
                        { value: 'completed', label: t('completed', 'تکمیل شده') },
                        { value: 'cancelled', label: t('cancelled', 'لغو شده') },
                        { value: 'expired', label: t('expired', 'منقضی شده') },
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

                <div>
                  <Text type="secondary">{t('code', 'کد نسخه')}</Text>
                  <div style={{ fontWeight: 600, marginTop: 4 }}>
                    {prescription?.code || '—'}
                  </div>
                </div>

                <div style={{ marginTop: 12 }}>
                  <Text type="secondary">{t('created_at', 'تاریخ ایجاد')}</Text>
                  <div style={{ fontWeight: 500, marginTop: 4 }}>
                    {prescription?.created_at ? dayjs(prescription.created_at).format('jYYYY/jMM/jDD HH:mm') : '—'}
                  </div>
                </div>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('prescription_edit_help', 'تغییرات روی نسخه اعمال می‌شود')}
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
