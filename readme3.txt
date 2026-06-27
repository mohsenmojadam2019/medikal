

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

==================================================
==================================================
==================================================
==================================================
==================================================
==================================================

cat >> /home/god/Videos/medikal/readme3.txt << 'EOF'

==================================================
==================================================
۲۴. سیستم آزمایشگاه (Laboratory)
==================================================
==================================================

۲۴.۱ لیست دسته‌بندی‌های آزمایشگاه
curl -X GET http://localhost:8210/api/lab/categories \
  -H "Authorization: Bearer $TOKEN"

۲۴.۲ لیست تست‌های آزمایشگاهی
curl -X GET http://localhost:8210/api/lab/tests \
  -H "Authorization: Bearer $TOKEN"

۲۴.۳ لیست تست‌های فعال
curl -X GET http://localhost:8210/api/lab/tests/active \
  -H "Authorization: Bearer $TOKEN"

۲۴.۴ مشاهده یک تست
curl -X GET http://localhost:8210/api/lab/tests/1 \
  -H "Authorization: Bearer $TOKEN"

۲۴.۵ ایجاد سفارش آزمایش جدید (پزشک)
curl -X POST http://localhost:8210/api/lab/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "priority": "urgent",
    "clinical_history": "بیمار با علائم دیابت",
    "tests": [
      {"test_id": 2, "quantity": 1},
      {"test_id": 5, "quantity": 1}
    ]
  }'

۲۴.۶ لیست سفارش‌های من (بیمار)
curl -X GET http://localhost:8210/api/lab/my/orders \
  -H "Authorization: Bearer $TOKEN"

۲۴.۷ لیست سفارش‌های پزشک
curl -X GET http://localhost:8210/api/lab/doctor/orders \
  -H "Authorization: Bearer $TOKEN"

۲۴.۸ مشاهده جزئیات سفارش
curl -X GET http://localhost:8210/api/lab/orders/1 \
  -H "Authorization: Bearer $TOKEN"

۲۴.۹ تغییر وضعیت سفارش (کارشناس آزمایشگاه)
curl -X PUT http://localhost:8210/api/lab/orders/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "processing"
  }'
وضعیت‌های ممکن: pending, waiting_payment, paid, scheduled, sample_collected, processing, partial, completed, cancelled, rejected

۲۴.۱۰ ثبت نتیجه آزمایش (کارشناس)
curl -X POST http://localhost:8210/api/lab/results \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "lab_order_id": 1,
    "lab_test_id": 2,
    "value": 185,
    "comment": "بیمار ناشتا نبوده است"
  }'

۲۴.۱۱ ثبت چند نتیجه همزمان
curl -X POST http://localhost:8210/api/lab/results/bulk \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "results": [
      {
        "lab_order_id": 1,
        "lab_test_id": 2,
        "value": 185
      },
      {
        "lab_order_id": 1,
        "lab_test_id": 5,
        "value": 3.2
      }
    ]
  }'

۲۴.۱۲ تایید نتیجه آزمایش
curl -X POST http://localhost:8210/api/lab/results/1/verify \
  -H "Authorization: Bearer $TOKEN"

۲۴.۱۳ حذف نتیجه آزمایش
curl -X DELETE http://localhost:8210/api/lab/results/1 \
  -H "Authorization: Bearer $TOKEN"

۲۴.۱۴ آمار آزمایشگاه
curl -X GET http://localhost:8210/api/lab/stats \
  -H "Authorization: Bearer $TOKEN"

۲۴.۱۵ آمار آزمایشگاه (پزشک)
curl -X GET http://localhost:8210/api/lab/my/stats \
  -H "Authorization: Bearer $TOKEN"

۲۴.۱۶ مدیریت تست‌ها (ادمین)
# ایجاد تست جدید
curl -X POST http://localhost:8210/api/admin/lab/tests \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "name": "آزمایش قند خون",
    "short_name": "FBS",
    "sample_type": "blood",
    "unit": "mg/dL",
    "min_range": 70,
    "max_range": 100,
    "price": 80000,
    "requires_fasting": true,
    "fasting_hours": 8
  }'

# بروزرسانی تست
curl -X PUT http://localhost:8210/api/admin/lab/tests/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "price": 90000
  }'

# تغییر وضعیت تست
curl -X POST http://localhost:8210/api/admin/lab/tests/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"

# حذف تست
curl -X DELETE http://localhost:8210/api/admin/lab/tests/1 \
  -H "Authorization: Bearer $TOKEN"

۲۴.۱۷ مدیریت دسته‌بندی‌ها (ادمین)
# ایجاد دسته‌بندی جدید
curl -X POST http://localhost:8210/api/admin/lab/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "آزمایش هورمون",
    "icon": "🧬",
    "is_active": true
  }'

# بروزرسانی دسته‌بندی
curl -X PUT http://localhost:8210/api/admin/lab/categories/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "بیوشیمی بالینی"
  }'

# حذف دسته‌بندی
curl -X DELETE http://localhost:8210/api/admin/lab/categories/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۲۵. سیستم بستری (Hospitalization)
==================================================
==================================================

۲۵.۱ مدیریت بخش‌ها (Wards)
# لیست بخش‌ها
curl -X GET http://localhost:8210/api/hospital/wards \
  -H "Authorization: Bearer $TOKEN"

# لیست بخش‌های فعال
curl -X GET http://localhost:8210/api/hospital/wards/active \
  -H "Authorization: Bearer $TOKEN"

# مشاهده بخش
curl -X GET http://localhost:8210/api/hospital/wards/1 \
  -H "Authorization: Bearer $TOKEN"

# آمار بخش
curl -X GET http://localhost:8210/api/hospital/wards/1/stats \
  -H "Authorization: Bearer $TOKEN"

# ایجاد بخش (ادمین)
curl -X POST http://localhost:8210/api/admin/hospital/wards \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "بخش قلب",
    "type": "ccu",
    "floor": 2,
    "capacity": 10,
    "is_active": true
  }'

# بروزرسانی بخش (ادمین)
curl -X PUT http://localhost:8210/api/admin/hospital/wards/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "capacity": 15
  }'

# حذف بخش (ادمین)
curl -X DELETE http://localhost:8210/api/admin/hospital/wards/1 \
  -H "Authorization: Bearer $TOKEN"

۲۵.۲ مدیریت تخت‌ها (Beds)
# لیست تخت‌ها
curl -X GET http://localhost:8210/api/hospital/beds \
  -H "Authorization: Bearer $TOKEN"

# مشاهده تخت
curl -X GET http://localhost:8210/api/hospital/beds/1 \
  -H "Authorization: Bearer $TOKEN"

# ایجاد تخت (ادمین)
curl -X POST http://localhost:8210/api/admin/hospital/beds \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ward_id": 1,
    "bed_number": "A12",
    "is_private": false,
    "price_per_day": 1000000,
    "is_active": true
  }'

# بروزرسانی تخت (ادمین)
curl -X PUT http://localhost:8210/api/admin/hospital/beds/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "price_per_day": 1200000
  }'

# تغییر وضعیت تخت (ادمین)
curl -X POST http://localhost:8210/api/admin/hospital/beds/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "occupied"
  }'

# حذف تخت (ادمین)
curl -X DELETE http://localhost:8210/api/admin/hospital/beds/1 \
  -H "Authorization: Bearer $TOKEN"

