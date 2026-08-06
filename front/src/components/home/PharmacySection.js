'use client';

import {
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import Link from 'next/link';
import {
  LeftOutlined,
  MedicineBoxOutlined,
  RightOutlined,
  ShopOutlined,
} from '@ant-design/icons';

import styles from './DoctorWebHome.module.css';

const FALLBACK_PRODUCTS = [
  {
    id: 'medicine-1',
    name: 'استامینوفن',
    category: 'medicine',
    price: 45000,
  },
  {
    id: 'medicine-2',
    name: 'شربت سرماخوردگی',
    category: 'medicine',
    price: 78000,
  },
  {
    id: 'medicine-3',
    name: 'قرص ویتامین C',
    category: 'medicine',
    price: 95000,
  },
  {
    id: 'beauty-1',
    name: 'کرم ضد آفتاب',
    category: 'beauty',
    price: 320000,
  },
  {
    id: 'beauty-2',
    name: 'ژل شستشوی صورت',
    category: 'beauty',
    price: 285000,
  },
  {
    id: 'beauty-3',
    name: 'کرم مرطوب‌کننده',
    category: 'beauty',
    price: 245000,
  },
  {
    id: 'equipment-1',
    name: 'فشارسنج دیجیتال',
    category: 'equipment',
    price: 1850000,
  },
  {
    id: 'equipment-2',
    name: 'تب‌سنج دیجیتال',
    category: 'equipment',
    price: 430000,
  },
  {
    id: 'equipment-3',
    name: 'دستگاه تست قند خون',
    category: 'equipment',
    price: 1250000,
  },
];

const FALLBACK_PHARMACIES = [
  {
    id: 1,
    name: 'داروخانه دکتر وب',
    city: 'تهران',
  },
  {
    id: 2,
    name: 'داروخانه مرکزی',
    city: 'تهران',
  },
  {
    id: 3,
    name: 'داروخانه شبانه‌روزی سلامت',
    city: 'تهران',
  },
  {
    id: 4,
    name: 'داروخانه آنلاین ایرانیان',
    city: 'کرج',
  },
];

function detectCategory(product) {
  const text = [
    product.name,
    product.generic_name,
    product.category_name,
    product.category?.name,
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();

  if (
    /آرایشی|بهداشتی|پوست|مو|کرم|beauty|cosmetic/.test(text)
  ) {
    return 'beauty';
  }

  if (
    /تجهیزات|دستگاه|فشارسنج|تب سنج|medical equipment|device/.test(
      text,
    )
  ) {
    return 'equipment';
  }

  return 'medicine';
}

function normalizeProduct(product) {
  return {
    id: product.id,
    name:
      product.generic_name ||
      product.name ||
      'محصول داروخانه',
    category: detectCategory(product),
    price: Number(
      product.amount ||
        product.price ||
        product.sale_price ||
        0,
    ),
    image:
      product.image_url ||
      product.image ||
      product.thumbnail ||
      product.media?.[0]?.original_url ||
      null,
  };
}

export default function PharmacySection() {
  const scrollerRef = useRef(null);

  const [activeTab, setActiveTab] = useState('medicine');
  const [products, setProducts] = useState(FALLBACK_PRODUCTS);
  const [pharmacies, setPharmacies] = useState(FALLBACK_PHARMACIES);

  useEffect(() => {
    Promise.allSettled([
      fetch('/backend-api/api/products?per_page=30').then((response) =>
        response.json(),
      ),
      fetch(
        '/backend-api/api/pharmacy/pharmacies?per_page=8',
      ).then((response) => response.json()),
    ]).then(([productsResult, pharmaciesResult]) => {
      if (productsResult.status === 'fulfilled') {
        const source =
          productsResult.value?.data?.data ||
          productsResult.value?.data ||
          productsResult.value;

        if (Array.isArray(source) && source.length) {
          setProducts(source.map(normalizeProduct));
        }
      }

      if (pharmaciesResult.status === 'fulfilled') {
        const source =
          pharmaciesResult.value?.data?.data ||
          pharmaciesResult.value?.data ||
          pharmaciesResult.value;

        if (Array.isArray(source) && source.length) {
          setPharmacies(
            source.map((pharmacy) => ({
              id: pharmacy.id,
              name: pharmacy.name || 'داروخانه',
              city:
                pharmacy.city?.name ||
                pharmacy.city_name ||
                pharmacy.address ||
                '',
              logo:
                pharmacy.logo_url ||
                pharmacy.image_url ||
                null,
            })),
          );
        }
      }
    });
  }, []);

  useEffect(() => {
    const timer = window.setInterval(() => {
      const element = scrollerRef.current;

      if (!element) {
        return;
      }

      const maximum = element.scrollWidth - element.clientWidth;

      if (element.scrollLeft >= maximum - 30) {
        element.scrollTo({
          left: 0,
          behavior: 'smooth',
        });
      } else {
        element.scrollBy({
          left: 250,
          behavior: 'smooth',
        });
      }
    }, 3500);

    return () => window.clearInterval(timer);
  }, []);

  const visibleProducts = useMemo(() => {
    const result = products.filter(
      (product) => product.category === activeTab,
    );

    return result.length
      ? result
      : FALLBACK_PRODUCTS.filter(
          (product) => product.category === activeTab,
        );
  }, [products, activeTab]);

  const formatPrice = (price) =>
    price
      ? new Intl.NumberFormat('fa-IR').format(price)
      : 'استعلام قیمت';

  const scroll = (offset) => {
    scrollerRef.current?.scrollBy({
      left: offset,
      behavior: 'smooth',
    });
  };

  return (
    <section className={styles.section}>
      <div className={styles.sectionHeading}>
        <div>
          <span className={styles.sectionEyebrow}>
            دارو و سلامت
          </span>

          <h2>داروخانه آنلاین</h2>

          <p>
            دارو، محصولات آرایشی و بهداشتی و تجهیزات پزشکی
          </p>
        </div>

        <Link href="/pharmacy">مشاهده همه</Link>
      </div>

      <div className={styles.pharmacyTabs}>
        <button
          type="button"
          className={
            activeTab === 'medicine'
              ? styles.pharmacyTabActive
              : styles.pharmacyTab
          }
          onClick={() => setActiveTab('medicine')}
        >
          داروها
        </button>

        <button
          type="button"
          className={
            activeTab === 'beauty'
              ? styles.pharmacyTabActive
              : styles.pharmacyTab
          }
          onClick={() => setActiveTab('beauty')}
        >
          آرایشی و بهداشتی
        </button>

        <button
          type="button"
          className={
            activeTab === 'equipment'
              ? styles.pharmacyTabActive
              : styles.pharmacyTab
          }
          onClick={() => setActiveTab('equipment')}
        >
          لوازم پزشکی
        </button>
      </div>

      <div className={styles.productScrollerWrapper}>
        <button
          type="button"
          className={styles.scrollPrevious}
          onClick={() => scroll(-300)}
        >
          <RightOutlined />
        </button>

        <div
          ref={scrollerRef}
          className={styles.productScroller}
        >
          {visibleProducts.map((product) => (
            <article
              key={product.id}
              className={styles.productCard}
            >
              <div className={styles.productImage}>
                {product.image ? (
                  <img src={product.image} alt={product.name} />
                ) : (
                  <MedicineBoxOutlined />
                )}
              </div>

              <div className={styles.productBody}>
                <h3>{product.name}</h3>

                <span>
                  {activeTab === 'medicine'
                    ? 'دارو'
                    : activeTab === 'beauty'
                      ? 'آرایشی و بهداشتی'
                      : 'تجهیزات پزشکی'}
                </span>

                <strong>
                  {formatPrice(product.price)}
                  {product.price ? ' تومان' : ''}
                </strong>

                <Link href={`/pharmacy/drugs/${product.id}`}>
                  مشاهده محصول
                </Link>
              </div>
            </article>
          ))}
        </div>

        <button
          type="button"
          className={styles.scrollNext}
          onClick={() => scroll(300)}
        >
          <LeftOutlined />
        </button>
      </div>

      <div className={styles.pharmacyListHeading}>
        <h3>
          <ShopOutlined />
          داروخانه‌های فعال
        </h3>

        <Link href="/pharmacy">مشاهده داروخانه‌ها</Link>
      </div>

      <div className={styles.pharmacyGrid}>
        {pharmacies.slice(0, 8).map((pharmacy) => (
          <Link
            key={pharmacy.id}
            href={`/pharmacy/${pharmacy.id}`}
            className={styles.pharmacyCard}
          >
            <div className={styles.pharmacyLogo}>
              {pharmacy.logo ? (
                <img src={pharmacy.logo} alt={pharmacy.name} />
              ) : (
                <ShopOutlined />
              )}
            </div>

            <div>
              <strong>{pharmacy.name}</strong>
              <span>{pharmacy.city}</span>
            </div>

            <small>مشاهده</small>
          </Link>
        ))}
      </div>
    </section>
  );
}
