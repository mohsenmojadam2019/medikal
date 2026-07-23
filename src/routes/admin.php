<?php
// routes/admin.php

use App\Http\Controllers\Admin\AIChatAdminController;
use App\Http\Controllers\Admin\EmergencyController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\RatingController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Api\BI\BIController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReminderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Admin\PharmacyOrderController;
use App\Http\Controllers\Admin\PharmacyManagementController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\DoctorProfileController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\DrugController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\SpecialtyMediaController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SuperAdmin\TenantController;
use App\Http\Controllers\Admin\MedicalNoteController;
use App\Http\Controllers\Api\Dashboard\ManagementDashboardController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\VaccinationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Admin\LabController as AdminLabController;
use App\Http\Controllers\Admin\PACSController as AdminPACSController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| نسخه: 2.0.0
| تاریخ: 2026-07-24
|--------------------------------------------------------------------------
*/

// ==========================================
// تست
// ==========================================
Route::get('/test-route', function () {
    return response()->json(['message' => 'Admin routes loaded!']);
});

// ============================================================================
// 1. مسیرهای عمومی ادمین
// ============================================================================
Route::post('/login', [AdminAuthController::class, 'loginWithEmail']);

// ============================================================================
// 2. مسیرهای احراز هویت
// ============================================================================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);
});