۲۵.۳ مدیریت پذیرش (Admissions)
# لیست پذیرش‌ها
curl -X GET http://localhost:8210/api/hospital/admissions \
  -H "Authorization: Bearer $TOKEN"

# ثبت پذیرش جدید
curl -X POST http://localhost:8210/api/hospital/admissions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "doctor_id": 1,
    "ward_id": 3,
    "bed_id": 21,
    "diagnosis": "درد قفسه سینه",
    "chief_complaint": "درد شدید قفسه سینه از دیروز",
    "emergency_contact": "همسر",
    "emergency_phone": "09123456789"
  }'

# مشاهده پذیرش
curl -X GET http://localhost:8210/api/hospital/admissions/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی پذیرش
curl -X PUT http://localhost:8210/api/hospital/admissions/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "diagnosis": "آنژین صدری"
  }'

# پذیرش بیمار (تغییر وضعیت به admitted)
curl -X POST http://localhost:8210/api/hospital/admissions/1/admit \
  -H "Authorization: Bearer $TOKEN"

# پذیرش‌های من (بیمار)
curl -X GET http://localhost:8210/api/hospital/my/admissions \
  -H "Authorization: Bearer $TOKEN"

# پذیرش‌های پزشک
curl -X GET http://localhost:8210/api/hospital/doctor/admissions \
  -H "Authorization: Bearer $TOKEN"

۲۵.۴ مدیریت ترخیص (Discharge)
# ترخیص بیمار
curl -X POST http://localhost:8210/api/hospital/admissions/1/discharge \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "final_diagnosis": "آنژین صدری",
    "summary": "بیمار با درمان بهبود یافت",
    "medications_at_discharge": "آسپرین 80mg روزانه",
    "follow_up_instructions": "مراجعه مجدد در 7 روز آینده",
    "follow_up_date": "2026-07-07"
  }'

۲۵.۵ ثبت روزانه (Vital Signs)
# ثبت علائم حیاتی روزانه
curl -X POST http://localhost:8210/api/hospital/admission-days \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "admission_id": 1,
    "temperature": 37.2,
    "heart_rate": 72,
    "blood_pressure_systolic": 120,
    "blood_pressure_diastolic": 80,
    "oxygen_saturation": 98,
    "pain_score": 3
  }'

# دریافت علائم حیاتی روزانه
curl -X GET http://localhost:8210/api/hospital/admissions/1/days \
  -H "Authorization: Bearer $TOKEN"

۲۵.۶ خدمات و داروهای بستری
# ثبت خدمت بستری
curl -X POST http://localhost:8210/api/hospital/services \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "admission_id": 1,
    "service_name": "اکوکاردیوگرافی",
    "type": "paraclinical",
    "unit_price": 1500000,
    "quantity": 1
  }'

# ثبت داروی بستری
curl -X POST http://localhost:8210/api/hospital/drugs \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "admission_id": 1,
    "drug_name": "آسپرین",
    "dosage": "80mg",
    "frequency": 1,
    "route": "oral",
    "unit_price": 5000,
    "quantity": 30
  }'

۲۵.۷ آمار بستری
# آمار کلی بستری
curl -X GET http://localhost:8210/api/hospital/stats \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۲۶. سیستم فرم‌های دیجیتال (Digital Forms)
==================================================
==================================================

۲۶.۱ فرم‌های عمومی (بدون احراز هویت)
# مشاهده فرم عمومی
curl -X GET http://localhost:8210/api/forms/public/consent-form

# ثبت فرم عمومی
curl -X POST http://localhost:8210/api/forms/public/consent-form/submit \
  -H "Content-Type: application/json" \
  -d '{
    "response_data": {
      "full_name": "رضا کریمی",
      "national_code": "1234567890",
      "phone": "09123456789",
      "treatment_description": "جراحی زانو",
      "risks": ["من از خطرات و عوارض احتمالی درمان آگاه هستم"],
      "consent": ["من با انجام درمان موافقت می‌کنم"]
    }
  }'

۲۶.۲ مدیریت فرم‌ها (ادمین)
# لیست فرم‌ها
curl -X GET http://localhost:8210/api/forms \
  -H "Authorization: Bearer $TOKEN"

# لیست فرم‌های منتشر شده
curl -X GET http://localhost:8210/api/forms/published \
  -H "Authorization: Bearer $TOKEN"

# ایجاد فرم جدید
curl -X POST http://localhost:8210/api/forms \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "فرم رضایت‌نامه",
    "category": "consent",
    "fields": [
      {
        "label": "نام و نام خانوادگی",
        "type": "text",
        "required": true
      },
      {
        "label": "کد ملی",
        "type": "text",
        "required": true
      },
      {
        "label": "رضایت",
        "type": "checkbox",
        "required": true,
        "options": ["من با انجام درمان موافقت می‌کنم"]
      }
    ],
    "settings": {
      "confirmation_message": "فرم با موفقیت ثبت شد"
    }
  }'

# مشاهده فرم
curl -X GET http://localhost:8210/api/forms/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی فرم
curl -X PUT http://localhost:8210/api/forms/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "فرم رضایت‌نامه جدید"
  }'

# انتشار فرم
curl -X POST http://localhost:8210/api/forms/1/publish \
  -H "Authorization: Bearer $TOKEN"

# بایگانی فرم
curl -X POST http://localhost:8210/api/forms/1/archive \
  -H "Authorization: Bearer $TOKEN"

# کپی فرم
curl -X POST http://localhost:8210/api/forms/1/duplicate \
  -H "Authorization: Bearer $TOKEN"

# حذف فرم
curl -X DELETE http://localhost:8210/api/forms/1 \
  -H "Authorization: Bearer $TOKEN"

۲۶.۳ مدیریت پاسخ‌ها
# ثبت پاسخ (با احراز هویت)
curl -X POST http://localhost:8210/api/forms/responses \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "digital_form_id": 1,
    "response_data": {
      "full_name": "رضا کریمی",
      "national_code": "1234567890"
    }
  }'

# لیست پاسخ‌ها
curl -X GET http://localhost:8210/api/forms/responses \
  -H "Authorization: Bearer $TOKEN"

# پاسخ‌های من (بیمار)
curl -X GET http://localhost:8210/api/forms/my/responses \
  -H "Authorization: Bearer $TOKEN"

# مشاهده پاسخ
curl -X GET http://localhost:8210/api/forms/responses/1 \
  -H "Authorization: Bearer $TOKEN"

# تکمیل پاسخ
curl -X POST http://localhost:8210/api/forms/responses/1/complete \
  -H "Authorization: Bearer $TOKEN"

# حذف پاسخ
curl -X DELETE http://localhost:8210/api/forms/responses/1 \
  -H "Authorization: Bearer $TOKEN"

۲۶.۴ مدیریت امضاها
# لیست امضاها
curl -X GET http://localhost:8210/api/forms/signatures \
  -H "Authorization: Bearer $TOKEN"

# حذف امضا
curl -X DELETE http://localhost:8210/api/forms/signatures/1 \
  -H "Authorization: Bearer $TOKEN"

۲۶.۵ دسته‌بندی‌ها و آمار
# لیست دسته‌بندی‌ها
curl -X GET http://localhost:8210/api/forms/categories \
  -H "Authorization: Bearer $TOKEN"

