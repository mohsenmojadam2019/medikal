'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
    Table,
    Button,
    Input,
    Space,
    Card,
    Typography,
    Tag,
    Modal,
    Popconfirm,
    Tooltip,
    Row,
    Col,
    Badge,
    Avatar,
    Switch,
    message,
    Select,
    Empty,
    Spin,
} from 'antd';
import {
    PlusOutlined,
    SearchOutlined,
    EditOutlined,
    DeleteOutlined,
    EyeOutlined,
    UserOutlined,
    ReloadOutlined,
    CheckCircleOutlined,
} from '@ant-design/icons';
import { doctorsService } from '@/services/api';
import { specialtiesService } from '@/services/api/admin/specialties';

const { Title, Text } = Typography;

export default function DoctorsPage() {
    const router = useRouter();
    const [loading, setLoading] = useState(false);
    const [loadingSpecialties, setLoadingSpecialties] = useState(false);
    const [doctors, setDoctors] = useState([]);
    const [specialties, setSpecialties] = useState([]);
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 10,
        total: 0,
    });
    const [searchText, setSearchText] = useState('');
    const [filters, setFilters] = useState({});
    const [selectedDoctor, setSelectedDoctor] = useState(null);
    const [isModalVisible, setIsModalVisible] = useState(false);

    // ===== دریافت تخصص‌ها =====
    const fetchSpecialties = async () => {
        setLoadingSpecialties(true);
        try {
            const response = await specialtiesService.getAll({ per_page: 100 });
            if (response.data?.success) {
                const data = response.data.data;
                if (data?.data && Array.isArray(data.data)) {
                    setSpecialties(data.data);
                } else if (Array.isArray(data)) {
                    setSpecialties(data);
                } else {
                    setSpecialties([]);
                }
            } else {
                setSpecialties([]);
            }
        } catch (error) {
            console.error('Error fetching specialties:', error);
            setSpecialties([]);
        } finally {
            setLoadingSpecialties(false);
        }
    };

    // ===== دریافت لیست پزشکان =====
    const fetchDoctors = async (params = {}) => {
        setLoading(true);
        try {
            // ✅ اصلاح: فقط در صورتی که searchText پر باشه، به پارامترها اضافه کن
            const requestParams = {
                page: pagination.current,
                per_page: pagination.pageSize,
                ...filters,
                ...params,
            };

            if (searchText.trim()) {
                requestParams.search = searchText.trim();
            }

            const response = await doctorsService.getAll(requestParams);

            if (response.data?.success) {
                const data = response.data.data;
                if (data?.data && Array.isArray(data.data)) {
                    setDoctors(data.data);
                    setPagination({
                        current: data.current_page || 1,
                        pageSize: data.per_page || 10,
                        total: data.total || 0,
                    });
                } else if (Array.isArray(data)) {
                    setDoctors(data);
                    setPagination({
                        ...pagination,
                        total: data.length,
                    });
                } else {
                    setDoctors([]);
                    setPagination({
                        ...pagination,
                        total: 0,
                    });
                }
            } else {
                setDoctors([]);
                setPagination({
                    ...pagination,
                    total: 0,
                });
                if (response.data?.message) {
                    message.warning(response.data.message);
                }
            }
        } catch (error) {
            console.error('❌ Error fetching doctors:', error);
            message.error('خطا در دریافت اطلاعات پزشکان');
            setDoctors([]);
            setPagination({
                ...pagination,
                total: 0,
            });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchDoctors();
        fetchSpecialties();
    }, [pagination.current, pagination.pageSize]);

    // ===== جستجو =====
    const handleSearch = () => {
        fetchDoctors({ page: 1 });
    };

    // ===== ریست فیلترها =====
    const handleReset = () => {
        setSearchText('');
        setFilters({});
        fetchDoctors({ page: 1 });
    };

    const handleToggleStatus = async (id) => {
        try {
            await doctorsService.toggleAvailability(id);
            message.success('وضعیت با موفقیت تغییر کرد');
            fetchDoctors();
        } catch (error) {
            message.error('خطا در تغییر وضعیت');
        }
    };

    const handleVerify = async (id) => {
        try {
            await doctorsService.verify(id);
            message.success('پزشک با موفقیت تایید شد');
            fetchDoctors();
        } catch (error) {
            message.error('خطا در تایید پزشک');
        }
    };

    const handleDelete = async (id) => {
        try {
            await doctorsService.delete(id);
            message.success('پزشک با موفقیت حذف شد');
            fetchDoctors();
        } catch (error) {
            message.error('خطا در حذف پزشک');
        }
    };

    const handleView = (record) => {
        setSelectedDoctor(record);
        setIsModalVisible(true);
    };

    const columns = [
        { title: 'شناسه', dataIndex: 'id', key: 'id', width: 70 },
        {
            title: 'پزشک',
            dataIndex: 'user',
            key: 'full_name',
            render: (user) => (
                <Space>
                    <Avatar icon={<UserOutlined />} />
                    <div>
                        <div style={{ fontWeight: 600 }}>{user?.name || 'بدون نام'}</div>
                        <div style={{ fontSize: 12, color: '#64748b' }}>
                            {user?.email || '—'}
                        </div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'تخصص',
            dataIndex: 'specialty',
            key: 'specialty',
            render: (specialty) => specialty?.name || '—'
        },
        { title: 'شماره نظام', dataIndex: 'license_number', key: 'license_number' },
        {
            title: 'موبایل',
            dataIndex: 'user',
            key: 'mobile',
            render: (user) => user?.mobile || '—'
        },
        {
            title: 'هزینه ویزیت',
            dataIndex: 'consultation_fee',
            key: 'consultation_fee',
            render: (fee) => fee ? `${Number(fee).toLocaleString()} تومان` : '—'
        },
        {
            title: 'وضعیت',
            dataIndex: 'is_available',
            key: 'is_available',
            render: (val, record) => (
                <Space>
                    <Badge status={val ? 'success' : 'error'} text={val ? 'فعال' : 'غیرفعال'} />
                    <Switch checked={val} onChange={() => handleToggleStatus(record.id)} size="small" />
                </Space>
            ),
        },
        {
            title: 'تایید',
            dataIndex: 'is_verified',
            key: 'is_verified',
            render: (val, record) => (
                <Space>
                    <Tag color={val ? 'success' : 'warning'}>
                        {val ? 'تایید شده' : 'در انتظار'}
                    </Tag>
                    {!val && (
                        <Button
                            icon={<CheckCircleOutlined />}
                            onClick={() => handleVerify(record.id)}
                            size="small"
                            type="primary"
                            ghost
                        />
                    )}
                </Space>
            ),
        },
        {
            title: 'عملیات',
            key: 'actions',
            width: 200,
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="مشاهده">
                        <Button icon={<EyeOutlined />} onClick={() => handleView(record)} size="small" />
                    </Tooltip>
                    <Tooltip title="ویرایش">
                        <Button icon={<EditOutlined />} onClick={() => router.push(`/admin/doctors/${record.id}/edit`)} size="small" />
                    </Tooltip>
                    <Popconfirm title="آیا از حذف این پزشک اطمینان دارید؟" onConfirm={() => handleDelete(record.id)} okText="بله" cancelText="خیر">
                        <Tooltip title="حذف">
                            <Button icon={<DeleteOutlined />} size="small" danger />
                        </Tooltip>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div style={{ padding: 24 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 24 }}>
                <div>
                    <Title level={2} style={{ margin: 0 }}>مدیریت پزشکان</Title>
                    <Text type="secondary">لیست و مدیریت پزشکان کلینیک</Text>
                </div>
                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={() => router.push('/admin/doctors/create')}
                    style={{ height: 40 }}
                >
                    پزشک جدید
                </Button>
            </div>

            <Card>
                <Row gutter={16} style={{ marginBottom: 16 }}>
                    <Col xs={24} sm={12} md={8}>
                        <Input
                            placeholder="جستجوی پزشک..."
                            prefix={<SearchOutlined />}
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onPressEnter={handleSearch}
                            allowClear
                        />
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            placeholder="فیلتر تخصص"
                            style={{ width: '100%' }}
                            allowClear
                            onChange={(val) => setFilters({ ...filters, specialty_id: val })}
                            loading={loadingSpecialties}
                        >
                            {specialties.map((item) => (
                                <Select.Option key={item.id} value={item.id}>
                                    {item.name}
                                </Select.Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            placeholder="فیلتر وضعیت"
                            style={{ width: '100%' }}
                            allowClear
                            onChange={(val) => setFilters({ ...filters, is_available: val })}
                        >
                            <Select.Option value={true}>فعال</Select.Option>
                            <Select.Option value={false}>غیرفعال</Select.Option>
                        </Select>
                    </Col>
                    <Col xs={24} sm={24} md={4}>
                        <Space>
                            <Button type="primary" onClick={handleSearch} icon={<SearchOutlined />}>
                                جستجو
                            </Button>
                            <Button onClick={handleReset} icon={<ReloadOutlined />}>
                                ریست
                            </Button>
                        </Space>
                    </Col>
                </Row>

                <Table
                    columns={columns}
                    dataSource={doctors}
                    loading={loading}
                    rowKey="id"
                    pagination={{
                        ...pagination,
                        showSizeChanger: true,
                        showTotal: (total) => `مجموع ${total} نفر`,
                        onChange: (page, pageSize) => setPagination({ ...pagination, current: page, pageSize }),
                    }}
                    scroll={{ x: 1000 }}
                    locale={{
                        emptyText: <Empty description="هیچ پزشکی یافت نشد" />
                    }}
                />
            </Card>

            <Modal
                title="جزئیات پزشک"
                open={isModalVisible}
                onCancel={() => setIsModalVisible(false)}
                footer={[
                    <Button key="close" onClick={() => setIsModalVisible(false)}>بستن</Button>,
                    <Button key="edit" type="primary" onClick={() => { setIsModalVisible(false); router.push(`/admin/doctors/${selectedDoctor?.id}/edit`); }}>
                        ویرایش
                    </Button>,
                ]}
                width={600}
            >
                {selectedDoctor && (
                    <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 16 }}>
                            <Avatar size={64} icon={<UserOutlined />} />
                            <div>
                                <div style={{ fontSize: 18, fontWeight: 700 }}>{selectedDoctor.user?.name || 'بدون نام'}</div>
                                <div style={{ color: '#64748b' }}>{selectedDoctor.specialty?.name || 'بدون تخصص'}</div>
                            </div>
                        </div>
                        <Row gutter={[16, 16]}>
                            <Col span={12}>
                                <Text type="secondary">شماره نظام پزشکی</Text>
                                <div style={{ fontWeight: 500 }}>{selectedDoctor.license_number || '—'}</div>
                            </Col>
                            <Col span={12}>
                                <Text type="secondary">شماره موبایل</Text>
                                <div style={{ fontWeight: 500 }}>{selectedDoctor.user?.mobile || '—'}</div>
                            </Col>
                            <Col span={12}>
                                <Text type="secondary">ایمیل</Text>
                                <div style={{ fontWeight: 500 }}>{selectedDoctor.user?.email || '—'}</div>
                            </Col>
                            <Col span={12}>
                                <Text type="secondary">وضعیت</Text>
                                <div style={{ fontWeight: 500 }}>{selectedDoctor.is_available ? 'فعال' : 'غیرفعال'}</div>
                            </Col>
                            <Col span={24}>
                                <Text type="secondary">بیوگرافی</Text>
                                <div style={{ fontWeight: 500 }}>{selectedDoctor.bio || '—'}</div>
                            </Col>
                        </Row>
                    </div>
                )}
            </Modal>
        </div>
    );
}
