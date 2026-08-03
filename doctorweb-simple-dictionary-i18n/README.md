# رفع ساده و کامل چندزبانه دکتر وب

این نسخه دقیقاً یک کار انجام می‌دهد:

1. تمام متن‌های فارسی موجود در `front/src` را پیدا می‌کند.
2. همه را در یک دیکشنری فارسی، انگلیسی و عربی قرار می‌دهد.
3. وقتی کاربر زبان را عوض می‌کند، تمام متن‌های Body همان لحظه تغییر می‌کنند.
4. URL و مسیرهای سایت را تغییر نمی‌دهد.
5. نیاز ندارد تک‌تک کامپوننت‌ها دستی به `t()` تبدیل شوند.

فایل دیکشنری نهایی:

```text
front/src/i18n/dictionary.generated.json
```

ساختار:

```json
{
  "phrases": {
    "fa": {
      "دریافت نوبت": "دریافت نوبت"
    },
    "en": {
      "دریافت نوبت": "Book appointment"
    },
    "ar": {
      "دریافت نوبت": "حجز موعد"
    }
  }
}
```

تابع `t()` قدیمی پروژه نیز حفظ می‌شود و هر دو حالت را پشتیبانی می‌کند:

```jsx
t('common.search')
t('دریافت نوبت')
t('book_appointment', 'دریافت نوبت')
```

## نصب آزمایشی

```bash
python3 install.py \
  /home/god/Videos/medikal/front \
  --model qwen3.5:4b \
  --dry-run
```

## نصب واقعی

```bash
python3 install.py \
  /home/god/Videos/medikal/front \
  --model qwen3.5:4b
```

اسکریپت با Python اجرا می‌شود و به Node روی Ubuntu نیاز ندارد.

Ollama فقط یک‌بار برای ساخت معادل انگلیسی و عربی استفاده می‌شود. خود سایت بعد از نصب به Ollama وابسته نیست.

## بعد از نصب

چون Node داخل Docker است:

```bash
cd /home/god/Videos/medikal

docker compose exec medikal-front sh -lc \
  'rm -rf .next && npm run build'
```

سپس:

```bash
docker compose restart medikal-front
docker compose logs --tail=100 medikal-front
```

در مرورگر:

```text
Ctrl + Shift + R
```

## بازگشت

مسیر Backup هنگام نصب چاپ می‌شود:

```bash
python3 rollback.py \
  /home/god/Videos/medikal/front \
  /home/god/Videos/medikal/front-simple-i18n-backup-YYYYMMDD-HHMMSS
```