# آمار فرم‌ها
curl -X GET http://localhost:8210/api/forms/stats \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۲۷. سیستم هوش تجاری (BI) و داشبورد مدیریتی
==================================================
==================================================

۲۷.۱ داشبورد مدیریتی
# آمار کلی
curl -X GET http://localhost:8210/api/dashboard/management/stats \
  -H "Authorization: Bearer $TOKEN"

# داده‌های نمودارها
curl -X GET "http://localhost:8210/api/dashboard/management/charts?days=30" \
  -H "Authorization: Bearer $TOKEN"

# آمار سریع (ویجت‌ها)
curl -X GET http://localhost:8210/api/dashboard/management/quick-stats \
  -H "Authorization: Bearer $TOKEN"

# فعالیت‌های اخیر
curl -X GET "http://localhost:8210/api/dashboard/management/recent-activities?limit=10" \
  -H "Authorization: Bearer $TOKEN"

# پزشکان برتر
curl -X GET "http://localhost:8210/api/dashboard/management/top-doctors?limit=5" \
  -H "Authorization: Bearer $TOKEN"

# خلاصه عملکرد
curl -X GET http://localhost:8210/api/dashboard/management/summary \
  -H "Authorization: Bearer $TOKEN"

۲۷.۲ هوش تجاری (BI) - پیش‌بینی‌ها
# پیش‌بینی نوبت‌ها
curl -X GET "http://localhost:8210/api/bi/predict/appointments?days=30&doctor_id=1" \
  -H "Authorization: Bearer $TOKEN"

# پیش‌بینی درآمد
curl -X GET "http://localhost:8210/api/bi/forecast/revenue?months=6" \
  -H "Authorization: Bearer $TOKEN"

# بخش‌بندی بیماران
curl -X GET http://localhost:8210/api/bi/segment/patients \
  -H "Authorization: Bearer $TOKEN"

# تحلیل عملکرد پزشکان
curl -X GET "http://localhost:8210/api/bi/analyze/doctors?period=30" \
  -H "Authorization: Bearer $TOKEN"

# دریافت تحلیل‌های ذخیره شده
curl -X GET "http://localhost:8210/api/bi/analytics?type=appointment_prediction&limit=10" \
  -H "Authorization: Bearer $TOKEN"

۲۷.۳ گزارش‌های سفارشی
# لیست گزارش‌ها
curl -X GET http://localhost:8210/api/bi/reports \
  -H "Authorization: Bearer $TOKEN"

# ایجاد گزارش جدید
curl -X POST http://localhost:8210/api/bi/reports \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "گزارش عملکرد پزشکان",
    "type": "custom",
    "config": {
      "source": "doctors",
      "metrics": ["appointments", "revenue", "rating"]
    },
    "columns": ["doctor", "appointments", "revenue", "rating"],
    "chart_type": "bar",
    "is_public": true
  }'

# بروزرسانی گزارش
curl -X PUT http://localhost:8210/api/bi/reports/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "گزارش جدید"
  }'

# تولید گزارش (PDF/Excel/CSV)
curl -X POST http://localhost:8210/api/bi/reports/1/generate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "pdf",
    "filters": {
      "from_date": "2026-06-01",
      "to_date": "2026-06-30"
    }
  }' \
  --output report.pdf

# حذف گزارش
curl -X DELETE http://localhost:8210/api/bi/reports/1 \
  -H "Authorization: Bearer $TOKEN"

۲۷.۴ زمان‌بندی گزارش‌ها
# ایجاد زمان‌بندی
curl -X POST http://localhost:8210/api/bi/schedules \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bi_report_id": 1,
    "frequency": "daily",
    "recipients": ["admin@clinic.com", "manager@clinic.com"],
    "format": "pdf",
    "is_active": true
  }'

# بروزرسانی زمان‌بندی
curl -X PUT http://localhost:8210/api/bi/schedules/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "frequency": "weekly"
  }'

# حذف زمان‌بندی
curl -X DELETE http://localhost:8210/api/bi/schedules/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۲۸. سیستم بک‌آپ و لاگ استوریج
==================================================
==================================================

۲۸.۱ مدیریت بک‌آپ
# گرفتن بک‌آپ از دیتابیس
curl -X POST http://localhost:8210/api/bi/backup/database \
  -H "Authorization: Bearer $TOKEN"

# گرفتن بک‌آپ از فایل‌ها
curl -X POST http://localhost:8210/api/bi/backup/files \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "paths": ["/path/to/important/files"]
  }'

# لیست بک‌آپ‌ها
curl -X GET http://localhost:8210/api/bi/backup/history \
  -H "Authorization: Bearer $TOKEN"

# بازیابی بک‌آپ
curl -X POST http://localhost:8210/api/bi/backup/1/restore \
  -H "Authorization: Bearer $TOKEN"

# پاکسازی بک‌آپ‌های قدیمی
curl -X DELETE "http://localhost:8210/api/bi/backup/cleanup?days=30" \
  -H "Authorization: Bearer $TOKEN"

۲۸.۲ مدیریت لاگ‌ها
# مشاهده لاگ‌های حسابرسی
curl -X GET "http://localhost:8210/api/bi/audit-logs?limit=50&user_id=1" \
  -H "Authorization: Bearer $TOKEN"

# ثبت لاگ دستی
curl -X POST http://localhost:8210/api/bi/audit-logs \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "custom_event",
    "model_type": "App\\Models\\User",
    "model_id": 1,
    "metadata": {"action": "manual_log"}
  }'

# آرشیو لاگ‌ها
curl -X POST "http://localhost:8210/api/bi/logs/archive?type=laravel" \
  -H "Authorization: Bearer $TOKEN"

# لیست لاگ‌های آرشیو شده
curl -X GET "http://localhost:8210/api/bi/logs/archived?type=laravel" \
  -H "Authorization: Bearer $TOKEN"

# بازیابی لاگ آرشیو شده
curl -X POST http://localhost:8210/api/bi/logs/archived/1/restore \
  -H "Authorization: Bearer $TOKEN"

# پاکسازی لاگ‌های آرشیو قدیمی
curl -X DELETE "http://localhost:8210/api/bi/logs/archived/cleanup?days=90" \
  -H "Authorization: Bearer $TOKEN"

# آمار کلی سیستم
curl -X GET http://localhost:8210/api/bi/stats \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۲۹. سیستم چندزبانه (Multi-Language)
==================================================
==================================================

۲۹.۱ مدیریت زبان
# تغییر زبان
curl -X POST http://localhost:8210/api/language/switch \
  -H "Content-Type: application/json" \
  -d '{
    "locale": "en"
  }'
# زبان‌های پشتیبانی شده: fa (فارسی), en (انگلیسی), ar (عربی)

# دریافت زبان فعلی
curl -X GET http://localhost:8210/api/language/current

# دریافت ترجمه‌ها
curl -X GET http://localhost:8210/api/language/translations


==================================================
==================================================
۳۰. سیستم مدیریت تصاویر پزشکی (PACS)
==================================================
==================================================

۳۰.۱ مدیریت تصاویر
# آپلود تصویر پزشکی
curl -X POST http://localhost:8210/api/pacs/upload \
  -H "Authorization: Bearer $TOKEN" \
  -F "patient_id=1" \
  -F "image_type=xray" \
  -F "modality=DX" \
  -F "body_part=chest" \
  -F "description=رادیوگرافی قفسه سینه" \
  -F "image=@/path/to/image.dcm"

