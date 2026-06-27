#!/bin/bash

echo "🚀 شروع ایجاد ماژول‌های تکمیلی..."

# ایجاد پوشه‌های مورد نیاز
mkdir -p app/Services/MultiLanguage
mkdir -p app/Services/PACS
mkdir -p app/Services/OR
mkdir -p app/Services/Emergency
mkdir -p app/Models/PACS
mkdir -p app/Models/OR
mkdir -p app/Models/Emergency
mkdir -p app/Http/Controllers/Api/PACS
mkdir -p app/Http/Controllers/Api/OR
mkdir -p app/Http/Controllers/Api/Emergency
mkdir -p app/Http/Middleware
mkdir -p database/migrations/PACS
mkdir -p database/migrations/OR
mkdir -p database/migrations/Emergency
mkdir -p lang/fa
mkdir -p lang/en
mkdir -p lang/ar
mkdir -p resources/lang/fa
mkdir -p resources/lang/en
mkdir -p resources/lang/ar

# ============================================================
# 1. MULTI-LANGUAGE SYSTEM
# ============================================================

# 1.1 Language Middleware
cat > app/Http/Middleware/LocalizationMiddleware.php << 'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Get language from header, session, or default
        $locale = $request->header('Accept-Language');

        if (!$locale) {
            $locale = Session::get('locale', config('app.locale', 'fa'));
        }

        // Validate locale
        $allowedLocales = ['fa', 'en', 'ar'];
        if (!in_array($locale, $allowedLocales)) {
            $locale = 'fa';
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
PHP

# 1.2 Language Controller
cat > app/Http/Controllers/Api/LanguageController.php << 'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    use ApiResponse;

    public function switch(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:fa,en,ar',
        ]);

        $locale = $request->locale;
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $this->success([
            'locale' => $locale,
            'message' => trans('messages.language_switched'),
        ], 'Language switched successfully');
    }

    public function current()
    {
        return $this->success([
            'locale' => App::getLocale(),
            'locales' => [
                'fa' => ['name' => 'فارسی', 'flag' => '🇮🇷', 'dir' => 'rtl'],
                'en' => ['name' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
                'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'dir' => 'rtl'],
            ],
        ]);
    }

    public function translations(Request $request)
    {
        $locale = App::getLocale();
        $translations = $this->loadTranslations($locale);

        return $this->success($translations);
    }

    private function loadTranslations(string $locale): array
    {
        $files = glob(resource_path("lang/{$locale}/*.php"));
        $translations = [];

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $translations[$key] = include $file;
        }

        return $translations;
    }
}
PHP

# 1.3 Language Files - Persian (fa)
cat > resources/lang/fa/messages.php << 'PHP'
<?php

