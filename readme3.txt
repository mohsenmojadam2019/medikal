

داکیومنت کامل API سیستم کلینیک
پیش‌نیازها
bash
# توکن لاگین (بعد از لاگین گرفتی)
TOKEN="11|3fPLnO3lewQBR6ylxWXG8VL9qzYYwmAlaWqYqRga632fa4f7"
۱. احراز هویت (Authentication)
۱.۱ ورود با موبایل (درخواست کد)
bash
curl -X POST http://localhost:8210/api/auth/login/mobile \
  -H "Content-Type: application/json" \
  -d '{
    "mobile": "09123456789"
  }'
خروجی: کد تایید به شماره موبایل ارسال می‌شود (در محیط تست کد 1234 است)

۱.۲ تایید کد و دریافت توکن
bash
curl -X POST http://localhost:8210/api/auth/login/mobile/verify \
  -H "Content-Type: application/json" \
  -d '{
    "mobile": "09123456789",
    "code": "1234"
  }'
خروجی: توکن دریافت می‌شود

۱.۳ ورود با ایمیل و رمز عبور
bash
curl -X POST http://localhost:8210/api/auth/login/email \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@medikal.com",
    "password": "password123"
  }'
۱.۴ اطلاعات کاربر جاری
bash
curl -X GET http://localhost:8210/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
۱.۵ خروج از سیستم
bash
curl -X POST http://localhost:8210/api/auth/logout \
  -H "Authorization: Bearer $TOKEN"
۲. مدیریت کلینیک (Clinic Management)
۲.۱ تنظیمات عمومی کلینیک (بدون احراز هویت)
bash
curl -X GET http://localhost:8210/api/clinic/settings
کاربرد: نمایش اطلاعات عمومی کلینیک برای صفحه اصلی و فرانت‌اند

۲.۲ اطلاعات کامل کلینیک (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/clinic \
  -H "Authorization: Bearer $TOKEN"
۲.۳ بروزرسانی کلینیک
bash
curl -X PUT http://localhost:8210/api/admin/clinic \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "کلینیک تخصصی دکتر رضایی",
    "phone": "۰۲۱-۸۸۸۸۸۸۸۸",
    "address": "تهران، خیابان انقلاب، پلاک ۴۵۶",
    "primary_color": "#e53e3e",
    "secondary_color": "#f6ad55"
  }'
۲.۴ آپلود لوگو کلینیک
bash
curl -X POST http://localhost:8210/api/admin/clinic/upload-logo \
  -H "Authorization: Bearer $TOKEN" \
  -F "logo=@/path/to/logo.png"
۲.۵ تغییر وضعیت کلینیک (فعال/غیرفعال)
bash
curl -X POST http://localhost:8210/api/admin/clinic/toggle-status \
  -H "Authorization: Bearer $TOKEN"
۳. مدیریت تخصص‌ها (Specialties)
۳.۱ لیست تخصص‌ها (عمومی)
bash
curl -X GET http://localhost:8210/api/specialties
۳.۲ لیست تخصص‌ها (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/specialties \
  -H "Authorization: Bearer $TOKEN"
۳.۳ ایجاد تخصص جدید
bash
curl -X POST http://localhost:8210/api/admin/specialties \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "جراحی عمومی",
    "description": "متخصص جراحی عمومی",
    "is_active": true
  }'
۳.۴ آپلود عکس تخصص
bash
curl -X POST http://localhost:8210/api/admin/specialties/1/icon \
  -H "Authorization: Bearer $TOKEN" \
  -F "icon=@/path/to/icon.png"
۳.۵ تغییر وضعیت تخصص
bash
curl -X POST http://localhost:8210/api/admin/specialties/1/toggle \
  -H "Authorization: Bearer $TOKEN"
۴. مدیریت پزشکان (Doctors)
۴.۱ لیست پزشکان (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/doctors \
  -H "Authorization: Bearer $TOKEN"
۴.۲ لیست پزشکان عمومی
bash
curl -X GET http://localhost:8210/api/doctors/public
۴.۳ ایجاد پزشک جدید
bash
curl -X POST http://localhost:8210/api/admin/doctors \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "دکتر علی محمدی",
    "mobile": "09123456788",
    "email": "ali@doctor.com",
    "specialty_id": 1,
    "license_number": "123456",
    "consultation_fee": 150000,
    "is_available": true
  }'
