<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PharmacyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================
// 1. PUBLIC ROUTES (بدون احراز هویت)
// ============================================================

// 1.1 Auth
Route::post('/auth/login/mobile', [AuthController::class, 'loginWithMobile']);
Route::post('/auth/login/mobile/verify', [AuthController::class, 'verifyOtp']);
Route::post('/auth/login/email', [AuthController::class, 'loginWithEmail']);

// 1.2 Doctors (Public)
Route::get('/doctors/public', [DoctorController::class, 'publicList']);
Route::get('/doctors/{id}/public', [DoctorController::class, 'publicShow']);

// 1.3 Patients (Public Search)
Route::get('/patients/search/by-national-code', [PatientController::class, 'findByNationalCode']);
Route::get('/patients/search/by-mobile', [PatientController::class, 'findByMobile']);

// 1.4 Appointment (Public - Available Slots)
Route::get('/appointments/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlots']);

// 1.5 Payment Callbacks
Route::get('/payments/callback/{gateway}', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/payments/callback/{gateway}', [PaymentController::class, 'callback']);
Route::any('/pharmacy/payment/callback/{gateway}', [PharmacyController::class, 'paymentCallback'])->name('pharmacy.payment.callback');

// ============================================================
// 2. PROTECTED ROUTES (نیاز به احراز هویت - auth:sanctum)
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // ============================================================
    // 2.1 AUTH
    // ============================================================
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // ============================================================
    // 2.2 PROFILE
    // ============================================================
    Route::prefix('profile')->group(function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/address', [ProfileController::class, 'updateAddress']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
    });

    // ============================================================
    // 2.3 DASHBOARD
    // ============================================================
    Route::prefix('dashboard')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin']);
        Route::get('/doctor', [DashboardController::class, 'doctor']);
        Route::get('/patient', [DashboardController::class, 'patient']);
    });

    // ============================================================
    // 2.4 APPOINTMENTS
    // ============================================================
    Route::prefix('appointments')->group(function () {
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        Route::put('/{id}', [AppointmentController::class, 'update']);
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule']);
        Route::post('/{id}/start', [AppointmentController::class, 'start']);
        Route::post('/{id}/complete', [AppointmentController::class, 'complete']);
        Route::post('/{id}/no-show', [AppointmentController::class, 'noShow']);
        Route::get('/my/appointments', [AppointmentController::class, 'myAppointments']);
        Route::get('/my/stats', [AppointmentController::class, 'myPatientStats']);
        Route::get('/my/doctor/appointments', [AppointmentController::class, 'myDoctorAppointments']);
        Route::get('/my/doctor/stats', [AppointmentController::class, 'myDoctorStats']);
    });

    // ============================================================
    // 2.5 PRESCRIPTIONS
    // ============================================================
    Route::prefix('prescriptions')->group(function () {
        Route::get('/', [PrescriptionController::class, 'index']);
        Route::post('/', [PrescriptionController::class, 'store']);
        Route::get('/{id}', [PrescriptionController::class, 'show']);
        Route::put('/{id}', [PrescriptionController::class, 'update']);
        Route::delete('/{id}', [PrescriptionController::class, 'destroy']);
        Route::post('/{id}/status', [PrescriptionController::class, 'changeStatus']);
        Route::get('/my', [PrescriptionController::class, 'myPrescriptions']);
        Route::get('/doctor/my', [PrescriptionController::class, 'myDoctorPrescriptions']);
        Route::get('/patient/{patientId}', [PrescriptionController::class, 'patientPrescriptions']);
        Route::get('/{id}/interactions', [PrescriptionController::class, 'checkInteractions']);
        Route::get('/{id}/print', [PrescriptionController::class, 'print']);
        Route::get('/stats', [PrescriptionController::class, 'stats']);
    });

    // ============================================================
    // 2.6 INVOICES
    // ============================================================
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/my', [InvoiceController::class, 'myInvoices']);
        Route::get('/stats', [InvoiceController::class, 'stats']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
    });

    // ============================================================
    // 2.7 PAYMENTS
    // ============================================================
    Route::prefix('payments')->group(function () {
        Route::get('/gateways', [PaymentController::class, 'gateways']);
        Route::post('/initiate', [PaymentController::class, 'initiate']);
        Route::get('/status/{invoiceId}', [PaymentController::class, 'status']);
        Route::get('/history', [PaymentController::class, 'history']);
        Route::post('/refund/{paymentId}', [PaymentController::class, 'refund']);
    });

    // ============================================================
    // 2.8 PHARMACY
    // ============================================================
    Route::prefix('pharmacy')->group(function () {
        Route::get('/nearby', [PharmacyController::class, 'nearby']);
        Route::get('/contracted', [PharmacyController::class, 'contracted']);
        Route::post('/orders', [PharmacyController::class, 'store']);
        Route::get('/orders', [PharmacyController::class, 'myOrders']);
        Route::get('/orders/{id}', [PharmacyController::class, 'show']);
        Route::post('/orders/{id}/pay', [PharmacyController::class, 'pay']);
        Route::post('/orders/{id}/cancel', [PharmacyController::class, 'cancel']);
        Route::get('/notifications', [PharmacyController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [PharmacyController::class, 'markNotificationAsRead']);
    });

    // ============================================================
    // 2.9 PATIENTS (Protected - Admin/Doctor)
    // ============================================================
    Route::prefix('patients')->middleware(['role:admin|super_admin|doctor'])->group(function () {
        Route::get('/without-doctor', [PatientController::class, 'withoutDoctor']);
        Route::get('/top', [PatientController::class, 'topPatients']);
        Route::get('/my/patients', [PatientController::class, 'myPatients']);
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
    });

    // ============================================================
    // 2.10 ADMIN ROUTES (فقط ادمین)
    // ============================================================
    Route::prefix('admin')->middleware(['role:admin|super_admin'])->group(function () {

        // Users
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('users/{id}/assign-role', [UserController::class, 'assignRole']);

        // Roles
        Route::apiResource('roles', RoleController::class);

        // Permissions
        Route::apiResource('permissions', PermissionController::class);
        Route::post('permissions/assign-to-role', [PermissionController::class, 'assignToRole']);

        // Doctors (Admin Management)
        Route::apiResource('doctors', DoctorController::class);
        Route::post('doctors/{id}/toggle-availability', [DoctorController::class, 'toggleAvailability']);
        Route::post('doctors/{id}/verify', [DoctorController::class, 'verify']);

        // Pharmacy Admin Orders
        Route::get('/pharmacy/orders', [PharmacyController::class, 'pharmacyOrders']);
    });

    // ============================================================
    // 2.11 PHARMACY ADMIN (ادمین/داروخانه)
    // ============================================================
    Route::prefix('pharmacy')->middleware(['role:admin|super_admin'])->group(function () {
        Route::get('/admin/orders', [PharmacyController::class, 'pharmacyOrders']);
    });

}); // End auth:sanctum

// ============================================================
// 3. HEALTH CHECK
// ============================================================
Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// ============================================================
// 4. FALLBACK (مسیرهای پیدا نشد)
// ============================================================
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'مسیر مورد نظر یافت نشد',
        'errors' => ['route' => 'The requested route does not exist']
    ], 404);
});


// ====== Report Routes ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('reports')->group(function () {
    Route::get('/types', [App\Http\Controllers\Api\ReportController::class, 'types']);
    Route::post('/excel', [App\Http\Controllers\Api\ReportController::class, 'excel']);
    Route::post('/pdf', [App\Http\Controllers\Api\ReportController::class, 'pdf']);
    Route::post('/stream', [App\Http\Controllers\Api\ReportController::class, 'stream']);
});

// ====== Landing Page Routes (Public) ======
Route::prefix('landing')->group(function () {
    Route::get('/', [App\Http\Controllers\LandingPageController::class, 'index']);
    Route::get('/stats', [App\Http\Controllers\LandingPageController::class, 'stats']);
    Route::get('/top-doctors', [App\Http\Controllers\LandingPageController::class, 'topDoctors']);
    Route::get('/recent-reviews', [App\Http\Controllers\LandingPageController::class, 'recentReviews']);
});

// ====== Pharmacy Management (Admin) ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('pharmacies', App\Http\Controllers\Admin\PharmacyManagementController::class);
    Route::post('pharmacies/{id}/toggle-status', [App\Http\Controllers\Admin\PharmacyManagementController::class, 'toggleStatus']);
    Route::post('pharmacies/{id}/toggle-online', [App\Http\Controllers\Admin\PharmacyManagementController::class, 'toggleOnline']);
});

// ====== Rating Routes ======
Route::prefix('ratings')->group(function () {
    Route::get('/doctors/{doctorId}', [App\Http\Controllers\Api\RatingController::class, 'doctorRatings']);
    Route::get('/doctors/{doctorId}/stats', [App\Http\Controllers\Api\RatingController::class, 'doctorStats']);
    Route::get('/top-doctors', [App\Http\Controllers\Api\RatingController::class, 'topDoctors']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\RatingController::class, 'store']);
    });
});

// ====== System Management (Admin) ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('system')->group(function () {
    Route::get('/info', [App\Http\Controllers\Admin\SystemController::class, 'info']);
    Route::get('/logs', [App\Http\Controllers\Admin\SystemController::class, 'logs']);
    Route::get('/logs/{filename}', [App\Http\Controllers\Admin\SystemController::class, 'logContent']);
    Route::delete('/logs/{filename}', [App\Http\Controllers\Admin\SystemController::class, 'deleteLog']);
    Route::delete('/logs', [App\Http\Controllers\Admin\SystemController::class, 'clearLogs']);
    Route::post('/clear-cache', [App\Http\Controllers\Admin\SystemController::class, 'clearCache']);
});