# دریافت تصاویر بیمار
curl -X GET "http://localhost:8210/api/pacs/patients/1/images?image_type=xray&modality=DX" \
  -H "Authorization: Bearer $TOKEN"

# مشاهده تصویر
curl -X GET http://localhost:8210/api/pacs/images/1 \
  -H "Authorization: Bearer $TOKEN"

# دانلود تصویر
curl -X GET http://localhost:8210/api/pacs/images/1/download \
  -H "Authorization: Bearer $TOKEN" \
  --output image.dcm

# آمار تصاویر بیمار
curl -X GET http://localhost:8210/api/pacs/patients/1/stats \
  -H "Authorization: Bearer $TOKEN"

# حذف تصویر
curl -X DELETE http://localhost:8210/api/pacs/images/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۱. سیستم اتاق عمل (OR Management)
==================================================
==================================================

۳۱.۱ مدیریت اتاق‌های عمل
# لیست اتاق‌های عمل
curl -X GET http://localhost:8210/api/or/rooms \
  -H "Authorization: Bearer $TOKEN"

# ایجاد اتاق عمل جدید (ادمین)
curl -X POST http://localhost:8210/api/or/rooms \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "اتاق عمل ۱",
    "type": "general",
    "floor": 3,
    "capacity": 1,
    "equipment": ["ventilator", "monitor", "anesthesia_machine"],
    "is_active": true
  }'

# مشاهده اتاق عمل
curl -X GET http://localhost:8210/api/or/rooms/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی اتاق عمل (ادمین)
curl -X PUT http://localhost:8210/api/or/rooms/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "اتاق عمل VIP"
  }'

# حذف اتاق عمل (ادمین)
curl -X DELETE http://localhost:8210/api/or/rooms/1 \
  -H "Authorization: Bearer $TOKEN"

۳۱.۲ مدیریت زمان‌بندی جراحی
# لیست زمان‌بندی‌ها
curl -X GET "http://localhost:8210/api/or/schedules?date=2026-07-01" \
  -H "Authorization: Bearer $TOKEN"

# ایجاد زمان‌بندی جدید
curl -X POST http://localhost:8210/api/or/schedules \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "operation_room_id": 1,
    "patient_id": 1,
    "doctor_id": 1,
    "surgeon_id": 1,
    "anesthesiologist_id": 2,
    "surgery_type": "appendectomy",
    "diagnosis": "آپاندیسیت حاد",
    "priority": "urgent",
    "scheduled_date": "2026-07-01",
    "scheduled_time": "10:00",
    "estimated_duration": 60
  }'

# مشاهده زمان‌بندی
curl -X GET http://localhost:8210/api/or/schedules/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی زمان‌بندی
curl -X PUT http://localhost:8210/api/or/schedules/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "scheduled_time": "14:00"
  }'

# تغییر وضعیت جراحی
curl -X POST http://localhost:8210/api/or/schedules/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress"
  }'

# حذف زمان‌بندی
curl -X DELETE http://localhost:8210/api/or/schedules/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۲. سیستم اورژانس (Emergency)
==================================================
==================================================

۳۲.۱ مدیریت بیماران اورژانسی
# ثبت بیمار اورژانسی
curl -X POST http://localhost:8210/api/emergency/register \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "triage_level": "red",
    "arrival_time": "2026-07-01 09:30:00",
    "chief_complaint": "درد شدید قفسه سینه",
    "history_of_present_illness": "درد از ۲ ساعت پیش شروع شده",
    "vital_signs": {
      "blood_pressure": "160/95",
      "heart_rate": 110,
      "respiratory_rate": 22,
      "temperature": 37.5,
      "oxygen_saturation": 94
    },
    "allergies": "آسپرین",
    "medications": "لوزارتان",
    "past_medical_history": "فشار خون بالا"
  }'

# لیست انتظار اورژانس
curl -X GET http://localhost:8210/api/emergency/waiting-list \
  -H "Authorization: Bearer $TOKEN"

# مشاهده بیمار اورژانسی
curl -X GET http://localhost:8210/api/emergency/patients/1 \
  -H "Authorization: Bearer $TOKEN"

# تغییر وضعیت بیمار
curl -X POST http://localhost:8210/api/emergency/patients/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_treatment"
  }'

# تعیین تکلیف بیمار
curl -X POST http://localhost:8210/api/emergency/patients/1/disposition \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "disposition": "admitted"
  }'

# آمار اورژانس
curl -X GET http://localhost:8210/api/emergency/stats \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۳. سیستم ربات بله (Bale Bot)
==================================================
==================================================

۳۳.۱ مدیریت ربات
# Webhook ربات (عمومی - بدون احراز هویت)
curl -X POST http://localhost:8210/api/bale/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "chat": {"id": "123456"},
      "text": "/start"
    }
  }'

# آمار ربات (ادمین)
curl -X GET http://localhost:8210/api/bale/stats \
  -H "Authorization: Bearer $TOKEN"

# ارسال پیام به کاربر (ادمین)
curl -X POST http://localhost:8210/api/bale/send \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "title": "یادآوری نوبت",
    "body": "فردا ساعت ۱۰ نوبت دارید"
  }'


==================================================
==================================================
۳۴. سیستم نقش‌ها و مجوزها (Roles & Permissions)
==================================================
==================================================

۳۴.۱ مدیریت نقش‌ها (ادمین)
# لیست نقش‌ها
curl -X GET http://localhost:8210/api/admin/roles \
  -H "Authorization: Bearer $TOKEN"

# ایجاد نقش جدید
curl -X POST http://localhost:8210/api/admin/roles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "supervisor",
    "permissions": ["view_doctors", "view_patients", "view_appointments"]
  }'

# مشاهده نقش
curl -X GET http://localhost:8210/api/admin/roles/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی نقش
curl -X PUT http://localhost:8210/api/admin/roles/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "مدیر ارشد"
  }'

# حذف نقش
curl -X DELETE http://localhost:8210/api/admin/roles/1 \
  -H "Authorization: Bearer $TOKEN"

۳۴.۲ مدیریت مجوزها (ادمین)
# لیست مجوزها
curl -X GET http://localhost:8210/api/admin/permissions \
  -H "Authorization: Bearer $TOKEN"

# ایجاد مجوز جدید
curl -X POST http://localhost:8210/api/admin/permissions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "manage_lab_results"
  }'

# بروزرسانی مجوز
curl -X PUT http://localhost:8210/api/admin/permissions/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "view_lab_results"
  }'

# حذف مجوز
curl -X DELETE http://localhost:8210/api/admin/permissions/1 \
  -H "Authorization: Bearer $TOKEN"

# اختصاص مجوز به نقش
curl -X POST http://localhost:8210/api/admin/permissions/assign-to-role \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "permission": "manage_lab_results",
    "role": "lab_technician"
  }'


==================================================
==================================================
۳۵. سیستم مدیریت کاربران (Users)
==================================================
==================================================

۳۵.۱ مدیریت کاربران (ادمین)
# لیست کاربران
curl -X GET http://localhost:8210/api/admin/users \
  -H "Authorization: Bearer $TOKEN"

# ایجاد کاربر جدید
curl -X POST http://localhost:8210/api/admin/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "محمد رضایی",
    "mobile": "09123456780",
    "email": "mohammad@clinic.com",
    "password": "password123",
    "role": "doctor"
  }'