۴.۴ تایید پزشک
bash
curl -X POST http://localhost:8210/api/admin/doctors/1/verify \
  -H "Authorization: Bearer $TOKEN"
۴.۵ تغییر وضعیت پزشک (فعال/غیرفعال)
bash
curl -X POST http://localhost:8210/api/admin/doctors/1/toggle-availability \
  -H "Authorization: Bearer $TOKEN"
۴.۶ نمایش عمومی پزشک
bash
curl -X GET http://localhost:8210/api/doctors/1/public
۵. مدیریت بیماران (Patients)
۵.۱ لیست بیماران
bash
curl -X GET http://localhost:8210/api/admin/patients \
  -H "Authorization: Bearer $TOKEN"
۵.۲ ایجاد بیمار جدید
bash
curl -X POST http://localhost:8210/api/admin/patients \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "رضا کریمی",
    "mobile": "09123456787",
    "national_code": "1234567890",
    "blood_type": "A+"
  }'
۵.۳ جستجوی بیمار با کدملی
bash
curl -X GET "http://localhost:8210/api/patients/search/by-national-code?national_code=1234567890" \
  -H "Authorization: Bearer $TOKEN"
۵.۴ جستجوی بیمار با موبایل
bash
curl -X GET "http://localhost:8210/api/patients/search/by-mobile?mobile=09123456787" \
  -H "Authorization: Bearer $TOKEN"
۵.۵ تاریخچه پزشکی بیمار
bash
curl -X GET http://localhost:8210/api/admin/patients/1/medical-history \
  -H "Authorization: Bearer $TOKEN"
۵.۶ اختصاص پزشک به بیمار
bash
curl -X POST http://localhost:8210/api/admin/patients/1/assign-doctor \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "doctor_id": 1
  }'
۶. مدیریت نوبت‌ها (Appointments)
۶.۱ دریافت زمان‌های خالی پزشک
bash
curl -X GET "http://localhost:8210/api/appointments/doctors/1/available-slots?date=2026-06-30" \
  -H "Authorization: Bearer $TOKEN"
۶.۲ رزرو نوبت جدید
bash
curl -X POST http://localhost:8210/api/appointments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "doctor_id": 1,
    "patient_id": 1,
    "date": "2026-06-30",
    "start_time": "10:00",
    "notes": "ویزیت معمولی"
  }'
۶.۳ لیست نوبت‌های من (بیمار)
bash
curl -X GET http://localhost:8210/api/appointments/my/appointments \
  -H "Authorization: Bearer $TOKEN"
۶.۴ تایید نوبت (پزشک)
bash
curl -X POST http://localhost:8210/api/appointments/1/confirm \
  -H "Authorization: Bearer $TOKEN"
۶.۵ شروع ویزیت (ثبت حضور بیمار)
bash
curl -X POST http://localhost:8210/api/appointments/1/start \
  -H "Authorization: Bearer $TOKEN"
۶.۶ پایان ویزیت
bash
curl -X POST http://localhost:8210/api/appointments/1/complete \
  -H "Authorization: Bearer $TOKEN"
۶.۷ لغو نوبت
bash
curl -X POST http://localhost:8210/api/appointments/1/cancel \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "بیمار قادر به حضور نیست"
  }'
۶.۸ تغییر زمان نوبت
bash
curl -X POST http://localhost:8210/api/appointments/1/reschedule \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "date": "2026-07-01",
    "start_time": "11:00"
  }'
۷. مدیریت نسخه‌ها (Prescriptions)
۷.۱ ایجاد نسخه جدید
bash
curl -X POST http://localhost:8210/api/prescriptions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "appointment_id": 1,
    "drug_name": "آموکسی‌سیلین",
    "dosage": "500mg",
    "frequency": 3,
    "duration": 7,
    "diagnosis": "عفونت تنفسی",
    "instructions": "هر ۸ ساعت یک عدد"
  }'
۷.۲ لیست نسخه‌های من (بیمار)
bash
curl -X GET http://localhost:8210/api/prescriptions/my \
  -H "Authorization: Bearer $TOKEN"
۷.۳ نمایش نسخه با بررسی تداخل دارویی
bash
curl -X GET http://localhost:8210/api/prescriptions/1 \
  -H "Authorization: Bearer $TOKEN"
۷.۴ دریافت اطلاعات چاپ نسخه
bash
curl -X GET http://localhost:8210/api/prescriptions/1/print \
  -H "Authorization: Bearer $TOKEN"