// ====== Chat Routes ======
Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
    Route::get('/conversations', [App\Http\Controllers\Api\ChatController::class, 'conversations']);
    Route::get('/messages/{userId}', [App\Http\Controllers\Api\ChatController::class, 'messages']);
    Route::post('/send', [App\Http\Controllers\Api\ChatController::class, 'send']);
    Route::get('/unread-count', [App\Http\Controllers\Api\ChatController::class, 'unreadCount']);
    Route::post('/mark-as-read/{userId}', [App\Http\Controllers\Api\ChatController::class, 'markAllAsRead']);
    Route::get('/recent', [App\Http\Controllers\Api\ChatController::class, 'recent']);
});

// ====== Public SEO Routes ======
Route::get('/seo/doctor/{slug}', [App\Http\Controllers\Api\PublicController::class, 'doctorSeo']);
Route::get('/seo/page', [App\Http\Controllers\Api\PublicController::class, 'pageSeo']);

// ====== Admin SEO Routes ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('seo', App\Http\Controllers\Admin\SeoController::class);
    Route::get('/seo/model', [App\Http\Controllers\Admin\SeoController::class, 'getByModel']);
});

// ====== Public SEO Routes ======
Route::get('/seo/doctor/{id}', [App\Http\Controllers\Api\PublicController::class, 'doctorSeo']);
Route::get('/seo/page', [App\Http\Controllers\Api\PublicController::class, 'pageSeo']);

// ====== Admin SEO Routes ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('seo', App\Http\Controllers\Admin\SeoController::class);
    Route::get('/seo/model', [App\Http\Controllers\Admin\SeoController::class, 'getByModel']);
});

// ====== Location Routes ======
Route::prefix('location')->group(function () {
    // جستجوی پزشکان نزدیک
    Route::get('/nearby-doctors', [App\Http\Controllers\Api\LocationController::class, 'nearByDoctors']);
    Route::get('/nearby-clinics', [App\Http\Controllers\Api\LocationController::class, 'nearByClinics']);

    // اطلاعات پایه
    Route::get('/provinces', [App\Http\Controllers\Api\LocationController::class, 'provinces']);
    Route::get('/provinces/{provinceId}/cities', [App\Http\Controllers\Api\LocationController::class, 'cities']);
    Route::get('/specialties', [App\Http\Controllers\Api\LocationController::class, 'specialties']);

    // محاسبه فاصله
    Route::post('/distance', [App\Http\Controllers\Api\LocationController::class, 'calculateDistance']);

    // پروفایل پزشک
    Route::get('/doctors/{id}/profile', [App\Http\Controllers\Api\LocationController::class, 'doctorProfile']);
    Route::get('/doctors/{id}/reviews', [App\Http\Controllers\Api\LocationController::class, 'doctorReviews']);
});

// ====== Admin Profile Management ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::put('/doctors/{id}/profile', [App\Http\Controllers\Admin\DoctorProfileController::class, 'update']);
    Route::post('/doctors/{id}/verify', [App\Http\Controllers\Admin\DoctorProfileController::class, 'verify']);
    Route::post('/doctors/{id}/unverify', [App\Http\Controllers\Admin\DoctorProfileController::class, 'unverify']);
    Route::put('/doctors/{id}/location', [App\Http\Controllers\Api\LocationController::class, 'updateDoctorLocation']);
});

// ====== Specialty Management ======
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('specialties', App\Http\Controllers\Admin\SpecialtyController::class);
    Route::post('specialties/{id}/toggle', [App\Http\Controllers\Admin\SpecialtyController::class, 'toggleStatus']);

    // مدیریت عکس تخصص
    Route::post('specialties/{id}/icon', [App\Http\Controllers\Admin\SpecialtyMediaController::class, 'uploadIcon']);
    Route::delete('specialties/{id}/icon', [App\Http\Controllers\Admin\SpecialtyMediaController::class, 'deleteIcon']);
    Route::get('specialties/{id}/icon', [App\Http\Controllers\Admin\SpecialtyMediaController::class, 'getIcon']);
});

// ====== Public Specialty Routes ======
Route::get('/specialties', [App\Http\Controllers\Admin\SpecialtyController::class, 'activeSpecialties']);

// ====== Schedule Routes ======
Route::prefix('schedules')->group(function () {
    // Public Routes
    Route::get('/doctors/{doctorId}/weekly', [App\Http\Controllers\Api\ScheduleController::class, 'weekly']);
    Route::get('/doctors/{doctorId}/calendar', [App\Http\Controllers\Api\ScheduleController::class, 'calendar']);
    Route::get('/doctors/{doctorId}/day', [App\Http\Controllers\Api\ScheduleController::class, 'daySchedule']);
    Route::get('/doctors/{doctorId}/special', [App\Http\Controllers\Api\ScheduleController::class, 'specialSchedules']);

    // Protected Routes (نیاز به احراز هویت)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/doctors/{doctorId}/weekly', [App\Http\Controllers\Api\ScheduleController::class, 'setWeekly']);
        Route::post('/doctors/{doctorId}/special', [App\Http\Controllers\Api\ScheduleController::class, 'setSpecial']);
        Route::delete('/special/{scheduleId}', [App\Http\Controllers\Api\ScheduleController::class, 'deleteSpecial']);
        Route::post('/doctors/{doctorId}/copy-previous-week', [App\Http\Controllers\Api\ScheduleController::class, 'copyFromPreviousWeek']);
    });
});

// ====== Schedule Routes ======
Route::prefix('schedules')->group(function () {
    // Public Routes
    Route::get('/doctors/{doctorId}/weekly', [App\Http\Controllers\Api\ScheduleController::class, 'weekly']);
    Route::get('/doctors/{doctorId}/calendar', [App\Http\Controllers\Api\ScheduleController::class, 'calendar']);
    Route::get('/doctors/{doctorId}/day', [App\Http\Controllers\Api\ScheduleController::class, 'daySchedule']);
    Route::get('/doctors/{doctorId}/special', [App\Http\Controllers\Api\ScheduleController::class, 'specialSchedules']);

    // Protected Routes (نیاز به احراز هویت)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/doctors/{doctorId}/weekly', [App\Http\Controllers\Api\ScheduleController::class, 'setWeekly']);
        Route::post('/doctors/{doctorId}/special', [App\Http\Controllers\Api\ScheduleController::class, 'setSpecial']);
        Route::delete('/special/{scheduleId}', [App\Http\Controllers\Api\ScheduleController::class, 'deleteSpecial']);
        Route::post('/doctors/{doctorId}/copy-previous-week', [App\Http\Controllers\Api\ScheduleController::class, 'copyFromPreviousWeek']);
    });
});

// ====== Rating Reply Routes ======
Route::middleware('auth:sanctum')->prefix('ratings')->group(function () {
    Route::post('/{id}/reply', [App\Http\Controllers\Api\RatingController::class, 'reply']);
    Route::delete('/{id}/reply', [App\Http\Controllers\Api\RatingController::class, 'deleteReply']);
});

// ====== Reminder Routes ======
Route::middleware('auth:sanctum')->prefix('reminders')->group(function () {
    Route::get('/my', [App\Http\Controllers\Api\ReminderController::class, 'myReminders']);
    Route::post('/process', [App\Http\Controllers\Api\ReminderController::class, 'process']);
    Route::get('/pending-count', [App\Http\Controllers\Api\ReminderController::class, 'pendingCount']);
});

// ====== Doctor Documents ======
Route::middleware('auth:sanctum')->prefix('doctors')->group(function () {
    Route::post('/{doctorId}/certificates', [App\Http\Controllers\Api\DoctorDocumentController::class, 'uploadCertificate']);
    Route::get('/{doctorId}/certificates', [App\Http\Controllers\Api\DoctorDocumentController::class, 'getCertificates']);
    Route::delete('/{doctorId}/certificates/{mediaId}', [App\Http\Controllers\Api\DoctorDocumentController::class, 'deleteCertificate']);
});

// ====== Doctor Social Links ======
Route::middleware('auth:sanctum')->prefix('doctors')->group(function () {
    Route::put('/{doctorId}/social-links', [App\Http\Controllers\Api\DoctorSocialController::class, 'updateSocialLinks']);
    Route::get('/{doctorId}/social-links', [App\Http\Controllers\Api\DoctorSocialController::class, 'getSocialLinks']);
});

// ====== Financial Reports ======
Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('/doctors/{doctorId}/income', [App\Http\Controllers\Api\FinancialReportController::class, 'doctorIncome']);
    Route::get('/doctors/{doctorId}/daily-income', [App\Http\Controllers\Api\FinancialReportController::class, 'dailyIncome']);
    Route::get('/doctors/{doctorId}/monthly-income', [App\Http\Controllers\Api\FinancialReportController::class, 'monthlyIncome']);
});

    // ====== Cancelled Appointments Report ======
    Route::get('/doctors/{doctorId}/cancelled-appointments', [App\Http\Controllers\Api\FinancialReportController::class, 'cancelledAppointments']);

// ====== Financial Reports ======
Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('/doctors/{doctorId}/income', [App\Http\Controllers\Api\FinancialReportController::class, 'doctorIncome']);
    Route::get('/doctors/{doctorId}/daily-income', [App\Http\Controllers\Api\FinancialReportController::class, 'dailyIncome']);
    Route::get('/doctors/{doctorId}/monthly-income', [App\Http\Controllers\Api\FinancialReportController::class, 'monthlyIncome']);
    Route::get('/doctors/{doctorId}/cancelled-appointments', [App\Http\Controllers\Api\FinancialReportController::class, 'cancelledAppointments']);
});