return [
    // General
    'welcome' => 'به سیستم مدیریت سلامت خوش آمدید',
    'dashboard' => 'داشبورد',
    'profile' => 'پروفایل',
    'settings' => 'تنظیمات',
    'logout' => 'خروج',
    'login' => 'ورود',
    'register' => 'ثبت نام',
    'save' => 'ذخیره',
    'cancel' => 'لغو',
    'delete' => 'حذف',
    'edit' => 'ویرایش',
    'create' => 'ایجاد',
    'search' => 'جستجو',
    'loading' => 'در حال بارگذاری...',
    'no_data' => 'داده‌ای یافت نشد',
    'confirm' => 'تایید',
    'back' => 'بازگشت',
    'next' => 'بعدی',
    'previous' => 'قبلی',
    'close' => 'بستن',
    'yes' => 'بله',
    'no' => 'خیر',
    'all' => 'همه',
    'none' => 'هیچ',
    'success' => 'عملیات با موفقیت انجام شد',
    'error' => 'خطایی رخ داده است',
    'warning' => 'هشدار',
    'info' => 'اطلاعات',

    // Languages
    'language_switched' => 'زبان با موفقیت تغییر یافت',
    'language_fa' => 'فارسی',
    'language_en' => 'انگلیسی',
    'language_ar' => 'عربی',

    // Roles
    'role_admin' => 'مدیر سیستم',
    'role_doctor' => 'پزشک',
    'role_patient' => 'بیمار',
    'role_receptionist' => 'منشی',
    'role_lab_technician' => 'کارشناس آزمایشگاه',
    'role_pharmacist' => 'داروساز',

    // Appointments
    'appointment' => 'نوبت',
    'appointments' => 'نوبت‌ها',
    'new_appointment' => 'نوبت جدید',
    'appointment_date' => 'تاریخ نوبت',
    'appointment_time' => 'ساعت نوبت',
    'appointment_status' => 'وضعیت نوبت',
    'appointment_pending' => 'در انتظار تایید',
    'appointment_confirmed' => 'تایید شده',
    'appointment_completed' => 'انجام شده',
    'appointment_cancelled' => 'لغو شده',
    'appointment_no_show' => 'حاضر نشده',

    // Patients
    'patient' => 'بیمار',
    'patients' => 'بیماران',
    'new_patient' => 'بیمار جدید',
    'patient_name' => 'نام بیمار',
    'patient_national_code' => 'کد ملی',
    'patient_phone' => 'شماره تماس',
    'patient_blood_type' => 'گروه خونی',

    // Doctors
    'doctor' => 'پزشک',
    'doctors' => 'پزشکان',
    'new_doctor' => 'پزشک جدید',
    'doctor_name' => 'نام پزشک',
    'doctor_specialty' => 'تخصص',
    'doctor_license' => 'شماره نظام پزشکی',

    // Prescriptions
    'prescription' => 'نسخه',
    'prescriptions' => 'نسخه‌ها',
    'new_prescription' => 'نسخه جدید',
    'prescription_drug' => 'دارو',
    'prescription_dosage' => 'دوز مصرف',
    'prescription_frequency' => 'تعداد دفعات',

    // Laboratory
    'laboratory' => 'آزمایشگاه',
    'lab_order' => 'سفارش آزمایش',
    'lab_test' => 'تست آزمایشگاهی',
    'lab_result' => 'نتیجه آزمایش',
    'lab_critical' => 'بحرانی',
    'lab_abnormal' => 'غیرطبیعی',
    'lab_normal' => 'طبیعی',

    // Hospitalization
    'admission' => 'پذیرش بستری',
    'ward' => 'بخش',
    'bed' => 'تخت',
    'discharge' => 'ترخیص',
    'admission_status' => 'وضعیت پذیرش',

    // Pharmacy
    'pharmacy' => 'داروخانه',
    'pharmacy_order' => 'سفارش دارو',
    'drug' => 'دارو',
    'drug_stock' => 'موجودی دارو',

    // Payments
    'payment' => 'پرداخت',
    'invoice' => 'فاکتور',
    'wallet' => 'کیف پول',
    'transaction' => 'تراکنش',

    // Emergency
    'emergency' => 'اورژانس',
    'triage' => 'تریاز',
    'emergency_patient' => 'بیمار اورژانسی',
    'emergency_level' => 'سطح اورژانس',
    'emergency_red' => 'بحرانی',
    'emergency_yellow' => 'فوری',
    'emergency_green' => 'معمولی',
    'emergency_blue' => 'غیرفوری',

    // OR
    'operation_room' => 'اتاق عمل',
    'surgery' => 'جراحی',
    'surgery_schedule' => 'زمان‌بندی جراحی',
    'surgeon' => 'جراح',
    'anesthesiologist' => 'متخصص بیهوشی',

    // PACS
    'pacs' => 'تصاویر پزشکی',
    'radiology' => 'رادیولوژی',
    'xray' => 'رادیوگرافی',
    'ct_scan' => 'سی‌تی اسکن',
    'mri' => 'ام‌آر‌آی',
    'ultrasound' => 'سونوگرافی',
    'image_upload' => 'آپلود تصویر',
    'image_view' => 'مشاهده تصویر',

    // Messages
    'message' => 'پیام',
    'chat' => 'چت',
    'notification' => 'اعلان',
    'reminder' => 'یادآوری',

    // Reports
    'report' => 'گزارش',
    'reports' => 'گزارش‌ها',
    'daily_report' => 'گزارش روزانه',
    'monthly_report' => 'گزارش ماهانه',
    'annual_report' => 'گزارش سالانه',

    // System
    'system' => 'سیستم',
    'backup' => 'بک‌آپ',
    'log' => 'لاگ',
    'audit' => 'حسابرسی',
    'maintenance' => 'نگهداری',

    // Errors
    'error_404' => 'صفحه مورد نظر یافت نشد',
    'error_403' => 'شما دسترسی به این صفحه را ندارید',
    'error_500' => 'خطای سرور',
    'validation_error' => 'خطای اعتبارسنجی',
];
PHP

# 1.4 Language Files - English (en)
cat > resources/lang/en/messages.php << 'PHP'
<?php