۷.۵ تغییر وضعیت نسخه
bash
curl -X POST http://localhost:8210/api/prescriptions/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "complete"
  }'
۸. سیستم اعلان‌ها (Notifications)
۸.۱ دریافت لیست اعلان‌ها
bash
curl -X GET http://localhost:8210/api/notifications \
  -H "Authorization: Bearer $TOKEN"
۸.۲ تعداد اعلان‌های خوانده‌نشده
bash
curl -X GET http://localhost:8210/api/notifications/unread-count \
  -H "Authorization: Bearer $TOKEN"
۸.۳ دریافت اعلان‌های خوانده‌نشده
bash
curl -X GET http://localhost:8210/api/notifications/unread \
  -H "Authorization: Bearer $TOKEN"
۸.۴ علامت‌گذاری اعلان به عنوان خوانده‌شده
bash
curl -X PUT http://localhost:8210/api/notifications/1/read \
  -H "Authorization: Bearer $TOKEN"
۸.۵ علامت‌گذاری همه اعلان‌ها به عنوان خوانده‌شده
bash
curl -X PUT http://localhost:8210/api/notifications/read-all \
  -H "Authorization: Bearer $TOKEN"
۸.۶ حذف یک اعلان
bash
curl -X DELETE http://localhost:8210/api/notifications/1 \
  -H "Authorization: Bearer $TOKEN"
۸.۷ ارسال اعلان به کاربر (ادمین)
bash
curl -X POST http://localhost:8210/api/admin/notifications/send-to-user \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "title": "تست اعلان",
    "body": "این یک پیام تست است",
    "priority": "high"
  }'
۸.۸ ارسال اعلان به همه پزشکان (ادمین)
bash
curl -X POST http://localhost:8210/api/admin/notifications/send-to-doctors \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "اطلاعیه مهم",
    "body": "جلسه هماهنگی فردا ساعت ۱۰ برگزار می‌شود",
    "priority": "urgent"
  }'
۹. مدیریت داروها (Drugs)
۹.۱ لیست داروها
bash
curl -X GET http://localhost:8210/api/admin/drugs \
  -H "Authorization: Bearer $TOKEN"
۹.۲ ایجاد دارو جدید
bash
curl -X POST http://localhost:8210/api/admin/drugs \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ایبوپروفن",
    "generic_name": "Ibuprofen",
    "category": "مسکن",
    "form": "قرص",
    "strength": "400mg",
    "manufacturer": "داروسازی سبحان",
    "price": 25000,
    "stock": 200,
    "requires_prescription": false
  }'
۹.۳ جستجوی دارو
bash
curl -X GET "http://localhost:8210/api/drugs/search?q=ایبوپروفن" \
  -H "Authorization: Bearer $TOKEN"
۹.۴ افزایش موجودی دارو
bash
curl -X POST http://localhost:8210/api/admin/drugs/1/increase-stock \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 50
  }'
۹.۵ کاهش موجودی دارو
bash
curl -X POST http://localhost:8210/api/admin/drugs/1/decrease-stock \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 10
  }'
۹.۶ تغییر وضعیت دارو (فعال/غیرفعال)
bash
curl -X POST http://localhost:8210/api/admin/drugs/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"
۱۰. سیستم زمان‌بندی پزشکان (Schedules)
۱۰.۱ دریافت زمان‌بندی هفتگی پزشک
bash
curl -X GET http://localhost:8210/api/schedules/doctors/1/weekly \
  -H "Authorization: Bearer $TOKEN"
۱۰.۲ تنظیم زمان‌بندی هفتگی پزشک
bash
curl -X POST http://localhost:8210/api/schedules/doctors/1/weekly \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "schedules": [
      {
        "day_of_week": 0,
        "start_time": "08:00",
        "end_time": "14:00"
      },
      {
        "day_of_week": 1,
        "start_time": "08:00",
        "end_time": "14:00"
      }
    ]
  }'
۱۰.۳ ثبت مرخصی/تعطیلی ویژه
bash
curl -X POST http://localhost:8210/api/schedules/doctors/1/special \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "special_date": "2026-07-10",
    "special_reason": "مرخصی سالانه",
    "is_active": true
  }'
۱۰.۴ دریافت تقویم پزشک
bash
curl -X GET "http://localhost:8210/api/schedules/doctors/1/calendar?month=6&year=2026" \
  -H "Authorization: Bearer $TOKEN"