# مشاهده کاربر
curl -X GET http://localhost:8210/api/admin/users/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی کاربر
curl -X PUT http://localhost:8210/api/admin/users/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "دکتر محمد رضایی",
    "is_active": true
  }'

# تغییر وضعیت کاربر
curl -X POST http://localhost:8210/api/admin/users/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"

# اختصاص نقش به کاربر
curl -X POST http://localhost:8210/api/admin/users/1/assign-role \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "supervisor"
  }'

# حذف کاربر
curl -X DELETE http://localhost:8210/api/admin/users/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۶. سیستم مدیریت داروخانه (Pharmacy Management)
==================================================
==================================================

۳۶.۱ مدیریت داروخانه‌ها (ادمین)
# لیست داروخانه‌ها
curl -X GET http://localhost:8210/api/admin/pharmacies \
  -H "Authorization: Bearer $TOKEN"

# ایجاد داروخانه جدید
curl -X POST http://localhost:8210/api/admin/pharmacies \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "داروخانه مرکزی",
    "license_number": "PH-123456",
    "address": "تهران، خیابان انقلاب",
    "phone": "021-88888888",
    "is_active": true,
    "is_online": true
  }'

# مشاهده داروخانه
curl -X GET http://localhost:8210/api/admin/pharmacies/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی داروخانه
curl -X PUT http://localhost:8210/api/admin/pharmacies/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "داروخانه مرکزی ۲"
  }'

# تغییر وضعیت داروخانه
curl -X POST http://localhost:8210/api/admin/pharmacies/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"

# تغییر وضعیت فروش آنلاین
curl -X POST http://localhost:8210/api/admin/pharmacies/1/toggle-online \
  -H "Authorization: Bearer $TOKEN"

# حذف داروخانه
curl -X DELETE http://localhost:8210/api/admin/pharmacies/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۷. سیستم مدیریت بیمه (Insurance)
==================================================
==================================================

۳۷.۱ مدیریت بیمه‌ها (ادمین)
# لیست بیمه‌ها
curl -X GET http://localhost:8210/api/insurance \
  -H "Authorization: Bearer $TOKEN"

# لیست بیمه‌های فعال
curl -X GET http://localhost:8210/api/insurance/active \
  -H "Authorization: Bearer $TOKEN"

# ایجاد بیمه جدید
curl -X POST http://localhost:8210/api/insurance \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "بیمه سلامت",
    "code": "INS-001",
    "coverage_percentage": 70,
    "max_coverage_per_year": 100000000,
    "max_coverage_per_visit": 5000000,
    "is_active": true
  }'

# مشاهده بیمه
curl -X GET http://localhost:8210/api/insurance/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی بیمه
curl -X PUT http://localhost:8210/api/insurance/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "coverage_percentage": 80
  }'

# تغییر وضعیت بیمه
curl -X POST http://localhost:8210/api/insurance/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"

# حذف بیمه
curl -X DELETE http://localhost:8210/api/insurance/1 \
  -H "Authorization: Bearer $TOKEN"

۳۷.۲ مدیریت بیمه بیماران
# اختصاص بیمه به بیمار
curl -X POST http://localhost:8210/api/insurance/assign-to-patient \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "insurance_id": 1,
    "policy_number": "POL-123456",
    "is_primary": true
  }'

# لیست بیمه‌های بیمار
curl -X GET http://localhost:8210/api/insurance/patients/1/insurances \
  -H "Authorization: Bearer $TOKEN"

# بیمه اصلی بیمار
curl -X GET http://localhost:8210/api/insurance/patients/1/primary \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی بیمه بیمار
curl -X PUT http://localhost:8210/api/insurance/patient-insurances/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "is_primary": true
  }'

# غیرفعال کردن بیمه بیمار
curl -X POST http://localhost:8210/api/insurance/patient-insurances/1/deactivate \
  -H "Authorization: Bearer $TOKEN"

۳۷.۳ مدیریت بیمه نوبت
# اعمال بیمه به نوبت
curl -X POST http://localhost:8210/api/insurance/apply-to-appointment \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "appointment_id": 1,
    "patient_insurance_id": 1
  }'

# مشاهده بیمه نوبت
curl -X GET http://localhost:8210/api/insurance/appointments/1 \
  -H "Authorization: Bearer $TOKEN"

# تایید درخواست بیمه
curl -X POST http://localhost:8210/api/insurance/claims/1/approve \
  -H "Authorization: Bearer $TOKEN"

# رد درخواست بیمه
curl -X POST http://localhost:8210/api/insurance/claims/1/reject \
  -H "Authorization: Bearer $TOKEN"

۳۷.۴ آمار و گزارش بیمه
# آمار بیمه
curl -X GET http://localhost:8210/api/insurance/stats \
  -H "Authorization: Bearer $TOKEN"

# گزارش بیمه
curl -X GET "http://localhost:8210/api/insurance/reports/1?from_date=2026-06-01&to_date=2026-06-30" \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۸. سیستم پزشکی از راه دور (Telemedicine)
==================================================
==================================================

۳۸.۱ مدیریت جلسات
# ایجاد جلسه جدید
curl -X POST http://localhost:8210/api/telemedicine/sessions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "appointment_id": 1,
    "notes": "ویزیت آنلاین"
  }'

# لیست جلسات
curl -X GET http://localhost:8210/api/telemedicine/sessions \
  -H "Authorization: Bearer $TOKEN"

# مشاهده جلسه
curl -X GET http://localhost:8210/api/telemedicine/sessions/1 \
  -H "Authorization: Bearer $TOKEN"

# مشاهده جلسه با نام اتاق
curl -X GET http://localhost:8210/api/telemedicine/sessions/room/room_name \
  -H "Authorization: Bearer $TOKEN"

# جلسات پزشک
curl -X GET http://localhost:8210/api/telemedicine/doctors/1/sessions \
  -H "Authorization: Bearer $TOKEN"

# جلسات بیمار
curl -X GET http://localhost:8210/api/telemedicine/patients/1/sessions \
  -H "Authorization: Bearer $TOKEN"

# جلسات فعال پزشک
curl -X GET http://localhost:8210/api/telemedicine/doctors/1/active \
  -H "Authorization: Bearer $TOKEN"

# شروع جلسه
curl -X POST http://localhost:8210/api/telemedicine/sessions/1/start \
  -H "Authorization: Bearer $TOKEN"

# تکمیل جلسه
curl -X POST http://localhost:8210/api/telemedicine/sessions/1/complete \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "ویزیت با موفقیت انجام شد",
    "diagnosis": "آنفولانزا",
    "prescription": "استامینوفن 500mg"
  }'

# لغو جلسه
curl -X POST http://localhost:8210/api/telemedicine/sessions/1/cancel \
  -H "Authorization: Bearer $TOKEN"

# پیوستن به جلسه
curl -X POST http://localhost:8210/api/telemedicine/sessions/1/join \
  -H "Authorization: Bearer $TOKEN"

۳۸.۲ مدیریت پیام‌ها
# ارسال پیام
curl -X POST http://localhost:8210/api/telemedicine/messages \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "session_id": 1,
    "message": "سلام، آماده هستم"
  }'

# دریافت پیام‌ها
curl -X GET http://localhost:8210/api/telemedicine/sessions/1/messages \
  -H "Authorization: Bearer $TOKEN"