return [
    // General
    'welcome' => 'Welcome to Health Management System',
    'dashboard' => 'Dashboard',
    'profile' => 'Profile',
    'settings' => 'Settings',
    'logout' => 'Logout',
    'login' => 'Login',
    'register' => 'Register',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'search' => 'Search',
    'loading' => 'Loading...',
    'no_data' => 'No data found',
    'confirm' => 'Confirm',
    'back' => 'Back',
    'next' => 'Next',
    'previous' => 'Previous',
    'close' => 'Close',
    'yes' => 'Yes',
    'no' => 'No',
    'all' => 'All',
    'none' => 'None',
    'success' => 'Operation completed successfully',
    'error' => 'An error occurred',
    'warning' => 'Warning',
    'info' => 'Information',

    // Languages
    'language_switched' => 'Language switched successfully',
    'language_fa' => 'Persian',
    'language_en' => 'English',
    'language_ar' => 'Arabic',

    // Roles
    'role_admin' => 'System Administrator',
    'role_doctor' => 'Doctor',
    'role_patient' => 'Patient',
    'role_receptionist' => 'Receptionist',
    'role_lab_technician' => 'Lab Technician',
    'role_pharmacist' => 'Pharmacist',

    // Appointments
    'appointment' => 'Appointment',
    'appointments' => 'Appointments',
    'new_appointment' => 'New Appointment',
    'appointment_date' => 'Appointment Date',
    'appointment_time' => 'Appointment Time',
    'appointment_status' => 'Appointment Status',
    'appointment_pending' => 'Pending',
    'appointment_confirmed' => 'Confirmed',
    'appointment_completed' => 'Completed',
    'appointment_cancelled' => 'Cancelled',
    'appointment_no_show' => 'No Show',

    // Patients
    'patient' => 'Patient',
    'patients' => 'Patients',
    'new_patient' => 'New Patient',
    'patient_name' => 'Patient Name',
    'patient_national_code' => 'National ID',
    'patient_phone' => 'Phone Number',
    'patient_blood_type' => 'Blood Type',

    // Doctors
    'doctor' => 'Doctor',
    'doctors' => 'Doctors',
    'new_doctor' => 'New Doctor',
    'doctor_name' => 'Doctor Name',
    'doctor_specialty' => 'Specialty',
    'doctor_license' => 'License Number',

    // Prescriptions
    'prescription' => 'Prescription',
    'prescriptions' => 'Prescriptions',
    'new_prescription' => 'New Prescription',
    'prescription_drug' => 'Drug',
    'prescription_dosage' => 'Dosage',
    'prescription_frequency' => 'Frequency',

    // Laboratory
    'laboratory' => 'Laboratory',
    'lab_order' => 'Lab Order',
    'lab_test' => 'Lab Test',
    'lab_result' => 'Lab Result',
    'lab_critical' => 'Critical',
    'lab_abnormal' => 'Abnormal',
    'lab_normal' => 'Normal',

    // Hospitalization
    'admission' => 'Admission',
    'ward' => 'Ward',
    'bed' => 'Bed',
    'discharge' => 'Discharge',
    'admission_status' => 'Admission Status',

    // Pharmacy
    'pharmacy' => 'Pharmacy',
    'pharmacy_order' => 'Pharmacy Order',
    'drug' => 'Drug',
    'drug_stock' => 'Drug Stock',

    // Payments
    'payment' => 'Payment',
    'invoice' => 'Invoice',
    'wallet' => 'Wallet',
    'transaction' => 'Transaction',

    // Emergency
    'emergency' => 'Emergency',
    'triage' => 'Triage',
    'emergency_patient' => 'Emergency Patient',
    'emergency_level' => 'Emergency Level',
    'emergency_red' => 'Critical',
    'emergency_yellow' => 'Urgent',
    'emergency_green' => 'Normal',
    'emergency_blue' => 'Non-urgent',

    // OR
    'operation_room' => 'Operation Room',
    'surgery' => 'Surgery',
    'surgery_schedule' => 'Surgery Schedule',
    'surgeon' => 'Surgeon',
    'anesthesiologist' => 'Anesthesiologist',

    // PACS
    'pacs' => 'Medical Images',
    'radiology' => 'Radiology',
    'xray' => 'X-Ray',
    'ct_scan' => 'CT Scan',
    'mri' => 'MRI',
    'ultrasound' => 'Ultrasound',
    'image_upload' => 'Upload Image',
    'image_view' => 'View Image',

    // Messages
    'message' => 'Message',
    'chat' => 'Chat',
    'notification' => 'Notification',
    'reminder' => 'Reminder',

    // Reports
    'report' => 'Report',
    'reports' => 'Reports',
    'daily_report' => 'Daily Report',
    'monthly_report' => 'Monthly Report',
    'annual_report' => 'Annual Report',

    // System
    'system' => 'System',
    'backup' => 'Backup',
    'log' => 'Log',
    'audit' => 'Audit',
    'maintenance' => 'Maintenance',

    // Errors
    'error_404' => 'Page not found',
    'error_403' => 'Access denied',
    'error_500' => 'Server error',
    'validation_error' => 'Validation error',
];
PHP