۱۱. سیستم پیام‌رسانی (Chat)
۱۱.۱ دریافت لیست مکالمات
bash
curl -X GET http://localhost:8210/api/chat/conversations \
  -H "Authorization: Bearer $TOKEN"
۱۱.۲ دریافت پیام‌های یک مکالمه
bash
curl -X GET http://localhost:8210/api/chat/messages/2 \
  -H "Authorization: Bearer $TOKEN"
۱۱.۳ ارسال پیام جدید
bash
curl -X POST http://localhost:8210/api/chat/send \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "receiver_id": 2,
    "message": "سلام، نوبت شما تایید شد"
  }'
۱۱.۴ تعداد پیام‌های خوانده‌نشده
bash
curl -X GET http://localhost:8210/api/chat/unread-count \
  -H "Authorization: Bearer $TOKEN"
۱۲. سیستم ارجاع (Referrals)
۱۲.۱ ایجاد ارجاع جدید
bash
curl -X POST http://localhost:8210/api/referrals \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "to_doctor_id": 2,
    "reason": "نیاز به مشاوره تخصصی",
    "notes": "بیمار نیاز به جراحی دارد"
  }'
۱۲.۲ لیست ارجاعات پزشک
bash
curl -X GET "http://localhost:8210/api/referrals/doctor?type=incoming" \
  -H "Authorization: Bearer $TOKEN"
۱۲.۳ پذیرش ارجاع
bash
curl -X POST http://localhost:8210/api/referrals/1/accept \
  -H "Authorization: Bearer $TOKEN"
۱۲.۴ تکمیل ارجاع
bash
curl -X POST http://localhost:8210/api/referrals/1/complete \
  -H "Authorization: Bearer $TOKEN"
۱۳. سیستم امتیازدهی (Ratings)
۱۳.۱ ثبت امتیاز برای پزشک
bash
curl -X POST http://localhost:8210/api/ratings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "doctor_id": 1,
    "score": 5,
    "comment": "پزشک بسیار خوبی بودند",
    "is_anonymous": false
  }'
۱۳.۲ دریافت امتیازات پزشک
bash
curl -X GET http://localhost:8210/api/ratings/doctors/1 \
  -H "Authorization: Bearer $TOKEN"
۱۳.۳ پاسخ به نظر (ادمین)
bash
curl -X POST http://localhost:8210/api/ratings/1/reply \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reply": "با تشکر از شما، خوشحالیم که راضی بودید"
  }'
۱۴. سیستم گزارش‌گیری (Reports)
۱۴.۱ دریافت لیست گزارش‌های موجود
bash
curl -X GET http://localhost:8210/api/reports/types \
  -H "Authorization: Bearer $TOKEN"
۱۴.۲ دریافت گزارش Excel نوبت‌ها
bash
curl -X POST http://localhost:8210/api/reports/excel \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "appointments",
    "from_date": "2026-06-01",
    "to_date": "2026-06-30"
  }' \
  --output appointments.xlsx
۱۴.۳ دریافت گزارش PDF درآمد
bash
curl -X POST http://localhost:8210/api/reports/pdf \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "revenue",
    "from_date": "2026-06-01",
    "to_date": "2026-06-30"
  }' \
  --output revenue.pdf
۱۴.۴ گزارش درآمد پزشک
bash
curl -X GET "http://localhost:8210/api/reports/doctors/1/income?from_date=2026-06-01&to_date=2026-06-30" \
  -H "Authorization: Bearer $TOKEN"
۱۴.۵ گزارش نوبت‌های لغو شده پزشک
bash
curl -X GET "http://localhost:8210/api/reports/doctors/1/cancelled-appointments?from_date=2026-06-01&to_date=2026-06-30" \
  -H "Authorization: Bearer $TOKEN"
۱۵. داشبورد (Dashboard)
۱۵.۱ داشبورد ادمین
bash
curl -X GET http://localhost:8210/api/dashboard/admin \
  -H "Authorization: Bearer $TOKEN"
۱۵.۲ داشبورد پزشک
bash
curl -X GET http://localhost:8210/api/dashboard/doctor \
  -H "Authorization: Bearer $TOKEN"
۱۵.۳ داشبورد بیمار
bash
curl -X GET http://localhost:8210/api/dashboard/patient \
  -H "Authorization: Bearer $TOKEN"