// ====== Referral Routes ======
Route::middleware('auth:sanctum')->prefix('referrals')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\ReferralController::class, 'store']);
    Route::get('/patients/{patientId}', [App\Http\Controllers\Api\ReferralController::class, 'patientReferrals']);
    Route::get('/doctor', [App\Http\Controllers\Api\ReferralController::class, 'doctorReferrals']);
    Route::post('/{id}/accept', [App\Http\Controllers\Api\ReferralController::class, 'accept']);
    Route::post('/{id}/reject', [App\Http\Controllers\Api\ReferralController::class, 'reject']);
    Route::post('/{id}/complete', [App\Http\Controllers\Api\ReferralController::class, 'complete']);
});

// ============================================
// NOTIFICATIONS
// ============================================

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/unread', [App\Http\Controllers\Api\NotificationController::class, 'unread']);
    Route::get('/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::get('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'show']);
    Route::put('/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::put('/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    Route::delete('/read/all', [App\Http\Controllers\Api\NotificationController::class, 'deleteRead']);
});

// ADMIN NOTIFICATION ROUTES
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/notifications')->group(function () {
    // ارسال به کاربر خاص
    Route::post('/send-to-user', [App\Http\Controllers\Api\NotificationController::class, 'sendToUser']);
    Route::post('/send-to-users', [App\Http\Controllers\Api\NotificationController::class, 'sendToUsers']);
    
    // ارسال به نقش
    Route::post('/send-to-role', [App\Http\Controllers\Api\NotificationController::class, 'sendToRole']);
    
    // ارسال به همه
    Route::post('/send-to-all', [App\Http\Controllers\Api\NotificationController::class, 'sendToAll']);
    Route::post('/send-to-doctors', [App\Http\Controllers\Api\NotificationController::class, 'sendToAllDoctors']);
    Route::post('/send-to-patients', [App\Http\Controllers\Api\NotificationController::class, 'sendToAllPatients']);
    
    // ارسال به بیماران یک پزشک
    Route::post('/send-to-doctor-patients/{doctorId}', [App\Http\Controllers\Api\NotificationController::class, 'sendToDoctorPatients']);
    
    // ارسال با فیلتر
    Route::post('/send-filtered', [App\Http\Controllers\Api\NotificationController::class, 'sendFiltered']);
    
    // مشاهده اعلان‌های کاربران
    Route::get('/user/{userId}', [App\Http\Controllers\Api\NotificationController::class, 'userNotifications']);
});

// ============================================
// DRUG MANAGEMENT (ADMIN)
// ============================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('drugs', App\Http\Controllers\Admin\DrugController::class);
    Route::post('drugs/{id}/toggle-status', [App\Http\Controllers\Admin\DrugController::class, 'toggleStatus']);
    Route::post('drugs/{id}/increase-stock', [App\Http\Controllers\Admin\DrugController::class, 'increaseStock']);
    Route::post('drugs/{id}/decrease-stock', [App\Http\Controllers\Admin\DrugController::class, 'decreaseStock']);
    Route::get('drugs/categories', [App\Http\Controllers\Admin\DrugController::class, 'categories']);
});

// PUBLIC DRUG SEARCH
Route::get('/drugs/search', [App\Http\Controllers\Admin\DrugController::class, 'search']);
Route::get('/drugs/active', [App\Http\Controllers\Admin\DrugController::class, 'activeDrugs']);


// ============================================
// CLINIC MANAGEMENT
// ============================================

Route::get('/clinic/settings', [App\Http\Controllers\Admin\ClinicController::class, 'publicSettings']);

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::get('/clinic', [App\Http\Controllers\Admin\ClinicController::class, 'show']);
    Route::put('/clinic', [App\Http\Controllers\Admin\ClinicController::class, 'update']);
    Route::post('/clinic/upload-logo', [App\Http\Controllers\Admin\ClinicController::class, 'uploadLogo']);
    Route::post('/clinic/toggle-status', [App\Http\Controllers\Admin\ClinicController::class, 'toggleStatus']);
});

// ============================================
// WEBHOOK - ISP (ویپ کلینیک)
// ============================================

// مسیر عمومی برای دریافت نوبت از ویپ کلینیک
Route::post('/webhook/appointment', [App\Http\Controllers\Api\WebhookController::class, 'appointment']);

// مدیریت Webhook (ادمین)
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::get('/webhook/status', [App\Http\Controllers\Api\WebhookController::class, 'status']);
    Route::post('/webhook/toggle', [App\Http\Controllers\Api\WebhookController::class, 'toggle']);
    Route::get('/webhook/logs', [App\Http\Controllers\Api\WebhookController::class, 'logs']);
});

// ============================================
// WEBHOOK - ISP (ویپ کلینیک)
// ============================================

// مسیر عمومی برای دریافت نوبت از ویپ کلینیک
Route::post('/webhook/appointment', [App\Http\Controllers\Api\WebhookController::class, 'appointment']);

// مدیریت Webhook (ادمین)
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::get('/webhook/status', [App\Http\Controllers\Api\WebhookController::class, 'status']);
    Route::post('/webhook/toggle', [App\Http\Controllers\Api\WebhookController::class, 'toggle']);
    Route::get('/webhook/logs', [App\Http\Controllers\Api\WebhookController::class, 'logs']);
});

// ============================================
// WALLET SYSTEM
// ============================================

Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    // کاربر
    Route::get('/balance', [App\Http\Controllers\Api\WalletController::class, 'balance']);
    Route::get('/transactions', [App\Http\Controllers\Api\WalletController::class, 'transactions']);
    Route::get('/summary', [App\Http\Controllers\Api\WalletController::class, 'summary']);
    Route::post('/deposit', [App\Http\Controllers\Api\WalletController::class, 'deposit']);
    Route::post('/pay-appointment', [App\Http\Controllers\Api\WalletController::class, 'payAppointment']);
});

// Callback پرداخت (عمومی)
Route::post('/wallet/deposit/callback', [App\Http\Controllers\Api\WalletController::class, 'depositCallback'])
    ->name('wallet.payment.callback');

// ادمین
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/wallet')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\WalletController::class, 'index']);
    Route::get('/stats', [App\Http\Controllers\Api\WalletController::class, 'stats']);
    Route::get('/{userId}', [App\Http\Controllers\Api\WalletController::class, 'show']);
    Route::post('/{userId}/toggle-status', [App\Http\Controllers\Api\WalletController::class, 'toggleStatus']);
    Route::post('/{userId}/add-bonus', [App\Http\Controllers\Api\WalletController::class, 'addBonus']);
});

// ============================================
// BLOG SYSTEM
// ============================================

// PUBLIC ROUTES
Route::prefix('blog')->group(function () {
    Route::get('/posts', [App\Http\Controllers\Api\BlogController::class, 'posts']);
    Route::get('/posts/featured', [App\Http\Controllers\Api\BlogController::class, 'featured']);
    Route::get('/posts/breaking', [App\Http\Controllers\Api\BlogController::class, 'breaking']);
    Route::get('/posts/popular', [App\Http\Controllers\Api\BlogController::class, 'popular']);
    Route::get('/posts/{slug}', [App\Http\Controllers\Api\BlogController::class, 'show']);
    Route::get('/categories', [App\Http\Controllers\Api\BlogController::class, 'categories']);
    Route::get('/tags', [App\Http\Controllers\Api\BlogController::class, 'tags']);
    Route::post('/comments', [App\Http\Controllers\Api\BlogController::class, 'comment']);
});

// ADMIN ROUTES
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/blog')->group(function () {
    // Posts
    Route::get('/posts', [App\Http\Controllers\Api\BlogController::class, 'adminPosts']);
    Route::post('/posts', [App\Http\Controllers\Api\BlogController::class, 'store']);
    Route::get('/posts/{id}', [App\Http\Controllers\Api\BlogController::class, 'adminShow']);
    Route::put('/posts/{id}', [App\Http\Controllers\Api\BlogController::class, 'update']);
    Route::delete('/posts/{id}', [App\Http\Controllers\Api\BlogController::class, 'destroy']);
    Route::post('/posts/{id}/publish', [App\Http\Controllers\Api\BlogController::class, 'publish']);
    Route::post('/posts/{id}/unpublish', [App\Http\Controllers\Api\BlogController::class, 'unpublish']);

    // Categories
    Route::get('/categories', [App\Http\Controllers\Api\BlogController::class, 'adminCategories']);
    Route::post('/categories', [App\Http\Controllers\Api\BlogController::class, 'storeCategory']);
    Route::put('/categories/{id}', [App\Http\Controllers\Api\BlogController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [App\Http\Controllers\Api\BlogController::class, 'deleteCategory']);

    // Tags
    Route::get('/tags', [App\Http\Controllers\Api\BlogController::class, 'adminTags']);
    Route::post('/tags', [App\Http\Controllers\Api\BlogController::class, 'storeTag']);
    Route::put('/tags/{id}', [App\Http\Controllers\Api\BlogController::class, 'updateTag']);
    Route::delete('/tags/{id}', [App\Http\Controllers\Api\BlogController::class, 'deleteTag']);

    // Comments
    Route::get('/comments', [App\Http\Controllers\Api\BlogController::class, 'adminComments']);
    Route::post('/comments/{id}/approve', [App\Http\Controllers\Api\BlogController::class, 'approveComment']);
    Route::post('/comments/{id}/reject', [App\Http\Controllers\Api\BlogController::class, 'rejectComment']);
    Route::delete('/comments/{id}', [App\Http\Controllers\Api\BlogController::class, 'deleteComment']);

    // Stats
    Route::get('/stats', [App\Http\Controllers\Api\BlogController::class, 'stats']);
});