# 1.5 Language Files - Arabic (ar)
cat > resources/lang/ar/messages.php << 'PHP'
<?php

return [
    // General
    'welcome' => 'مرحباً بكم في نظام إدارة الصحة',
    'dashboard' => 'لوحة التحكم',
    'profile' => 'الملف الشخصي',
    'settings' => 'الإعدادات',
    'logout' => 'تسجيل الخروج',
    'login' => 'تسجيل الدخول',
    'register' => 'التسجيل',
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'delete' => 'حذف',
    'edit' => 'تعديل',
    'create' => 'إنشاء',
    'search' => 'بحث',
    'loading' => 'جاري التحميل...',
    'no_data' => 'لا توجد بيانات',
    'confirm' => 'تأكيد',
    'back' => 'رجوع',
    'next' => 'التالي',
    'previous' => 'السابق',
    'close' => 'إغلاق',
    'yes' => 'نعم',
    'no' => 'لا',
    'all' => 'الكل',
    'none' => 'لا شيء',
    'success' => 'تمت العملية بنجاح',
    'error' => 'حدث خطأ',
    'warning' => 'تحذير',
    'info' => 'معلومات',

    // Languages
    'language_switched' => 'تم تغيير اللغة بنجاح',
    'language_fa' => 'الفارسية',
    'language_en' => 'الإنجليزية',
    'language_ar' => 'العربية',

    // Roles
    'role_admin' => 'مدير النظام',
    'role_doctor' => 'طبيب',
    'role_patient' => 'مريض',
    'role_receptionist' => 'موظف استقبال',
    'role_lab_technician' => 'فني مختبر',
    'role_pharmacist' => 'صيدلي',

    // Appointments
    'appointment' => 'موعد',
    'appointments' => 'المواعيد',
    'new_appointment' => 'موعد جديد',
    'appointment_date' => 'تاريخ الموعد',
    'appointment_time' => 'وقت الموعد',
    'appointment_status' => 'حالة الموعد',
    'appointment_pending' => 'قيد الانتظار',
    'appointment_confirmed' => 'مؤكد',
    'appointment_completed' => 'مكتمل',
    'appointment_cancelled' => 'ملغى',
    'appointment_no_show' => 'لم يحضر',

    // Patients
    'patient' => 'مريض',
    'patients' => 'المرضى',
    'new_patient' => 'مريض جديد',
    'patient_name' => 'اسم المريض',
    'patient_national_code' => 'الرقم الوطني',
    'patient_phone' => 'رقم الهاتف',
    'patient_blood_type' => 'فصيلة الدم',

    // Doctors
    'doctor' => 'طبيب',
    'doctors' => 'الأطباء',
    'new_doctor' => 'طبيب جديد',
    'doctor_name' => 'اسم الطبيب',
    'doctor_specialty' => 'التخصص',
    'doctor_license' => 'رقم الترخيص',

    // Prescriptions
    'prescription' => 'وصفة طبية',
    'prescriptions' => 'الوصفات الطبية',
    'new_prescription' => 'وصفة جديدة',
    'prescription_drug' => 'الدواء',
    'prescription_dosage' => 'الجرعة',
    'prescription_frequency' => 'التكرار',

    // Laboratory
    'laboratory' => 'المختبر',
    'lab_order' => 'طلب مخبري',
    'lab_test' => 'اختبار مخبري',
    'lab_result' => 'نتيجة الاختبار',
    'lab_critical' => 'حرج',
    'lab_abnormal' => 'غير طبيعي',
    'lab_normal' => 'طبيعي',

    // Hospitalization
    'admission' => 'الاستقبال',
    'ward' => 'الجناح',
    'bed' => 'سرير',
    'discharge' => 'الخروج',
    'admission_status' => 'حالة الاستقبال',

    // Pharmacy
    'pharmacy' => 'الصيدلية',
    'pharmacy_order' => 'طلب صيدلية',
    'drug' => 'دواء',
    'drug_stock' => 'مخزون الدواء',

    // Payments
    'payment' => 'دفع',
    'invoice' => 'فاتورة',
    'wallet' => 'محفظة',
    'transaction' => 'معاملة',

    // Emergency
    'emergency' => 'الطوارئ',
    'triage' => 'الفرز',
    'emergency_patient' => 'مريض طوارئ',
    'emergency_level' => 'مستوى الطوارئ',
    'emergency_red' => 'حرج',
    'emergency_yellow' => 'عاجل',
    'emergency_green' => 'عادي',
    'emergency_blue' => 'غير عاجل',

    // OR
    'operation_room' => 'غرفة العمليات',
    'surgery' => 'جراحة',
    'surgery_schedule' => 'جدول الجراحة',
    'surgeon' => 'جراح',
    'anesthesiologist' => 'طبيب التخدير',

    // PACS
    'pacs' => 'الصور الطبية',
    'radiology' => 'الأشعة',
    'xray' => 'الأشعة السينية',
    'ct_scan' => 'التصوير المقطعي',
    'mri' => 'الرنين المغناطيسي',
    'ultrasound' => 'الموجات فوق الصوتية',
    'image_upload' => 'رفع الصورة',
    'image_view' => 'عرض الصورة',

    // Messages
    'message' => 'رسالة',
    'chat' => 'محادثة',
    'notification' => 'إشعار',
    'reminder' => 'تذكير',

    // Reports
    'report' => 'تقرير',
    'reports' => 'التقارير',
    'daily_report' => 'تقرير يومي',
    'monthly_report' => 'تقرير شهري',
    'annual_report' => 'تقرير سنوي',

    // System
    'system' => 'النظام',
    'backup' => 'نسخ احتياطي',
    'log' => 'سجل',
    'audit' => 'تدقيق',
    'maintenance' => 'الصيانة',

    // Errors
    'error_404' => 'الصفحة غير موجودة',
    'error_403' => 'تم رفض الوصول',
    'error_500' => 'خطأ في الخادم',
    'validation_error' => 'خطأ في التحقق',
];
PHP

