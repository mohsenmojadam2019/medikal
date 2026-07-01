<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
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
| API Routes (Public & User)
|--------------------------------------------------------------------------
|
| این فایل شامل مسیرهای عمومی و مسیرهای کاربران عادی است
| مسیرهای مدیریتی (ادمین) در فایل admin.php قرار دارند
|
*/

// ============================================================
// 1. HEALTH CHECK
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
// 2. PUBLIC ROUTES (بدون احراز هویت)
// ============================================================

// 2.1 Authentication
Route::prefix('auth')->group(function () {
    Route::post('/login/mobile', [AuthController::class, 'loginWithMobile']);
    Route::post('/login/mobile/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/login/email', [AuthController::class, 'loginWithEmail']);
});

// 2.2 Doctors (Public)
Route::prefix('doctors')->group(function () {
    Route::get('/public', [DoctorController::class, 'publicList']);
    Route::get('/{id}/public', [DoctorController::class, 'publicShow']);
});

// 2.3 Patients (Public Search)
Route::prefix('patients')->group(function () {
    Route::get('/search/by-national-code', [PatientController::class, 'findByNationalCode']);
    Route::get('/search/by-mobile', [PatientController::class, 'findByMobile']);
});

// 2.4 Appointments (Public - Available Slots)
Route::get('/appointments/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlots']);