# تعداد پیام‌های خوانده نشده
curl -X GET http://localhost:8210/api/telemedicine/sessions/1/unread-count \
  -H "Authorization: Bearer $TOKEN"

۳۸.۳ مدیریت فایل‌ها
# آپلود فایل
curl -X POST http://localhost:8210/api/telemedicine/files \
  -H "Authorization: Bearer $TOKEN" \
  -F "session_id=1" \
  -F "description=نتیجه آزمایش" \
  -F "file=@/path/to/file.pdf"

# دریافت فایل‌ها
curl -X GET http://localhost:8210/api/telemedicine/sessions/1/files \
  -H "Authorization: Bearer $TOKEN"

# حذف فایل
curl -X DELETE http://localhost:8210/api/telemedicine/files/1 \
  -H "Authorization: Bearer $TOKEN"

۳۸.۴ آمار
# آمار پزشکی از راه دور
curl -X GET http://localhost:8210/api/telemedicine/stats \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۳۹. سیستم واکسیناسیون (Vaccination)
==================================================
==================================================

۳۹.۱ مدیریت واکسن‌ها (ادمین)
# لیست واکسن‌ها
curl -X GET http://localhost:8210/api/vaccination \
  -H "Authorization: Bearer $TOKEN"

# لیست واکسن‌های فعال
curl -X GET http://localhost:8210/api/vaccination/active \
  -H "Authorization: Bearer $TOKEN"

# ایجاد واکسن جدید
curl -X POST http://localhost:8210/api/vaccination \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "واکسن آنفولانزا",
    "manufacturer": "داروسازی رازی",
    "disease": "آنفولانزا",
    "doses_required": 1,
    "is_active": true,
    "is_required": false
  }'

# مشاهده واکسن
curl -X GET http://localhost:8210/api/vaccination/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی واکسن
curl -X PUT http://localhost:8210/api/vaccination/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "doses_required": 2
  }'

# تغییر وضعیت واکسن
curl -X POST http://localhost:8210/api/vaccination/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"

# حذف واکسن
curl -X DELETE http://localhost:8210/api/vaccination/1 \
  -H "Authorization: Bearer $TOKEN"

۳۹.۲ ثبت واکسیناسیون بیمار
# ثبت واکسیناسیون
curl -X POST http://localhost:8210/api/vaccination/record \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "vaccine_id": 1,
    "dose_number": 1,
    "administration_date": "2026-07-01",
    "batch_number": "BATCH-123",
    "administration_site": "بازوی چپ"
  }'

# لیست واکسیناسیون‌های بیمار
curl -X GET http://localhost:8210/api/vaccination/patients/1/vaccinations \
  -H "Authorization: Bearer $TOKEN"

# خلاصه واکسیناسیون‌های بیمار
curl -X GET http://localhost:8210/api/vaccination/patients/1/summary \
  -H "Authorization: Bearer $TOKEN"

# واکسیناسیون‌های آینده بیمار
curl -X GET http://localhost:8210/api/vaccination/patients/1/upcoming \
  -H "Authorization: Bearer $TOKEN"

# واکسیناسیون‌های عقب افتاده بیمار
curl -X GET http://localhost:8210/api/vaccination/patients/1/overdue \
  -H "Authorization: Bearer $TOKEN"

۳۹.۳ مدیریت یادآوری‌ها
# لیست یادآوری‌های بیمار
curl -X GET "http://localhost:8210/api/vaccination/patients/1/reminders?status=pending" \
  -H "Authorization: Bearer $TOKEN"

# پردازش یادآوری‌ها (ادمین)
curl -X POST http://localhost:8210/api/vaccination/reminders/process \
  -H "Authorization: Bearer $TOKEN"

۳۹.۴ آمار
# آمار واکسیناسیون
curl -X GET "http://localhost:8210/api/vaccination/stats?from_date=2026-06-01&to_date=2026-06-30" \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۴۰. سیستم نظرسنجی (Survey)
==================================================
==================================================

۴۰.۱ مدیریت نظرسنجی‌ها (ادمین)
# لیست نظرسنجی‌ها
curl -X GET http://localhost:8210/api/survey \
  -H "Authorization: Bearer $TOKEN"

# نظرسنجی‌های فعال
curl -X GET http://localhost:8210/api/survey/available \
  -H "Authorization: Bearer $TOKEN"

# ایجاد نظرسنجی جدید
curl -X POST http://localhost:8210/api/survey \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "نظرسنجی رضایت بیماران",
    "type": "appointment",
    "questions": [
      {
        "text": "چقدر از خدمات راضی هستید؟",
        "type": "rating",
        "required": true
      },
      {
        "text": "نظرات خود را بنویسید",
        "type": "text",
        "required": false
      }
    ],
    "is_active": true
  }'

# مشاهده نظرسنجی
curl -X GET http://localhost:8210/api/survey/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی نظرسنجی
curl -X PUT http://localhost:8210/api/survey/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "نظرسنجی جدید"
  }'

# تغییر وضعیت نظرسنجی
curl -X POST http://localhost:8210/api/survey/1/toggle-status \
  -H "Authorization: Bearer $TOKEN"

# حذف نظرسنجی
curl -X DELETE http://localhost:8210/api/survey/1 \
  -H "Authorization: Bearer $TOKEN"

۴۰.۲ ثبت پاسخ نظرسنجی
# ثبت پاسخ
curl -X POST http://localhost:8210/api/survey/submit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "survey_id": 1,
    "patient_id": 1,
    "answers": {
      "1": 5,
      "2": "خدمات عالی بود"
    }
  }'

# پاسخ‌های یک نظرسنجی
curl -X GET "http://localhost:8210/api/survey/1/responses?from_date=2026-06-01" \
  -H "Authorization: Bearer $TOKEN"

# پاسخ‌های من (بیمار)
curl -X GET http://localhost:8210/api/survey/patients/1/responses \
  -H "Authorization: Bearer $TOKEN"

۴۰.۳ مدیریت بازخوردها
# ثبت بازخورد
curl -X POST http://localhost:8210/api/survey/feedback \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "doctor_id": 1,
    "category": "doctor",
    "rating": 5,
    "comment": "پزشک بسیار عالی بود"
  }'

# لیست بازخوردها
curl -X GET "http://localhost:8210/api/survey/feedbacks?status=pending" \
  -H "Authorization: Bearer $TOKEN"

# بازخوردهای بیمار
curl -X GET http://localhost:8210/api/survey/patients/1/feedbacks \
  -H "Authorization: Bearer $TOKEN"

# بازخوردهای پزشک
curl -X GET http://localhost:8210/api/survey/doctors/1/feedbacks \
  -H "Authorization: Bearer $TOKEN"

# پاسخ به بازخورد (ادمین)
curl -X POST http://localhost:8210/api/survey/feedbacks/1/reply \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reply": "با تشکر از شما"
  }'

# حل بازخورد (ادمین)
curl -X POST http://localhost:8210/api/survey/feedbacks/1/resolve \
  -H "Authorization: Bearer $TOKEN"

۴۰.۴ آمار
# آمار نظرسنجی
curl -X GET "http://localhost:8210/api/survey/stats?from_date=2026-06-01" \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۴۱. سیستم رویدادها (Events)
==================================================
==================================================

۴۱.۱ مدیریت رویدادها (ادمین)
# لیست رویدادها
curl -X GET http://localhost:8210/api/events \
  -H "Authorization: Bearer $TOKEN"