echo "✅ Multi-Language ایجاد شد."

# ============================================================
# 2. PACS (تصاویر پزشکی)
# ============================================================

# 2.1 PACS Model
cat > app/Models/PACS/MedicalImage.php << 'PHP'
<?php

namespace App\Models\PACS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalImage extends Model
{
    use SoftDeletes;

    protected $table = 'medical_images';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'admission_id',
        'appointment_id',
        'image_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'study_uid',
        'series_uid',
        'instance_uid',
        'body_part',
        'modality',
        'description',
        'study_date',
        'report',
        'is_confidential',
        'uploaded_by',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'study_date' => 'datetime',
        'is_confidential' => 'boolean',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getFileSizeDisplayAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getImageTypeLabelAttribute(): string
    {
        $labels = [
            'xray' => 'رادیوگرافی',
            'ct' => 'سی‌تی اسکن',
            'mri' => 'ام‌آر‌آی',
            'ultrasound' => 'سونوگرافی',
            'pet' => 'پت اسکن',
            'spect' => 'اسپکت',
            'mammogram' => 'ماموگرافی',
            'dental' => 'دندانپزشکی',
            'other' => 'سایر',
        ];
        return $labels[$this->image_type] ?? $this->image_type;
    }

    public function getModalityLabelAttribute(): string
    {
        $labels = [
            'DX' => 'رادیوگرافی دیجیتال',
            'CR' => 'رادیوگرافی کامپیوتری',
            'CT' => 'سی‌تی اسکن',
            'MR' => 'ام‌آر‌آی',
            'US' => 'سونوگرافی',
            'PT' => 'پت اسکن',
            'NM' => 'پزشکی هسته‌ای',
            'MG' => 'ماموگرافی',
            'IO' => 'رادیوگرافی داخل دهانی',
        ];
        return $labels[$this->modality] ?? $this->modality;
    }
}
PHP

# 2.2 PACS Migration
cat > database/migrations/PACS/2026_07_04_000001_create_medical_images_table.php << 'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            $table->string('image_type');
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();

            // DICOM fields
            $table->string('study_uid')->nullable();
            $table->string('series_uid')->nullable();
            $table->string('instance_uid')->nullable();
            $table->string('body_part')->nullable();
            $table->string('modality')->nullable();

            $table->text('description')->nullable();
            $table->timestamp('study_date')->nullable();
            $table->text('report')->nullable();

            $table->boolean('is_confidential')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'image_type']);
            $table->index(['patient_id', 'study_date']);
            $table->index('study_uid');
            $table->index('modality');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
};
PHP

