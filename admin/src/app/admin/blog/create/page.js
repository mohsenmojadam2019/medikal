// src/app/admin/blog/create/page.js

'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card,
  Form,
  Input,
  Button,
  Select,
  Upload,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Switch,
  App,
  Image,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  FileTextOutlined,
  DeleteOutlined,
} from '@ant-design/icons';
import { blogService, categoriesService, tagsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';

const { Title, Text } = Typography;
const { TextArea } = Input;

export default function CreatePostPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const { message } = App.useApp();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState([]);
  const [tags, setTags] = useState([]);
  const [fileList, setFileList] = useState([]);
  const [previewImage, setPreviewImage] = useState(null);

  // ===== دریافت دسته‌بندی‌ها و تگ‌ها =====
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await categoriesService.getAll();
        if (response.data?.success) {
          const data = response.data.data;
          const list = data?.data || data || [];
          setCategories(Array.isArray(list) ? list : []);
        } else {
          setCategories([]);
        }
      } catch (error) {
        console.error('Error fetching categories:', error);
        setCategories([]);
      }
    };
    const fetchTags = async () => {
      try {
        const response = await tagsService.getAll();
        if (response.data?.success) {
          const data = response.data.data;
          const list = data?.data || data || [];
          setTags(Array.isArray(list) ? list : []);
        } else {
          setTags([]);
        }
      } catch (error) {
        console.error('Error fetching tags:', error);
        setTags([]);
      }
    };
    fetchCategories();
    fetchTags();
  }, []);

  // ===== ارسال فرم =====
  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(values).forEach((key) => {
        if (key === 'tags' && Array.isArray(values[key])) {
          values[key].forEach((tag) => formData.append('tags[]', tag));
        } else if (values[key] !== undefined && values[key] !== null) {
          formData.append(key, values[key]);
        }
      });

      if (fileList.length > 0 && fileList[0].originFileObj) {
        formData.append('featured_image', fileList[0].originFileObj);
      }

      const response = await blogService.create(formData);

      if (response.data?.success) {
        message.success(t('post_created', 'مقاله با موفقیت ایجاد شد'));
        router.push('/admin/blog');
      } else {
        message.error(response.data?.message || t('create_error', 'خطا در ایجاد مقاله'));
      }
    } catch (error) {
      console.error('Error creating post:', error);
      const errorMessage = error?.response?.data?.message || error?.message || t('create_error', 'خطا در ایجاد مقاله');
      message.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    router.back();
  };

  // ===== آپلود تصویر با پیش‌نمایش =====
  const uploadProps = {
    onRemove: () => {
      setFileList([]);
      setPreviewImage(null);
    },
    beforeUpload: (file) => {
      // بررسی نوع فایل
      if (!file.type.startsWith('image/')) {
        message.error(t('invalid_image', 'لطفاً یک فایل تصویری انتخاب کنید'));
        return false;
      }

      // بررسی حجم فایل (حداکثر 5MB)
      if (file.size > 5 * 1024 * 1024) {
        message.error(t('file_too_large', 'حجم فایل نباید بیشتر از ۵ مگابایت باشد'));
        return false;
      }

      // ایجاد پیش‌نمایش
      const reader = new FileReader();
      reader.onload = (e) => {
        setPreviewImage(e.target.result);
      };
      reader.readAsDataURL(file);

      setFileList([file]);
      return false;
    },
    fileList: fileList.map((file) => ({
      uid: file.uid || '-1',
      name: file.name,
      status: 'done',
      url: previewImage || URL.createObjectURL(file),
    })),
    maxCount: 1,
    accept: 'image/*',
  };

  // ===== دکمه‌های ویرایشگر =====
  const insertTag = (tag) => {
    const textarea = document.getElementById('content-editor');
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const after = text.substring(end);
    
    const newText = before + tag + after;
    form.setFieldsValue({ content: newText });
    
    // قرار دادن cursor بعد از تگ
    setTimeout(() => {
      textarea.focus();
      textarea.setSelectionRange(start + tag.length, start + tag.length);
    }, 10);
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
                {t('new_post', 'مقاله جدید')}
              </Title>
              <Text type="secondary">
                {t('create_post_subtitle', 'نوشتن مقاله جدید')}
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
            status: 'draft',
            is_featured: false,
          }}
        >
          <Row gutter={[24, 0]}>
            <Col xs={24} lg={16}>
              <Form.Item
                name="title"
                label={t('title', 'عنوان مقاله')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <Input
                  prefix={<FileTextOutlined />}
                  placeholder={t('title_placeholder', 'عنوان مقاله...')}
                />
              </Form.Item>

              <Form.Item
                name="summary"
                label={t('summary', 'خلاصه مقاله')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('summary_placeholder', 'خلاصه مقاله...')}
                />
              </Form.Item>

              {/* ===== دکمه‌های ویرایشگر ===== */}
              <div style={{ marginBottom: 8 }}>
                <Text type="secondary" style={{ display: 'block', marginBottom: 8 }}>
                  {t('formatting_tools', 'ابزارهای قالب‌بندی')}:
                </Text>
                <Space wrap size="small">
                  <Button size="small" onClick={() => insertTag('# ')}>H1</Button>
                  <Button size="small" onClick={() => insertTag('## ')}>H2</Button>
                  <Button size="small" onClick={() => insertTag('### ')}>H3</Button>
                  <Button size="small" onClick={() => insertTag('**متن**')}>پررنگ</Button>
                  <Button size="small" onClick={() => insertTag('*متن*')}>کج</Button>
                  <Button size="small" onClick={() => insertTag('- ')}>لیست</Button>
                  <Button size="small" onClick={() => insertTag('1. ')}>لیست شماره‌دار</Button>
                  <Button size="small" onClick={() => insertTag('> ')}>نقل قول</Button>
                  <Button size="small" onClick={() => insertTag('```\nکد\n```')}>کد</Button>
                  <Button size="small" onClick={() => insertTag('[متن](لینک)')}>لینک</Button>
                  <Button size="small" onClick={() => insertTag('---')}>خط جداکننده</Button>
                </Space>
              </div>

              <Form.Item
                name="content"
                label={t('content', 'محتوا')}
                rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
              >
                <TextArea
                  id="content-editor"
                  rows={15}
                  placeholder={t('content_placeholder', 'محتوا...')}
                  style={{ fontFamily: 'monospace', fontSize: 14 }}
                />
              </Form.Item>

              <Divider />

              <Row gutter={[16, 0]}>
                <Col xs={24} md={12}>
                  <Form.Item
                    name="category_id"
                    label={t('category', 'دسته‌بندی')}
                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                  >
                    <Select
                      placeholder={t('select_category', 'انتخاب دسته‌بندی...')}
                      options={categories.map((cat) => ({
                        value: cat.id,
                        label: cat.name,
                      }))}
                    />
                  </Form.Item>
                </Col>

                <Col xs={24} md={12}>
                  <Form.Item
                    name="tags"
                    label={t('tags', 'تگ‌ها')}
                  >
                    <Select
                      mode="tags"
                      placeholder={t('select_tags', 'انتخاب تگ‌ها...')}
                      options={tags.map((tag) => ({
                        value: tag.id,
                        label: tag.name,
                      }))}
                      style={{ width: '100%' }}
                    />
                  </Form.Item>
                </Col>
              </Row>

              <Form.Item
                name="meta_tags"
                label={t('meta_tags', 'تگ‌های متا')}
              >
                <TextArea
                  rows={2}
                  placeholder={t('meta_tags_placeholder', 'تگ‌های متا برای سئو...')}
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
                  {/* ===== پیش‌نمایش تصویر ===== */}
                  <div
                    style={{
                      width: '100%',
                      height: 200,
                      background: '#e2e8f0',
                      borderRadius: 8,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                      marginBottom: 16,
                      position: 'relative',
                    }}
                  >
                    {previewImage ? (
                      <>
                        <img
                          src={previewImage}
                          alt="تصویر شاخص"
                          style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                        />
                        <Button
                          type="text"
                          danger
                          icon={<DeleteOutlined />}
                          onClick={() => {
                            setFileList([]);
                            setPreviewImage(null);
                          }}
                          style={{
                            position: 'absolute',
                            top: 8,
                            right: 8,
                            background: 'rgba(255,255,255,0.9)',
                            borderRadius: '50%',
                          }}
                        />
                      </>
                    ) : (
                      <FileTextOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...uploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {previewImage ? t('change_image', 'تغییر تصویر') : t('upload_featured_image', 'آپلود تصویر شاخص')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('image_size', 'حداکثر ۵ مگابایت - فرمت‌های JPG, PNG, WebP')}
                  </Text>
                </div>

                <Divider />

                <Form.Item
                  name="status"
                  label={t('status', 'وضعیت')}
                >
                  <Select
                    options={[
                      { value: 'draft', label: t('draft', 'پیش‌نویس') },
                      { value: 'published', label: t('published', 'منتشر شده') },
                    ]}
                  />
                </Form.Item>

                <Form.Item
                  name="is_featured"
                  label={t('is_featured', 'مقاله ویژه')}
                  valuePropName="checked"
                >
                  <Switch
                    checkedChildren={t('yes', 'بله')}
                    unCheckedChildren={t('no', 'خیر')}
                  />
                </Form.Item>

                <Divider />

                <div style={{ textAlign: 'center' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {t('post_help', 'پس از انتشار، مقاله در وبلاگ نمایش داده می‌شود')}
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
