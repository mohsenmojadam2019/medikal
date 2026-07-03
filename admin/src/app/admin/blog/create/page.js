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
  message,
  Row,
  Col,
  Typography,
  Divider,
  Space,
  Switch,
} from 'antd';
import {
  ArrowLeftOutlined,
  SaveOutlined,
  UploadOutlined,
  FileTextOutlined,
} from '@ant-design/icons';
import { blogService, categoriesService, tagsService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import JalaliDatePicker from '@/components/admin/common/JalaliDatePicker';
import dynamic from 'next/dynamic';

const { Title, Text } = Typography;
const { TextArea } = Input;

const ReactQuill = dynamic(() => import('react-quill'), {
  ssr: false,
  loading: () => <div style={{ height: 200, background: '#f8fafc', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>در حال بارگذاری...</div>,
});

import 'react-quill/dist/quill.snow.css';

export default function CreatePostPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState([]);
  const [tags, setTags] = useState([]);
  const [fileList, setFileList] = useState([]);
  const [content, setContent] = useState('');

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await categoriesService.getAll();
        setCategories(response.data || []);
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };
    const fetchTags = async () => {
      try {
        const response = await tagsService.getAll();
        setTags(response.data || []);
      } catch (error) {
        console.error('Error fetching tags:', error);
      }
    };
    fetchCategories();
    fetchTags();
  }, []);

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
      
      formData.append('content', content);

      if (fileList.length > 0) {
        formData.append('featured_image', fileList[0].originFileObj);
      }

      await blogService.create(formData);
      message.success(t('post_created', 'مقاله با موفقیت ایجاد شد'));
      router.push('/admin/blog');
    } catch (error) {
      console.error('Error creating post:', error);
      message.error(t('create_error', 'خطا در ایجاد مقاله'));
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

  const modules = {
    toolbar: [
      [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      ['link', 'image', 'video'],
      ['clean']
    ],
  };

  const formats = [
    'header',
    'bold', 'italic', 'underline', 'strike',
    'list', 'bullet',
    'link', 'image', 'video',
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
                name="excerpt"
                label={t('excerpt', 'خلاصه مقاله')}
              >
                <TextArea
                  rows={3}
                  placeholder={t('excerpt_placeholder', 'خلاصه مقاله...')}
                />
              </Form.Item>

              <Form.Item
                label={t('content', 'محتوا')}
                required
              >
                <ReactQuill
                  theme="snow"
                  value={content}
                  onChange={setContent}
                  modules={modules}
                  formats={formats}
                  placeholder={t('content_placeholder', 'محتوا...')}
                  style={{ height: 300, marginBottom: 40 }}
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
                name="meta_description"
                label={t('meta_description', 'توضیحات متا')}
              >
                <TextArea
                  rows={2}
                  placeholder={t('meta_description_placeholder', 'توضیحات برای سئو...')}
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
                  <div
                    style={{
                      width: '100%',
                      height: 150,
                      background: '#e2e8f0',
                      borderRadius: 8,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      overflow: 'hidden',
                      marginBottom: 16,
                    }}
                  >
                    {fileList.length > 0 ? (
                      <img
                        src={URL.createObjectURL(fileList[0].originFileObj)}
                        alt="تصویر شاخص"
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    ) : (
                      <FileTextOutlined style={{ fontSize: 48, color: '#94a3b8' }} />
                    )}
                  </div>

                  <Upload {...uploadProps}>
                    <Button icon={<UploadOutlined />}>
                      {t('upload_featured_image', 'آپلود تصویر شاخص')}
                    </Button>
                  </Upload>
                  <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                    {t('image_size', 'حداکثر ۵ مگابایت')}
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
