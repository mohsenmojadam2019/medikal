'use client';

import { useState, useEffect, useCallback } from 'react';
import {
    Card, Row, Col, Typography, Spin, Empty, Tag,
    Button, Input, Space, App, Badge, Select, Pagination,
    Tooltip, Drawer, Divider
} from 'antd';
import {
    SearchOutlined, MedicineBoxOutlined,
    ShoppingCartOutlined, PlusOutlined, MinusOutlined, DeleteOutlined
} from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import Header from '@/components/front/Header/Header';
import Footer from '@/components/front/Footer/Footer';
import LoadingSpinner from '@/components/shared/LoadingSpinner';

const { Title, Text } = Typography;
const { Option } = Select;

export default function ProductsPage() {
    const router = useRouter();
    const { message: appMessage } = App.useApp();

    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [pageSize, setPageSize] = useState(12);
    const [totalItems, setTotalItems] = useState(0);
    const [pharmacyId, setPharmacyId] = useState('all');

    // سبد خرید
    const [cart, setCart] = useState([]);
    const [cartVisible, setCartVisible] = useState(false);

    const [searchTerm, setSearchTerm] = useState('');
    const [selectedPharmacy, setSelectedPharmacy] = useState('all');
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [requiresPrescription, setRequiresPrescription] = useState('all');
    const [pharmacies, setPharmacies] = useState([]);
    const [categories, setCategories] = useState([]);

    const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8210';
    const getToken = () => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('token');
        }
        return null;
    };

    // دریافت pharmacy_id از URL
    useEffect(() => {
        if (typeof window !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('pharmacy_id');
            if (id) {
                setPharmacyId(id);
                setSelectedPharmacy(id);
            }
        }
    }, []);

    // بارگذاری سبد خرید از localStorage
    useEffect(() => {
        const savedCart = JSON.parse(localStorage.getItem('pharmacyCart') || '[]');
        setCart(savedCart);
    }, []);

    // ذخیره سبد خرید در localStorage
    useEffect(() => {
        localStorage.setItem('pharmacyCart', JSON.stringify(cart));
    }, [cart]);

    const fetchProducts = useCallback(async () => {
        setLoading(true);
        try {
            const queryParams = new URLSearchParams();
            if (searchTerm) queryParams.append('search', searchTerm);
            if (selectedPharmacy !== 'all') queryParams.append('pharmacy_id', selectedPharmacy);
            if (selectedCategory !== 'all') queryParams.append('category', selectedCategory);
            if (requiresPrescription !== 'all') {
                queryParams.append('requires_prescription', requiresPrescription === 'required' ? '1' : '0');
            }
            queryParams.append('page', currentPage);
            queryParams.append('per_page', pageSize);

            const res = await fetch(`${API_URL}/api/products?${queryParams}`);
            const data = await res.json();
            if (data.success) {
                const productsData = data.data.data || data.data || [];
                setProducts(productsData);
                setTotalItems(data.data.total || productsData.length || 0);
                const uniqueCategories = [...new Set(productsData.map(d => d.category).filter(Boolean))];
                setCategories(uniqueCategories);
            }
        } catch (error) {
            console.error('Error fetching products:', error);
        } finally {
            setLoading(false);
        }
    }, [searchTerm, selectedPharmacy, selectedCategory, requiresPrescription, currentPage, pageSize]);

    const fetchPharmacies = useCallback(async () => {
        try {
            const res = await fetch(`${API_URL}/api/pharmacy/pharmacies?per_page=100`);
            const data = await res.json();
            if (data.success) {
                const list = data.data.data || data.data || [];
                setPharmacies(list);
            }
        } catch (error) {
            console.error('Error fetching pharmacies:', error);
        }
    }, [API_URL]);

    useEffect(() => {
        fetchProducts();
        fetchPharmacies();
    }, [fetchProducts, fetchPharmacies]);

    const handleSearch = () => {
        setCurrentPage(1);
        fetchProducts();
    };

    const resetFilters = () => {
        setSearchTerm('');
        setSelectedCategory('all');
        setRequiresPrescription('all');
        setSelectedPharmacy('all');
        setCurrentPage(1);
        fetchProducts();
    };

    // ============================================
    // توابع سبد خرید
    // ============================================
    const addToCart = (product) => {
        const token = getToken();
        if (!token) {
            appMessage.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
            router.push('/login?redirect=/fa/pharmacy/products');
            return;
        }

        const existing = cart.find(item => item.id === product.id);
        let newCart;
        if (existing) {
            newCart = cart.map(item =>
                item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item
            );
        } else {
            newCart = [...cart, {
                ...product,
                quantity: 1,
                price: product.price
            }];
        }
        setCart(newCart);
        appMessage.success(`${product.name} به سبد خرید اضافه شد`);
    };

    const removeFromCart = (productId) => {
        const newCart = cart.filter(item => item.id !== productId);
        setCart(newCart);
    };

    const updateQuantity = (productId, change) => {
        const newCart = cart.map(item =>
            item.id === productId
                ? { ...item, quantity: Math.max(1, item.quantity + change) }
                : item
        );
        setCart(newCart);
    };

    const getTotalPrice = () => {
        return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    };

    const getTotalItems = () => {
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    };

    const goToCheckout = () => {
        if (cart.length === 0) {
            appMessage.warning('سبد خرید شما خالی است');
            return;
        }
        const checkoutData = {
            items: cart.map(item => ({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: item.quantity,
            })),
            total: getTotalPrice(),
        };
        localStorage.setItem('pharmacyCheckoutData', JSON.stringify(checkoutData));
        setCartVisible(false);
        router.push('/pharmacy/checkout');
    };

    // ============================================
    // رفتن به صفحه درخواست نسخه
    // ============================================
    const goToPrescriptionRequest = (product) => {
        const token = getToken();
        if (!token) {
            appMessage.warning('لطفاً ابتدا وارد حساب کاربری خود شوید');
            router.push('/login?redirect=/fa/pharmacy/prescription-request');
            return;
        }
        router.push(`/pharmacy/prescription-request?product_id=${product.id}&pharmacy_id=${selectedPharmacy}`);
    };

    if (loading && products.length === 0) {
        return <><Header /><LoadingSpinner /><Footer /></>;
    }

    const pharmacyName = selectedPharmacy !== 'all'
        ? pharmacies.find(p => String(p.id) === String(selectedPharmacy))?.name
        : null;

    return (
        <>
            <Header />
            <main style={{ minHeight: 'calc(100vh - 200px)', background: '#f8fafc', padding: '24px 20px' }}>
                <div style={{ maxWidth: '1200px', margin: '0 auto' }}>

                    {/* مسیر راهنما */}
                    <div style={{ marginBottom: 8 }}>
                        <Text type="secondary">
                            <a href="/pharmacy" style={{ color: '#2563eb' }}>داروخانه</a>
                            {selectedPharmacy !== 'all' && <span> / {pharmacyName || 'داروخانه مورد نظر'}</span>}
                            <span> / داروها</span>
                        </Text>
                    </div>

                    {/* عنوان + دکمه سبد خرید */}
                    <div style={{ marginBottom: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div>
                            <Title level={2} style={{ marginBottom: 4 }}>💊 داروها</Title>
                            <Text type="secondary">
                                {selectedPharmacy !== 'all'
                                    ? `داروهای موجود در ${pharmacyName || 'داروخانه مورد نظر'}`
                                    : 'لیست داروهای موجود در داروخانه‌ها'}
                            </Text>
                        </div>
                        <Badge count={getTotalItems()} offset={[10, 0]} size="large">
                            <Button
                                type="primary"
                                icon={<ShoppingCartOutlined />}
                                onClick={() => setCartVisible(true)}
                                size="large"
                                style={{ borderRadius: '12px', height: '48px' }}
                            >
                                سبد خرید ({getTotalItems()})
                            </Button>
                        </Badge>
                    </div>

                    {/* فیلترها */}
                    <Card style={{ borderRadius: 16, marginBottom: 24 }}>
                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={6}>
                                <Input
                                    placeholder="جستجوی دارو..."
                                    prefix={<SearchOutlined />}
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onPressEnter={handleSearch}
                                    allowClear
                                />
                            </Col>
                            <Col xs={12} md={5}>
                                <Select
                                    placeholder="انتخاب داروخانه"
                                    style={{ width: '100%' }}
                                    value={selectedPharmacy}
                                    onChange={setSelectedPharmacy}
                                    allowClear
                                >
                                    <Option value="all">همه داروخانه‌ها</Option>
                                    {pharmacies.map(p => (
                                        <Option key={p.id} value={p.id}>{p.name}</Option>
                                    ))}
                                </Select>
                            </Col>
                            <Col xs={12} md={5}>
                                <Select
                                    placeholder="انتخاب دسته‌بندی"
                                    style={{ width: '100%' }}
                                    value={selectedCategory}
                                    onChange={setSelectedCategory}
                                    allowClear
                                >
                                    <Option value="all">همه دسته‌بندی‌ها</Option>
                                    {categories.map(cat => (
                                        <Option key={cat} value={cat}>{cat}</Option>
                                    ))}
                                </Select>
                            </Col>
                            <Col xs={12} md={5}>
                                <Select
                                    placeholder="نیاز به نسخه"
                                    style={{ width: '100%' }}
                                    value={requiresPrescription}
                                    onChange={setRequiresPrescription}
                                >
                                    <Option value="all">همه</Option>
                                    <Option value="required">نیاز به نسخه</Option>
                                    <Option value="not_required">بدون نسخه</Option>
                                </Select>
                            </Col>
                            <Col xs={12} md={3}>
                                <Button type="primary" block onClick={handleSearch}>
                                    جستجو
                                </Button>
                            </Col>
                        </Row>
                    </Card>

                    <div style={{ marginBottom: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Text type="secondary">
                            {totalItems} دارو یافت شد
                        </Text>
                        <Button onClick={resetFilters} size="small">
                            بازنشانی فیلترها
                        </Button>
                    </div>

                    {/* لیست داروها */}
                    {products.length > 0 ? (
                        <>
                            <Row gutter={[16, 16]}>
                                {products.map((product) => (
                                    <Col xs={24} sm={12} md={8} lg={6} key={product.id}>
                                        <Card
                                            hoverable
                                            style={{ borderRadius: 12, height: '100%' }}
                                            cover={
                                                <div style={{
                                                    height: 140,
                                                    background: 'linear-gradient(135deg, #f0f5ff, #e0e7ff)',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    position: 'relative',
                                                    borderRadius: '12px 12px 0 0'
                                                }}>
                                                    <MedicineBoxOutlined style={{ fontSize: 56, color: '#2563eb' }} />
                                                    {product.requires_prescription && (
                                                        <Tag color="red" style={{ position: 'absolute', top: 8, left: 8, fontSize: 10 }}>
                                                            📋 نیاز به نسخه
                                                        </Tag>
                                                    )}
                                                    {product.stock === 0 && (
                                                        <Tag color="red" style={{ position: 'absolute', top: 8, right: 8, fontSize: 10 }}>
                                                            ناموجود
                                                        </Tag>
                                                    )}
                                                    {product.pharmacy && (
                                                        <Tag color="blue" style={{ position: 'absolute', bottom: 8, right: 8, fontSize: 10 }}>
                                                            {product.pharmacy.name}
                                                        </Tag>
                                                    )}
                                                </div>
                                            }
                                        >
                                            <Tooltip title={product.name}>
                                                <Text strong style={{ fontSize: 14 }}>
                                                    {product.name.length > 20 ? product.name.substring(0, 20) + '...' : product.name}
                                                </Text>
                                            </Tooltip>
                                            {product.generic_name && (
                                                <Text type="secondary" style={{ fontSize: 12, display: 'block' }}>
                                                    {product.generic_name}
                                                </Text>
                                            )}
                                            <div>
                                                <Tag color="blue" style={{ fontSize: 10 }}>
                                                    {product.category || 'عمومی'}
                                                </Tag>
                                                {product.requires_prescription && (
                                                    <Tag color="orange" style={{ fontSize: 10 }}>نیاز به نسخه</Tag>
                                                )}
                                            </div>
                                            <div>
                                                <Text strong style={{ color: '#2563eb', fontSize: 16 }}>
                                                    {product.price?.toLocaleString() || 0}
                                                </Text>
                                                <Text type="secondary" style={{ fontSize: 12 }}> تومان</Text>
                                            </div>
                                            <Text type="secondary" style={{ fontSize: 12 }}>
                                                موجودی: {product.stock > 0 ? `${product.stock} عدد` : 'ناموجود'}
                                            </Text>

                                            {/* ✅ دکمه‌های جداگانه برای داروهای با/بدون نسخه */}
                                            <div style={{ marginTop: 8 }}>
                                                {product.requires_prescription ? (
                                                    <Button
                                                        type="primary"
                                                        size="small"
                                                        block
                                                        icon={<MedicineBoxOutlined />}
                                                        disabled={product.stock === 0}
                                                        onClick={() => goToPrescriptionRequest(product)}
                                                        style={{ background: '#f59e0b', borderColor: '#f59e0b' }}
                                                    >
                                                        📋 درخواست با نسخه
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        type="primary"
                                                        size="small"
                                                        block
                                                        icon={<ShoppingCartOutlined />}
                                                        disabled={product.stock === 0}
                                                        onClick={() => addToCart(product)}
                                                    >
                                                        {product.stock > 0 ? 'افزودن به سبد' : 'ناموجود'}
                                                    </Button>
                                                )}
                                            </div>
                                        </Card>
                                    </Col>
                                ))}
                            </Row>
                            <div style={{ marginTop: 32, textAlign: 'center' }}>
                                <Pagination
                                    current={currentPage}
                                    total={totalItems}
                                    pageSize={pageSize}
                                    onChange={(page) => setCurrentPage(page)}
                                    showSizeChanger
                                    onShowSizeChange={(_, size) => {
                                        setPageSize(size);
                                        setCurrentPage(1);
                                    }}
                                    locale={{ items_per_page: 'در هر صفحه' }}
                                />
                            </div>
                        </>
                    ) : (
                        <Empty
                            description="هیچ دارویی یافت نشد"
                            image={Empty.PRESENTED_IMAGE_SIMPLE}
                        >
                            <Button type="primary" onClick={resetFilters}>
                                بازنشانی فیلترها
                            </Button>
                        </Empty>
                    )}
                </div>
            </main>

            {/* ✅ دراور سبد خرید */}
            <Drawer
                title="🛒 سبد خرید"
                placement="left"
                onClose={() => setCartVisible(false)}
                open={cartVisible}
                width={400}
                styles={{ body: { padding: '16px' } }}
            >
                {cart.length > 0 ? (
                    <>
                        {cart.map((item) => (
                            <Card key={item.id} size="small" style={{ marginBottom: '8px', borderRadius: '8px' }}>
                                <Row align="middle" gutter={[8, 8]}>
                                    <Col flex="auto">
                                        <Text strong>{item.name}</Text>
                                        <br />
                                        <Text type="secondary" style={{ fontSize: '12px' }}>
                                            {item.price.toLocaleString()} تومان
                                        </Text>
                                        {item.requires_prescription && (
                                            <Tag color="orange" style={{ fontSize: '10px', marginLeft: '8px' }}>
                                                نیاز به نسخه
                                            </Tag>
                                        )}
                                    </Col>
                                    <Col>
                                        <Space>
                                            <Button
                                                icon={<MinusOutlined />}
                                                size="small"
                                                onClick={() => updateQuantity(item.id, -1)}
                                            />
                                            <Text strong>{item.quantity}</Text>
                                            <Button
                                                icon={<PlusOutlined />}
                                                size="small"
                                                onClick={() => updateQuantity(item.id, 1)}
                                            />
                                            <Button
                                                icon={<DeleteOutlined />}
                                                size="small"
                                                danger
                                                onClick={() => removeFromCart(item.id)}
                                            />
                                        </Space>
                                    </Col>
                                </Row>
                            </Card>
                        ))}
                        <Divider />
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <Text strong style={{ fontSize: '18px' }}>
                                جمع کل: {getTotalPrice().toLocaleString()} تومان
                            </Text>
                            <Button
                                type="primary"
                                size="large"
                                onClick={goToCheckout}
                                style={{ borderRadius: '8px' }}
                            >
                                تسویه حساب
                            </Button>
                        </div>
                    </>
                ) : (
                    <Empty description="سبد خرید شما خالی است" />
                )}
            </Drawer>

            <Footer />
        </>
    );
}