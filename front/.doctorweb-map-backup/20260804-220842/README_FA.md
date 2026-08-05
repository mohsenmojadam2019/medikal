# نقشه Leaflet دکتر وب

این بسته صفحه کامل `/map` را برای Next.js App Router ایجاد می‌کند.

## امکانات

- نمایش داروخانه، کلینیک، بیمارستان، آزمایشگاه و تصویربرداری
- جستجو و فیلتر مراکز
- دریافت موقعیت کاربر
- انتخاب دستی مبدا روی نقشه
- نزدیک‌ترین مرکز
- مسیریابی OSRM و رسم مسیر
- API Route داخلی Next.js
- داده آزمایشی در صورت نبود API لاراول
- طراحی واکنش‌گرا

## نصب

بسته را در ریشه فرانت، کنار `package.json`، استخراج و اجرا کنید:

```bash
python3 doctorweb-leaflet-map-installer/install.py .
```

بدون نصب خودکار npm:

```bash
python3 doctorweb-leaflet-map-installer/install.py . --skip-npm
```

نصب‌کننده قبل از جایگزینی فایل‌ها نسخه پشتیبان می‌سازد.

## اتصال Laravel

مقادیر `doctorweb-map.env.example` را در `.env.local` قرار دهید:

```env
MEDIKAL_MAP_FACILITIES_URL=http://medikall-webserver/api/map/facilities
OSRM_BASE_URL=https://router.project-osrm.org
```

API مراکز می‌تواند یکی از این ساختارها را برگرداند:

- آرایه مستقیم
- `{ "data": [] }`
- GeoJSON FeatureCollection

فیلدهای پشتیبانی‌شده:

`id`, `type`, `name`, `latitude/lat`, `longitude/lng`, `address`,
`phone`, `rating`, `is_open`, `is_24_hours`, `services`

## تست

```bash
npm run build
npm run dev
```

آدرس:

```text
http://localhost:3000/map
```

## نسخه واقعی

سرور عمومی OpenStreetMap و OSRM برای توسعه مناسب است. برای مصرف زیاد، کاشی نقشه و OSRM باید روی زیرساخت خود پروژه یا سرویس مناسب اجرا شود.