// 2.5 Payment Callbacks
Route::prefix('payments')->group(function () {
    Route::get('/callback/{gateway}', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::post('/callback/{gateway}', [PaymentController::class, 'callback']);
});
Route::any('/pharmacy/payment/callback/{gateway}', [PharmacyController::class, 'paymentCallback'])->name('pharmacy.payment.callback');

// 2.6 Wallet Deposit Callback
Route::post('/wallet/deposit/callback', [App\Http\Controllers\Api\WalletController::class, 'depositCallback'])
    ->name('wallet.payment.callback');

// 2.7 Webhook (Public)
Route::post('/webhook/appointment', [App\Http\Controllers\Api\WebhookController::class, 'appointment']);

// 2.8 Public Clinic Settings
Route::get('/clinic/settings', [App\Http\Controllers\Admin\ClinicController::class, 'publicSettings']);

// 2.9 Public Drug Search
Route::prefix('drugs')->group(function () {
    Route::get('/search', [App\Http\Controllers\Admin\DrugController::class, 'search']);
    Route::get('/active', [App\Http\Controllers\Admin\DrugController::class, 'activeDrugs']);
});

// 2.10 Public Specialties
Route::get('/specialties', [App\Http\Controllers\Admin\SpecialtyController::class, 'activeSpecialties']);

// 2.11 Public SEO
Route::prefix('seo')->group(function () {
    Route::get('/doctor/{id}', [App\Http\Controllers\Api\PublicController::class, 'doctorSeo']);
    Route::get('/page', [App\Http\Controllers\Api\PublicController::class, 'pageSeo']);
});

// 2.12 Landing Page
Route::prefix('landing')->group(function () {
    Route::get('/', [App\Http\Controllers\LandingPageController::class, 'index']);
    Route::get('/stats', [App\Http\Controllers\LandingPageController::class, 'stats']);
    Route::get('/top-doctors', [App\Http\Controllers\LandingPageController::class, 'topDoctors']);
    Route::get('/recent-reviews', [App\Http\Controllers\LandingPageController::class, 'recentReviews']);
});

// 2.13 Public Blog
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

// 2.14 Public Location
Route::prefix('location')->group(function () {
    Route::get('/nearby-doctors', [App\Http\Controllers\Api\LocationController::class, 'nearByDoctors']);
    Route::get('/nearby-clinics', [App\Http\Controllers\Api\LocationController::class, 'nearByClinics']);
    Route::get('/provinces', [App\Http\Controllers\Api\LocationController::class, 'provinces']);
    Route::get('/provinces/{provinceId}/cities', [App\Http\Controllers\Api\LocationController::class, 'cities']);
    Route::get('/specialties', [App\Http\Controllers\Api\LocationController::class, 'specialties']);
    Route::post('/distance', [App\Http\Controllers\Api\LocationController::class, 'calculateDistance']);
    Route::get('/doctors/{id}/profile', [App\Http\Controllers\Api\LocationController::class, 'doctorProfile']);
    Route::get('/doctors/{id}/reviews', [App\Http\Controllers\Api\LocationController::class, 'doctorReviews']);
});

// 2.15 Public Ratings
Route::prefix('ratings')->group(function () {
    Route::get('/doctors/{doctorId}', [App\Http\Controllers\Api\RatingController::class, 'doctorRatings']);
    Route::get('/doctors/{doctorId}/stats', [App\Http\Controllers\Api\RatingController::class, 'doctorStats']);
    Route::get('/top-doctors', [App\Http\Controllers\Api\RatingController::class, 'topDoctors']);
});

// 2.16 Public Schedules
Route::prefix('schedules')->group(function () {
    Route::get('/doctors/{doctorId}/weekly', [App\Http\Controllers\Api\ScheduleController::class, 'weekly']);
    Route::get('/doctors/{doctorId}/calendar', [App\Http\Controllers\Api\ScheduleController::class, 'calendar']);
    Route::get('/doctors/{doctorId}/day', [App\Http\Controllers\Api\ScheduleController::class, 'daySchedule']);
    Route::get('/doctors/{doctorId}/special', [App\Http\Controllers\Api\ScheduleController::class, 'specialSchedules']);
});

// 2.17 Public Digital Forms
Route::prefix('forms')->group(function () {
    Route::get('/public/{slug}', [App\Http\Controllers\Api\FormController::class, 'publicShow']);
    Route::post('/public/{slug}/submit', [App\Http\Controllers\Api\FormController::class, 'publicSubmit']);
});

// 2.18 Language
Route::prefix('language')->group(function () {
    Route::post('/switch', [App\Http\Controllers\Api\LanguageController::class, 'switch']);
    Route::get('/current', [App\Http\Controllers\Api\LanguageController::class, 'current']);
    Route::get('/translations', [App\Http\Controllers\Api\LanguageController::class, 'translations']);
});

// ============================================================
// 3. PROTECTED ROUTES (نیاز به احراز هویت - auth:sanctum)
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // ============================================================
    // 3.1 AUTH
    // ============================================================
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // ============================================================
    // 3.2 PROFILE
    // ============================================================
    Route::prefix('profile')->group(function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/address', [ProfileController::class, 'updateAddress']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
    });

    // ============================================================
    // 3.3 DASHBOARD (User/Doctor/Patient)
    // ============================================================
    Route::prefix('dashboard')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin']);
        Route::get('/doctor', [DashboardController::class, 'doctor']);
        Route::get('/patient', [DashboardController::class, 'patient']);
    });

    // ============================================================
    // 3.4 APPOINTMENTS
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
    // 3.5 PRESCRIPTIONS
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
    // 3.6 INVOICES
    // ============================================================
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/my', [InvoiceController::class, 'myInvoices']);
        Route::get('/stats', [InvoiceController::class, 'stats']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
    });

    // ============================================================
    // 3.7 PAYMENTS
    // ============================================================
    Route::prefix('payments')->group(function () {
        Route::get('/gateways', [PaymentController::class, 'gateways']);
        Route::post('/initiate', [PaymentController::class, 'initiate']);
        Route::get('/status/{invoiceId}', [PaymentController::class, 'status']);
        Route::get('/history', [PaymentController::class, 'history']);
        Route::post('/refund/{paymentId}', [PaymentController::class, 'refund']);
    });

    // ============================================================
    // 3.8 PHARMACY (User)
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
    // 3.9 RATINGS (User)
    // ============================================================
    Route::prefix('ratings')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\RatingController::class, 'store']);
        Route::post('/{id}/reply', [App\Http\Controllers\Api\RatingController::class, 'reply']);
        Route::delete('/{id}/reply', [App\Http\Controllers\Api\RatingController::class, 'deleteReply']);
    });

    // ============================================================
    // 3.10 CHAT
    // ============================================================
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [App\Http\Controllers\Api\ChatController::class, 'conversations']);
        Route::get('/messages/{userId}', [App\Http\Controllers\Api\ChatController::class, 'messages']);
        Route::post('/send', [App\Http\Controllers\Api\ChatController::class, 'send']);
        Route::get('/unread-count', [App\Http\Controllers\Api\ChatController::class, 'unreadCount']);
        Route::post('/mark-as-read/{userId}', [App\Http\Controllers\Api\ChatController::class, 'markAllAsRead']);
        Route::get('/recent', [App\Http\Controllers\Api\ChatController::class, 'recent']);
    });

    // ============================================================
    // 3.11 WALLET (User)
    // ============================================================
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [App\Http\Controllers\Api\WalletController::class, 'balance']);
        Route::get('/transactions', [App\Http\Controllers\Api\WalletController::class, 'transactions']);
        Route::get('/summary', [App\Http\Controllers\Api\WalletController::class, 'summary']);
        Route::post('/deposit', [App\Http\Controllers\Api\WalletController::class, 'deposit']);
        Route::post('/pay-appointment', [App\Http\Controllers\Api\WalletController::class, 'payAppointment']);
    });

    // ============================================================
    // 3.12 NOTIFICATIONS (User)
    // ============================================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/unread', [App\Http\Controllers\Api\NotificationController::class, 'unread']);
        Route::get('/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::get('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'show']);
        Route::put('/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy']);
        Route::delete('/read/all', [App\Http\Controllers\Api\NotificationController::class, 'deleteRead']);
    });

    // ============================================================
    // 3.13 REMINDERS
    // ============================================================
    Route::prefix('reminders')->group(function () {
        Route::get('/my', [App\Http\Controllers\Api\ReminderController::class, 'myReminders']);
        Route::post('/process', [App\Http\Controllers\Api\ReminderController::class, 'process']);
        Route::get('/pending-count', [App\Http\Controllers\Api\ReminderController::class, 'pendingCount']);
    });

    // ============================================================
    // 3.14 SCHEDULES (Protected)
    // ============================================================
    Route::prefix('schedules')->group(function () {
        Route::post('/doctors/{doctorId}/weekly', [App\Http\Controllers\Api\ScheduleController::class, 'setWeekly']);
        Route::post('/doctors/{doctorId}/special', [App\Http\Controllers\Api\ScheduleController::class, 'setSpecial']);
        Route::delete('/special/{scheduleId}', [App\Http\Controllers\Api\ScheduleController::class, 'deleteSpecial']);
        Route::post('/doctors/{doctorId}/copy-previous-week', [App\Http\Controllers\Api\ScheduleController::class, 'copyFromPreviousWeek']);
    });

    // ============================================================
    // 3.15 DOCTOR DOCUMENTS
    // ============================================================
    Route::prefix('doctors')->group(function () {
        Route::post('/{doctorId}/certificates', [App\Http\Controllers\Api\DoctorDocumentController::class, 'uploadCertificate']);
        Route::get('/{doctorId}/certificates', [App\Http\Controllers\Api\DoctorDocumentController::class, 'getCertificates']);
        Route::delete('/{doctorId}/certificates/{mediaId}', [App\Http\Controllers\Api\DoctorDocumentController::class, 'deleteCertificate']);
        Route::put('/{doctorId}/social-links', [App\Http\Controllers\Api\DoctorSocialController::class, 'updateSocialLinks']);
        Route::get('/{doctorId}/social-links', [App\Http\Controllers\Api\DoctorSocialController::class, 'getSocialLinks']);
    });

    // ============================================================
    // 3.16 REFERRALS
    // ============================================================
    Route::prefix('referrals')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\ReferralController::class, 'store']);
        Route::get('/patients/{patientId}', [App\Http\Controllers\Api\ReferralController::class, 'patientReferrals']);
        Route::get('/doctor', [App\Http\Controllers\Api\ReferralController::class, 'doctorReferrals']);
        Route::post('/{id}/accept', [App\Http\Controllers\Api\ReferralController::class, 'accept']);
        Route::post('/{id}/reject', [App\Http\Controllers\Api\ReferralController::class, 'reject']);
        Route::post('/{id}/complete', [App\Http\Controllers\Api\ReferralController::class, 'complete']);
    });

    // ============================================================
    // 3.17 FINANCIAL REPORTS (User/Doctor)
    // ============================================================
    Route::prefix('reports')->group(function () {
        Route::get('/doctors/{doctorId}/income', [App\Http\Controllers\Api\FinancialReportController::class, 'doctorIncome']);
        Route::get('/doctors/{doctorId}/daily-income', [App\Http\Controllers\Api\FinancialReportController::class, 'dailyIncome']);
        Route::get('/doctors/{doctorId}/monthly-income', [App\Http\Controllers\Api\FinancialReportController::class, 'monthlyIncome']);
        Route::get('/doctors/{doctorId}/cancelled-appointments', [App\Http\Controllers\Api\FinancialReportController::class, 'cancelledAppointments']);
    });

    // ============================================================
    // 3.18 TELEMEDICINE
    // ============================================================
    Route::prefix('telemedicine')->group(function () {
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
    // 3.19 MEDICAL NOTES
    // ============================================================
    Route::prefix('medical-notes')->group(function () {
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
    // 3.20 LABORATORY (User)
    // ============================================================
    Route::prefix('lab')->group(function () {
        Route::get('/tests', [App\Http\Controllers\Api\LabController::class, 'tests']);
        Route::get('/tests/active', [App\Http\Controllers\Api\LabController::class, 'activeTests']);
        Route::get('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'showTest']);
        Route::get('/categories', [App\Http\Controllers\Api\LabController::class, 'categories']);
        Route::get('/categories/active', [App\Http\Controllers\Api\LabController::class, 'activeCategories']);
        Route::get('/my/orders', [App\Http\Controllers\Api\LabController::class, 'myOrders']);
        Route::get('/my/stats', [App\Http\Controllers\Api\LabController::class, 'myStats']);
        Route::get('/doctor/orders', [App\Http\Controllers\Api\LabController::class, 'myDoctorOrders']);
        Route::post('/orders', [App\Http\Controllers\Api\LabController::class, 'createOrder']);
        Route::get('/orders', [App\Http\Controllers\Api\LabController::class, 'orders']);
        Route::get('/orders/{id}', [App\Http\Controllers\Api\LabController::class, 'showOrder']);
        Route::put('/orders/{id}/status', [App\Http\Controllers\Api\LabController::class, 'updateOrderStatus']);
        Route::post('/results', [App\Http\Controllers\Api\LabController::class, 'addResult']);
        Route::post('/results/bulk', [App\Http\Controllers\Api\LabController::class, 'addResults']);
        Route::post('/results/{id}/verify', [App\Http\Controllers\Api\LabController::class, 'verifyResult']);
        Route::delete('/results/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteResult']);
        Route::get('/stats', [App\Http\Controllers\Api\LabController::class, 'stats']);
    });

    // ============================================================
    // 3.21 HOSPITALIZATION (User)
    // ============================================================
    Route::prefix('hospital')->group(function () {
        Route::get('/wards', [App\Http\Controllers\Api\HospitalController::class, 'wards']);
        Route::get('/wards/active', [App\Http\Controllers\Api\HospitalController::class, 'activeWards']);
        Route::get('/wards/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showWard']);
        Route::get('/wards/{id}/stats', [App\Http\Controllers\Api\HospitalController::class, 'wardStats']);
        Route::get('/beds', [App\Http\Controllers\Api\HospitalController::class, 'beds']);
        Route::get('/beds/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showBed']);
        Route::get('/admissions', [App\Http\Controllers\Api\HospitalController::class, 'admissions']);
        Route::post('/admissions', [App\Http\Controllers\Api\HospitalController::class, 'storeAdmission']);
        Route::get('/admissions/{id}', [App\Http\Controllers\Api\HospitalController::class, 'showAdmission']);
        Route::put('/admissions/{id}', [App\Http\Controllers\Api\HospitalController::class, 'updateAdmission']);
        Route::post('/admissions/{id}/admit', [App\Http\Controllers\Api\HospitalController::class, 'admitPatient']);
        Route::post('/admissions/{id}/discharge', [App\Http\Controllers\Api\HospitalController::class, 'dischargePatient']);
        Route::post('/admission-days', [App\Http\Controllers\Api\HospitalController::class, 'addAdmissionDay']);
        Route::get('/admissions/{admissionId}/days', [App\Http\Controllers\Api\HospitalController::class, 'getAdmissionDays']);
        Route::post('/services', [App\Http\Controllers\Api\HospitalController::class, 'addService']);
        Route::post('/drugs', [App\Http\Controllers\Api\HospitalController::class, 'addDrug']);
        Route::get('/my/admissions', [App\Http\Controllers\Api\HospitalController::class, 'myAdmissions']);
        Route::get('/doctor/admissions', [App\Http\Controllers\Api\HospitalController::class, 'doctorAdmissions']);
        Route::get('/stats', [App\Http\Controllers\Api\HospitalController::class, 'stats']);
    });

    // ============================================================
    // 3.22 DIGITAL FORMS (User)
    // ============================================================
    Route::prefix('forms')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\FormController::class, 'forms']);
        Route::get('/published', [App\Http\Controllers\Api\FormController::class, 'publishedForms']);
        Route::post('/', [App\Http\Controllers\Api\FormController::class, 'storeForm']);
        Route::get('/{id}', [App\Http\Controllers\Api\FormController::class, 'showForm']);
        Route::put('/{id}', [App\Http\Controllers\Api\FormController::class, 'updateForm']);
        Route::delete('/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteForm']);
        Route::post('/{id}/publish', [App\Http\Controllers\Api\FormController::class, 'publishForm']);
        Route::post('/{id}/archive', [App\Http\Controllers\Api\FormController::class, 'archiveForm']);
        Route::post('/{id}/duplicate', [App\Http\Controllers\Api\FormController::class, 'duplicateForm']);
        Route::get('/responses', [App\Http\Controllers\Api\FormController::class, 'responses']);
        Route::post('/responses', [App\Http\Controllers\Api\FormController::class, 'submitResponse']);
        Route::get('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'showResponse']);
        Route::put('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'updateResponse']);
        Route::delete('/responses/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteResponse']);
        Route::post('/responses/{id}/complete', [App\Http\Controllers\Api\FormController::class, 'completeResponse']);
        Route::get('/forms/{formId}/responses', [App\Http\Controllers\Api\FormController::class, 'formResponses']);
        Route::get('/my/responses', [App\Http\Controllers\Api\FormController::class, 'myResponses']);
        Route::get('/signatures', [App\Http\Controllers\Api\FormController::class, 'signatures']);
        Route::delete('/signatures/{id}', [App\Http\Controllers\Api\FormController::class, 'deleteSignature']);
        Route::get('/categories', [App\Http\Controllers\Api\FormController::class, 'categories']);
        Route::get('/stats', [App\Http\Controllers\Api\FormController::class, 'stats']);
    });

    // ============================================================
    // 3.23 INSTALLMENT (User)
    // ============================================================
    Route::prefix('installments')->group(function () {
        Route::get('/settings', [App\Http\Controllers\Api\InstallmentController::class, 'getSettings']);
        Route::put('/settings', [App\Http\Controllers\Api\InstallmentController::class, 'updateSettings']);
        Route::post('/settings/toggle', [App\Http\Controllers\Api\InstallmentController::class, 'toggleInstallments']);
        Route::post('/contracts', [App\Http\Controllers\Api\InstallmentController::class, 'createContract']);
        Route::get('/contracts', [App\Http\Controllers\Api\InstallmentController::class, 'getContracts']);
        Route::get('/contracts/{id}', [App\Http\Controllers\Api\InstallmentController::class, 'getContract']);
        Route::get('/patients/{patientId}/contracts', [App\Http\Controllers\Api\InstallmentController::class, 'patientContracts']);
        Route::post('/contracts/{id}/activate', [App\Http\Controllers\Api\InstallmentController::class, 'activateContract']);
        Route::post('/contracts/{id}/cancel', [App\Http\Controllers\Api\InstallmentController::class, 'cancelContract']);
        Route::get('/contracts/{id}/summary', [App\Http\Controllers\Api\InstallmentController::class, 'contractSummary']);
        Route::get('/installments', [App\Http\Controllers\Api\InstallmentController::class, 'getInstallments']);
        Route::get('/patients/{patientId}/installments', [App\Http\Controllers\Api\InstallmentController::class, 'patientInstallments']);
        Route::get('/patients/{patientId}/upcoming', [App\Http\Controllers\Api\InstallmentController::class, 'upcomingInstallments']);
        Route::get('/patients/{patientId}/overdue', [App\Http\Controllers\Api\InstallmentController::class, 'overdueInstallments']);
        Route::post('/installments/{id}/pay', [App\Http\Controllers\Api\InstallmentController::class, 'payInstallment']);
        Route::post('/installments/{id}/waive', [App\Http\Controllers\Api\InstallmentController::class, 'waiveInstallment']);
        Route::get('/stats', [App\Http\Controllers\Api\InstallmentController::class, 'stats']);
    });

    // ============================================================
    // 3.24 SURVEY (User)
    // ============================================================
    Route::prefix('survey')->group(function () {
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
    // 3.25 EVENTS (User)
    // ============================================================
    Route::prefix('events')->group(function () {
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
    // 3.26 PACS (User)
    // ============================================================
    Route::prefix('pacs')->group(function () {
        Route::post('/upload', [App\Http\Controllers\Api\PACS\PACSController::class, 'upload']);
        Route::get('/patients/{patientId}/images', [App\Http\Controllers\Api\PACS\PACSController::class, 'patientImages']);
        Route::get('/images/{id}', [App\Http\Controllers\Api\PACS\PACSController::class, 'showImage']);
        Route::get('/images/{id}/download', [App\Http\Controllers\Api\PACS\PACSController::class, 'downloadImage']);
        Route::delete('/images/{id}', [App\Http\Controllers\Api\PACS\PACSController::class, 'deleteImage']);
        Route::get('/patients/{patientId}/stats', [App\Http\Controllers\Api\PACS\PACSController::class, 'imageStats']);
    });

    // ============================================================
    // 3.27 OR (Operation Room) (User)
    // ============================================================
    Route::prefix('or')->group(function () {
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
    // 3.28 EMERGENCY (User)
    // ============================================================
    Route::prefix('emergency')->group(function () {
        Route::post('/register', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'register']);
        Route::get('/waiting-list', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'waitingList']);
        Route::get('/stats', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'stats']);
        Route::get('/patients/{id}', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'show']);
        Route::post('/patients/{id}/status', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'updateStatus']);
        Route::post('/patients/{id}/disposition', [App\Http\Controllers\Api\Emergency\EmergencyController::class, 'setDisposition']);
    });

    // ============================================================
    // 3.29 EHR (Electronic Health Record)
    // ============================================================
    Route::prefix('ehr')->group(function () {
        Route::post('/records', [App\Http\Controllers\Api\EHRController::class, 'createRecord']);
        Route::get('/records/{id}', [App\Http\Controllers\Api\EHRController::class, 'getRecord']);
        Route::get('/patients/{patientId}/records', [App\Http\Controllers\Api\EHRController::class, 'patientRecords']);
        Route::put('/records/{id}', [App\Http\Controllers\Api\EHRController::class, 'updateRecord']);
        Route::delete('/records/{id}', [App\Http\Controllers\Api\EHRController::class, 'deleteRecord']);
        Route::post('/visits', [App\Http\Controllers\Api\EHRController::class, 'addVisit']);
        Route::get('/records/{recordId}/visits', [App\Http\Controllers\Api\EHRController::class, 'getVisits']);
        Route::post('/documents', [App\Http\Controllers\Api\EHRController::class, 'uploadDocument']);
        Route::get('/patients/{patientId}/documents', [App\Http\Controllers\Api\EHRController::class, 'getDocuments']);
        Route::delete('/documents/{id}', [App\Http\Controllers\Api\EHRController::class, 'deleteDocument']);
        Route::post('/alerts', [App\Http\Controllers\Api\EHRController::class, 'createAlert']);
        Route::get('/patients/{patientId}/alerts', [App\Http\Controllers\Api\EHRController::class, 'getPatientAlerts']);
        Route::post('/alerts/{id}/resolve', [App\Http\Controllers\Api\EHRController::class, 'resolveAlert']);
        Route::get('/patients/{patientId}/full-history', [App\Http\Controllers\Api\EHRController::class, 'fullHistory']);
        Route::get('/patients/{patientId}/stats', [App\Http\Controllers\Api\EHRController::class, 'stats']);
    });

    // ============================================================
    // 3.30 RECEPTIONIST PANEL
    // ============================================================
    Route::middleware(['role:admin|super_admin|receptionist'])
        ->prefix('receptionist')
        ->group(function () {
            // Waiting List
            Route::post('/waiting-list', [App\Http\Controllers\Api\ReceptionistController::class, 'addToWaitingList']);
            Route::get('/waiting-list/{doctorId}', [App\Http\Controllers\Api\ReceptionistController::class, 'waitingList']);
            Route::get('/waiting-list/{doctorId}/count', [App\Http\Controllers\Api\ReceptionistController::class, 'waitingCount']);
            Route::post('/waiting-list/{waitingId}/call', [App\Http\Controllers\Api\ReceptionistController::class, 'callPatient']);
            Route::post('/waiting-list/{waitingId}/complete', [App\Http\Controllers\Api\ReceptionistController::class, 'completePatient']);
            Route::delete('/waiting-list/{waitingId}', [App\Http\Controllers\Api\ReceptionistController::class, 'cancelWaiting']);

            // Phone Appointments
            Route::post('/phone-appointments', [App\Http\Controllers\Api\ReceptionistController::class, 'createPhoneAppointment']);
            Route::get('/phone-appointments', [App\Http\Controllers\Api\ReceptionistController::class, 'phoneAppointments']);
            Route::post('/phone-appointments/{id}/confirm', [App\Http\Controllers\Api\ReceptionistController::class, 'confirmPhoneAppointment']);
            Route::delete('/phone-appointments/{id}', [App\Http\Controllers\Api\ReceptionistController::class, 'cancelPhoneAppointment']);

            // Appointment Cards
            Route::post('/cards/appointment/{appointmentId}', [App\Http\Controllers\Api\ReceptionistController::class, 'generateCard']);
            Route::get('/cards/appointment/{appointmentId}', [App\Http\Controllers\Api\ReceptionistController::class, 'getCard']);
            Route::post('/cards/{cardId}/print', [App\Http\Controllers\Api\ReceptionistController::class, 'printCard']);

            // Settings
            Route::get('/settings', [App\Http\Controllers\Api\ReceptionistController::class, 'getSettings']);
            Route::put('/settings', [App\Http\Controllers\Api\ReceptionistController::class, 'updateSettings']);

            // Dashboard
            Route::get('/dashboard/{doctorId}', [App\Http\Controllers\Api\ReceptionistController::class, 'dashboard']);
        });

    // ============================================================
    // 3.31 PATIENTS (Admin/Doctor)
    // ============================================================
    Route::prefix('patients')
        ->middleware(['role:admin|super_admin|doctor'])
        ->group(function () {
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
    // 3.32 CAMPAIGNS (User)
    // ============================================================
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\CampaignController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\CampaignController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\CampaignController::class, 'destroy']);
        Route::get('/stats/overall', [App\Http\Controllers\Api\CampaignController::class, 'overallStats']);
    });

}); // End auth:sanctum

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

