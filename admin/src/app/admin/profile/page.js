// src/app/admin/profile/page.js

'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
    Card,
    Form,
    Input,
    Button,
    Upload,
    Avatar,
    Space,
    Typography,
    Row,
    Col,
    Divider,
    message,
    Spin,
    Badge,
    Modal,
} from 'antd';
import {
    UserOutlined,
    UploadOutlined,
    DeleteOutlined,
    SaveOutlined,
    LockOutlined,
    ArrowLeftOutlined,
    CameraOutlined,
    LogoutOutlined,
} from '@ant-design/icons';
import { profileService } from '@/services/api';
import { useLanguage } from '@/context/LanguageContext';
import { useAuth } from '@/context/AuthContext';
import moment from 'moment-jalaali';

moment.loadPersian({ dialect: 'persian-modern' });

const { Title, Text } = Typography;

export default function ProfilePage() {
    const router = useRouter();
    const { t } = useLanguage();
    const { user, logout } = useAuth();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [profile, setProfile] = useState(null);
    const [avatarUrl, setAvatarUrl] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [activities, setActivities] = useState([]);
    const [showPasswordForm, setShowPasswordForm] = useState(false);
    const [passwordForm] = Form.useForm();
    const [passwordLoading, setPasswordLoading] = useState(false);
    const [isLogoutModalVisible, setIsLogoutModalVisible] = useState(false);

    // ===== دریافت اطلاعات پروفایل =====
    useEffect(() => {
        const fetchProfile = async () => {
            setLoading(true);
            try {
                const response = await profileService.getProfile();
                if (response.data?.success) {
                    const data = response.data.data;
                    setProfile(data);
                    setAvatarUrl(data.avatar_url);
                    form.setFieldsValue({
                        name: data.name,
                        email: data.email,
                        mobile: data.mobile,
                    });
                }
            } catch (error) {
                message.error(t('fetch_error', 'خطا در دریافت اطلاعات'));
            } finally {
                setLoading(false);
            }
        };

        const fetchActivities = async () => {
            try {
                const response = await profileService.getActivities();
                if (response.data?.success) {
                    setActivities(response.data.data || []);
                }
            } catch (error) {
                console.error('Error fetching activities:', error);
            }
        };

        fetchProfile();
        fetchActivities();
    }, []);

    // ===== به‌روزرسانی پروفایل =====
    const handleUpdate = async (values) => {
        try {
            const response = await profileService.updateProfile(values);
            if (response.data?.success) {
                message.success(t('profile_updated', 'پروفایل با موفقیت به‌روزرسانی شد'));
                setProfile(response.data.data.user);
                // بروزرسانی user در AuthContext
                if (typeof window !== 'undefined') {
                    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
                    localStorage.setItem('user', JSON.stringify({
                        ...currentUser,
                        ...response.data.data.user,
                    }));
                }
            }
        } catch (error) {
            message.error(t('update_error', 'خطا در به‌روزرسانی'));
        }
    };

    // ===== آپلود عکس =====
    const handleUpload = async ({ file }) => {
        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('avatar', file);

            const response = await profileService.uploadAvatar(formData);
            if (response.data?.success) {
                setAvatarUrl(response.data.data.avatar_url);
                message.success(t('avatar_uploaded', 'عکس با موفقیت آپلود شد'));
                // بروزرسانی user در AuthContext
                if (typeof window !== 'undefined') {
                    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
                    localStorage.setItem('user', JSON.stringify({
                        ...currentUser,
                        avatar: response.data.data.avatar_url,
                    }));
                }
            }
        } catch (error) {
            message.error(t('upload_error', 'خطا در آپلود عکس'));
        } finally {
            setUploading(false);
        }
    };

    // ===== حذف عکس =====
    const handleDeleteAvatar = async () => {
        Modal.confirm({
            title: t('delete_avatar_confirm', 'آیا از حذف عکس پروفایل اطمینان دارید؟'),
            okText: t('yes', 'بله'),
            cancelText: t('no', 'خیر'),
            onOk: async () => {
                try {
                    await profileService.deleteAvatar();
                    setAvatarUrl(null);
                    message.success(t('avatar_deleted', 'عکس با موفقیت حذف شد'));
                    if (typeof window !== 'undefined') {
                        const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
                        localStorage.setItem('user', JSON.stringify({
                            ...currentUser,
                            avatar: null,
                        }));
                    }
                } catch (error) {
                    message.error(t('delete_error', 'خطا در حذف عکس'));
                }
            },
        });
    };

    // ===== تغییر رمز عبور =====
    const handleChangePassword = async (values) => {
        setPasswordLoading(true);
        try {
            await profileService.changePassword({
                current_password: values.current_password,
                new_password: values.new_password,
                new_password_confirmation: values.new_password_confirmation,
            });
            message.success(t('password_changed', 'رمز عبور با موفقیت تغییر کرد'));
            passwordForm.resetFields();
            setShowPasswordForm(false);
        } catch (error) {
            message.error(error?.response?.data?.message || t('password_error', 'خطا در تغییر رمز عبور'));
        } finally {
            setPasswordLoading(false);
        }
    };

    // ===== خروج از سیستم =====
    const handleLogout = () => {
        setIsLogoutModalVisible(true);
    };

    const confirmLogout = async () => {
        setIsLogoutModalVisible(false);
        await logout();
        router.push('/admin/login');
    };

    // ===== فرمت تاریخ =====
    const formatDateTime = (date) => {
        if (!date) return '—';
        try {
            return moment(date).format('jYYYY/jMM/jDD HH:mm');
        } catch {
            return '—';
        }
    };

    if (loading) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', padding: 100 }}>
                <Spin size="large" />
            </div>
        );
    }

    const uploadProps = {
        showUploadList: false,
        customRequest: handleUpload,
        accept: 'image/jpeg,image/png,image/webp',
        maxCount: 1,
    };

    return (
        <div>
            {/* ===== هدر ===== */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 24 }}>
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => router.back()}
                    style={{ fontSize: 18 }}
                />
                <div>
                    <Title level={2} style={{ margin: 0 }}>
                        {t('profile', 'پروفایل من')}
                    </Title>
                    <Text type="secondary">
                        {t('profile_subtitle', 'مدیریت اطلاعات شخصی')}
                    </Text>
                </div>
            </div>

            <Row gutter={[24, 24]}>
                {/* ===== سمت راست: اطلاعات کاربر ===== */}
                <Col xs={24} lg={8}>
                    <Card
                        style={{
                            borderRadius: 12,
                            borderColor: '#e8e8f0',
                            textAlign: 'center',
                        }}
                    >
                        <Badge
                            dot
                            status="success"
                            offset={[-4, 4]}
                        >
                            <Avatar
                                size={120}
                                src={avatarUrl}
                                icon={<UserOutlined />}
                                style={{
                                    backgroundColor: !avatarUrl ? '#2563eb' : undefined,
                                    marginBottom: 16,
                                }}
                            />
                        </Badge>

                        <div style={{ marginBottom: 16 }}>
                            <Upload {...uploadProps}>
                                <Button
                                    icon={<CameraOutlined />}
                                    loading={uploading}
                                    style={{ marginRight: 8 }}
                                >
                                    {t('change_avatar', 'تغییر عکس')}
                                </Button>
                            </Upload>

                            {avatarUrl && (
                                <Button
                                    danger
                                    icon={<DeleteOutlined />}
                                    onClick={handleDeleteAvatar}
                                >
                                    {t('delete_avatar', 'حذف عکس')}
                                </Button>
                            )}
                        </div>

                        <Divider />

                        <div style={{ textAlign: 'right' }}>
                            <div style={{ marginBottom: 12 }}>
                                <Text type="secondary">{t('full_name', 'نام و نام خانوادگی')}</Text>
                                <div style={{ fontWeight: 600, fontSize: 15 }}>{profile?.name || '—'}</div>
                            </div>
                            <div style={{ marginBottom: 12 }}>
                                <Text type="secondary">{t('email', 'ایمیل')}</Text>
                                <div style={{ fontWeight: 600, fontSize: 15 }}>{profile?.email || '—'}</div>
                            </div>
                            <div style={{ marginBottom: 12 }}>
                                <Text type="secondary">{t('mobile', 'موبایل')}</Text>
                                <div style={{ fontWeight: 600, fontSize: 15 }}>{profile?.mobile || '—'}</div>
                            </div>
                            <div style={{ marginBottom: 12 }}>
                                <Text type="secondary">{t('role', 'نقش')}</Text>
                                <div style={{ fontWeight: 600, fontSize: 15 }}>
                                    {profile?.roles?.join(', ') || '—'}
                                </div>
                            </div>
                            <div style={{ marginBottom: 12 }}>
                                <Text type="secondary">{t('last_login', 'آخرین ورود')}</Text>
                                <div style={{ fontWeight: 600, fontSize: 15 }}>
                                    {formatDateTime(profile?.last_login_at)}
                                </div>
                            </div>
                            <div>
                                <Text type="secondary">{t('member_since', 'عضو از')}</Text>
                                <div style={{ fontWeight: 600, fontSize: 15 }}>
                                    {formatDateTime(profile?.created_at)}
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <Button
                            danger
                            block
                            icon={<LogoutOutlined />}
                            onClick={handleLogout}
                            style={{ height: 40, borderRadius: 8 }}
                        >
                            {t('logout', 'خروج از حساب کاربری')}
                        </Button>
                    </Card>
                </Col>

                {/* ===== سمت چپ: فرم‌ها ===== */}
                <Col xs={24} lg={16}>
                    {/* ===== اطلاعات شخصی ===== */}
                    <Card
                        style={{
                            borderRadius: 12,
                            borderColor: '#e8e8f0',
                            marginBottom: 16,
                        }}
                        title={
                            <Space>
                                <UserOutlined />
                                <span>{t('personal_info', 'اطلاعات شخصی')}</span>
                            </Space>
                        }
                    >
                        <Form
                            form={form}
                            layout="vertical"
                            onFinish={handleUpdate}
                            size="large"
                        >
                            <Row gutter={[16, 0]}>
                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="name"
                                        label={t('full_name', 'نام و نام خانوادگی')}
                                        rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                                    >
                                        <Input prefix={<UserOutlined />} placeholder="نام کامل..." />
                                    </Form.Item>
                                </Col>

                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="email"
                                        label={t('email', 'ایمیل')}
                                        rules={[
                                            { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                                            { type: 'email', message: t('email_invalid', 'ایمیل نامعتبر است') },
                                        ]}
                                    >
                                        <Input placeholder="admin@clinic.com" />
                                    </Form.Item>
                                </Col>

                                <Col xs={24} md={12}>
                                    <Form.Item
                                        name="mobile"
                                        label={t('mobile', 'شماره موبایل')}
                                        rules={[
                                            { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                                            { pattern: /^09[0-9]{9}$/, message: t('mobile_invalid', 'شماره موبایل نامعتبر است') },
                                        ]}
                                    >
                                        <Input placeholder="۰۹۱۲۳۴۵۶۷۸۹" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Form.Item>
                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    icon={<SaveOutlined />}
                                    style={{
                                        height: 44,
                                        background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                                        border: 'none',
                                        borderRadius: 8,
                                        fontWeight: 600,
                                    }}
                                >
                                    {t('save_changes', 'ذخیره تغییرات')}
                                </Button>
                            </Form.Item>
                        </Form>
                    </Card>

                    {/* ===== تغییر رمز عبور ===== */}
                    <Card
                        style={{
                            borderRadius: 12,
                            borderColor: '#e8e8f0',
                            marginBottom: 16,
                        }}
                        title={
                            <Space>
                                <LockOutlined />
                                <span>{t('change_password', 'تغییر رمز عبور')}</span>
                            </Space>
                        }
                    >
                        {!showPasswordForm ? (
                            <Button
                                type="primary"
                                ghost
                                onClick={() => setShowPasswordForm(true)}
                                style={{ borderRadius: 8 }}
                            >
                                {t('change_password', 'تغییر رمز عبور')}
                            </Button>
                        ) : (
                            <Form
                                form={passwordForm}
                                layout="vertical"
                                onFinish={handleChangePassword}
                                size="large"
                            >
                                <Form.Item
                                    name="current_password"
                                    label={t('current_password', 'رمز عبور فعلی')}
                                    rules={[{ required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') }]}
                                >
                                    <Input.Password placeholder="●●●●●●●●" />
                                </Form.Item>

                                <Form.Item
                                    name="new_password"
                                    label={t('new_password', 'رمز عبور جدید')}
                                    rules={[
                                        { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                                        { min: 8, message: t('password_min', 'رمز عبور باید حداقل ۸ کاراکتر باشد') },
                                        {
                                            pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/,
                                            message: t('password_complexity', 'رمز عبور باید شامل حروف بزرگ، کوچک و عدد باشد'),
                                        },
                                    ]}
                                    extra={t('password_extra', 'رمز عبور باید حداقل ۸ کاراکتر شامل حروف بزرگ، کوچک و عدد باشد')}
                                >
                                    <Input.Password placeholder="●●●●●●●●" />
                                </Form.Item>

                                <Form.Item
                                    name="new_password_confirmation"
                                    label={t('confirm_password', 'تکرار رمز عبور جدید')}
                                    rules={[
                                        { required: true, message: t('required', 'لطفاً این فیلد را وارد کنید') },
                                        ({ getFieldValue }) => ({
                                            validator(_, value) {
                                                if (!value || getFieldValue('new_password') === value) {
                                                    return Promise.resolve();
                                                }
                                                return Promise.reject(new Error(t('passwords_not_match', 'رمز عبور و تکرار آن مطابقت ندارند')));
                                            },
                                        }),
                                    ]}
                                >
                                    <Input.Password placeholder="●●●●●●●●" />
                                </Form.Item>

                                <Space>
                                    <Button
                                        type="primary"
                                        htmlType="submit"
                                        loading={passwordLoading}
                                        style={{
                                            height: 40,
                                            background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                                            border: 'none',
                                            borderRadius: 8,
                                            fontWeight: 600,
                                        }}
                                    >
                                        {t('save', 'ذخیره')}
                                    </Button>
                                    <Button
                                        onClick={() => {
                                            setShowPasswordForm(false);
                                            passwordForm.resetFields();
                                        }}
                                        style={{ borderRadius: 8 }}
                                    >
                                        {t('cancel', 'انصراف')}
                                    </Button>
                                </Space>
                            </Form>
                        )}
                    </Card>

                    {/* ===== فعالیت‌های اخیر ===== */}
                    <Card
                        style={{
                            borderRadius: 12,
                            borderColor: '#e8e8f0',
                        }}
                        title={t('recent_activities', 'فعالیت‌های اخیر')}
                    >
                        {activities.length === 0 ? (
                            <Text type="secondary" style={{ display: 'block', textAlign: 'center', padding: 20 }}>
                                {t('no_activities', 'هیچ فعالیتی ثبت نشده است')}
                            </Text>
                        ) : (
                            <div>
                                {activities.map((activity) => (
                                    <div
                                        key={activity.id}
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            padding: '12px 0',
                                            borderBottom: '1px solid #f0f0f0',
                                        }}
                                    >
                                        <div>
                                            <div style={{ fontWeight: 500 }}>{activity.description}</div>
                                            <Text type="secondary" style={{ fontSize: 12 }}>
                                                {activity.ip && `IP: ${activity.ip}`}
                                            </Text>
                                        </div>
                                        <Text type="secondary" style={{ fontSize: 12, whiteSpace: 'nowrap' }}>
                                            {formatDateTime(activity.created_at)}
                                        </Text>
                                    </div>
                                ))}
                            </div>
                        )}
                    </Card>
                </Col>
            </Row>

            {/* ===== مودال خروج ===== */}
            <Modal
                title={t('logout_confirm', 'خروج از حساب کاربری')}
                open={isLogoutModalVisible}
                onCancel={() => setIsLogoutModalVisible(false)}
                okText={t('yes', 'بله، خروج')}
                cancelText={t('no', 'خیر')}
                onOk={confirmLogout}
                okButtonProps={{
                    danger: true,
                    style: { borderRadius: 8 },
                }}
                cancelButtonProps={{ style: { borderRadius: 8 } }}
            >
                <p>{t('logout_message', 'آیا از خروج از حساب کاربری خود اطمینان دارید؟')}</p>
            </Modal>
        </div>
    );
}