// ============================================================
// RECEPTIONIST PANEL
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin|receptionist'])->prefix('receptionist')->group(function () {

    // ===== Waiting List =====
    Route::post('/waiting-list', [App\Http\Controllers\Api\ReceptionistController::class, 'addToWaitingList']);
    Route::get('/waiting-list/{doctorId}', [App\Http\Controllers\Api\ReceptionistController::class, 'waitingList']);
    Route::get('/waiting-list/{doctorId}/count', [App\Http\Controllers\Api\ReceptionistController::class, 'waitingCount']);
    Route::post('/waiting-list/{waitingId}/call', [App\Http\Controllers\Api\ReceptionistController::class, 'callPatient']);
    Route::post('/waiting-list/{waitingId}/complete', [App\Http\Controllers\Api\ReceptionistController::class, 'completePatient']);
    Route::delete('/waiting-list/{waitingId}', [App\Http\Controllers\Api\ReceptionistController::class, 'cancelWaiting']);

    // ===== Phone Appointments =====
    Route::post('/phone-appointments', [App\Http\Controllers\Api\ReceptionistController::class, 'createPhoneAppointment']);
    Route::get('/phone-appointments', [App\Http\Controllers\Api\ReceptionistController::class, 'phoneAppointments']);
    Route::post('/phone-appointments/{id}/confirm', [App\Http\Controllers\Api\ReceptionistController::class, 'confirmPhoneAppointment']);
    Route::delete('/phone-appointments/{id}', [App\Http\Controllers\Api\ReceptionistController::class, 'cancelPhoneAppointment']);

    // ===== Appointment Cards =====
    Route::post('/cards/appointment/{appointmentId}', [App\Http\Controllers\Api\ReceptionistController::class, 'generateCard']);
    Route::get('/cards/appointment/{appointmentId}', [App\Http\Controllers\Api\ReceptionistController::class, 'getCard']);
    Route::post('/cards/{cardId}/print', [App\Http\Controllers\Api\ReceptionistController::class, 'printCard']);

    // ===== Settings =====
    Route::get('/settings', [App\Http\Controllers\Api\ReceptionistController::class, 'getSettings']);
    Route::put('/settings', [App\Http\Controllers\Api\ReceptionistController::class, 'updateSettings']);

    // ===== Dashboard =====
    Route::get('/dashboard/{doctorId}', [App\Http\Controllers\Api\ReceptionistController::class, 'dashboard']);
});

// ============================================================
// EHR (Electronic Health Record)
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('ehr')->group(function () {

    // ===== Records =====
    Route::post('/records', [App\Http\Controllers\Api\EHRController::class, 'createRecord']);
    Route::get('/records/{id}', [App\Http\Controllers\Api\EHRController::class, 'getRecord']);
    Route::get('/patients/{patientId}/records', [App\Http\Controllers\Api\EHRController::class, 'patientRecords']);
    Route::put('/records/{id}', [App\Http\Controllers\Api\EHRController::class, 'updateRecord']);
    Route::delete('/records/{id}', [App\Http\Controllers\Api\EHRController::class, 'deleteRecord']);

    // ===== Visits =====
    Route::post('/visits', [App\Http\Controllers\Api\EHRController::class, 'addVisit']);
    Route::get('/records/{recordId}/visits', [App\Http\Controllers\Api\EHRController::class, 'getVisits']);

    // ===== Documents =====
    Route::post('/documents', [App\Http\Controllers\Api\EHRController::class, 'uploadDocument']);
    Route::get('/patients/{patientId}/documents', [App\Http\Controllers\Api\EHRController::class, 'getDocuments']);
    Route::delete('/documents/{id}', [App\Http\Controllers\Api\EHRController::class, 'deleteDocument']);

    // ===== Alerts =====
    Route::post('/alerts', [App\Http\Controllers\Api\EHRController::class, 'createAlert']);
    Route::get('/patients/{patientId}/alerts', [App\Http\Controllers\Api\EHRController::class, 'getPatientAlerts']);
    Route::post('/alerts/{id}/resolve', [App\Http\Controllers\Api\EHRController::class, 'resolveAlert']);

    // ===== Patient History & Stats =====
    Route::get('/patients/{patientId}/full-history', [App\Http\Controllers\Api\EHRController::class, 'fullHistory']);
    Route::get('/patients/{patientId}/stats', [App\Http\Controllers\Api\EHRController::class, 'stats']);
});