# 2.3 PACS Service
cat > app/Services/PACS/PACSService.php << 'PHP'
<?php

namespace App\Services\PACS;

use App\Models\PACS\MedicalImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PACSService
{
    public function uploadImage(array $data, $file): MedicalImage
    {
        $path = $file->store('pacs/' . $data['patient_id'], 'public');

        return MedicalImage::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'admission_id' => $data['admission_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'image_type' => $data['image_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'study_uid' => $data['study_uid'] ?? null,
            'series_uid' => $data['series_uid'] ?? null,
            'instance_uid' => $data['instance_uid'] ?? null,
            'body_part' => $data['body_part'] ?? null,
            'modality' => $data['modality'] ?? null,
            'description' => $data['description'] ?? null,
            'study_date' => $data['study_date'] ?? now(),
            'report' => $data['report'] ?? null,
            'is_confidential' => $data['is_confidential'] ?? false,
            'uploaded_by' => auth()->id(),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function getPatientImages(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = MedicalImage::where('patient_id', $patientId);

        if (isset($filters['image_type'])) {
            $query->where('image_type', $filters['image_type']);
        }

        if (isset($filters['modality'])) {
            $query->where('modality', $filters['modality']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('study_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('study_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('study_date', 'desc')->paginate($perPage);
    }

    public function deleteImage(int $imageId): void
    {
        $image = MedicalImage::findOrFail($imageId);
        Storage::disk('public')->delete($image->file_path);
        $image->delete();
    }

    public function getImageStats(int $patientId): array
    {
        return [
            'total' => MedicalImage::where('patient_id', $patientId)->count(),
            'by_type' => MedicalImage::where('patient_id', $patientId)
                ->selectRaw('image_type, count(*) as count')
                ->groupBy('image_type')
                ->get()
                ->pluck('count', 'image_type')
                ->toArray(),
            'by_modality' => MedicalImage::where('patient_id', $patientId)
                ->selectRaw('modality, count(*) as count')
                ->groupBy('modality')
                ->get()
                ->pluck('count', 'modality')
                ->toArray(),
        ];
    }
}
PHP

echo "✅ PACS ایجاد شد."

# ============================================================
# 3. OR MANAGEMENT (اتاق عمل)
# ============================================================

# 3.1 OR Models
cat > app/Models/OR/OperationRoom.php << 'PHP'
<?php

namespace App\Models\OR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationRoom extends Model
{
    use SoftDeletes;

    protected $table = 'operation_rooms';

    protected $fillable = [
        'name',
        'code',
        'floor',
        'type',
        'capacity',
        'equipment',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function schedules()
    {
        return $this->hasMany(SurgerySchedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
PHP

cat > app/Models/OR/SurgerySchedule.php << 'PHP'
<?php

namespace App\Models\OR;

use Illuminate\Database\Eloquent\Model;

class SurgerySchedule extends Model
{
    protected $table = 'surgery_schedules';

    protected $fillable = [
        'operation_room_id',
        'patient_id',
        'doctor_id',
        'surgeon_id',
        'anesthesiologist_id',
        'assistant_doctor_id',
        'surgery_type',
        'diagnosis',
        'procedure',
        'priority',
        'scheduled_date',
        'scheduled_time',
        'estimated_duration',
        'actual_duration',
        'status',
        'notes',
        'pre_op_notes',
        'post_op_notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'metadata' => 'array',
    ];

    public function operationRoom()
    {
        return $this->belongsTo(OperationRoom::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function surgeon()
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id');
    }

    public function anesthesiologist()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_id');
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'scheduled' => 'برنامه‌ریزی شده',
            'in_progress' => 'در حال انجام',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            'postponed' => 'به تعویق افتاده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'routine' => 'معمولی',
            'urgent' => 'فوری',
            'emergency' => 'اورژانسی',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }
}
PHP

# 3.2 OR Migrations
cat > database/migrations/OR/2026_07_04_000001_create_operation_rooms_table.php << 'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('floor')->nullable();
            $table->string('type');
            $table->integer('capacity')->default(1);
            $table->json('equipment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_rooms');
    }
};
PHP

cat > database/migrations/OR/2026_07_04_000002_create_surgery_schedules_table.php << 'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_room_id')->constrained('operation_rooms')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('surgeon_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('assistant_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();

            $table->string('surgery_type');
            $table->text('diagnosis')->nullable();
            $table->text('procedure')->nullable();
            $table->string('priority')->default('routine');
            $table->date('scheduled_date');
            $table->timestamp('scheduled_time');
            $table->integer('estimated_duration')->default(60);
            $table->integer('actual_duration')->nullable();

            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('pre_op_notes')->nullable();
            $table->text('post_op_notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['operation_room_id', 'scheduled_date']);
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'scheduled_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_schedules');
    }
};
PHP

echo "✅ OR Management ایجاد شد."

# ============================================================
# 4. EMERGENCY (اورژانس)
# ============================================================

# 4.1 Emergency Models
cat > app/Models/Emergency/EmergencyPatient.php << 'PHP'
<?php

namespace App\Models\Emergency;

use Illuminate\Database\Eloquent\Model;

class EmergencyPatient extends Model
{
    protected $table = 'emergency_patients';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'admission_id',
        'triage_level',
        'arrival_time',
        'chief_complaint',
        'history_of_present_illness',
        'vital_signs',
        'allergies',
        'medications',
        'past_medical_history',
        'status',
        'disposition',
        'disposition_time',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'disposition_time' => 'datetime',
        'vital_signs' => 'array',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function getTriageLevelLabelAttribute(): string
    {
        $labels = [
            'red' => '🔴 بحرانی (فوری)',
            'yellow' => '🟡 فوری',
            'green' => '🟢 معمولی',
            'blue' => '🔵 غیرفوری',
        ];
        return $labels[$this->triage_level] ?? $this->triage_level;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'waiting' => 'در انتظار',
            'in_triage' => 'در حال تریاز',
            'in_exam' => 'در حال معاینه',
            'in_treatment' => 'در حال درمان',
            'admitted' => 'بستری',
            'discharged' => 'ترخیص',
            'transferred' => 'منتقل شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getDispositionLabelAttribute(): string
    {
        $labels = [
            'discharged' => 'ترخیص',
            'admitted' => 'بستری',
            'transferred' => 'منتقل به بیمارستان دیگر',
            'died' => 'فوت',
            'left_against_advice' => 'ترخیص با رضایت شخصی',
        ];
        return $labels[$this->disposition] ?? $this->disposition;
    }
}
PHP

# 4.2 Emergency Migrations
cat > database/migrations/Emergency/2026_07_04_000001_create_emergency_patients_table.php << 'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();

            $table->string('triage_level');
            $table->timestamp('arrival_time');
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->json('vital_signs')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->text('past_medical_history')->nullable();

            $table->string('status')->default('waiting');
            $table->string('disposition')->nullable();
            $table->timestamp('disposition_time')->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['triage_level', 'arrival_time']);
            $table->index('arrival_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_patients');
    }
};
PHP

# 4.3 Emergency Service
cat > app/Services/Emergency/EmergencyService.php << 'PHP'
<?php

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyPatient;
use Carbon\Carbon;

class EmergencyService
{
    public function register(array $data): EmergencyPatient
    {
        return EmergencyPatient::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'admission_id' => $data['admission_id'] ?? null,
            'triage_level' => $data['triage_level'] ?? 'green',
            'arrival_time' => $data['arrival_time'] ?? now(),
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'history_of_present_illness' => $data['history_of_present_illness'] ?? null,
            'vital_signs' => $data['vital_signs'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'medications' => $data['medications'] ?? null,
            'past_medical_history' => $data['past_medical_history'] ?? null,
            'status' => 'waiting',
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function updateStatus(int $id, string $status): EmergencyPatient
    {
        $patient = EmergencyPatient::findOrFail($id);
        $patient->update(['status' => $status]);
        return $patient->fresh();
    }

    public function setDisposition(int $id, string $disposition): EmergencyPatient
    {
        $patient = EmergencyPatient::findOrFail($id);
        $patient->update([
            'disposition' => $disposition,
            'disposition_time' => now(),
        ]);
        return $patient->fresh();
    }

    public function getWaitingList()
    {
        return EmergencyPatient::where('status', 'waiting')
            ->orderBy('arrival_time')
            ->with(['patient.user'])
            ->get();
    }

    public function getTriageStats(): array
    {
        return [
            'red' => EmergencyPatient::where('triage_level', 'red')->count(),
            'yellow' => EmergencyPatient::where('triage_level', 'yellow')->count(),
            'green' => EmergencyPatient::where('triage_level', 'green')->count(),
            'blue' => EmergencyPatient::where('triage_level', 'blue')->count(),
            'total' => EmergencyPatient::count(),
            'today' => EmergencyPatient::whereDate('arrival_time', today())->count(),
        ];
    }
}
PHP

echo "✅ Emergency ایجاد شد."

# ============================================================
# 5. UPDATE ROUTES
# ============================================================

cat >> routes/api.php << 'PHP'

// ============================================================
// MULTI-LANGUAGE ROUTES
// ============================================================
Route::prefix('language')->group(function () {
    Route::post('/switch', [App\Http\Controllers\Api\LanguageController::class, 'switch']);
    Route::get('/current', [App\Http\Controllers\Api\LanguageController::class, 'current']);
    Route::get('/translations', [App\Http\Controllers\Api\LanguageController::class, 'translations']);
});

// ============================================================
// PACS ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('pacs')->group(function () {
    Route::post('/upload', [App\Http\Controllers\Api\PACS\PACSController::class, 'upload']);
    Route::get('/patients/{patientId}/images', [App\Http\Controllers\Api\PACS\PACSController::class, 'patientImages']);
    Route::get('/images/{id}', [App\Http\Controllers\Api\PACS\PACSController::class, 'showImage']);
    Route::get('/images/{id}/download', [App\Http\Controllers\Api\PACS\PACSController::class, 'downloadImage']);
    Route::delete('/images/{id}', [App\Http\Controllers\Api\PACS\PACSController::class, 'deleteImage']);
    Route::get('/patients/{patientId}/stats', [App\Http\Controllers\Api\PACS\PACSController::class, 'imageStats']);
});

// ============================================================
// OR (Operation Room) ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('or')->group(function () {
    Route::get('/rooms', [App\Http\Controllers\Api\OR\ORController::class, 'rooms']);
    Route::post('/rooms', [App\Http\Controllers\Api\OR\ORController::class, 'createRoom']);
    Route::get('/rooms/{id}', [App\Http\Controllers\Api\OR\ORController::class, 'showRoom']);
    Route::put('/rooms/{id}', [App\Http\Controllers\Api\OR\ORController::class, 'updateRoom']);
    Route::delete('/rooms/{id}', [App\Http\Controllers\Api\OR\ORController::class, 'deleteRoom']);

    Route::get('/schedules', [App\Http\Controllers\Api\OR\ORController::class, 'schedules']);
    Route::post('/schedules', [App\Http\Controllers\Api\OR\ORController::class, 'createSchedule']);
    Route::get('/schedules/{id}', [App\Http\Controllers\Api\OR\ORController::class, 'showSchedule']);
    Route::put('/schedules/{id}', [App\Http\Controllers\Api\OR\ORController::class, 'updateSchedule']);
    Route::delete('/schedules/{id}', [App\Http\Controllers\Api\OR\ORController::class, 'deleteSchedule']);
    Route::post('/schedules/{id}/status', [App\Http\Controllers\Api\OR\ORController::class, 'updateStatus']);
});

// ============================================================
// EMERGENCY ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('emergency')->group(function () {
    Route::post('/register', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'register']);
    Route::get('/waiting-list', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'waitingList']);
    Route::get('/stats', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'stats']);
    Route::get('/patients/{id}', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'show']);
    Route::post('/patients/{id}/status', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'updateStatus']);
    Route::post('/patients/{id}/disposition', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'setDisposition']);
});
PHP

echo "✅ Routes بروزرسانی شدند."

echo "=========================================="
echo "✅ همه ماژول‌های تکمیلی با موفقیت ایجاد شدند!"
echo ""
echo "📊 خلاصه فایل‌ها:"
echo "   - Multi-Language: 5 فایل"
echo "   - PACS: 3 فایل"
echo "   - OR Management: 4 فایل"
echo "   - Emergency: 3 فایل"
echo ""
echo "🚀 مراحل بعدی:"
echo "1. php artisan migrate --path=database/migrations/PACS"
echo "2. php artisan migrate --path=database/migrations/OR"
echo "3. php artisan migrate --path=database/migrations/Emergency"
echo "4. php artisan cache:clear"
echo "5. php artisan config:clear"
echo "6. php artisan route:clear"
echo "7. php artisan storage:link"
echo ""
echo "📌 تنظیمات فایل .env:"
echo "APP_LOCALE=fa"
echo "APP_FALLBACK_LOCALE=en"