// ============================================================
// MULTI-LANGUAGE ROUTES
// ============================================================

Route::prefix('language')->group(function () {
    // مسیرهای عمومی
    Route::get('/', [App\Http\Controllers\Api\LanguageController::class, 'index']);
    Route::get('/current', [App\Http\Controllers\Api\LanguageController::class, 'current']);
    Route::post('/switch', [App\Http\Controllers\Api\LanguageController::class, 'switch']);
    Route::get('/translations', [App\Http\Controllers\Api\LanguageController::class, 'translations']);
    Route::get('/translate', [App\Http\Controllers\Api\LanguageController::class, 'getTranslation']);
    
    // مسیرهای مدیریتی (فقط ادمین)
    Route::middleware(['role:admin|super_admin'])->group(function () {
        Route::get('/manage', [App\Http\Controllers\Api\LanguageController::class, 'manage']);
        Route::post('/', [App\Http\Controllers\Api\LanguageController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\Api\LanguageController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\LanguageController::class, 'destroy']);
        Route::post('/{id}/toggle', [App\Http\Controllers\Api\LanguageController::class, 'toggle']);
        Route::post('/set-fallback', [App\Http\Controllers\Api\LanguageController::class, 'setFallback']);
        Route::post('/import', [App\Http\Controllers\Api\LanguageController::class, 'import']);
        Route::post('/export', [App\Http\Controllers\Api\LanguageController::class, 'export']);
        Route::post('/translation', [App\Http\Controllers\Api\LanguageController::class, 'setTranslation']);
    });
});