// ============================================================
// INSURANCE MANAGEMENT
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('insurance')->group(function () {

    // ===== Insurances =====
    Route::get('/', [App\Http\Controllers\Api\InsuranceController::class, 'index']);
    Route::get('/active', [App\Http\Controllers\Api\InsuranceController::class, 'activeInsurances']);
    Route::post('/', [App\Http\Controllers\Api\InsuranceController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\InsuranceController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\InsuranceController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\InsuranceController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [App\Http\Controllers\Api\InsuranceController::class, 'toggleStatus']);

    // ===== Patient Insurances =====
    Route::post('/assign-to-patient', [App\Http\Controllers\Api\InsuranceController::class, 'assignToPatient']);
    Route::get('/patients/{patientId}/insurances', [App\Http\Controllers\Api\InsuranceController::class, 'patientInsurances']);
    Route::get('/patients/{patientId}/primary', [App\Http\Controllers\Api\InsuranceController::class, 'patientPrimaryInsurance']);
    Route::put('/patient-insurances/{id}', [App\Http\Controllers\Api\InsuranceController::class, 'updatePatientInsurance']);
    Route::post('/patient-insurances/{id}/deactivate', [App\Http\Controllers\Api\InsuranceController::class, 'deactivatePatientInsurance']);

    // ===== Appointment Insurance =====
    Route::post('/apply-to-appointment', [App\Http\Controllers\Api\InsuranceController::class, 'applyToAppointment']);
    Route::get('/appointments/{appointmentId}', [App\Http\Controllers\Api\InsuranceController::class, 'appointmentInsurance']);
    Route::post('/claims/{id}/approve', [App\Http\Controllers\Api\InsuranceController::class, 'approveClaim']);
    Route::post('/claims/{id}/reject', [App\Http\Controllers\Api\InsuranceController::class, 'rejectClaim']);

    // ===== Reports =====
    Route::get('/stats', [App\Http\Controllers\Api\InsuranceController::class, 'stats']);
    Route::get('/reports/{insuranceId}', [App\Http\Controllers\Api\InsuranceController::class, 'insuranceReport']);
});

// ============================================================
// TELEMEDICINE
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('telemedicine')->group(function () {

    // ===== Sessions =====
    Route::post('/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'createSession']);
    Route::get('/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'listSessions']);
    Route::get('/sessions/{id}', [App\Http\Controllers\Api\TelemedicineController::class, 'getSession']);
    Route::get('/sessions/room/{roomName}', [App\Http\Controllers\Api\TelemedicineController::class, 'getSessionByRoom']);
    Route::get('/doctors/{doctorId}/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'doctorSessions']);
    Route::get('/patients/{patientId}/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'patientSessions']);
    Route::get('/doctors/{doctorId}/active', [App\Http\Controllers\Api\TelemedicineController::class, 'activeSessions']);
    Route::post('/sessions/{id}/start', [App\Http\Controllers\Api\TelemedicineController::class, 'startSession']);
    Route::post('/sessions/{id}/complete', [App\Http\Controllers\Api\TelemedicineController::class, 'completeSession']);
    Route::post('/sessions/{id}/cancel', [App\Http\Controllers\Api\TelemedicineController::class, 'cancelSession']);
    Route::post('/sessions/{id}/join', [App\Http\Controllers\Api\TelemedicineController::class, 'joinSession']);

    // ===== Messages =====
    Route::post('/messages', [App\Http\Controllers\Api\TelemedicineController::class, 'sendMessage']);
    Route::get('/sessions/{sessionId}/messages', [App\Http\Controllers\Api\TelemedicineController::class, 'getMessages']);
    Route::get('/sessions/{sessionId}/unread-count', [App\Http\Controllers\Api\TelemedicineController::class, 'unreadCount']);

    // ===== Files =====
    Route::post('/files', [App\Http\Controllers\Api\TelemedicineController::class, 'uploadFile']);
    Route::get('/sessions/{sessionId}/files', [App\Http\Controllers\Api\TelemedicineController::class, 'getFiles']);
    Route::delete('/files/{id}', [App\Http\Controllers\Api\TelemedicineController::class, 'deleteFile']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\TelemedicineController::class, 'stats']);
});

// ============================================================
// VACCINATION MANAGEMENT
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('vaccination')->group(function () {

    // ===== Vaccines =====
    Route::get('/', [App\Http\Controllers\Api\VaccinationController::class, 'index']);
    Route::get('/active', [App\Http\Controllers\Api\VaccinationController::class, 'activeVaccines']);
    Route::post('/', [App\Http\Controllers\Api\VaccinationController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\VaccinationController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\VaccinationController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\VaccinationController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [App\Http\Controllers\Api\VaccinationController::class, 'toggleStatus']);

    // ===== Patient Vaccinations =====
    Route::post('/record', [App\Http\Controllers\Api\VaccinationController::class, 'record']);
    Route::get('/patients/{patientId}/vaccinations', [App\Http\Controllers\Api\VaccinationController::class, 'patientVaccinations']);
    Route::get('/patients/{patientId}/summary', [App\Http\Controllers\Api\VaccinationController::class, 'patientSummary']);
    Route::get('/patients/{patientId}/upcoming', [App\Http\Controllers\Api\VaccinationController::class, 'upcoming']);
    Route::get('/patients/{patientId}/overdue', [App\Http\Controllers\Api\VaccinationController::class, 'overdue']);

    // ===== Reminders =====
    Route::get('/patients/{patientId}/reminders', [App\Http\Controllers\Api\VaccinationController::class, 'reminders']);
    Route::post('/reminders/process', [App\Http\Controllers\Api\VaccinationController::class, 'processReminders']);

    // ===== Reports =====    Route::get('/stats', [App\Http\Controllers\Api\VaccinationController::class, 'stats']);
});

// ============================================================
// SURVEY & FEEDBACK
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('survey')->group(function () {

    // ===== Surveys =====
    Route::get('/', [App\Http\Controllers\Api\SurveyController::class, 'index']);
    Route::get('/available', [App\Http\Controllers\Api\SurveyController::class, 'available']);
    Route::post('/', [App\Http\Controllers\Api\SurveyController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\SurveyController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\SurveyController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\SurveyController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [App\Http\Controllers\Api\SurveyController::class, 'toggleStatus']);

    // ===== Responses =====
    Route::post('/submit', [App\Http\Controllers\Api\SurveyController::class, 'submit']);
    Route::get('/{surveyId}/responses', [App\Http\Controllers\Api\SurveyController::class, 'surveyResponses']);
    Route::get('/patients/{patientId}/responses', [App\Http\Controllers\Api\SurveyController::class, 'patientResponses']);

    // ===== Feedback =====
    Route::post('/feedback', [App\Http\Controllers\Api\SurveyController::class, 'submitFeedback']);
    Route::get('/feedbacks', [App\Http\Controllers\Api\SurveyController::class, 'feedbacks']);
    Route::get('/patients/{patientId}/feedbacks', [App\Http\Controllers\Api\SurveyController::class, 'patientFeedbacks']);
    Route::get('/doctors/{doctorId}/feedbacks', [App\Http\Controllers\Api\SurveyController::class, 'doctorFeedbacks']);
    Route::post('/feedbacks/{id}/reply', [App\Http\Controllers\Api\SurveyController::class, 'replyFeedback']);
    Route::post('/feedbacks/{id}/resolve', [App\Http\Controllers\Api\SurveyController::class, 'resolveFeedback']);

    // ===== Reports =====
    Route::get('/stats', [App\Http\Controllers\Api\SurveyController::class, 'stats']);
});

// ============================================================
// EVENTS & CAMPAIGNS
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('events')->group(function () {

    // ===== Public =====
    Route::get('/published', [App\Http\Controllers\Api\EventController::class, 'published']);
    Route::get('/upcoming', [App\Http\Controllers\Api\EventController::class, 'upcoming']);
    Route::get('/active', [App\Http\Controllers\Api\EventController::class, 'active']);
    Route::get('/{slug}/slug', [App\Http\Controllers\Api\EventController::class, 'showBySlug']);

    // ===== Admin =====
    Route::get('/', [App\Http\Controllers\Api\EventController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\EventController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\EventController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\EventController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\EventController::class, 'destroy']);
    Route::post('/{id}/publish', [App\Http\Controllers\Api\EventController::class, 'publish']);
    Route::post('/{id}/complete', [App\Http\Controllers\Api\EventController::class, 'complete']);

    // ===== Registrations =====
    Route::post('/register', [App\Http\Controllers\Api\EventController::class, 'register']);
    Route::get('/{eventId}/registrations', [App\Http\Controllers\Api\EventController::class, 'eventRegistrations']);
    Route::post('/registrations/{id}/confirm', [App\Http\Controllers\Api\EventController::class, 'confirmRegistration']);
    Route::post('/registrations/{id}/cancel', [App\Http\Controllers\Api\EventController::class, 'cancelRegistration']);
    Route::post('/registrations/{id}/attendance', [App\Http\Controllers\Api\EventController::class, 'markAttendance']);

    // ===== Patient =====
    Route::get('/patients/{patientId}/registrations', [App\Http\Controllers\Api\EventController::class, 'patientRegistrations']);

    // ===== Stats =====
    Route::get('/stats/overview', [App\Http\Controllers\Api\EventController::class, 'stats']);
});

// ============================================================
// CAMPAIGNS
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('campaigns')->group(function () {

    Route::get('/', [App\Http\Controllers\Api\CampaignController::class, 'index']);
    Route::get('/active', [App\Http\Controllers\Api\CampaignController::class, 'active']);
    Route::post('/', [App\Http\Controllers\Api\CampaignController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'destroy']);
    Route::post('/{id}/activate', [App\Http\Controllers\Api\CampaignController::class, 'activate']);
    Route::post('/{id}/pause', [App\Http\Controllers\Api\CampaignController::class, 'pause']);
    Route::post('/{id}/complete', [App\Http\Controllers\Api\CampaignController::class, 'complete']);

    // ===== Interactions =====
    Route::post('/interactions', [App\Http\Controllers\Api\CampaignController::class, 'trackInteraction']);
    Route::get('/{campaignId}/interactions', [App\Http\Controllers\Api\CampaignController::class, 'interactions']);

    // ===== Stats =====
    Route::get('/{id}/stats', [App\Http\Controllers\Api\CampaignController::class, 'stats']);
    Route::get('/stats/overall', [App\Http\Controllers\Api\CampaignController::class, 'overallStats']);
});

// ============================================================
// TELEMEDICINE ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('telemedicine')->group(function () {
    Route::post('/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'createSession']);
    Route::get('/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'listSessions']);
    Route::get('/sessions/{id}', [App\Http\Controllers\Api\TelemedicineController::class, 'getSession']);
    Route::get('/sessions/room/{roomName}', [App\Http\Controllers\Api\TelemedicineController::class, 'getSessionByRoom']);
    Route::get('/doctors/{doctorId}/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'doctorSessions']);
    Route::get('/patients/{patientId}/sessions', [App\Http\Controllers\Api\TelemedicineController::class, 'patientSessions']);
    Route::get('/doctors/{doctorId}/active', [App\Http\Controllers\Api\TelemedicineController::class, 'activeSessions']);
    Route::post('/sessions/{id}/start', [App\Http\Controllers\Api\TelemedicineController::class, 'startSession']);
    Route::post('/sessions/{id}/complete', [App\Http\Controllers\Api\TelemedicineController::class, 'completeSession']);
    Route::post('/sessions/{id}/cancel', [App\Http\Controllers\Api\TelemedicineController::class, 'cancelSession']);
    Route::post('/sessions/{id}/join', [App\Http\Controllers\Api\TelemedicineController::class, 'joinSession']);
    Route::post('/messages', [App\Http\Controllers\Api\TelemedicineController::class, 'sendMessage']);
    Route::get('/sessions/{sessionId}/messages', [App\Http\Controllers\Api\TelemedicineController::class, 'getMessages']);
    Route::get('/sessions/{sessionId}/unread-count', [App\Http\Controllers\Api\TelemedicineController::class, 'unreadCount']);
    Route::post('/files', [App\Http\Controllers\Api\TelemedicineController::class, 'uploadFile']);
    Route::get('/sessions/{sessionId}/files', [App\Http\Controllers\Api\TelemedicineController::class, 'getFiles']);
    Route::delete('/files/{id}', [App\Http\Controllers\Api\TelemedicineController::class, 'deleteFile']);
    Route::get('/stats', [App\Http\Controllers\Api\TelemedicineController::class, 'stats']);
});

// ============================================================
// VACCINATION ROUTES
// ============================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('vaccination')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\VaccinationController::class, 'index']);
    Route::get('/active', [App\Http\Controllers\Api\VaccinationController::class, 'activeVaccines']);
    Route::post('/', [App\Http\Controllers\Api\VaccinationController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\VaccinationController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\VaccinationController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\VaccinationController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [App\Http\Controllers\Api\VaccinationController::class, 'toggleStatus']);
    Route::post('/record', [App\Http\Controllers\Api\VaccinationController::class, 'record']);
    Route::get('/patients/{patientId}/vaccinations', [App\Http\Controllers\Api\VaccinationController::class, 'patientVaccinations']);
    Route::get('/patients/{patientId}/summary', [App\Http\Controllers\Api\VaccinationController::class, 'patientSummary']);
    Route::get('/patients/{patientId}/upcoming', [App\Http\Controllers\Api\VaccinationController::class, 'upcoming']);
    Route::get('/patients/{patientId}/overdue', [App\Http\Controllers\Api\VaccinationController::class, 'overdue']);
    Route::get('/patients/{patientId}/reminders', [App\Http\Controllers\Api\VaccinationController::class, 'reminders']);
    Route::post('/reminders/process', [App\Http\Controllers\Api\VaccinationController::class, 'processReminders']);
    Route::get('/stats', [App\Http\Controllers\Api\VaccinationController::class, 'stats']);
});

// ============================================================
// SURVEY ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('survey')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\SurveyController::class, 'index']);
    Route::get('/available', [App\Http\Controllers\Api\SurveyController::class, 'available']);
    Route::post('/', [App\Http\Controllers\Api\SurveyController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\SurveyController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\SurveyController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\SurveyController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [App\Http\Controllers\Api\SurveyController::class, 'toggleStatus']);
    Route::post('/submit', [App\Http\Controllers\Api\SurveyController::class, 'submit']);
    Route::get('/{surveyId}/responses', [App\Http\Controllers\Api\SurveyController::class, 'surveyResponses']);
    Route::get('/patients/{patientId}/responses', [App\Http\Controllers\Api\SurveyController::class, 'patientResponses']);
    Route::post('/feedback', [App\Http\Controllers\Api\SurveyController::class, 'submitFeedback']);
    Route::get('/feedbacks', [App\Http\Controllers\Api\SurveyController::class, 'feedbacks']);
    Route::get('/patients/{patientId}/feedbacks', [App\Http\Controllers\Api\SurveyController::class, 'patientFeedbacks']);
    Route::get('/doctors/{doctorId}/feedbacks', [App\Http\Controllers\Api\SurveyController::class, 'doctorFeedbacks']);
    Route::post('/feedbacks/{id}/reply', [App\Http\Controllers\Api\SurveyController::class, 'replyFeedback']);
    Route::post('/feedbacks/{id}/resolve', [App\Http\Controllers\Api\SurveyController::class, 'resolveFeedback']);
    Route::get('/stats', [App\Http\Controllers\Api\SurveyController::class, 'stats']);
});

// ============================================================
// EVENT ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('events')->group(function () {
    Route::get('/published', [App\Http\Controllers\Api\EventController::class, 'published']);
    Route::get('/upcoming', [App\Http\Controllers\Api\EventController::class, 'upcoming']);
    Route::get('/active', [App\Http\Controllers\Api\EventController::class, 'active']);
    Route::get('/{slug}/slug', [App\Http\Controllers\Api\EventController::class, 'showBySlug']);
    Route::get('/', [App\Http\Controllers\Api\EventController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\EventController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\EventController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\EventController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\EventController::class, 'destroy']);
    Route::post('/{id}/publish', [App\Http\Controllers\Api\EventController::class, 'publish']);
    Route::post('/{id}/complete', [App\Http\Controllers\Api\EventController::class, 'complete']);
    Route::post('/register', [App\Http\Controllers\Api\EventController::class, 'register']);
    Route::get('/{eventId}/registrations', [App\Http\Controllers\Api\EventController::class, 'eventRegistrations']);
    Route::post('/registrations/{id}/confirm', [App\Http\Controllers\Api\EventController::class, 'confirmRegistration']);
    Route::post('/registrations/{id}/cancel', [App\Http\Controllers\Api\EventController::class, 'cancelRegistration']);
    Route::post('/registrations/{id}/attendance', [App\Http\Controllers\Api\EventController::class, 'markAttendance']);
    Route::get('/patients/{patientId}/registrations', [App\Http\Controllers\Api\EventController::class, 'patientRegistrations']);
    Route::get('/stats/overview', [App\Http\Controllers\Api\EventController::class, 'stats']);
});

// ============================================================
// CAMPAIGN ROUTES
// ============================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('campaigns')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\CampaignController::class, 'index']);
    Route::get('/active', [App\Http\Controllers\Api\CampaignController::class, 'active']);
    Route::post('/', [App\Http\Controllers\Api\CampaignController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'destroy']);
    Route::post('/{id}/activate', [App\Http\Controllers\Api\CampaignController::class, 'activate']);
    Route::post('/{id}/pause', [App\Http\Controllers\Api\CampaignController::class, 'pause']);
    Route::post('/{id}/complete', [App\Http\Controllers\Api\CampaignController::class, 'complete']);
    Route::post('/interactions', [App\Http\Controllers\Api\CampaignController::class, 'trackInteraction']);
    Route::get('/{campaignId}/interactions', [App\Http\Controllers\Api\CampaignController::class, 'interactions']);
    Route::get('/{id}/stats', [App\Http\Controllers\Api\CampaignController::class, 'stats']);
    Route::get('/stats/overall', [App\Http\Controllers\Api\CampaignController::class, 'overallStats']);
});

// ============================================================
// MEDICAL NOTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('medical-notes')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'destroy']);
    Route::post('/{id}/share', [App\Http\Controllers\Api\MedicalNoteController::class, 'share']);
    Route::post('/{id}/unshare', [App\Http\Controllers\Api\MedicalNoteController::class, 'unshare']);
    Route::get('/patients/{patientId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'patientNotes']);
    Route::get('/doctors/{doctorId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'doctorNotes']);
    Route::get('/patients/{patientId}/summary', [App\Http\Controllers\Api\MedicalNoteController::class, 'summary']);
});

// ============================================================
// MEDICAL NOTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('medical-notes')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'destroy']);
    Route::post('/{id}/share', [App\Http\Controllers\Api\MedicalNoteController::class, 'share']);
    Route::post('/{id}/unshare', [App\Http\Controllers\Api\MedicalNoteController::class, 'unshare']);
    Route::get('/patients/{patientId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'patientNotes']);
    Route::get('/doctors/{doctorId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'doctorNotes']);
    Route::get('/patients/{patientId}/summary', [App\Http\Controllers\Api\MedicalNoteController::class, 'summary']);
});

// ============================================================
// MEDICAL NOTES ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('medical-notes')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'destroy']);
    Route::get('/patient/{patientId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'patientNotes']);
    Route::get('/doctor/{doctorId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'doctorNotes']);
});

// ============================================================
// CAMPAIGN ROUTES
// ============================================================
Route::middleware(['auth:sanctum'])->prefix('campaigns')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\CampaignController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\CampaignController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'destroy']);
    Route::get('/stats/overall', [App\Http\Controllers\Api\CampaignController::class, 'overallStats']);
});