۱۶. سیستم موقعیت مکانی (Location)
۱۶.۱ جستجوی پزشکان نزدیک
bash
curl -X GET "http://localhost:8210/api/location/nearby-doctors?lat=35.6892&lng=51.3890&radius=10&specialty_id=1" \
  -H "Authorization: Bearer $TOKEN"
۱۶.۲ دریافت لیست استان‌ها
bash
curl -X GET http://localhost:8210/api/location/provinces
۱۶.۳ دریافت لیست شهرهای یک استان
bash
curl -X GET http://localhost:8210/api/location/provinces/8/cities
۱۶.۴ محاسبه فاصله بین دو نقطه
bash
curl -X POST http://localhost:8210/api/location/distance \
  -H "Content-Type: application/json" \
  -d '{
    "lat1": 35.6892,
    "lng1": 51.3890,
    "lat2": 35.6992,
    "lng2": 51.3990
  }'
۱۷. سیستم فاکتور و پرداخت (Invoices & Payments)
۱۷.۱ لیست فاکتورهای من (بیمار)
bash
curl -X GET http://localhost:8210/api/invoices/my \
  -H "Authorization: Bearer $TOKEN"
۱۷.۲ دریافت اطلاعات یک فاکتور
bash
curl -X GET http://localhost:8210/api/invoices/1 \
  -H "Authorization: Bearer $TOKEN"
۱۷.۳ لیست درگاه‌های پرداخت موجود
bash
curl -X GET http://localhost:8210/api/payments/gateways \
  -H "Authorization: Bearer $TOKEN"
۱۷.۴ شروع پرداخت آنلاین
bash
curl -X POST http://localhost:8210/api/payments/initiate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_id": 1,
    "gateway": "zarinpal"
  }'
۱۷.۵ تاریخچه پرداخت‌ها
bash
curl -X GET http://localhost:8210/api/payments/history \
  -H "Authorization: Bearer $TOKEN"
۱۸. تنظیمات کاربر (Profile)
۱۸.۱ بروزرسانی اطلاعات کاربر
bash
curl -X PUT http://localhost:8210/api/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "دکتر علی محمدی",
    "email": "ali@doctor.com"
  }'
۱۸.۲ تغییر رمز عبور
bash
curl -X POST http://localhost:8210/api/profile/change-password \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "old_password": "password123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
  }'
۱۸.۳ بروزرسانی آدرس
bash
curl -X PUT http://localhost:8210/api/profile/address \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "address_line_1": "تهران، خیابان ولیعصر، پلاک ۱۲۳",
    "province_id": 8,
    "city_id": 301,
    "postal_code": "1234567890"
  }'
۱۹. سیستم مدیریت سیستم (System)
۱۹.۱ اطلاعات سیستم
bash
curl -X GET http://localhost:8210/api/system/info \
  -H "Authorization: Bearer $TOKEN"
۱۹.۲ لیست فایل‌های لاگ
bash
curl -X GET http://localhost:8210/api/system/logs \
  -H "Authorization: Bearer $TOKEN"
۱۹.۳ پاک کردن کش سیستم
bash
curl -X POST http://localhost:8210/api/system/clear-cache \
  -H "Authorization: Bearer $TOKEN"
۲۰. سیستم سئو (SEO)
۲۰.۱ دریافت اطلاعات سئوی پزشک
bash
curl -X GET http://localhost:8210/api/seo/doctor/1
۲۰.۲ دریافت اطلاعات سئوی صفحات
bash
curl -X GET "http://localhost:8210/api/seo/page?page=home"
۲۱. صفحه اصلی (Landing Page)
۲۱.۱ اطلاعات صفحه اصلی
bash
curl -X GET http://localhost:8210/api/landing
۲۱.۲ آمار کلی
bash
curl -X GET http://localhost:8210/api/landing/stats
۲۱.۳ پزشکان برتر
bash
curl -X GET http://localhost:8210/api/landing/top-doctors?limit=6
۲۱.۴ نظرات اخیر
bash
curl -X GET http://localhost:8210/api/landing/recent-reviews?limit=6
مقادیر نمونه برای تست (Seeders)
bash
# کاربر ادمین
Email: admin@medikal.com
Password: password123

# یا
Mobile: 09123456789
OTP Code: 1234

# کلینیک نمونه
Name: کلینیک نمونه
Slug: clinic-sample
نکات مهم
همه درخواست‌ها به جز موارد مشخص شده، نیاز به توکن دارند

