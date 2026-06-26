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
------------------------------------------------------------



localStorage.getItem('token')

curl -X GET http://localhost:8210/api/user \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 2|681PaTQCPKxBhUI3Jsy9og27Akcu2FQ8jaM0HXXh5db6ded9"

-------------------------------------------------
TOKEN="23|UKKkSE19nF3P9cux7lOXWZJLklFj7AyyKnssPNFQc2d43e45"

curl -X GET http://localhost:8210/api/admin/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
----------------------------------------------------
docker exec -it medikal-laravel bash

docker exec -it medikal-laravel php artisan config:clear
docker exec -it medikal-laravel php artisan cache:clear
docker exec -it medikal-laravel php artisan view:clear
docker exec -it medikal-laravel php artisan optimize:clear

docker exec -it medikal-laravel php artisan tinker
----------------------------------
curl -X POST http://localhost:8210/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'


php artisan tinker

$user = User::where('email', 'admin@example.com')->first();
$user->assignRole('super_admin');
exit

  -------------------------------- bulk
  TOKEN="2|vJA4CvqmZk8NtWk0hr4J9p3EjuJYI5INeQZ2kV5fdc7d00a1"

  curl -X GET "http://localhost:8210/api/admin/bulk/export-products" \
    -H "Authorization: Bearer $TOKEN"

    --------------------------------

    # یک alias بساز
    alias art='php artisan optimize:clear && php artisan route:clear && php artisan config:clear'

    # هر بار که می‌خوای Route جدید اضافه کنی، اجرا کن:
    art

----------------------------  لاگین کاربر عادی
curl -X POST http://localhost:8210/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
    ----------------------------- خروجی اکسل
    curl -X POST "http://localhost:8210/api/admin/reports/sales/excel" \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Content-Type: application/json" \
      -d '{"start_date":"2026-01-01","end_date":"2026-12-31"}'


---------------------------------------------- خروجی محصول
ADMIN_TOKEN="10|tMfrkKOFpVeYBkhXHwQxb7hZUkaDKBhOE7A1Dv2b84bf839e"

curl -X GET "http://localhost:8210/api/admin/bulk/export-products" \
  -H "Authorization: Bearer $ADMIN_TOKEN"

  -----------------------------------------------------------------اضافه کردن محصول
  curl -X POST "http://localhost:8210/api/admin/products" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
      "name": "محصول تست",
      "slug": "محصول-تست-002",
      "code": "PRD-TEST-002",
      "sku": "SKU-TEST-002",
      "price": 150000,
      "stock": 5,
      "category_id": 1
    }'

    --------------------------------------------------------------------------- خروجی pdf


 ADMIN_TOKEN="13|zGrCnop3Wn1jwbABzB8JZi2vfPPnBqJFhn3PHOBga35f2d12"

   curl -X POST "http://localhost:8210/api/admin/reports/sales/pdf" \
     -H "Authorization: Bearer $ADMIN_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"start_date":"2026-01-01","end_date":"2026-12-31"}'





      --------------------------موجودی و شارژ کیف پول

      USER_TOKEN="9|mBwvYIdgFwUP09IoNlK73Yxlfe7lVJiVZ79rOkNO27452743"

      # دیدن موجودی
      curl -X GET "http://localhost:8210/api/wallet/balance" \
        -H "Authorization: Bearer $USER_TOKEN"

      # شارژ کیف پول
      curl -X POST "http://localhost:8210/api/wallet/deposit" \
        -H "Authorization: Bearer $USER_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"amount": 50000}'

      # دیدن تراکنش‌ها
      curl -X GET "http://localhost:8210/api/wallet/transactions" \
        -H "Authorization: Bearer $USER_TOKEN"


-------------------------------------
curl -X POST http://localhost:8210/api/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"phone": "09034325329"}'


  curl -X POST http://localhost:8210/api/auth/verify-otp \
    -H "Content-Type: application/json" \
    -d '{"phone": "09034325329", "code": "12345", "name": "محسن مجدم"}'

--------------------------------------
OrderDetailPage (جزئیات سفارش)
    ↓
    دکمه "پرداخت سفارش" (Link به /payment/${order.id})
    ↓
PaymentGatewayPage (همین فایل)
    ↓
    انتخاب درگاه → کلیک "پرداخت"
    ↓
    POST /api/payment/initiate
    ↓
    دریافت redirect_url
    ↓
    window.location.href = redirect_url
    ↓
    رفتن به درگاه زرین‌پال ✅





==========================================================================