# رویدادهای منتشر شده
curl -X GET http://localhost:8210/api/events/published \
  -H "Authorization: Bearer $TOKEN"

# رویدادهای آینده
curl -X GET http://localhost:8210/api/events/upcoming \
  -H "Authorization: Bearer $TOKEN"

# رویدادهای فعال
curl -X GET http://localhost:8210/api/events/active \
  -H "Authorization: Bearer $TOKEN"

# ایجاد رویداد جدید
curl -X POST http://localhost:8210/api/events \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "همایش پزشکی",
    "slug": "medical-conference",
    "description": "همایش سالانه پزشکی",
    "type": "seminar",
    "start_date": "2026-07-10 09:00:00",
    "end_date": "2026-07-10 17:00:00",
    "location": "تهران، سالن همایش",
    "max_participants": 100,
    "is_free": true
  }'

# مشاهده رویداد
curl -X GET http://localhost:8210/api/events/1 \
  -H "Authorization: Bearer $TOKEN"

# مشاهده رویداد با اسلاگ
curl -X GET http://localhost:8210/api/events/medical-conference/slug \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی رویداد
curl -X PUT http://localhost:8210/api/events/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "همایش بزرگ پزشکی"
  }'

# انتشار رویداد
curl -X POST http://localhost:8210/api/events/1/publish \
  -H "Authorization: Bearer $TOKEN"

# تکمیل رویداد
curl -X POST http://localhost:8210/api/events/1/complete \
  -H "Authorization: Bearer $TOKEN"

# حذف رویداد
curl -X DELETE http://localhost:8210/api/events/1 \
  -H "Authorization: Bearer $TOKEN"

۴۱.۲ ثبت‌نام رویداد
# ثبت‌نام در رویداد
curl -X POST http://localhost:8210/api/events/register \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "event_id": 1,
    "patient_id": 1,
    "notes": "حضور دارم"
  }'

# لیست ثبت‌نام‌های رویداد
curl -X GET http://localhost:8210/api/events/1/registrations \
  -H "Authorization: Bearer $TOKEN"

# تایید ثبت‌نام
curl -X POST http://localhost:8210/api/events/registrations/1/confirm \
  -H "Authorization: Bearer $TOKEN"

# لغو ثبت‌نام
curl -X POST http://localhost:8210/api/events/registrations/1/cancel \
  -H "Authorization: Bearer $TOKEN"

# ثبت حضور (اسکن)
curl -X POST http://localhost:8210/api/events/registrations/1/attendance \
  -H "Authorization: Bearer $TOKEN"

# ثبت‌نام‌های من
curl -X GET http://localhost:8210/api/events/patients/1/registrations \
  -H "Authorization: Bearer $TOKEN"

۴۱.۳ آمار
# آمار رویدادها
curl -X GET http://localhost:8210/api/events/stats/overview \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۴۲. سیستم کمپین‌ها (Campaigns)
==================================================
==================================================

۴۲.۱ مدیریت کمپین‌ها (ادمین)
# لیست کمپین‌ها
curl -X GET http://localhost:8210/api/campaigns \
  -H "Authorization: Bearer $TOKEN"

# کمپین‌های فعال
curl -X GET http://localhost:8210/api/campaigns/active \
  -H "Authorization: Bearer $TOKEN"

# ایجاد کمپین جدید
curl -X POST http://localhost:8210/api/campaigns \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "کمپین سلامت نوروز",
    "description": "کمپین معاینه رایگان",
    "type": "health",
    "start_date": "2026-07-01",
    "end_date": "2026-07-30",
    "target_audience": "patients",
    "channels": ["sms", "email", "push"],
    "is_active": true
  }'

# مشاهده کمپین
curl -X GET http://localhost:8210/api/campaigns/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی کمپین
curl -X PUT http://localhost:8210/api/campaigns/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "کمپین جدید"
  }'

# فعال کردن کمپین
curl -X POST http://localhost:8210/api/campaigns/1/activate \
  -H "Authorization: Bearer $TOKEN"

# توقف کمپین
curl -X POST http://localhost:8210/api/campaigns/1/pause \
  -H "Authorization: Bearer $TOKEN"

# تکمیل کمپین
curl -X POST http://localhost:8210/api/campaigns/1/complete \
  -H "Authorization: Bearer $TOKEN"

# حذف کمپین
curl -X DELETE http://localhost:8210/api/campaigns/1 \
  -H "Authorization: Bearer $TOKEN"

۴۲.۲ ردیابی تعاملات
# ثبت تعامل
curl -X POST http://localhost:8210/api/campaigns/interactions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "campaign_id": 1,
    "patient_id": 1,
    "channel": "sms",
    "action": "sent"
  }'

# تعاملات کمپین
curl -X GET http://localhost:8210/api/campaigns/1/interactions \
  -H "Authorization: Bearer $TOKEN"

۴۲.۳ آمار
# آمار کمپین
curl -X GET http://localhost:8210/api/campaigns/1/stats \
  -H "Authorization: Bearer $TOKEN"

# آمار کلی کمپین‌ها
curl -X GET http://localhost:8210/api/campaigns/stats/overall \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۴۳. سیستم یادداشت پزشکی (Medical Notes)
==================================================
==================================================

۴۳.۱ مدیریت یادداشت‌ها
# لیست یادداشت‌ها
curl -X GET http://localhost:8210/api/medical-notes \
  -H "Authorization: Bearer $TOKEN"

# ایجاد یادداشت جدید
curl -X POST http://localhost:8210/api/medical-notes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "appointment_id": 1,
    "title": "یادداشت ویزیت",
    "content": "بیمار با علائم تب و سرفه مراجعه کرده",
    "type": "general",
    "priority": "normal"
  }'

# مشاهده یادداشت
curl -X GET http://localhost:8210/api/medical-notes/1 \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی یادداشت
curl -X PUT http://localhost:8210/api/medical-notes/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "بیمار با علائم بهبود مراجعه کرده"
  }'

# حذف یادداشت
curl -X DELETE http://localhost:8210/api/medical-notes/1 \
  -H "Authorization: Bearer $TOKEN"

# یادداشت‌های بیمار
curl -X GET http://localhost:8210/api/medical-notes/patients/1 \
  -H "Authorization: Bearer $TOKEN"

# یادداشت‌های پزشک
curl -X GET http://localhost:8210/api/medical-notes/doctors/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
۴۴. سیستم پنل منشی (Receptionist Panel)
==================================================
==================================================

۴۴.۱ مدیریت صف انتظار
# افزودن به صف انتظار
curl -X POST http://localhost:8210/api/receptionist/waiting-list \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": 1,
    "doctor_id": 1,
    "type": "walk_in",
    "notes": "بیمار حضوری مراجعه کرده"
  }'

# لیست صف انتظار
curl -X GET http://localhost:8210/api/receptionist/waiting-list/1 \
  -H "Authorization: Bearer $TOKEN"

# تعداد صف انتظار
curl -X GET http://localhost:8210/api/receptionist/waiting-list/1/count \
  -H "Authorization: Bearer $TOKEN"

# صدا زدن بیمار
curl -X POST http://localhost:8210/api/receptionist/waiting-list/1/call \
  -H "Authorization: Bearer $TOKEN"

# تکمیل ویزیت
curl -X POST http://localhost:8210/api/receptionist/waiting-list/1/complete \
  -H "Authorization: Bearer $TOKEN"

