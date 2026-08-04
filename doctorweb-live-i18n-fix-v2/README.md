# DoctorWeb Live i18n Fix v2

علت اصلی این بود که کامپوننت‌های مشترک، زبان را به‌صورت ثابت دریافت می‌کردند:

```jsx
<PlatformHome locale="fa" />
```

و داخل خود کامپوننت نیز:

```js
export default function PlatformHome({ locale = 'fa' })
```

بنابراین تغییر `LanguageContext` روی متن اصلی صفحه اثری نداشت.

این بسته:

- `locale="fa"`، `locale="en"` و `locale="ar"` ثابت را از Wrapperها حذف می‌کند.
- کامپوننت‌های مشترک را به `useLanguage()` متصل می‌کند.
- `LanguageSwitcher` را با رویداد رسمی `menu.onClick` اصلاح می‌کند.
- ترجمه متن‌های هاردکد شده را به‌عنوان پشتیبان نگه می‌دارد.
- URL را بدون پیشوند زبان نگه می‌دارد.
- قبل از تغییر از کل پوشه `src` نسخه پشتیبان می‌سازد.

## بررسی بدون تغییر

```bash
python3 install.py /home/god/Videos/medikal/front --dry-run
```

## نصب

```bash
python3 install.py /home/god/Videos/medikal/front
```

## پاک‌کردن کش و تست

```bash
cd /home/god/Videos/medikal/front
rm -rf .next
NODE_ENV=production npm run build
npm run lint
```

در Docker:

```bash
cd /home/god/Videos/medikal
docker compose restart medikal-front
docker compose logs --tail=100 medikal-front
```

بعد در مرورگر Hard Refresh انجام بده:

```text
Ctrl + Shift + R
```