// Add this inside the events group
Route::get('/stats/overall', [App\Http\Controllers\Api\EventController::class, 'getStats']);
// ============================================================
// EVENTS & CAMPAIGNS
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('events')->group(function () {

    // ===== Public =====
    Route::get('/published', [App\Http\Controllers\Api\EventController::class, 'published']);
    Route::get('/upcoming', [App\Http\Controllers\Api\EventController::class, 'upcoming']);
    Route::get('/active', [App\Http\Controllers\Api\EventController::class, 'active']);
    Route::get('/{slug}/slug', [App\Http\Controllers\Api\EventController::class, 'showBySlug']);

    // ===== Admin =====
    Route::get('/', [App\Http\Controllers\Api\EventController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\EventController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\EventController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\EventController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\EventController::class, 'destroy']);
    Route::post('/{id}/publish', [App\Http\Controllers\Api\EventController::class, 'publish']);
    Route::get('/stats/overall', [App\Http\Controllers\Api\EventController::class, 'getStats']);
});

// ============================================================
// LABORATORY (LBR) ROUTES
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('lab')->group(function () {

    // ===== Public/Patient Routes =====
    Route::get('/tests', [App\Http\Controllers\Api\LabController::class, 'tests']);
    Route::get('/tests/active', [App\Http\Controllers\Api\LabController::class, 'activeTests']);
    Route::get('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'showTest']);
    Route::get('/categories', [App\Http\Controllers\Api\LabController::class, 'categories']);
    Route::get('/categories/active', [App\Http\Controllers\Api\LabController::class, 'activeCategories']);

    // ===== Patient Orders =====
    Route::get('/my/orders', [App\Http\Controllers\Api\LabController::class, 'myOrders']);
    Route::get('/my/stats', [App\Http\Controllers\Api\LabController::class, 'myStats']);

    // ===== Doctor Orders =====
    Route::get('/doctor/orders', [App\Http\Controllers\Api\LabController::class, 'myDoctorOrders']);

    // ===== Order Management =====
    Route::post('/orders', [App\Http\Controllers\Api\LabController::class, 'createOrder']);
    Route::get('/orders', [App\Http\Controllers\Api\LabController::class, 'orders']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\LabController::class, 'showOrder']);
    Route::put('/orders/{id}/status', [App\Http\Controllers\Api\LabController::class, 'updateOrderStatus']);

    // ===== Results =====
    Route::post('/results', [App\Http\Controllers\Api\LabController::class, 'addResult']);
    Route::post('/results/bulk', [App\Http\Controllers\Api\LabController::class, 'addResults']);
    Route::post('/results/{id}/verify', [App\Http\Controllers\Api\LabController::class, 'verifyResult']);
    Route::delete('/results/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteResult']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\LabController::class, 'stats']);
});

// ===== Admin Routes =====
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/lab')->group(function () {

    // Tests
    Route::post('/tests', [App\Http\Controllers\Api\LabController::class, 'storeTest']);
    Route::put('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'updateTest']);
    Route::delete('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteTest']);
    Route::post('/tests/{id}/toggle-status', [App\Http\Controllers\Api\LabController::class, 'toggleTestStatus']);

    // Categories
    Route::post('/categories', [App\Http\Controllers\Api\LabController::class, 'storeCategory']);
    Route::put('/categories/{id}', [App\Http\Controllers\Api\LabController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteCategory']);

    // All Orders (Admin view)
    Route::get('/orders', [App\Http\Controllers\Api\LabController::class, 'orders']);
});

// ============================================================
// LABORATORY (LBR) ROUTES
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('lab')->group(function () {

    // ===== Public/Patient Routes =====
    Route::get('/tests', [App\Http\Controllers\Api\LabController::class, 'tests']);
    Route::get('/tests/active', [App\Http\Controllers\Api\LabController::class, 'activeTests']);
    Route::get('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'showTest']);
    Route::get('/categories', [App\Http\Controllers\Api\LabController::class, 'categories']);
    Route::get('/categories/active', [App\Http\Controllers\Api\LabController::class, 'activeCategories']);

    // ===== Patient Orders =====
    Route::get('/my/orders', [App\Http\Controllers\Api\LabController::class, 'myOrders']);
    Route::get('/my/stats', [App\Http\Controllers\Api\LabController::class, 'myStats']);

    // ===== Doctor Orders =====
    Route::get('/doctor/orders', [App\Http\Controllers\Api\LabController::class, 'myDoctorOrders']);

    // ===== Order Management =====
    Route::post('/orders', [App\Http\Controllers\Api\LabController::class, 'createOrder']);
    Route::get('/orders', [App\Http\Controllers\Api\LabController::class, 'orders']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\LabController::class, 'showOrder']);
    Route::put('/orders/{id}/status', [App\Http\Controllers\Api\LabController::class, 'updateOrderStatus']);

    // ===== Results =====
    Route::post('/results', [App\Http\Controllers\Api\LabController::class, 'addResult']);
    Route::post('/results/bulk', [App\Http\Controllers\Api\LabController::class, 'addResults']);
    Route::post('/results/{id}/verify', [App\Http\Controllers\Api\LabController::class, 'verifyResult']);
    Route::delete('/results/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteResult']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\LabController::class, 'stats']);
});

