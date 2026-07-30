'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import {
    Card, Row, Col, Typography, Button, Input, Form,
    Upload, Space, Divider, Alert, App, Select, Spin
} from 'antd';
import { UploadOutlined, MedicineBoxOutlined, InsuranceOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';

const { Title, Text } = Typography;
const { TextArea } = Input;
const { Option } = Select;

export default function PrescriptionRequestPage() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const { message } = App.useApp();

    const [loading, setLoading] = useState(false);
    const [productInfo, setProductInfo] = useState(null);
    const [fileList, setFileList] = useState([]);
    const [form] = Form.useForm();

    const productId = searchParams?.get('product_id');
    const pharmacyId = searchParams?.get('pharmacy_id');
    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';

    useEffect(() => {
        if (productId) {
            fetchProductInfo();
        }
    }, [productId]);

    const fetchProductInfo = async () => {
        try {
            const res = await fetch(`${API_URL}/api/products/${productId}`);
            const data = await res.json();
            if (data.success) {
                setProductInfo(data.data);
            }
        } catch (error) {
            console.error('Error fetching product:', error);
        }
    };

    const handleSubmit = async (values) => {
        if (fileList.length === 0) {
            message.warning('لطفاً تصویر نسخه پزشکی را آپلود کنید');
            return;
        }

        setLoading(true);
        try {
            const token = localStorage.getItem('token');

            // ✅ چک کردن توکن
            if (!token) {
                message.warning('لطفاً ابتدا وارد شوید');
                router.push('/fa/login');
                return;
            }

            // ✅ تمیز کردن توکن (اگه با `|` باشه)
            const cleanToken = token.trim();
            console.log('🔑 توکن:', cleanToken);

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('pharmacy_id', pharmacyId || '');
            formData.append('patient_name', values.patient_name);
            formData.append('national_code', values.national_code);
            formData.append('insurance_type', values.insurance_type || '');
            formData.append('insurance_number', values.insurance_number || '');
            formData.append('doctor_name', values.doctor_name);
            formData.append('diagnosis', values.diagnosis || '');
            formData.append('notes', values.notes || '');
            formData.append('prescription', fileList[0].originFileObj);

            // ✅ تست با fetch خام
            const res = await fetch(`${API_URL}/api/pharmacy/prescription-request`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${cleanToken}`,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            // ✅ اگه 401 بود، لاگین رو refresh کن
            if (res.status === 401) {
                message.error('نشست شما منقضی شده است. لطفاً مجدداً وارد شوید.');
                localStorage.removeItem('token');
                setTimeout(() => router.push('/fa/login'), 1500);
                return;
            }

            const data = await res.json();

            if (res.ok && data.success) {
                message.success('درخواست نسخه با موفقیت ارسال شد');
                setTimeout(() => router.push('/fa/pharmacy'), 2000);
            } else {
                message.error(data.message || 'خطا در ارسال درخواست');
            }
        } catch (error) {
            console.error('❌ خطا:', error);
            message.error('خطا در ارتباط با سرور');
        } finally {
            setLoading(false);
        }
    };

    if (!productId) {
        return (
            <>
                <Header />
                <div style={{ textAlign: 'center', padding: 60 }}>
                    <Title level={3}>اطلاعات دارو یافت نشد</Title>
                    <Button onClick={() => router.push('/fa/pharmacy')}>بازگشت</Button>
                </div>
                <Footer />
            </>
        );
    }

    return (
        <>
            <Header />
            <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc', padding: '24px 20px' }}>
                <div style={{ maxWidth: '700px', margin: '0 auto' }}>
                    {/* دکمه بازگشت */}
                    <Button
                        icon={<ArrowLeftOutlined />}
                        onClick={() => router.back()}
                        style={{ marginBottom: 16 }}
                    >
                        بازگشت
                    </Button>

                    <Card style={{ borderRadius: 16, boxShadow: '0 4px 20px rgba(0,0,0,0.06)' }}>
                        <Title level={3} style={{ textAlign: 'center', marginBottom: 4 }}>
                            📋 درخواست دارو با نسخه پزشکی
                        </Title>
                        <Text type="secondary" style={{ display: 'block', textAlign: 'center', marginBottom: 16 }}>
                            برای دریافت داروهای نیازمند نسخه، اطلاعات زیر را تکمیل کنید
                        </Text>

                        <Divider style={{ margin: '12px 0' }} />

                        {productInfo && (
                            <Alert
                                message={`دارو: ${productInfo.name}`}
                                description={
                                    <div>
                                        <div>قیمت: <strong>{productInfo.price?.toLocaleString()} تومان</strong></div>
                                        <div>موجودی: <strong>{productInfo.stock > 0 ? `${productInfo.stock} عدد` : 'ناموجود'}</strong></div>
                                        {productInfo.generic_name && <div>نام ژنریک: {productInfo.generic_name}</div>}
                                    </div>
                                }
                                type="info"
                                showIcon
                                style={{ marginBottom: 16 }}
                            />
                        )}

                        <Form
                            form={form}
                            layout="vertical"
                            onFinish={handleSubmit}
                            initialValues={{
                                patient_name: '',
                                national_code: '',
                                insurance_type: '',
                                insurance_number: '',
                                doctor_name: '',
                                diagnosis: '',
                                notes: '',
                            }}
                        >
                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="patient_name"
                                        label="نام و نام خانوادگی"
                                        rules={[{ required: true, message: 'لطفاً نام خود را وارد کنید' }]}
                                    >
                                        <Input placeholder="نام کامل" size="large" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="national_code"
                                        label="کد ملی"
                                        rules={[
                                            { required: true, message: 'لطفاً کد ملی را وارد کنید' },
                                            { len: 10, message: 'کد ملی باید ۱۰ رقم باشد' }
                                        ]}
                                    >
                                        <Input placeholder="کد ملی" maxLength={10} size="large" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="insurance_type"
                                        label="نوع بیمه"
                                    >
                                        <Select placeholder="انتخاب کنید" allowClear size="large">
                                            <Option value="tamin">تامین اجتماعی</Option>
                                            <Option value="iran">بیمه ایران</Option>
                                            <Option value="asal">بیمه آسایش</Option>
                                            <Option value="dana">بیمه دانا</Option>
                                            <Option value="saman">بیمه سامان</Option>
                                            <Option value="other">سایر</Option>
                                        </Select>
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="insurance_number"
                                        label="شماره بیمه"
                                    >
                                        <Input placeholder="شماره بیمه" size="large" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="doctor_name"
                                        label="نام پزشک معالج"
                                        rules={[{ required: true, message: 'لطفاً نام پزشک را وارد کنید' }]}
                                    >
                                        <Input placeholder="نام پزشک" size="large" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="diagnosis"
                                        label="تشخیص پزشک"
                                    >
                                        <Input placeholder="تشخیص پزشک (اختیاری)" size="large" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Form.Item
                                name="notes"
                                label="توضیحات اضافی"
                            >
                                <TextArea rows={3} placeholder="توضیحات اضافی..." />
                            </Form.Item>

                            <Form.Item
                                label="تصویر نسخه پزشکی"
                                required
                            >
                                <Upload
                                    listType="picture"
                                    fileList={fileList}
                                    onChange={({ fileList: newFileList }) => setFileList(newFileList)}
                                    beforeUpload={() => false}
                                    maxCount={1}
                                    accept="image/*,application/pdf"
                                >
                                    <Button icon={<UploadOutlined />} size="large">
                                        آپلود نسخه
                                    </Button>
                                </Upload>
                                <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 4 }}>
                                    فرمت‌های مجاز: JPG, PNG, PDF (حداکثر ۵ مگابایت)
                                </Text>
                            </Form.Item>

                            <Divider />

                            <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                                <Button onClick={() => router.back()} size="large">
                                    انصراف
                                </Button>
                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    loading={loading}
                                    size="large"
                                    style={{ minWidth: 150 }}
                                >
                                    ارسال درخواست
                                </Button>
                            </Space>
                        </Form>

                        <div style={{ marginTop: 16, textAlign: 'center' }}>
                            <Text type="secondary" style={{ fontSize: 12 }}>
                                <InsuranceOutlined /> پس از تایید نسخه توسط داروخانه، می‌توانید پرداخت را انجام دهید
                            </Text>
                        </div>
                    </Card>
                </div>
            </main>
            <Footer />
        </>
    );
}