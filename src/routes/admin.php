<?php

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
use App\Http\Controllers\Api\Dashboard\ManagementDashboardController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\VaccinationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\DoctorProfileController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\DrugController;
use App\Http\Controllers\Admin\PharmacyManagementController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\SpecialtyMediaController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SuperAdmin\TenantController;
use App\Http\Controllers\Admin\MedicalNoteController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| نسخه: 1.0.0
| تاریخ: 2026-07-09
|--------------------------------------------------------------------------
*/

// ==========================================
// تست - فقط برای بررسی لود شدن فایل
// ==========================================
Route::get('/test-route', function() {
    return response()->json(['message' => 'Admin routes loaded!']);
});

// ============================================================================
// 1. مسیرهای عمومی ادمین (بدون احراز هویت)
// ============================================================================
Route::post('/login', [AdminAuthController::class, 'loginWithEmail']);

// ============================================================================
// 2. مسیرهای احراز هویت ادمین (نیاز به توکن)
// ============================================================================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);
});

// ============================================================================
// 3. مسیرهای محافظت‌شده (با احراز هویت و نقش)
// ============================================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->group(function () {

    // ==========================================
    // 3.1 پروفایل ادمین
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
    // 3.2 داشبورد مدیریت
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
    // 3.5 مدیریت نقش‌ها
    // ==========================================
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    // ==========================================
    // 3.6 مدیریت دسترسی‌ها
    // ==========================================
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
        Route::post('/assign-to-role', [PermissionController::class, 'assignToRole']);
    });

    // ==========================================
    // 3.7 مدیریت پزشکان
    // ==========================================
    Route::prefix('doctors')->group(function () {
        Route::get('/', [DoctorController::class, 'index']);
        Route::post('/', [DoctorController::class, 'store']);
        Route::get('/{id}', [DoctorController::class, 'show']);
        Route::put('/{id}', [DoctorController::class, 'update']);
        Route::delete('/{id}', [DoctorController::class, 'destroy']);
        Route::post('/{id}/toggle-availability', [DoctorController::class, 'toggleAvailability']);
        Route::post('/{id}/verify', [DoctorController::class, 'verify']);
        
        // پروفایل پزشک
        Route::prefix('profile')->group(function () {
            Route::put('/{id}', [DoctorProfileController::class, 'update']);
            Route::post('/{id}/verify', [DoctorProfileController::class, 'verify']);
            Route::post('/{id}/unverify', [DoctorProfileController::class, 'unverify']);
            Route::put('/{id}/location', [LocationController::class, 'updateDoctorLocation']);
        });
    });

    // ==========================================
    // 3.8 مدیریت نوبت‌ها
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
        Route::post('/{id}/start', [AppointmentController::class, 'start']);
        Route::post('/{id}/complete', [AppointmentController::class, 'complete']);
        Route::get('/stats', [AppointmentController::class, 'stats']);
        Route::get('/doctors/{doctorId}/available-slots', [AppointmentController::class, 'getAvailableSlots']);
    });

    // ==========================================
    // 3.9 مدیریت نسخه‌ها
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
    // 3.10 مدیریت بیماران
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
    // 3.11 مدیریت تخصص‌ها
    // ==========================================
    Route::prefix('specialties')->group(function () {
        Route::get('/', [SpecialtyController::class, 'index']);
        Route::post('/', [SpecialtyController::class, 'store']);
        Route::get('/{id}', [SpecialtyController::class, 'show']);
        Route::put('/{id}', [SpecialtyController::class, 'update']);
        Route::delete('/{id}', [SpecialtyController::class, 'destroy']);
        Route::post('/{id}/toggle', [SpecialtyController::class, 'toggleStatus']);

        // Specialty Media
        Route::post('/{id}/icon', [SpecialtyMediaController::class, 'uploadIcon']);
        Route::delete('/{id}/icon', [SpecialtyMediaController::class, 'deleteIcon']);
        Route::get('/{id}/icon', [SpecialtyMediaController::class, 'getIcon']);
    });

    // ==========================================
    // 3.12 مدیریت داروها
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
    });

    // ==========================================
    // 3.13 مدیریت داروخانه‌ها
    // ==========================================
    Route::prefix('pharmacies')->group(function () {
        Route::get('/', [PharmacyManagementController::class, 'index']);
        Route::post('/', [PharmacyManagementController::class, 'store']);
        Route::get('/{id}', [PharmacyManagementController::class, 'show']);
        Route::put('/{id}', [PharmacyManagementController::class, 'update']);
        Route::delete('/{id}', [PharmacyManagementController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [PharmacyManagementController::class, 'toggleStatus']);
        Route::post('/{id}/toggle-online', [PharmacyManagementController::class, 'toggleOnline']);
    });

    // ==========================================
    // 3.14 مدیریت سفارشات داروخانه
    // ==========================================
    Route::prefix('pharmacy')->group(function () {
        Route::get('/admin/orders', [PharmacyController::class, 'pharmacyOrders']);
    });

    // ==========================================
    // 3.15 مدیریت سئو
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
    // 3.16 مدیریت اعلان‌ها
    // ==========================================
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        // GET
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/recent', 'recent');
        Route::get('/unread', 'unread');
        Route::get('/unread/count', 'unreadCount');
        Route::get('/user/{userId}', 'userNotifications');
        Route::get('/{id}', 'show');

        // POST
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

        // DELETE
        Route::delete('/{id}', 'destroy');
        Route::delete('/delete-all-read', 'deleteAllRead');
    });

    // ==========================================
    // 3.17 مدیریت وبلاگ
    // ==========================================
    Route::prefix('blog')->controller(BlogController::class)->group(function () {
        // Posts
        Route::get('/posts', 'posts');
        Route::post('/posts', 'storePost');
        Route::get('/posts/{id}', 'adminShowPost');
        Route::put('/posts/{id}', 'updatePost');
        Route::delete('/posts/{id}', 'deletePost');
        Route::post('/posts/{id}/publish', 'publishPost');
        Route::post('/posts/{id}/unpublish', 'unpublishPost');

        // Categories
        Route::get('/categories', 'adminCategories');
        Route::post('/categories', 'storeCategory');
        Route::put('/categories/{id}', 'updateCategory');
        Route::delete('/categories/{id}', 'deleteCategory');

        // Tags
        Route::get('/tags', 'adminTags');
        Route::post('/tags', 'storeTag');
        Route::put('/tags/{id}', 'updateTag');
        Route::delete('/tags/{id}', 'deleteTag');

        // Comments
        Route::get('/comments', 'adminComments');
        Route::post('/comments/{id}/approve', 'approveComment');
        Route::post('/comments/{id}/reject', 'rejectComment');
        Route::delete('/comments/{id}', 'deleteComment');

        // Stats
        Route::get('/stats', 'stats');
    });

    // ==========================================
    // 3.18 مدیریت کیف پول
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
    // 3.19 مدیریت گزارشات
    // ==========================================
    Route::prefix('reports')->controller(ReportController::class)->group(function () {
        Route::get('/types', 'types');
        Route::post('/generate', 'generate');
        Route::post('/export-excel', 'exportExcel');
        Route::post('/export-pdf', 'exportPdf');
        Route::post('/stream-pdf', 'streamPdf');
    });

    // ==========================================
    // 3.20 مدیریت پرداخت‌ها
    // ==========================================
    Route::prefix('payments')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/gateways', 'gateways');
        Route::get('/{id}', 'show');
        Route::post('/{id}/refund', 'refund');
    });

    // ==========================================
    // 3.21 مدیریت وب‌هوک
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
    // 3.22 مدیریت یادآوری‌ها
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
    // 3.23 مدیریت فاکتورها
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
    // 3.24 مدیریت نظرات
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
    // 3.25 مدیریت ارجاعات
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
    // 3.26 مدیریت یادداشت‌های پزشکی
    // ==========================================
    Route::prefix('medical-notes')->controller(MedicalNoteController::class)->group(function () {
        // لیست و جستجو
        Route::get('/', 'index');
        Route::get('/search', 'search');
        Route::get('/stats', 'stats');
        
        // بیمار
        Route::get('/patient/{patientId}', 'patientNotes');
        Route::get('/patient-summary/{patientId}', 'patientSummary');
        
        // CRUD
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/status', 'changeStatus');
        
        // خروجی
        Route::post('/export', 'export');
    });

    // ==========================================
    // 3.27 مدیریت بیمه
    // ==========================================
    Route::prefix('insurance')->group(function () {
        Route::get('/', [InsuranceController::class, 'index']);
        Route::post('/', [InsuranceController::class, 'store']);
        Route::get('/{id}', [InsuranceController::class, 'show']);
        Route::put('/{id}', [InsuranceController::class, 'update']);
        Route::delete('/{id}', [InsuranceController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [InsuranceController::class, 'toggleStatus']);

        // Patient Insurance
        Route::post('/assign-to-patient', [InsuranceController::class, 'assignToPatient']);
        Route::get('/patients/{patientId}/insurances', [InsuranceController::class, 'patientInsurances']);
        Route::get('/patients/{patientId}/primary', [InsuranceController::class, 'patientPrimaryInsurance']);
        Route::put('/patient-insurances/{id}', [InsuranceController::class, 'updatePatientInsurance']);
        Route::post('/patient-insurances/{id}/deactivate', [InsuranceController::class, 'deactivatePatientInsurance']);

        // Appointment Insurance
        Route::post('/apply-to-appointment', [InsuranceController::class, 'applyToAppointment']);
        Route::get('/appointments/{appointmentId}', [InsuranceController::class, 'appointmentInsurance']);

        // Claims
        Route::post('/claims/{id}/approve', [InsuranceController::class, 'approveClaim']);
        Route::post('/claims/{id}/reject', [InsuranceController::class, 'rejectClaim']);

        // Reports
        Route::get('/stats', [InsuranceController::class, 'stats']);
        Route::get('/reports/{insuranceId}', [InsuranceController::class, 'insuranceReport']);
    });

    // ==========================================
    // 3.28 مدیریت واکسیناسیون
    // ==========================================
    Route::prefix('vaccination')->group(function () {
        Route::get('/', [VaccinationController::class, 'index']);
        Route::post('/', [VaccinationController::class, 'store']);
        Route::get('/{id}', [VaccinationController::class, 'show']);
        Route::put('/{id}', [VaccinationController::class, 'update']);
        Route::delete('/{id}', [VaccinationController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [VaccinationController::class, 'toggleStatus']);

        // Patient Vaccinations
        Route::post('/record', [VaccinationController::class, 'record']);
        Route::get('/patients/{patientId}/vaccinations', [VaccinationController::class, 'patientVaccinations']);
        Route::get('/patients/{patientId}/summary', [VaccinationController::class, 'patientSummary']);
        Route::get('/patients/{patientId}/upcoming', [VaccinationController::class, 'upcoming']);
        Route::get('/patients/{patientId}/overdue', [VaccinationController::class, 'overdue']);

        // Reminders
        Route::get('/patients/{patientId}/reminders', [VaccinationController::class, 'reminders']);
        Route::post('/reminders/process', [VaccinationController::class, 'processReminders']);

        // Stats
        Route::get('/stats', [VaccinationController::class, 'stats']);
    });

    // ==========================================
    // 3.29 مدیریت نظرسنجی
    // ==========================================
    Route::prefix('survey')->group(function () {
        Route::get('/', [SurveyController::class, 'index']);
        Route::post('/', [SurveyController::class, 'store']);
        Route::get('/{id}', [SurveyController::class, 'show']);
        Route::put('/{id}', [SurveyController::class, 'update']);
        Route::delete('/{id}', [SurveyController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [SurveyController::class, 'toggleStatus']);

        // Responses
        Route::get('/{surveyId}/responses', [SurveyController::class, 'surveyResponses']);
        Route::get('/patients/{patientId}/responses', [SurveyController::class, 'patientResponses']);

        // Feedback
        Route::get('/feedbacks', [SurveyController::class, 'feedbacks']);
        Route::post('/feedbacks/{id}/reply', [SurveyController::class, 'replyFeedback']);
        Route::post('/feedbacks/{id}/resolve', [SurveyController::class, 'resolveFeedback']);

        // Stats
        Route::get('/stats', [SurveyController::class, 'stats']);
    });

    // ==========================================
    // 3.30 مدیریت سیستم
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
    // 3.31 هوش تجاری (BI)
    // ==========================================
    Route::prefix('bi')->group(function () {
        // Predictive Analytics
        Route::get('/predict/appointments', [BIController::class, 'predictAppointments']);
        Route::get('/forecast/revenue', [BIController::class, 'forecastRevenue']);
        Route::get('/segment/patients', [BIController::class, 'segmentPatients']);
        Route::get('/analyze/doctors', [BIController::class, 'analyzeDoctors']);
        Route::get('/analytics', [BIController::class, 'getAnalytics']);

        // Custom Reports
        Route::get('/reports', [BIController::class, 'reports']);
        Route::post('/reports', [BIController::class, 'createReport']);
        Route::put('/reports/{id}', [BIController::class, 'updateReport']);
        Route::delete('/reports/{id}', [BIController::class, 'deleteReport']);
        Route::post('/reports/{id}/generate', [BIController::class, 'generateReport']);

        // Report Scheduling
        Route::post('/schedules', [BIController::class, 'createSchedule']);
        Route::put('/schedules/{id}', [BIController::class, 'updateSchedule']);
        Route::delete('/schedules/{id}', [BIController::class, 'deleteSchedule']);

        // Backup
        Route::post('/backup/database', [BIController::class, 'backupDatabase']);
        Route::post('/backup/files', [BIController::class, 'backupFiles']);
        Route::post('/backup/{id}/restore', [BIController::class, 'restoreBackup']);
        Route::get('/backup/history', [BIController::class, 'backupHistory']);
        Route::delete('/backup/cleanup', [BIController::class, 'cleanupBackups']);

        // Audit Log
        Route::get('/audit-logs', [BIController::class, 'auditLogs']);
        Route::post('/audit-logs', [BIController::class, 'logActivity']);

        // Log Archive
        Route::post('/logs/archive', [BIController::class, 'archiveLogs']);
        Route::get('/logs/archived', [BIController::class, 'archivedLogs']);
        Route::post('/logs/archived/{id}/restore', [BIController::class, 'restoreArchivedLog']);
        Route::delete('/logs/archived/cleanup', [BIController::class, 'cleanupArchivedLogs']);

        // Stats
        Route::get('/stats', [BIController::class, 'stats']);
    });

}); // End auth:sanctum + role

// ============================================================================
// 4. مسیرهای سوپرادمین (فقط super_admin)
// ============================================================================
Route::middleware(['auth:sanctum', 'role:super_admin'])
    ->prefix('super-admin')
    ->group(function () {
        // مدیریت Tenant
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::get('/tenants/{id}', [TenantController::class, 'show']);
        Route::put('/tenants/{id}', [TenantController::class, 'update']);
        Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus']);
        Route::get('/stats', [TenantController::class, 'stats']);
        Route::get('/plans', [TenantController::class, 'plans']);
    });

// ==========================================
// 💰 DOCTOR APPOINTMENT FEE MANAGEMENT (ADMIN)
// ==========================================
Route::prefix('doctors')->group(function () {
    Route::post('/{id}/set-fee', [App\Http\Controllers\Admin\DoctorController::class, 'setAppointmentFee']);
    Route::get('/{id}/fee', [App\Http\Controllers\Admin\DoctorController::class, 'getAppointmentFee']);
});

// ============================================================
// AiChat Admin Routes - مدیریت دکتر آنلاین
// ============================================================
Route::prefix('v1/admin/chat')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // مدیریت پرامپت‌ها
    Route::get('/prompts', [App\Http\Controllers\Admin\AiChat\PromptController::class, 'index']);
    Route::post('/prompts', [App\Http\Controllers\Admin\AiChat\PromptController::class, 'store']);
    Route::put('/prompts/{id}', [App\Http\Controllers\Admin\AiChat\PromptController::class, 'update']);
    Route::delete('/prompts/{id}', [App\Http\Controllers\Admin\AiChat\PromptController::class, 'destroy']);
    Route::post('/prompts/{id}/toggle', [App\Http\Controllers\Admin\AiChat\PromptController::class, 'toggle']);

    // مدیریت تنظیمات
    Route::get('/settings', [App\Http\Controllers\Admin\AiChat\SettingsController::class, 'index']);
    Route::put('/settings', [App\Http\Controllers\Admin\AiChat\SettingsController::class, 'update']);

    // مدیریت مدل‌ها
    Route::get('/models', [App\Http\Controllers\Admin\AiChat\ModelController::class, 'index']);
    Route::post('/models/test', [App\Http\Controllers\Admin\AiChat\ModelController::class, 'test']);

    // آمار و گزارشات
    Route::get('/analytics', [App\Http\Controllers\Admin\AiChat\AnalyticsController::class, 'index']);
    Route::get('/analytics/queries', [App\Http\Controllers\Admin\AiChat\AnalyticsController::class, 'queries']);
    Route::get('/analytics/export', [App\Http\Controllers\Admin\AiChat\AnalyticsController::class, 'export']);

    // پاکسازی دستی
    Route::post('/cleanup', [App\Http\Controllers\Admin\AiChat\CleanupController::class, 'run']);
});