// ===== Admin Routes =====
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/lab')->group(function () {

    // Tests
    Route::post('/tests', [App\Http\Controllers\Api\LabController::class, 'storeTest']);
    Route::put('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'updateTest']);
    Route::delete('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteTest']);
    Route::post('/tests/{id}/toggle-status', [App\Http\Controllers\Api\LabController::class, 'toggleTestStatus']);

    // Categories
    Route::post('/categories', [App\Http\Controllers\Api\LabController::class, 'storeCategory']);
    Route::put('/categories/{id}', [App\Http\Controllers\Api\LabController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteCategory']);

    // All Orders (Admin view)
    Route::get('/orders', [App\Http\Controllers\Api\LabController::class, 'orders']);
});

// ============================================================
// HOSPITALIZATION (بستری) ROUTES
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('hospital')->group(function () {

    // ===== Wards =====
    Route::get('/wards', [App\Http\Controllers\Api\HospitalController::class, 'wards']);
    Route::get('/wards/active', [App\Http\Controllers\Api\HospitalController::class, 'activeWards']);
    Route::get('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showWard']);
    Route::get('/wards/{id}/stats', [App\Http\Controllers\Api\HospitalController::class, 'wardStats']);

    // ===== Beds =====
    Route::get('/beds', [App\Http\Controllers\Api\HospitalController::class, 'beds']);
    Route::get('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showBed']);

    // ===== Admissions =====
    Route::get('/admissions', [App\Http\Controllers\Api\HospitalController::class, 'admissions']);
    Route::post('/admissions', [App\Http\Controllers\Api\HospitalController::class, 'storeAdmission']);
    Route::get('/admissions/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showAdmission']);
    Route::put('/admissions/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateAdmission']);
    Route::post('/admissions/{id}/admit', [App\Http\Controllers\Api\HospitalController::class, 'admitPatient']);
    Route::post('/admissions/{id}/discharge', [App\Http\Controllers\Api\HospitalController::class, 'dischargePatient']);

    // ===== Admission Days (Vital Signs) =====
    Route::post('/admission-days', [App\Http\Controllers\Api\HospitalController::class, 'addAdmissionDay']);
    Route::get('/admissions/{admissionId}/days', [App\Http\Controllers\Api\HospitalController::class, 'getAdmissionDays']);

    // ===== Admission Services =====
    Route::post('/services', [App\Http\Controllers\Api\HospitalController::class, 'addService']);

    // ===== Admission Drugs =====
    Route::post('/drugs', [App\Http\Controllers\Api\HospitalController::class, 'addDrug']);

    // ===== My Admissions =====
    Route::get('/my/admissions', [App\Http\Controllers\Api\HospitalController::class, 'myAdmissions']);
    Route::get('/doctor/admissions', [App\Http\Controllers\Api\HospitalController::class, 'doctorAdmissions']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\HospitalController::class, 'stats']);
});

// ===== Admin Hospital Routes =====
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/hospital')->group(function () {

    // Wards
    Route::post('/wards', [App\Http\Controllers\Api\HospitalController::class, 'storeWard']);
    Route::put('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateWard']);
    Route::delete('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'deleteWard']);

    // Beds
    Route::post('/beds', [App\Http\Controllers\Api\HospitalController::class, 'storeBed']);
    Route::put('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateBed']);
    Route::delete('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'deleteBed']);
    Route::post('/beds/{id}/status', [App\Http\Controllers\Api\HospitalController::class, 'changeBedStatus']);
});

// ============================================================
// HOSPITALIZATION (بستری) ROUTES
// ============================================================

Route::middleware(['auth:sanctum'])->prefix('hospital')->group(function () {

    // ===== Wards =====
    Route::get('/wards', [App\Http\Controllers\Api\HospitalController::class, 'wards']);
    Route::get('/wards/active', [App\Http\Controllers\Api\HospitalController::class, 'activeWards']);
    Route::get('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showWard']);
    Route::get('/wards/{id}/stats', [App\Http\Controllers\Api\HospitalController::class, 'wardStats']);

    // ===== Beds =====
    Route::get('/beds', [App\Http\Controllers\Api\HospitalController::class, 'beds']);
    Route::get('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showBed']);

    // ===== Admissions =====
    Route::get('/admissions', [App\Http\Controllers\Api\HospitalController::class, 'admissions']);
    Route::post('/admissions', [App\Http\Controllers\Api\HospitalController::class, 'storeAdmission']);
    Route::get('/admissions/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showAdmission']);
    Route::put('/admissions/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateAdmission']);
    Route::post('/admissions/{id}/admit', [App\Http\Controllers\Api\HospitalController::class, 'admitPatient']);
    Route::post('/admissions/{id}/discharge', [App\Http\Controllers\Api\HospitalController::class, 'dischargePatient']);

    // ===== Admission Days (Vital Signs) =====
    Route::post('/admission-days', [App\Http\Controllers\Api\HospitalController::class, 'addAdmissionDay']);
    Route::get('/admissions/{admissionId}/days', [App\Http\Controllers\Api\HospitalController::class, 'getAdmissionDays']);

    // ===== Admission Services =====
    Route::post('/services', [App\Http\Controllers\Api\HospitalController::class, 'addService']);

    // ===== Admission Drugs =====
    Route::post('/drugs', [App\Http\Controllers\Api\HospitalController::class, 'addDrug']);

    // ===== My Admissions =====
    Route::get('/my/admissions', [App\Http\Controllers\Api\HospitalController::class, 'myAdmissions']);
    Route::get('/doctor/admissions', [App\Http\Controllers\Api\HospitalController::class, 'doctorAdmissions']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\HospitalController::class, 'stats']);
});

// ===== Admin Hospital Routes =====
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin/hospital')->group(function () {

    // Wards
    Route::post('/wards', [App\Http\Controllers\Api\HospitalController::class, 'storeWard']);
    Route::put('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateWard']);
    Route::delete('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'deleteWard']);

    // Beds
    Route::post('/beds', [App\Http\Controllers\Api\HospitalController::class, 'storeBed']);
    Route::put('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateBed']);
    Route::delete('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'deleteBed']);
    Route::post('/beds/{id}/status', [App\Http\Controllers\Api\HospitalController::class, 'changeBedStatus']);
});

// ============================================================
// DIGITAL FORMS ROUTES
// ============================================================

// Public Routes (بدون احراز هویت)
Route::prefix('forms')->group(function () {
    Route::get('/public/{slug}', [App\Http\Controllers\Api\FormController::class, 'publicShow']);
    Route::post('/public/{slug}/submit', [App\Http\Controllers\Api\FormController::class, 'publicSubmit']);
});

// Protected Routes (نیاز به احراز هویت)
Route::middleware(['auth:sanctum'])->prefix('forms')->group(function () {

    // ===== Forms =====
    Route::get('/', [App\Http\Controllers\Api\FormController::class, 'forms']);
    Route::get('/published', [App\Http\Controllers\Api\FormController::class, 'publishedForms']);
    Route::post('/', [App\Http\Controllers\Api\FormController::class, 'storeForm']);
    Route::get('/{id}', [App\Http\Controllers\Api\FormController::class, 'showForm']);
    Route::put('/{id}', [App\Http\Controllers\Api\FormController::class, 'updateForm']);
    Route::delete('/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteForm']);
    Route::post('/{id}/publish', [App\Http\Controllers\Api\FormController::class, 'publishForm']);
    Route::post('/{id}/archive', [App\Http\Controllers\Api\FormController::class, 'archiveForm']);
    Route::post('/{id}/duplicate', [App\Http\Controllers\Api\FormController::class, 'duplicateForm']);

    // ===== Responses =====
    Route::get('/responses', [App\Http\Controllers\Api\FormController::class, 'responses']);
    Route::post('/responses', [App\Http\Controllers\Api\FormController::class, 'submitResponse']);
    Route::get('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'showResponse']);
    Route::put('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'updateResponse']);
    Route::delete('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteResponse']);
    Route::post('/responses/{id}/complete', [App\Http\Controllers\Api\FormController::class, 'completeResponse']);
    Route::get('/forms/{formId}/responses', [App\Http\Controllers\Api\FormController::class, 'formResponses']);

    // ===== My Responses =====
    Route::get('/my/responses', [App\Http\Controllers\Api\FormController::class, 'myResponses']);

    // ===== Signatures =====
    Route::get('/signatures', [App\Http\Controllers\Api\FormController::class, 'signatures']);
    Route::delete('/signatures/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteSignature']);

    // ===== Categories =====
    Route::get('/categories', [App\Http\Controllers\Api\FormController::class, 'categories']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\FormController::class, 'stats']);
});

// ============================================================
// DIGITAL FORMS ROUTES
// ============================================================

