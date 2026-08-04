# ترجمه خودکار زنده دکتر وب

این بسته برای نسخه‌ای است که مسیرهای زبان از URL حذف شده‌اند.

پس از نصب، با انتخاب:

- فارسی
- English
- العربية

متن‌های هاردکد شده رابط کاربری بدون تغییر URL ترجمه می‌شوند.

## عملکرد

- تغییر لحظه‌ای متن‌های قابل مشاهده
- پشتیبانی از محتوای جدیدی که Ant Design بعداً در DOM ایجاد می‌کند
- ترجمه placeholder، title، aria-label و alt
- تغییر جهت صفحه:
  - فارسی و عربی: RTL
  - انگلیسی: LTR
- حفظ آدرس‌هایی مانند:
  - `/`
  - `/doctors`
  - `/pharmacy`

## نصب آزمایشی

```bash
python3 install.py /home/god/Videos/medikal/front --dry-run
```

## نصب واقعی

```bash
python3 install.py /home/god/Videos/medikal/front
```

## تست

```bash
cd /home/god/Videos/medikal/front
NODE_ENV=production npm run build
npm run lint
```

سپس:

```bash
cd /home/god/Videos/medikal
docker compose restart medikal-front
docker compose logs --tail=100 medikal-front
```

## بازگشت

مسیر Backup هنگام نصب چاپ می‌شود:

```bash
python3 rollback.py \
  /home/god/Videos/medikal/front \
  /home/god/Videos/medikal/front-translation-backup-YYYYMMDD-HHMMSS
```

## نکته فنی

این بسته یک Hotfix سراسری برای ترجمه متن‌های هاردکد شده است. برای معماری نهایی، بهتر است هر متن به کلیدهای `t('...')` منتقل شود؛ اما این بسته باعث می‌شود صفحات فعلی بلافاصله با تغییر زبان ترجمه شوند.