توکن باید در هدر Authorization: Bearer $TOKEN ارسال شود

محتوای JSON باید با Content-Type: application/json ارسال شود

فایل‌ها باید با multipart/form-data ارسال شوند

خطاها با فرمت {"success":false,"message":"..."} برمی‌گردند

۲۲. سیستم Webhook (ویپ کلینیک)
۲۲.۱ دریافت وضعیت Webhook (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/webhook/status \
  -H "Authorization: Bearer $TOKEN"

  ۲۲.۲ فعال/غیرفعال کردن Webhook (ادمین)
  bash
  # فعال کردن
  curl -X POST http://localhost:8210/api/admin/webhook/toggle \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
      "enabled": true,
      "secret": "my-secret-key-12345678"
    }'

  # غیرفعال کردن
  curl -X POST http://localhost:8210/api/admin/webhook/toggle \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
      "enabled": false
    }'

   ۲۲.۳ دریافت لاگ‌های Webhook (ادمین)
   bash
   curl -X GET http://localhost:8210/api/admin/webhook/logs \
     -H "Authorization: Bearer $TOKEN"


     ۲۲.۴ دریافت نوبت از ویپ کلینیک (عمومی - بدون احراز هویت)
     bash
     curl -X POST http://localhost:8210/api/webhook/appointment \
       -H "Content-Type: application/json" \
       -d '{
         "patient_national_code": "1234567890",
         "patient_name": "رضا کریمی",
         "patient_mobile": "09123456787",
         "patient_email": "reza@example.com",
         "doctor_name": "دکتر علی محمدی",
         "appointment_date": "2026-07-01",
         "appointment_time": "10:00",
         "appointment_type": "in_person",
         "notes": "ویزیت از طریق ویپ کلینیک"
       }'


       بریا غیرفعال سازی ویپ

       راه اول: از طریق API (سریع‌ترین)
       bash
       TOKEN="11|3fPLnO3lewQBR6ylxWXG8VL9qzYYwmAlaWqYqRga632fa4f7"

       curl -X POST http://localhost:8210/api/admin/webhook/toggle \
         -H "Authorization: Bearer $TOKEN" \
         -H "Content-Type: application/json" \
         -d '{
           "enabled": false
           }'


۲۳. سیستم کیف پول (Wallet System)
۲۳.۱ دریافت موجودی کیف پول (کاربر)
bash
curl -X GET http://localhost:8210/api/wallet/balance \
  -H "Authorization: Bearer $TOKEN"



۲۳.۲ دریافت تاریخچه تراکنش‌ها (کاربر)
bash
curl -X GET http://localhost:8210/api/wallet/transactions \
  -H "Authorization: Bearer $TOKEN"



۲۳.۳ دریافت خلاصه تراکنش‌ها (کاربر)
bash
curl -X GET http://localhost:8210/api/wallet/summary \
  -H "Authorization: Bearer $TOKEN"



۲۳.۴ شارژ کیف پول (کاربر)
bash
curl -X POST http://localhost:8210/api/wallet/deposit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500000,
    "gateway": "local"
  }'



۲۳.۵ تایید پرداخت شارژ (Callback - عمومی)
bash
curl -X POST "http://localhost:8210/api/wallet/deposit/callback?transaction_id=DEP-20260627-SW87GMTH&status=success"


۲۳.۶ پرداخت نوبت با کیف پول (کاربر)
bash
curl -X POST http://localhost:8210/api/wallet/pay-appointment \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "appointment_id": 4
  }'



۲۴.۱ لیست کیف‌پول‌ها (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/wallet \
  -H "Authorization: Bearer $TOKEN"


۲۴.۲ آمار کیف‌پول‌ها (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/wallet/stats \
  -H "Authorization: Bearer $TOKEN"



۲۴.۳ نمایش کیف پول کاربر (ادمین)
bash
curl -X GET http://localhost:8210/api/admin/wallet/{userId} \
  -H "Authorization: Bearer $TOKEN"



۲۴.۴ تغییر وضعیت کیف پول (ادمین)
bash
curl -X POST http://localhost:8210/api/admin/wallet/{userId}/toggle-status \
  -H "Authorization: Bearer $TOKEN"



۲۴.۵ اضافه کردن پاداش به کیف پول (ادمین)
bash
curl -X POST http://localhost:8210/api/admin/wallet/{userId}/add-bonus \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100000,
    "description": "پاداش ویژه نوروز"
  }'





































































