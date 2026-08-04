# رفع نهایی چندزبانه فرانت دکتر وب

این بسته از الگوی پنل ادمین استفاده می‌کند:

```jsx
const { t } = useLanguage();

{t('save', 'ذخیره')}
{t('book_appointment', 'دریافت نوبت')}
```

اما فقط چند عبارت محدود را اصلاح نمی‌کند؛ کل `front/src` را اسکن می‌کند.

## امکانات

- استخراج تمام جمله‌های فارسی از `JS / JSX / TS / TSX`
- ترجمه تمام جمله‌ها به انگلیسی و عربی با Ollama محلی
- تبدیل امن متن‌های داخل Client Component به `t(key, fallback)`
- ترجمه متن‌های سطح ماژول، داده‌های ثابت و Server Component با دیکشنری کامل و `I18nBridge`
- ترجمه Placeholder، Title، Alt و `aria-label`
- تغییر آنی `RTL / LTR`
- Locale کامل Ant Design
- ذخیره زبان در LocalStorage و Cookie
- اتصال به API زبان بک‌اند و ادغام ترجمه‌های دیتابیس
- باقی‌ماندن URL بدون `/fa`، `/en` و `/ar`
- Backup کامل
- گزارش پوشش ترجمه
- جلوگیری از Build در صورت وجود جمله جدید ترجمه‌نشده
- دستور دائمی `npm run i18n:sync` برای صفحه‌ها و متن‌های آینده

## پیش‌نیاز

Ollama باید روشن باشد و یک مدل چندزبانه نصب شده باشد:

```bash
ollama list
```

اگر Ollama خاموش است:

```bash
ollama serve
```

مدل پیشنهادی:

```bash
ollama pull qwen2.5:7b
```

## نصب آزمایشی

```bash
python3 install.py \
  /home/god/Videos/medikal/front \
  --dry-run
```

## نصب واقعی

انتخاب خودکار مدل نصب‌شده:

```bash
python3 install.py \
  /home/god/Videos/medikal/front
```

یا تعیین مدل:

```bash
python3 install.py \
  /home/god/Videos/medikal/front \
  --model qwen2.5:7b
```

ترجمه‌ها در این فایل ذخیره می‌شوند:

```text
front/src/i18n/messages.generated.json
```

در اجرای بعد فقط جمله‌های جدید ترجمه می‌شوند.

## بعد از نصب

```bash
cd /home/god/Videos/medikal/front
rm -rf .next
npm run build
npm run lint
```

سپس:

```bash
cd /home/god/Videos/medikal
docker compose restart medikal-front
docker compose logs --tail=100 medikal-front
```

در مرورگر:

```text
Ctrl + Shift + R
```

## متن‌ها و صفحه‌های جدید در آینده

```bash
cd /home/god/Videos/medikal/front
npm run i18n:sync
npm run i18n:audit
```

`prebuild` نیز Audit را اجرا می‌کند و اجازه نمی‌دهد جمله فارسی جدید بدون ترجمه وارد Build شود.

## بازگشت

مسیر Backup هنگام نصب چاپ می‌شود:

```bash
python3 rollback.py \
  /home/god/Videos/medikal/front \
  /home/god/Videos/medikal/front-i18n-final-backup-YYYYMMDD-HHMMSS
```

## نکته

Ollama فقط هنگام ساخت یا به‌روزرسانی ترجمه‌ها استفاده می‌شود. سایت در زمان اجرا کاملاً محلی است و به Ollama یا اینترنت وابستگی ندارد.
