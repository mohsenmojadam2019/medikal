'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  Upload,
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Spin,
  ColorPicker,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  HeartOutlined,
} from '@ant-design/icons';
import { specialtiesService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function EditSpecialtyPage() {
  const router = useRouter();
  const params = useParams();
  const specialtyId = params.id;
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [fetchLoading, setFetchLoading] = useState(true);
  const [specialty, setSpecialty] = useState(null);
  const [fileList, setFileList] = useState([]);
  const [color, setColor] = useState('#2563eb');

  useEffect(() => {
    const fetchSpecialty = async () => {
      try {
        const response = await specialtiesService.getById(specialtyId);
        setSpecialty(response.data);
        form.setFieldsValue(response.data);
        setColor(response.data.icon_color || '#2563eb');
        if (response.data.icon_url) {
          setFileList([
            {
              uid: '-1',
              name: 'icon',
              status: 'done',
              url: response.data.icon_url,
            },
          ]);
        }
      } catch (error) {
        console.error('Error fetching specialty:', error);
        message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
      } finally {
        setFetchLoading(false);
      }
    };

    if (specialtyId) {
      fetchSpecialty();
    }
  }, [specialtyId, form, t]);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(values).forEach((key) => {
        if (values[key] !== undefined && values[key] !== null) {
          formData.append(key, values[key]);
        }
      });

      formData.append('icon_color', color);

      if (fileList.length > 0 && fileList[0].originFileObj) {
        formData.append('icon', fileList[0].originFileObj);
      }

      await specialtiesService.update(specialtyId, formData);
      message.success(t('updated', 'اطلاعات با موفقیت به‌روزرسانی شد'));
      router.push('/admin/specialties');
    } catch (error) {
      console.error('Error updating specialty:', error);
      message.error(t('update_error', 'خطا در به‌روزرسانی'));
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  const uploadProps = {
    onRemove: () => {
      setFileList([]);
    },
    beforeUpload: (file) => {
      setFileList([file]);
      return false;
    },
    fileList,
    maxCount: 1,
    accept: 'image/*',
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
                {t('edit_specialty', 'ویرایش تخصص')}
              </Title>
              <Text type="secondary">
                {specialty?.name || t('edit_specialty_subtitle', 'ویرایش اطلاعات تخصص')}
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
                    name="name"
                    label={t('name', 'نام تخصص')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Input
                      prefix={<HeartOutlined />}
                      placeholder={t('name_placeholder', 'مثال: جراحی عمومی')}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="slug"
                    label={t('slug', 'شناسه یکتا')}
                    rules={[
                      { pattern: /^[a-z0-9-]+$/, message: t('slug_invalid', 'فقط حروف کوچک، اعداد و خط تیره') },
                    ]}
                  >
                    <Input
                      placeholder={t('slug_placeholder', 'مثال: general-surgery')}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="description"
                label={t('description', 'توضیحات')}
              >
                <TextArea
                  rows={4}
                  placeholder={t('description_placeholder', 'توضیحات درباره تخصص...')}
                />
              </Form.Item>
            </Col>

            <Col xs={24} lg={8}>
              <Card
                style={{
                  borderRadius: 12,
                  borderColor: '#e8e8f0',
                }}
              >
                <div style={{ textAlign: 'center' }}>
                  <div
                    style={{
                      width: 120,
                      height: 120,
                      margin: '0 auto 16px',
                      borderRadius: '50%',
                      background: color,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                    }}
                  >
                    {fileList.length > 0 && fileList[0].url ? (
                      <img
                        src={fileList[0].url}
                        alt="آیکون"
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    ) : fileList.length > 0 && fileList[0].originFileObj ? (
                      <img
                        src={URL.createObjectURL(fileList[0].originFileObj)}
                        alt="آیکون"
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    ) : (
                      <HeartOutlined style={{ fontSize: 48, color: '#fff' }} />
                    )}
                  </div>

                  <Upload {...uploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('change_icon', 'تغییر آیکون')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('icon_size', 'حداکثر ۱ مگابایت')}
                  </Text>
                </div>

                <Divider />

                <Form.Item
                  name="icon_color"
                  label={t('color', 'رنگ آیکون')}
                >
                  <ColorPicker
                    value={color}
                    onChange={(value) => setColor(value.toHexString())}
                    showText
                  />
                </Form.Item>

                <Form.Item
                  name="is_active"
                  label={t('status', 'وضعیت')}
                >
                  <Select
                    options={[
                      { value: true, label: t('active', 'فعال') },
                      { value: false, label: t('inactive', 'غیرفعال') },
                    ]}
                  />
                </Form.Item>
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
