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