// Public Routes (بدون احراز هویت)
Route::prefix('forms')->group(function () {
    Route::get('/public/{slug}', [App\Http\Controllers\Api\FormController::class, 'publicShow']);
    Route::post('/public/{slug}/submit', [App\Http\Controllers\Api\FormController::class, 'publicSubmit']);
});

// Protected Routes (نیاز به احراز هویت)
Route::middleware(['auth:sanctum'])->prefix('forms')->group(function () {

    // ===== Forms =====
    Route::get('/', [App\Http\Controllers\Api\FormController::class, 'forms']);
    Route::get('/published', [App\Http\Controllers\Api\FormController::class, 'publishedForms']);
    Route::post('/', [App\Http\Controllers\Api\FormController::class, 'storeForm']);
    Route::get('/{id}', [App\Http\Controllers\Api\FormController::class, 'showForm']);
    Route::put('/{id}', [App\Http\Controllers\Api\FormController::class, 'updateForm']);
    Route::delete('/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteForm']);
    Route::post('/{id}/publish', [App\Http\Controllers\Api\FormController::class, 'publishForm']);
    Route::post('/{id}/archive', [App\Http\Controllers\Api\FormController::class, 'archiveForm']);
    Route::post('/{id}/duplicate', [App\Http\Controllers\Api\FormController::class, 'duplicateForm']);

    // ===== Responses =====
    Route::get('/responses', [App\Http\Controllers\Api\FormController::class, 'responses']);
    Route::post('/responses', [App\Http\Controllers\Api\FormController::class, 'submitResponse']);
    Route::get('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'showResponse']);
    Route::put('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'updateResponse']);
    Route::delete('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteResponse']);
    Route::post('/responses/{id}/complete', [App\Http\Controllers\Api\FormController::class, 'completeResponse']);
    Route::get('/forms/{formId}/responses', [App\Http\Controllers\Api\FormController::class, 'formResponses']);

    // ===== My Responses =====
    Route::get('/my/responses', [App\Http\Controllers\Api\FormController::class, 'myResponses']);

    // ===== Signatures =====
    Route::get('/signatures', [App\Http\Controllers\Api\FormController::class, 'signatures']);
    Route::delete('/signatures/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteSignature']);

    // ===== Categories =====
    Route::get('/categories', [App\Http\Controllers\Api\FormController::class, 'categories']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\FormController::class, 'stats']);
});

// ============================================================
// MANAGEMENT DASHBOARD ROUTES
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('dashboard/management')->group(function () {
    
    // آمار کلی
    Route::get('/stats', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'stats']);
    
    // داده‌های نمودارها
    Route::get('/charts', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'charts']);
    
    // آمار سریع (ویجت‌ها)
    Route::get('/quick-stats', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'quickStats']);
    
    // فعالیت‌های اخیر
    Route::get('/recent-activities', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'recentActivities']);
    
    // پزشکان برتر
    Route::get('/top-doctors', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'topDoctors']);
    
    // خلاصه عملکرد
    Route::get('/summary', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'summary']);
});

// ============================================================
// MANAGEMENT DASHBOARD ROUTES
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('dashboard/management')->group(function () {
    
    // آمار کلی
    Route::get('/stats', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'stats']);
    
    // داده‌های نمودارها
    Route::get('/charts', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'charts']);
    
    // آمار سریع (ویجت‌ها)
    Route::get('/quick-stats', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'quickStats']);
    
    // فعالیت‌های اخیر
    Route::get('/recent-activities', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'recentActivities']);
    
    // پزشکان برتر
    Route::get('/top-doctors', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'topDoctors']);
    
    // خلاصه عملکرد
    Route::get('/summary', [App\Http\Controllers\Api\Dashboard\ManagementDashboardController::class, 'summary']);
});

// ============================================================
// BI & SYSTEM MANAGEMENT ROUTES
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('bi')->group(function () {

    // ===== Predictive Analytics =====
    Route::get('/predict/appointments', [App\Http\Controllers\Api\BI\BIController::class, 'predictAppointments']);
    Route::get('/forecast/revenue', [App\Http\Controllers\Api\BI\BIController::class, 'forecastRevenue']);
    Route::get('/segment/patients', [App\Http\Controllers\Api\BI\BIController::class, 'segmentPatients']);
    Route::get('/analyze/doctors', [App\Http\Controllers\Api\BI\BIController::class, 'analyzeDoctors']);
    Route::get('/analytics', [App\Http\Controllers\Api\BI\BIController::class, 'getAnalytics']);

    // ===== Custom Reports =====
    Route::get('/reports', [App\Http\Controllers\Api\BI\BIController::class, 'reports']);
    Route::post('/reports', [App\Http\Controllers\Api\BI\BIController::class, 'createReport']);
    Route::put('/reports/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'updateReport']);
    Route::delete('/reports/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'deleteReport']);
    Route::post('/reports/{id}/generate', [App\Http\Controllers\Api\BI\BIController::class, 'generateReport']);

    // ===== Report Scheduling =====
    Route::post('/schedules', [App\Http\Controllers\Api\BI\BIController::class, 'createSchedule']);
    Route::put('/schedules/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'updateSchedule']);
    Route::delete('/schedules/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'deleteSchedule']);

    // ===== Backup =====
    Route::post('/backup/database', [App\Http\Controllers\Api\BI\BIController::class, 'backupDatabase']);
    Route::post('/backup/files', [App\Http\Controllers\Api\BI\BIController::class, 'backupFiles']);
    Route::post('/backup/{id}/restore', [App\Http\Controllers\Api\BI\BIController::class, 'restoreBackup']);
    Route::get('/backup/history', [App\Http\Controllers\Api\BI\BIController::class, 'backupHistory']);
    Route::delete('/backup/cleanup', [App\Http\Controllers\Api\BI\BIController::class, 'cleanupBackups']);

    // ===== Audit Log =====
    Route::get('/audit-logs', [App\Http\Controllers\Api\BI\BIController::class, 'auditLogs']);
    Route::post('/audit-logs', [App\Http\Controllers\Api\BI\BIController::class, 'logActivity']);

    // ===== Log Archive =====
    Route::post('/logs/archive', [App\Http\Controllers\Api\BI\BIController::class, 'archiveLogs']);
    Route::get('/logs/archived', [App\Http\Controllers\Api\BI\BIController::class, 'archivedLogs']);
    Route::post('/logs/archived/{id}/restore', [App\Http\Controllers\Api\BI\BIController::class, 'restoreArchivedLog']);
    Route::delete('/logs/archived/cleanup', [App\Http\Controllers\Api\BI\BIController::class, 'cleanupArchivedLogs']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\BI\BIController::class, 'stats']);
});

// ============================================================
// BI & SYSTEM MANAGEMENT ROUTES
// ============================================================

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('bi')->group(function () {

    // ===== Predictive Analytics =====
    Route::get('/predict/appointments', [App\Http\Controllers\Api\BI\BIController::class, 'predictAppointments']);
    Route::get('/forecast/revenue', [App\Http\Controllers\Api\BI\BIController::class, 'forecastRevenue']);
    Route::get('/segment/patients', [App\Http\Controllers\Api\BI\BIController::class, 'segmentPatients']);
    Route::get('/analyze/doctors', [App\Http\Controllers\Api\BI\BIController::class, 'analyzeDoctors']);
    Route::get('/analytics', [App\Http\Controllers\Api\BI\BIController::class, 'getAnalytics']);

    // ===== Custom Reports =====
    Route::get('/reports', [App\Http\Controllers\Api\BI\BIController::class, 'reports']);
    Route::post('/reports', [App\Http\Controllers\Api\BI\BIController::class, 'createReport']);
    Route::put('/reports/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'updateReport']);
    Route::delete('/reports/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'deleteReport']);
    Route::post('/reports/{id}/generate', [App\Http\Controllers\Api\BI\BIController::class, 'generateReport']);

    // ===== Report Scheduling =====
    Route::post('/schedules', [App\Http\Controllers\Api\BI\BIController::class, 'createSchedule']);
    Route::put('/schedules/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'updateSchedule']);
    Route::delete('/schedules/{id}', [App\Http\Controllers\Api\BI\BIController::class, 'deleteSchedule']);

    // ===== Backup =====
    Route::post('/backup/database', [App\Http\Controllers\Api\BI\BIController::class, 'backupDatabase']);
    Route::post('/backup/files', [App\Http\Controllers\Api\BI\BIController::class, 'backupFiles']);
    Route::post('/backup/{id}/restore', [App\Http\Controllers\Api\BI\BIController::class, 'restoreBackup']);
    Route::get('/backup/history', [App\Http\Controllers\Api\BI\BIController::class, 'backupHistory']);
    Route::delete('/backup/cleanup', [App\Http\Controllers\Api\BI\BIController::class, 'cleanupBackups']);

    // ===== Audit Log =====
    Route::get('/audit-logs', [App\Http\Controllers\Api\BI\BIController::class, 'auditLogs']);
    Route::post('/audit-logs', [App\Http\Controllers\Api\BI\BIController::class, 'logActivity']);

    // ===== Log Archive =====
    Route::post('/logs/archive', [App\Http\Controllers\Api\BI\BIController::class, 'archiveLogs']);
    Route::get('/logs/archived', [App\Http\Controllers\Api\BI\BIController::class, 'archivedLogs']);
    Route::post('/logs/archived/{id}/restore', [App\Http\Controllers\Api\BI\BIController::class, 'restoreArchivedLog']);
    Route::delete('/logs/archived/cleanup', [App\Http\Controllers\Api\BI\BIController::class, 'cleanupArchivedLogs']);

    // ===== Stats =====
    Route::get('/stats', [App\Http\Controllers\Api\BI\BIController::class, 'stats']);
});

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