// ============================================================================
// 3. مسیرهای محافظت‌شده (ادمین)
// ============================================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->group(function () {

    // ==========================================
    // 3.1 پروفایل
    // ==========================================
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'show');
        Route::put('/', 'update');
        Route::post('/avatar', 'uploadAvatar');
        Route::delete('/avatar', 'deleteAvatar');
        Route::post('/change-password', 'changePassword');
        Route::get('/activities', 'activities');
    });

    // ==========================================
    // 3.2 داشبورد
    // ==========================================
    Route::prefix('dashboard/management')->group(function () {
        Route::get('/stats', [ManagementDashboardController::class, 'stats']);
        Route::get('/charts', [ManagementDashboardController::class, 'charts']);
        Route::get('/quick-stats', [ManagementDashboardController::class, 'quickStats']);
        Route::get('/recent-activities', [ManagementDashboardController::class, 'recentActivities']);
        Route::get('/top-doctors', [ManagementDashboardController::class, 'topDoctors']);
        Route::get('/summary', [ManagementDashboardController::class, 'summary']);
    });

    // ==========================================
    // 3.3 مدیریت کلینیک
    // ==========================================
    Route::prefix('clinic')->group(function () {
        Route::get('/', [ClinicController::class, 'show']);
        Route::put('/', [ClinicController::class, 'update']);
        Route::post('/upload-logo', [ClinicController::class, 'uploadLogo']);
        Route::post('/toggle-status', [ClinicController::class, 'toggleStatus']);
    });

    // ==========================================
    // 3.4 مدیریت کاربران
    // ==========================================
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
    });

    // ==========================================
    // 3.5 مدیریت نقش‌ها و دسترسی‌ها
    // ==========================================
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
        Route::post('/assign-to-role', [PermissionController::class, 'assignToRole']);
    });

    // ==========================================
    // 3.6 مدیریت پزشکان
    // ==========================================
    Route::prefix('doctors')->group(function () {
        Route::get('/', [DoctorController::class, 'index']);
        Route::post('/', [DoctorController::class, 'store']);
        Route::get('/{id}', [DoctorController::class, 'show']);
        Route::put('/{id}', [DoctorController::class, 'update']);
        Route::delete('/{id}', [DoctorController::class, 'destroy']);
        Route::post('/{id}/toggle-availability', [DoctorController::class, 'toggleAvailability']);
        Route::post('/{id}/verify', [DoctorController::class, 'verify']);
        Route::post('/{id}/set-fee', [DoctorController::class, 'setAppointmentFee']);
        Route::get('/{id}/fee', [DoctorController::class, 'getAppointmentFee']);

        Route::prefix('profile')->group(function () {
            Route::put('/{id}', [DoctorProfileController::class, 'update']);
            Route::post('/{id}/verify', [DoctorProfileController::class, 'verify']);
            Route::post('/{id}/unverify', [DoctorProfileController::class, 'unverify']);
            Route::put('/{id}/location', [LocationController::class, 'updateDoctorLocation']);
        });
    });

    // ==========================================
    // 3.7 مدیریت نوبت‌ها
    // ==========================================
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        Route::put('/{id}', [AppointmentController::class, 'update']);
        Route::delete('/{id}', [AppointmentController::class, 'destroy']);
        Route::post('/{id}/status', [AppointmentController::class, 'changeStatus']);
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
        Route::get('/stats', [AppointmentController::class, 'stats']);
        Route::get('/doctors/{doctorId}/available-slots', [AppointmentController::class, 'getAvailableSlots']);
    });

    // ==========================================
    // 3.8 مدیریت نسخه‌ها
    // ==========================================
    Route::prefix('prescriptions')->group(function () {
        Route::get('/', [PrescriptionController::class, 'index']);
        Route::post('/', [PrescriptionController::class, 'store']);
        Route::get('/{id}', [PrescriptionController::class, 'show']);
        Route::put('/{id}', [PrescriptionController::class, 'update']);
        Route::delete('/{id}', [PrescriptionController::class, 'destroy']);
        Route::post('/{id}/status', [PrescriptionController::class, 'changeStatus']);
        Route::get('/stats', [PrescriptionController::class, 'stats']);
    });

    // ==========================================
    // 3.9 مدیریت بیماران
    // ==========================================
    Route::prefix('patients')->group(function () {
        Route::get('/', [PatientController::class, 'index']);
        Route::post('/', [PatientController::class, 'store']);
        Route::get('/{id}', [PatientController::class, 'show']);
        Route::put('/{id}', [PatientController::class, 'update']);
        Route::delete('/{id}', [PatientController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [PatientController::class, 'toggleStatus']);
        Route::post('/{id}/verify', [PatientController::class, 'verify']);
        Route::post('/{id}/unverify', [PatientController::class, 'unverify']);
        Route::post('/{id}/assign-doctor', [PatientController::class, 'assignDoctor']);
        Route::get('/{id}/medical-history', [PatientController::class, 'medicalHistory']);
        Route::get('/{id}/statistics', [PatientController::class, 'statistics']);
        Route::get('/without-doctor', [PatientController::class, 'withoutDoctor']);
        Route::get('/top', [PatientController::class, 'topPatients']);
    });

    // ==========================================
    // 3.10 مدیریت تخصص‌ها
    // ==========================================
    Route::prefix('specialties')->group(function () {
        Route::get('/', [SpecialtyController::class, 'index']);
        Route::post('/', [SpecialtyController::class, 'store']);
        Route::get('/{id}', [SpecialtyController::class, 'show']);
        Route::put('/{id}', [SpecialtyController::class, 'update']);
        Route::delete('/{id}', [SpecialtyController::class, 'destroy']);
        Route::post('/{id}/toggle', [SpecialtyController::class, 'toggleStatus']);
        Route::post('/{id}/icon', [SpecialtyMediaController::class, 'uploadIcon']);
        Route::delete('/{id}/icon', [SpecialtyMediaController::class, 'deleteIcon']);
        Route::get('/{id}/icon', [SpecialtyMediaController::class, 'getIcon']);
    });

    // ==========================================
    // 3.11 مدیریت داروها
    // ==========================================
    Route::prefix('drugs')->group(function () {
        Route::get('/', [DrugController::class, 'index']);
        Route::post('/', [DrugController::class, 'store']);
        Route::get('/{id}', [DrugController::class, 'show']);
        Route::put('/{id}', [DrugController::class, 'update']);
        Route::delete('/{id}', [DrugController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [DrugController::class, 'toggleStatus']);
        Route::post('/{id}/increase-stock', [DrugController::class, 'increaseStock']);
        Route::post('/{id}/decrease-stock', [DrugController::class, 'decreaseStock']);
        Route::get('/categories', [DrugController::class, 'categories']);
        Route::get('/pharmacy/{pharmacyId}', [DrugController::class, 'getPharmacyDrugs']);
    });

    // ============================================================
    // 3.12 ✅ مدیریت داروخانه‌ها (با قابلیت فعال/غیرفعال)
    // ============================================================
    Route::prefix('pharmacies')->controller(PharmacyManagementController::class)->group(function () {
        // لیست داروخانه‌ها
        Route::get('/', 'index');

        // ایجاد داروخانه جدید
        Route::post('/', 'store');

        // نمایش یک داروخانه
        Route::get('/{id}', 'show');

        // بروزرسانی داروخانه
        Route::put('/{id}', 'update');

        // حذف داروخانه
        Route::delete('/{id}', 'destroy');

        // ✅ فعال/غیرفعال کردن داروخانه
        Route::post('/{id}/toggle-status', 'toggleStatus');

        // ✅ فعال/غیرفعال کردن فروش آنلاین
        Route::post('/{id}/toggle-online', 'toggleOnline');
    });

    // ==========================================
    // 3.13 ✅ مدیریت سفارشات داروخانه (ادمین)
    // ==========================================
    Route::prefix('pharmacy')->controller(PharmacyOrderController::class)->group(function () {
        // لیست سفارشات با فیلتر
        Route::get('/orders', 'index');

        // جزئیات سفارش
        Route::get('/orders/{id}', 'show');

        // بروزرسانی وضعیت سفارش
        Route::put('/orders/{id}/status', 'updateStatus');

        // ✅ تایید نسخه پزشکی
        Route::post('/orders/{id}/approve-prescription', 'approvePrescription');

        // ✅ رد نسخه پزشکی
        Route::post('/orders/{id}/reject-prescription', 'rejectPrescription');

        // ✅ ارسال پیام به کاربر
        Route::post('/orders/{id}/send-message', 'sendMessage');

        // ✅ سفارشات رها شده (سبد رها شده)
        Route::get('/abandoned-carts', 'abandonedCarts');

        // ✅ آمار سفارشات
        Route::get('/stats', 'stats');

        // حذف سفارش
        Route::delete('/orders/{id}', 'destroy');
    });

    // ============================================================
    // 3.14 ✅ مدیریت آزمایشگاه (با قابلیت فعال/غیرفعال)
    // ============================================================
    Route::prefix('lab')->controller(AdminLabController::class)->group(function () {
        // ===== دسته‌بندی‌ها =====
        Route::get('/categories', 'categories');
        Route::post('/categories', 'storeCategory');
        Route::put('/categories/{id}', 'updateCategory');
        Route::delete('/categories/{id}', 'deleteCategory');

        // ===== تست‌ها =====
        Route::get('/tests', 'tests');
        Route::post('/tests', 'storeTest');
        Route::get('/tests/{id}', 'showTest');
        Route::put('/tests/{id}', 'updateTest');
        Route::delete('/tests/{id}', 'deleteTest');

        // ✅ فعال/غیرفعال کردن تست آزمایشگاه
        Route::post('/tests/{id}/toggle', 'toggleTestStatus');

        // ===== سفارشات =====
        Route::get('/orders', 'orders');
        Route::get('/orders/{id}', 'showOrder');
        Route::put('/orders/{id}/status', 'updateOrderStatus');

        // ===== آمار =====
        Route::get('/stats', 'stats');
    });

    // ============================================================
    // 3.15 ✅ مدیریت تصویربرداری PACS (با قابلیت فعال/غیرفعال)
    // ============================================================
    Route::prefix('pacs')->controller(AdminPACSController::class)->group(function () {
        // لیست تصاویر
        Route::get('/', 'index');

        // نمایش یک تصویر
        Route::get('/{id}', 'show');

        // حذف تصویر
        Route::delete('/{id}', 'destroy');

        // ✅ فعال/غیرفعال کردن تصویر
        Route::post('/{id}/toggle', 'toggleStatus');

        // آمار تصاویر
        Route::get('/stats', 'stats');
    });

    // ==========================================
    // 3.16 مدیریت سئو
    // ==========================================
    Route::prefix('seo')->group(function () {
        Route::get('/', [SeoController::class, 'index']);
        Route::post('/', [SeoController::class, 'store']);
        Route::get('/{id}', [SeoController::class, 'show']);
        Route::put('/{id}', [SeoController::class, 'update']);
        Route::delete('/{id}', [SeoController::class, 'destroy']);
        Route::get('/model', [SeoController::class, 'getByModel']);
    });

    // ==========================================
    // 3.17 مدیریت اعلان‌ها
    // ==========================================
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/recent', 'recent');
        Route::get('/unread', 'unread');
        Route::get('/unread/count', 'unreadCount');
        Route::get('/user/{userId}', 'userNotifications');
        Route::get('/{id}', 'show');
        Route::post('/mark-all-as-read', 'markAllAsRead');
        Route::post('/{id}/mark-as-read', 'markAsRead');
        Route::post('/send-to-all', 'sendToAll');
        Route::post('/send-to-doctors', 'sendToDoctors');
        Route::post('/send-to-patients', 'sendToPatients');
        Route::post('/send-to-user', 'sendToUser');
        Route::post('/send-to-doctor-patients/{doctorId}', 'sendToDoctorPatients');
        Route::post('/send-filtered', 'sendFiltered');
        Route::post('/send-to-users', 'sendToUsers');
        Route::post('/send-to-role', 'sendToRole');
        Route::delete('/{id}', 'destroy');
        Route::delete('/delete-all-read', 'deleteAllRead');
    });

    // ==========================================
    // 3.18 مدیریت وبلاگ
    // ==========================================
    Route::prefix('blog')->controller(BlogController::class)->group(function () {
        Route::get('/posts', 'posts');
        Route::post('/posts', 'storePost');
        Route::get('/posts/{id}', 'adminShowPost');
        Route::put('/posts/{id}', 'updatePost');
        Route::delete('/posts/{id}', 'deletePost');
        Route::post('/posts/{id}/publish', 'publishPost');
        Route::post('/posts/{id}/unpublish', 'unpublishPost');
        Route::get('/categories', 'adminCategories');
        Route::post('/categories', 'storeCategory');
        Route::put('/categories/{id}', 'updateCategory');
        Route::delete('/categories/{id}', 'deleteCategory');
        Route::get('/tags', 'adminTags');
        Route::post('/tags', 'storeTag');
        Route::put('/tags/{id}', 'updateTag');
        Route::delete('/tags/{id}', 'deleteTag');
        Route::get('/comments', 'adminComments');
        Route::post('/comments/{id}/approve', 'approveComment');
        Route::post('/comments/{id}/reject', 'rejectComment');
        Route::delete('/comments/{id}', 'deleteComment');
        Route::get('/stats', 'stats');
    });

    // ==========================================
    // 3.19 مدیریت کیف پول
    // ==========================================
    Route::prefix('wallet')->controller(WalletController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/{userId}', 'show');
        Route::get('/{userId}/transactions', 'transactions');
        Route::post('/{userId}/toggle-status', 'toggleStatus');
        Route::post('/{userId}/add-bonus', 'addBonus');
    });

    // ==========================================
    // 3.20 مدیریت گزارشات
    // ==========================================
    Route::prefix('reports')->controller(ReportController::class)->group(function () {
        Route::get('/types', 'types');
        Route::post('/generate', 'generate');
        Route::post('/export-excel', 'exportExcel');
        Route::post('/export-pdf', 'exportPdf');
        Route::post('/stream-pdf', 'streamPdf');
    });

    // ==========================================
    // 3.21 مدیریت پرداخت‌ها
    // ==========================================
    Route::prefix('payments')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/gateways', 'gateways');
        Route::get('/{id}', 'show');
        Route::post('/{id}/refund', 'refund');
    });

    // ==========================================
    // 3.22 مدیریت وب‌هوک
    // ==========================================
    Route::prefix('webhook')->controller(WebhookController::class)->group(function () {
        Route::get('/status', 'status');
        Route::post('/toggle', 'toggle');
        Route::get('/logs', 'logs');
        Route::get('/settings', 'settings');
        Route::put('/settings', 'updateSettings');
        Route::post('/test', 'test');
    });

    // ==========================================
    // 3.23 مدیریت یادآوری‌ها
    // ==========================================
    Route::prefix('reminders')->controller(ReminderController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/process', 'process');
        Route::get('/settings', 'settings');
        Route::put('/settings', 'updateSettings');
        Route::get('/stats', 'stats');
    });

    // ==========================================
    // 3.24 مدیریت فاکتورها
    // ==========================================
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::put('/{id}', [InvoiceController::class, 'update']);
        Route::delete('/{id}', [InvoiceController::class, 'destroy']);
        Route::post('/{id}/status', [InvoiceController::class, 'changeStatus']);
        Route::get('/{id}/print', [InvoiceController::class, 'print']);
        Route::get('/stats', [InvoiceController::class, 'stats']);
    });

    // ==========================================
    // 3.25 مدیریت نظرات
    // ==========================================
    Route::prefix('ratings')->group(function () {
        Route::get('/', [RatingController::class, 'index']);
        Route::get('/{id}', [RatingController::class, 'show']);
        Route::delete('/{id}', [RatingController::class, 'destroy']);
        Route::post('/{id}/approve', [RatingController::class, 'approve']);
        Route::post('/{id}/reject', [RatingController::class, 'reject']);
        Route::post('/{id}/reply', [RatingController::class, 'reply']);
        Route::get('/stats', [RatingController::class, 'stats']);
    });

    // ==========================================
    // 3.26 مدیریت ارجاعات
    // ==========================================
    Route::prefix('referrals')->group(function () {
        Route::get('/', [ReferralController::class, 'index']);
        Route::post('/', [ReferralController::class, 'store']);
        Route::get('/{id}', [ReferralController::class, 'show']);
        Route::put('/{id}', [ReferralController::class, 'update']);
        Route::delete('/{id}', [ReferralController::class, 'destroy']);
        Route::post('/{id}/accept', [ReferralController::class, 'accept']);
        Route::post('/{id}/reject', [ReferralController::class, 'reject']);
        Route::post('/{id}/complete', [ReferralController::class, 'complete']);
        Route::get('/stats', [ReferralController::class, 'stats']);
    });

    // ==========================================
    // 3.27 مدیریت یادداشت‌های پزشکی
    // ==========================================
    Route::prefix('medical-notes')->controller(MedicalNoteController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/search', 'search');
        Route::get('/stats', 'stats');
        Route::get('/patient/{patientId}', 'patientNotes');
        Route::get('/patient-summary/{patientId}', 'patientSummary');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/status', 'changeStatus');
        Route::post('/export', 'export');
    });

    // ==========================================
    // 3.28 مدیریت اورژانس (ادمین)
    // ==========================================
    Route::prefix('emergency')->controller(EmergencyController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}/triage', 'triage');
        Route::post('/{id}/start-exam', 'startExam');
        Route::post('/{id}/start-treatment', 'startTreatment');
        Route::post('/{id}/dispatch', 'dispatchAmbulance');
        Route::post('/{id}/arrived', 'arrived');
        Route::post('/{id}/complete', 'complete');
        Route::post('/{id}/admit', 'admit');
        Route::post('/{id}/discharge', 'discharge');
        Route::post('/{id}/transfer', 'transfer');
        Route::get('/stats', 'stats');
    });

    // ==========================================
    // 3.29 مدیریت بیمه
    // ==========================================
    Route::prefix('insurance')->group(function () {
        Route::get('/', [InsuranceController::class, 'index']);
        Route::post('/', [InsuranceController::class, 'store']);
        Route::get('/{id}', [InsuranceController::class, 'show']);
        Route::put('/{id}', [InsuranceController::class, 'update']);
        Route::delete('/{id}', [InsuranceController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [InsuranceController::class, 'toggleStatus']);
        Route::post('/assign-to-patient', [InsuranceController::class, 'assignToPatient']);
        Route::get('/patients/{patientId}/insurances', [InsuranceController::class, 'patientInsurances']);
        Route::get('/patients/{patientId}/primary', [InsuranceController::class, 'patientPrimaryInsurance']);
        Route::put('/patient-insurances/{id}', [InsuranceController::class, 'updatePatientInsurance']);
        Route::post('/patient-insurances/{id}/deactivate', [InsuranceController::class, 'deactivatePatientInsurance']);
        Route::post('/apply-to-appointment', [InsuranceController::class, 'applyToAppointment']);
        Route::get('/appointments/{appointmentId}', [InsuranceController::class, 'appointmentInsurance']);
        Route::post('/claims/{id}/approve', [InsuranceController::class, 'approveClaim']);
        Route::post('/claims/{id}/reject', [InsuranceController::class, 'rejectClaim']);
        Route::get('/stats', [InsuranceController::class, 'stats']);
        Route::get('/reports/{insuranceId}', [InsuranceController::class, 'insuranceReport']);
    });

    // ==========================================
    // 3.30 مدیریت واکسیناسیون
    // ==========================================
    Route::prefix('vaccination')->group(function () {
        Route::get('/', [VaccinationController::class, 'index']);
        Route::post('/', [VaccinationController::class, 'store']);
        Route::get('/{id}', [VaccinationController::class, 'show']);
        Route::put('/{id}', [VaccinationController::class, 'update']);
        Route::delete('/{id}', [VaccinationController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [VaccinationController::class, 'toggleStatus']);
        Route::post('/record', [VaccinationController::class, 'record']);
        Route::get('/patients/{patientId}/vaccinations', [VaccinationController::class, 'patientVaccinations']);
        Route::get('/patients/{patientId}/summary', [VaccinationController::class, 'patientSummary']);
        Route::get('/patients/{patientId}/upcoming', [VaccinationController::class, 'upcoming']);
        Route::get('/patients/{patientId}/overdue', [VaccinationController::class, 'overdue']);
        Route::get('/patients/{patientId}/reminders', [VaccinationController::class, 'reminders']);
        Route::post('/reminders/process', [VaccinationController::class, 'processReminders']);
        Route::get('/stats', [VaccinationController::class, 'stats']);
    });

    // ==========================================
    // 3.31 مدیریت نظرسنجی
    // ==========================================
    Route::prefix('survey')->group(function () {
        Route::get('/', [SurveyController::class, 'index']);
        Route::post('/', [SurveyController::class, 'store']);
        Route::get('/{id}', [SurveyController::class, 'show']);
        Route::put('/{id}', [SurveyController::class, 'update']);
        Route::delete('/{id}', [SurveyController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [SurveyController::class, 'toggleStatus']);
        Route::get('/{surveyId}/responses', [SurveyController::class, 'surveyResponses']);
        Route::get('/patients/{patientId}/responses', [SurveyController::class, 'patientResponses']);
        Route::get('/feedbacks', [SurveyController::class, 'feedbacks']);
        Route::post('/feedbacks/{id}/reply', [SurveyController::class, 'replyFeedback']);
        Route::post('/feedbacks/{id}/resolve', [SurveyController::class, 'resolveFeedback']);
        Route::get('/stats', [SurveyController::class, 'stats']);
    });

    // ==========================================
    // 3.32 مدیریت سیستم
    // ==========================================
    Route::prefix('system')->group(function () {
        Route::get('/info', [SystemController::class, 'info']);
        Route::get('/logs', [SystemController::class, 'logs']);
        Route::get('/logs/{filename}', [SystemController::class, 'logContent']);
        Route::delete('/logs/{filename}', [SystemController::class, 'deleteLog']);
        Route::delete('/logs', [SystemController::class, 'clearLogs']);
        Route::post('/clear-cache', [SystemController::class, 'clearCache']);
    });

    // ==========================================
    // 3.33 هوش تجاری (BI)
    // ==========================================
    Route::prefix('bi')->group(function () {
        Route::get('/predict/appointments', [BIController::class, 'predictAppointments']);
        Route::get('/forecast/revenue', [BIController::class, 'forecastRevenue']);
        Route::get('/segment/patients', [BIController::class, 'segmentPatients']);
        Route::get('/analyze/doctors', [BIController::class, 'analyzeDoctors']);
        Route::get('/analytics', [BIController::class, 'getAnalytics']);
        Route::get('/reports', [BIController::class, 'reports']);
        Route::post('/reports', [BIController::class, 'createReport']);
        Route::put('/reports/{id}', [BIController::class, 'updateReport']);
        Route::delete('/reports/{id}', [BIController::class, 'deleteReport']);
        Route::post('/reports/{id}/generate', [BIController::class, 'generateReport']);
        Route::post('/schedules', [BIController::class, 'createSchedule']);
        Route::put('/schedules/{id}', [BIController::class, 'updateSchedule']);
        Route::delete('/schedules/{id}', [BIController::class, 'deleteSchedule']);
        Route::post('/backup/database', [BIController::class, 'backupDatabase']);
        Route::post('/backup/files', [BIController::class, 'backupFiles']);
        Route::post('/backup/{id}/restore', [BIController::class, 'restoreBackup']);
        Route::get('/backup/history', [BIController::class, 'backupHistory']);
        Route::delete('/backup/cleanup', [BIController::class, 'cleanupBackups']);
        Route::get('/audit-logs', [BIController::class, 'auditLogs']);
        Route::post('/audit-logs', [BIController::class, 'logActivity']);
        Route::post('/logs/archive', [BIController::class, 'archiveLogs']);
        Route::get('/logs/archived', [BIController::class, 'archivedLogs']);
        Route::post('/logs/archived/{id}/restore', [BIController::class, 'restoreArchivedLog']);
        Route::delete('/logs/archived/cleanup', [BIController::class, 'cleanupArchivedLogs']);
        Route::get('/stats', [BIController::class, 'stats']);
    });

    // ==========================================
    // 3.34 AiChat مدیریت
    // ==========================================
    Route::prefix('ai')->group(function () {
        // ===== پرامپت‌ها =====
        Route::get('/prompts', [AIChatAdminController::class, 'prompts']);
        Route::post('/prompts', [AIChatAdminController::class, 'storePrompt']);
        Route::put('/prompts/{id}', [AIChatAdminController::class, 'updatePrompt']);
        Route::delete('/prompts/{id}', [AIChatAdminController::class, 'deletePrompt']);
        Route::post('/prompts/{id}/toggle', [AIChatAdminController::class, 'togglePrompt']);

        // ===== تنظیمات =====
        Route::get('/settings', [AIChatAdminController::class, 'settings']);
        Route::put('/settings', [AIChatAdminController::class, 'updateSettings']);

        // ===== مدل‌ها =====
        Route::get('/models', [AIChatAdminController::class, 'models']);
        Route::post('/models/test', [AIChatAdminController::class, 'testModel']);

        // ===== آمار و تحلیل =====
        Route::get('/analytics', [AIChatAdminController::class, 'analytics']);
        Route::get('/analytics/queries', [AIChatAdminController::class, 'queries']);
        Route::get('/analytics/export', [AIChatAdminController::class, 'exportAnalytics']);

        // ===== پاکسازی =====
        Route::post('/cleanup', [AIChatAdminController::class, 'cleanup']);
    });
});

// ============================================================================
// 4. مسیرهای سوپرادمین
// ============================================================================
Route::middleware(['auth:sanctum', 'role:super_admin'])
    ->prefix('super-admin')
    ->group(function () {
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::get('/tenants/{id}', [TenantController::class, 'show']);
        Route::put('/tenants/{id}', [TenantController::class, 'update']);
        Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus']);
        Route::get('/stats', [TenantController::class, 'stats']);
        Route::get('/plans', [TenantController::class, 'plans']);
    });
