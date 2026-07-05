<?php

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
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Admin\SpecialtyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| این فایل شامل تمام مسیرهای API عمومی و محافظت شده است
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

Route::prefix('auth')->group(function () {
    Route::post('/login/mobile', [AuthController::class, 'loginWithMobile']);
    Route::post('/login/mobile/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/login/email', [AuthController::class, 'loginWithEmail']);
});

Route::prefix('doctors')->group(function () {
    Route::get('/public', [DoctorController::class, 'publicList']);
    Route::get('/{id}/public', [DoctorController::class, 'publicShow']);
});

Route::get('/specialties', [SpecialtyController::class, 'activeSpecialties']);

Route::get('/appointments/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlots']);

// ============================================================
// 3. PROTECTED ROUTES (نیاز به احراز هویت)
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
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
        Route::get('/avatar', [ProfileController::class, 'getAvatar']);
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });

    // ============================================================
    // 3.3 DASHBOARD
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
        // مسیرهای خاص قبل از مسیرهای داینامیک
        Route::get('/my/appointments', [AppointmentController::class, 'myAppointments']);
        Route::get('/my/stats', [AppointmentController::class, 'myPatientStats']);
        Route::get('/my/doctor/appointments', [AppointmentController::class, 'myDoctorAppointments']);
        Route::get('/my/doctor/stats', [AppointmentController::class, 'myDoctorStats']);

        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        Route::put('/{id}', [AppointmentController::class, 'update']);
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule']);
        Route::post('/{id}/start', [AppointmentController::class, 'start']);
        Route::post('/{id}/complete', [AppointmentController::class, 'complete']);
        Route::post('/{id}/no-show', [AppointmentController::class, 'noShow']);
    });

    // ============================================================
    // 3.5 PRESCRIPTIONS
    // ============================================================
    Route::prefix('prescriptions')->group(function () {
        Route::get('/my', [PrescriptionController::class, 'myPrescriptions']);
        Route::get('/doctor/my', [PrescriptionController::class, 'myDoctorPrescriptions']);
        Route::get('/stats', [PrescriptionController::class, 'stats']);
        Route::get('/', [PrescriptionController::class, 'index']);
        Route::post('/', [PrescriptionController::class, 'store']);
        Route::get('/{id}', [PrescriptionController::class, 'show']);
        Route::put('/{id}', [PrescriptionController::class, 'update']);
        Route::delete('/{id}', [PrescriptionController::class, 'destroy']);
        Route::post('/{id}/status', [PrescriptionController::class, 'changeStatus']);
        Route::get('/patient/{patientId}', [PrescriptionController::class, 'patientPrescriptions']);
        Route::get('/{id}/interactions', [PrescriptionController::class, 'checkInteractions']);
        Route::get('/{id}/print', [PrescriptionController::class, 'print']);
    });


// ============================================================
// 3.6 INVOICES
// ============================================================
    Route::prefix('invoices')->group(function () {
        Route::get('/my', [InvoiceController::class, 'myInvoices']);
        Route::get('/stats', [InvoiceController::class, 'stats']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        // ✅ مسیر جدید برای دریافت فاکتور بر اساس appointment_id
        Route::get('/appointment/{appointmentId}', [InvoiceController::class, 'getByAppointment']);
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
    // 3.8 WALLET
    // ============================================================
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::get('/summary', [WalletController::class, 'summary']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/pay-appointment', [WalletController::class, 'payAppointment']);
    });

    // ============================================================
    // 3.9 CHAT
    // ============================================================
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ChatController::class, 'conversations']);
        Route::get('/messages/{userId}', [ChatController::class, 'messages']);
        Route::post('/send', [ChatController::class, 'send']);
        Route::get('/unread-count', [ChatController::class, 'unreadCount']);
        Route::post('/mark-as-read/{userId}', [ChatController::class, 'markAllAsRead']);
        Route::get('/recent', [ChatController::class, 'recent']);
    });

    // ============================================================
    // 3.10 NOTIFICATIONS
    // ============================================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/read/all', [NotificationController::class, 'deleteRead']);
    });

    // ============================================================
    // 3.11 RATINGS
    // ============================================================
    Route::prefix('ratings')->group(function () {
        Route::post('/', [RatingController::class, 'store']);
        Route::post('/{id}/reply', [RatingController::class, 'reply']);
        Route::delete('/{id}/reply', [RatingController::class, 'deleteReply']);
        Route::get('/doctors/{doctorId}', [RatingController::class, 'doctorRatings']);
        Route::get('/doctors/{doctorId}/stats', [RatingController::class, 'doctorStats']);
    });

    // ============================================================
    // 3.12 PHARMACY
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
    // 3.13 PATIENTS (Admin/Doctor Only)
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

}); // End auth:sanctum

// ============================================================
// 4. PAYMENT CALLBACKS (عمومی - نیازی به احراز هویت ندارد)
// ============================================================
Route::prefix('payment')->group(function () {
    Route::get('/callback/{gateway}', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::post('/callback/{gateway}', [PaymentController::class, 'callback']);
});

// ============================================================
// 5. FALLBACK (مسیرهای پیدا نشد)
// ============================================================
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'مسیر مورد نظر یافت نشد',
        'errors' => ['route' => 'The requested route does not exist']
    ], 404);
});

// ============================================================
// 3.6.2 INVOICES - دریافت فاکتور بر اساس appointment_id
// ============================================================
Route::get('/invoices/appointment/{appointmentId}', [App\Http\Controllers\Api\InvoiceController::class, 'getByAppointment'])
    ->middleware('auth:sanctum');