# لغو صف انتظار
curl -X DELETE http://localhost:8210/api/receptionist/waiting-list/1 \
  -H "Authorization: Bearer $TOKEN"

۴۴.۲ مدیریت نوبت تلفنی
# ثبت نوبت تلفنی
curl -X POST http://localhost:8210/api/receptionist/phone-appointments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "doctor_id": 1,
    "patient_name": "علی رضایی",
    "mobile": "09123456789",
    "caller_name": "همسر بیمار",
    "caller_phone": "09123456788",
    "caller_relation": "همسر",
    "appointment_date": "2026-07-01",
    "appointment_time": "10:00",
    "reason": "درد کمر"
  }'

# لیست نوبت‌های تلفنی
curl -X GET http://localhost:8210/api/receptionist/phone-appointments \
  -H "Authorization: Bearer $TOKEN"

# تایید نوبت تلفنی
curl -X POST http://localhost:8210/api/receptionist/phone-appointments/1/confirm \
  -H "Authorization: Bearer $TOKEN"

# لغو نوبت تلفنی
curl -X DELETE http://localhost:8210/api/receptionist/phone-appointments/1 \
  -H "Authorization: Bearer $TOKEN"

۴۴.۳ مدیریت کارت نوبت
# تولید کارت نوبت
curl -X POST http://localhost:8210/api/receptionist/cards/appointment/1 \
  -H "Authorization: Bearer $TOKEN"

# دریافت کارت نوبت
curl -X GET http://localhost:8210/api/receptionist/cards/appointment/1 \
  -H "Authorization: Bearer $TOKEN"

# چاپ کارت نوبت
curl -X POST http://localhost:8210/api/receptionist/cards/1/print \
  -H "Authorization: Bearer $TOKEN"

۴۴.۴ تنظیمات
# دریافت تنظیمات
curl -X GET http://localhost:8210/api/receptionist/settings \
  -H "Authorization: Bearer $TOKEN"

# بروزرسانی تنظیمات
curl -X PUT http://localhost:8210/api/receptionist/settings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "clinic_id": 1,
    "allow_walk_in": true,
    "allow_phone_booking": true,
    "print_appointment_card": true,
    "max_walk_in_per_day": 10
  }'

۴۴.۵ داشبورد منشی
# داشبورد منشی
curl -X GET http://localhost:8210/api/receptionist/dashboard/1 \
  -H "Authorization: Bearer $TOKEN"


==================================================
==================================================
📊 خلاصه دسته‌بندی APIها
==================================================
==================================================

| دسته‌بندی | تعداد API | توضیح |
|-----------|-----------|--------|
| احراز هویت (Auth) | 5 | ورود، خروج، اطلاعات کاربر |
| مدیریت کلینیک | 5 | تنظیمات، لوگو، وضعیت |
| مدیریت تخصص‌ها | 5 | CRUD، آپلود عکس |
| مدیریت پزشکان | 6 | CRUD، تایید، وضعیت |
| مدیریت بیماران | 6 | CRUD، جستجو، تاریخچه |
| مدیریت نوبت‌ها | 8 | رزرو، تایید، لغو |
| مدیریت نسخه‌ها | 5 | CRUD، وضعیت، چاپ |
| سیستم اعلان‌ها | 8 | دریافت، ارسال، مدیریت |
| مدیریت داروها | 6 | CRUD، موجودی |
| زمان‌بندی پزشکان | 4 | تنظیم ساعات کاری |
| سیستم پیام‌رسانی | 4 | چت، مکالمه |
| سیستم ارجاع | 4 | CRUD، پذیرش |
| سیستم امتیازدهی | 3 | ثبت، دریافت، پاسخ |
| سیستم گزارش‌گیری | 5 | Excel، PDF، آمار |
| داشبورد | 3 | ادمین، پزشک، بیمار |
| سیستم موقعیت مکانی | 4 | جستجو، استان، شهر |
| سیستم فاکتور و پرداخت | 5 | فاکتور، پرداخت |
| تنظیمات کاربر | 3 | پروفایل، رمز، آدرس |
| سیستم مدیریت سیستم | 3 | اطلاعات، لاگ، کش |
| سیستم سئو | 2 | پزشک، صفحات |
| صفحه اصلی | 4 | اطلاعات، آمار |
| سیستم Webhook | 4 | وضعیت، فعال‌سازی، لاگ |
| سیستم کیف پول | 6 | موجودی، تراکنش، پرداخت |
| سیستم آزمایشگاه | 17 | تست‌ها، سفارش، نتایج |
| سیستم بستری | 15 | بخش، تخت، پذیرش، ترخیص |
| سیستم فرم‌های دیجیتال | 12 | فرم‌ها، پاسخ‌ها، امضا |
| سیستم هوش تجاری (BI) | 12 | پیش‌بینی، گزارش، تحلیل |
| سیستم بک‌آپ و لاگ | 10 | بک‌آپ، لاگ، آرشیو |
| سیستم چندزبانه | 3 | تغییر زبان |
| سیستم PACS | 6 | آپلود، مشاهده تصاویر |
| سیستم اتاق عمل | 8 | مدیریت اتاق، زمان‌بندی |
| سیستم اورژانس | 6 | ثبت، لیست، آمار |
| سیستم ربات بله | 3 | Webhook، ارسال پیام |
| سیستم نقش‌ها و مجوزها | 8 | مدیریت نقش و مجوز |
| سیستم کاربران | 7 | CRUD کاربران |
| سیستم داروخانه | 7 | مدیریت داروخانه‌ها |
| سیستم بیمه | 12 | مدیریت بیمه |
| سیستم پزشکی از راه دور | 14 | جلسات، پیام، فایل |
| سیستم واکسیناسیون | 11 | واکسن‌ها، ثبت، یادآوری |
| سیستم نظرسنجی | 10 | نظرسنجی، بازخورد |
| سیستم رویدادها | 11 | رویدادها، ثبت‌نام |
| سیستم کمپین‌ها | 9 | کمپین‌ها، تعاملات |
| سیستم یادداشت پزشکی | 7 | یادداشت‌ها |
| سیستم پنل منشی | 12 | صف، نوبت تلفنی، کارت |
| **مجموع** | **~۳۵۰ API** | **سیستم کامل** |

==================================================
==================================================
🎯 نکات نهایی
==================================================
==================================================

۱. همه APIها به جز موارد مشخص شده، نیاز به توکن دارند
۲. توکن باید در هدر Authorization: Bearer $TOKEN ارسال شود
۳. محتوای JSON باید با Content-Type: application/json ارسال شود
۴. فایل‌ها باید با multipart/form-data ارسال شوند
۵. خطاها با فرمت {"success":false,"message":"..."} برمی‌گردند
۶. برای محیط تست از کد OTP: 1234 استفاده کنید

==================================================
📌 مقادیر نمونه برای تست (Seeders)
==================================================

# کاربر ادمین
Email: admin@medikal.com
Password: password123

# یا
Mobile: 09123456789
OTP Code: 1234

# کلینیک نمونه
Name: کلینیک نمونه
Slug: clinic-sample

==================================================
✅ سیستم شما کاملاً مستند و آماده استفاده است! 🎉
==================================================
EOF

echo "✅ مستندات کامل API با موفقیت به readme3.txt اضافه شد!"

































































