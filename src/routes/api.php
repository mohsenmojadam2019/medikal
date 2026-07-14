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
use App\Http\Controllers\Admin\DrugController;
use App\Http\Controllers\Admin\PharmacyManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
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

// 2.1 AUTH
Route::prefix('auth')->group(function () {
    Route::post('/login/mobile', [AuthController::class, 'loginWithMobile']);
    Route::post('/login/mobile/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/login/email', [AuthController::class, 'loginWithEmail']);
});

// 2.2 DOCTORS
Route::prefix('doctors')->group(function () {
    Route::get('/public', [DoctorController::class, 'publicList']);
    Route::get('/{id}/public', [DoctorController::class, 'publicShow']);
});

// 2.3 SPECIALTIES
Route::get('/specialties', [SpecialtyController::class, 'activeSpecialties']);

// 2.4 APPOINTMENTS (Available Slots)
Route::get('/appointments/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlots']);

// ✅ 2.5 PHARMACY (عمومی)
Route::prefix('pharmacy')->group(function () {
    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/pharmacies/{id}', [PharmacyController::class, 'show']);
    Route::get('/pharmacies/{pharmacyId}/products', [PharmacyController::class, 'products']);
    Route::get('/categories', [PharmacyController::class, 'categories']);
    Route::get('/products/search', [PharmacyController::class, 'search']);
    Route::get('/nearby', [PharmacyController::class, 'nearby']);
    Route::get('/contracted', [PharmacyController::class, 'contracted']);
});

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
        Route::get('/my/appointments', [AppointmentController::class, 'myAppointments']);
        Route::get('/my/stats', [AppointmentController::class, 'myPatientStats']);
        // ⚠️ این دو مسیر نیاز به بررسی دارند - ممکن است در کنترلر نباشند
        // Route::get('/my/doctor/appointments', [AppointmentController::class, 'myDoctorAppointments']);
        // Route::get('/my/doctor/stats', [AppointmentController::class, 'myDoctorStats']);
        
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
        // ✅ دریافت فاکتور بر اساس appointment_id
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
    // 3.12 PHARMACY (کاربران معمولی)
    // ============================================================
    Route::prefix('pharmacy')->group(function () {
        // سفارشات
        Route::post('/orders', [PharmacyController::class, 'store']);
        Route::get('/orders', [PharmacyController::class, 'myOrders']);
        Route::get('/orders/{id}', [PharmacyController::class, 'show']);
        Route::post('/orders/{id}/pay', [PharmacyController::class, 'pay']);
        Route::post('/orders/{id}/cancel', [PharmacyController::class, 'cancel']);
        
        // نوتیفیکیشن‌ها
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
// 5. PHARMACY CALLBACK (عمومی)
// ============================================================
Route::prefix('pharmacy')->group(function () {
    Route::get('/payment/callback', [PharmacyController::class, 'paymentCallback']);
});

// ============================================================
// 6. ADMIN ROUTES (برای مدیریت داروخانه)
// ============================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // ============================================================
    // 6.1 DRUG MANAGEMENT
    // ============================================================
    Route::prefix('drugs')->group(function () {
        Route::get('/', [DrugController::class, 'index']);
        Route::post('/', [DrugController::class, 'store']);
        Route::get('/{id}', [DrugController::class, 'show']);
        Route::put('/{id}', [DrugController::class, 'update']);
        Route::delete('/{id}', [DrugController::class, 'destroy']);
        Route::post('/{id}/toggle', [DrugController::class, 'toggleStatus']);
        Route::post('/{id}/increase-stock', [DrugController::class, 'increaseStock']);
        Route::post('/{id}/decrease-stock', [DrugController::class, 'decreaseStock']);
        Route::get('/categories', [DrugController::class, 'categories']);
        Route::get('/active', [DrugController::class, 'activeDrugs']);
        Route::get('/search', [DrugController::class, 'search']);
    });

    // ============================================================
    // 6.2 PHARMACY MANAGEMENT
    // ============================================================
    Route::prefix('pharmacies')->group(function () {
        Route::get('/', [PharmacyManagementController::class, 'index']);
        Route::post('/', [PharmacyManagementController::class, 'store']);
        Route::get('/{id}', [PharmacyManagementController::class, 'show']);
        Route::put('/{id}', [PharmacyManagementController::class, 'update']);
        Route::delete('/{id}', [PharmacyManagementController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [PharmacyManagementController::class, 'toggleStatus']);
        Route::post('/{id}/toggle-online', [PharmacyManagementController::class, 'toggleOnline']);
    });

    // ============================================================
    // 6.3 PHARMACY ORDERS (ادمین)
    // ============================================================
    Route::prefix('pharmacy-orders')->group(function () {
        Route::get('/', [PharmacyController::class, 'pharmacyOrders']);
        Route::get('/{id}', [PharmacyController::class, 'show']);
        Route::post('/{id}/status', [PharmacyController::class, 'updateStatus']);
    });

});

// ============================================================
// 7. FALLBACK (مسیرهای پیدا نشد)
// ============================================================
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'مسیر مورد نظر یافت نشد',
        'errors' => ['route' => 'The requested route does not exist']
    ], 404);
});

// ============================================================
// 3.14 PHARMACY REPORTS (نیاز به احراز هویت)
// ============================================================
Route::middleware('auth:sanctum')->prefix('pharmacy')->group(function () {
    Route::prefix('reports')->group(function () {
        Route::get('/overview/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'overview']);
        Route::get('/product-sales/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'productSales']);
        Route::get('/inventory/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'inventory']);
        Route::get('/financial/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'financial']);
        Route::get('/daily/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'daily']);
    });
});

// ============================================================
// 6.4 DRUGS - PUBLIC (عمومی)
// ============================================================
Route::get('/drugs/active', [App\Http\Controllers\Admin\DrugController::class, 'activeDrugs']);

// ============================================================
// 3.15 MEDICAL NOTES (یادداشت‌های پزشکی)
// ============================================================
Route::middleware('auth:sanctum')->prefix('medical-notes')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'store']);
    Route::get('/patient/{patientId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'patientNotes']);
    Route::get('/doctor/{doctorId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'doctorNotes']);
    Route::get('/summary/{patientId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'summary']);
    Route::get('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'destroy']);
    Route::post('/{id}/share', [App\Http\Controllers\Api\MedicalNoteController::class, 'share']);
    Route::post('/{id}/unshare', [App\Http\Controllers\Api\MedicalNoteController::class, 'unshare']);
    Route::post('/{id}/lab-request', [App\Http\Controllers\Api\MedicalNoteController::class, 'addLabRequest']);
    Route::post('/{id}/imaging-request', [App\Http\Controllers\Api\MedicalNoteController::class, 'addImagingRequest']);
    Route::post('/{id}/referral', [App\Http\Controllers\Api\MedicalNoteController::class, 'addReferral']);
});

// ============================================================
// 3.15 MEDICAL NOTES (پزشک و بیمار)
// ============================================================
Route::middleware('auth:sanctum')->prefix('medical-notes')->group(function () {
    // پزشک: مشاهده یادداشت‌های خود و بیمارانش
    Route::get('/my', [App\Http\Controllers\Api\MedicalNoteController::class, 'myNotes']);
    Route::get('/patient/{patientId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'patientNotes']);
    Route::get('/appointment/{appointmentId}', [App\Http\Controllers\Api\MedicalNoteController::class, 'appointmentNote']);
    
    // ایجاد و مدیریت
    Route::post('/', [App\Http\Controllers\Api\MedicalNoteController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\MedicalNoteController::class, 'destroy']);
    
    // اقدامات
    Route::post('/{id}/share', [App\Http\Controllers\Api\MedicalNoteController::class, 'share']);
    Route::post('/{id}/unshare', [App\Http\Controllers\Api\MedicalNoteController::class, 'unshare']);
    Route::post('/{id}/finalize', [App\Http\Controllers\Api\MedicalNoteController::class, 'finalize']);
    
    // بیمار: مشاهده یادداشت‌های خود
    Route::get('/my-notes', [App\Http\Controllers\Api\MedicalNoteController::class, 'myPatientNotes']);
});

// ============================================================
// 6.5 DOCTOR FEE MANAGEMENT (ادمین)
// ============================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])
    ->prefix('admin/doctors')
    ->group(function () {
        Route::post('/{id}/set-fee', [App\Http\Controllers\Admin\DoctorController::class, 'setAppointmentFee']);
        Route::get('/{id}/fee', [App\Http\Controllers\Admin\DoctorController::class, 'getAppointmentFee']);
        Route::post('/{id}/set-free', [App\Http\Controllers\Admin\DoctorController::class, 'setFree']);
        Route::post('/{id}/set-paid', [App\Http\Controllers\Admin\DoctorController::class, 'setPaid']);
    });

// ============================================================
// 2.7 DRUGS - دریافت یک دارو (عمومی)
// ============================================================
Route::get('/drugs/{id}', [App\Http\Controllers\Admin\DrugController::class, 'show']);

// ============================================================
// 5. PHARMACY CALLBACK (عمومی)
// ============================================================
Route::prefix('pharmacy')->group(function () {
    Route::get('/payment/callback', [App\Http\Controllers\Api\PharmacyController::class, 'paymentCallback'])->name('pharmacy.payment.callback');
});

// ============================================================
// 8. LABORATORY ROUTES
// ============================================================

// مسیرهای عمومی
Route::prefix('lab')->group(function () {
    Route::get('/categories/active', [App\Http\Controllers\Api\LabController::class, 'activeCategories']);
    Route::get('/tests/active', [App\Http\Controllers\Api\LabController::class, 'activeTests']);
    Route::get('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'showTest']);
});

// مسیرهای محافظت شده
Route::middleware('auth:sanctum')->prefix('lab')->group(function () {
    // سفارشات
    Route::post('/orders', [App\Http\Controllers\Api\LabController::class, 'createOrder']);
    Route::get('/my/orders', [App\Http\Controllers\Api\LabController::class, 'myOrders']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\LabController::class, 'showOrder']);
    Route::put('/orders/{id}/status', [App\Http\Controllers\Api\LabController::class, 'updateOrderStatus']);
    
    // نتایج
    Route::post('/results', [App\Http\Controllers\Api\LabController::class, 'addResult']);
    Route::post('/results/bulk', [App\Http\Controllers\Api\LabController::class, 'addResults']);
    Route::post('/results/{id}/verify', [App\Http\Controllers\Api\LabController::class, 'verifyResult']);
    Route::delete('/results/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteResult']);
    
    // آمار
    Route::get('/stats', [App\Http\Controllers\Api\LabController::class, 'stats']);
    Route::get('/my/stats', [App\Http\Controllers\Api\LabController::class, 'myStats']);
});

// مسیرهای ادمین
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('lab')->group(function () {
    Route::get('/categories', [App\Http\Controllers\Api\LabController::class, 'categories']);
    Route::post('/categories', [App\Http\Controllers\Api\LabController::class, 'storeCategory']);
    Route::put('/categories/{id}', [App\Http\Controllers\Api\LabController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteCategory']);
    
    Route::get('/tests', [App\Http\Controllers\Api\LabController::class, 'tests']);
    Route::post('/tests', [App\Http\Controllers\Api\LabController::class, 'storeTest']);
    Route::put('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'updateTest']);
    Route::delete('/tests/{id}', [App\Http\Controllers\Api\LabController::class, 'deleteTest']);
    Route::post('/tests/{id}/toggle', [App\Http\Controllers\Api\LabController::class, 'toggleTestStatus']);
    
    Route::get('/orders', [App\Http\Controllers\Api\LabController::class, 'orders']);
});

// ============================================================
// AiChat Routes - دکتر آنلاین
// ============================================================
Route::prefix('v1/chat')->middleware(['auth:sanctum'])->group(function () {

    // مدیریت جلسات
    Route::post('/start', [App\Http\Controllers\Api\AiChat\ChatController::class, 'start']);
    Route::get('/active', [App\Http\Controllers\Api\AiChat\ChatController::class, 'active']);
    Route::post('/close', [App\Http\Controllers\Api\AiChat\ChatController::class, 'close']);
    Route::post('/extend', [App\Http\Controllers\Api\AiChat\ChatController::class, 'extend']);
    Route::delete('/destroy', [App\Http\Controllers\Api\AiChat\ChatController::class, 'destroy']);

    // ارسال پیام و تاریخچه
    Route::post('/send', [App\Http\Controllers\Api\AiChat\ChatController::class, 'send']);
    Route::get('/history', [App\Http\Controllers\Api\AiChat\ChatController::class, 'history']);

    // بازخورد
    Route::post('/feedback', [App\Http\Controllers\Api\AiChat\ChatController::class, 'feedback']);

    // سوالات پزشکی
    Route::prefix('medical')->group(function () {
        Route::post('/ask', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'ask']);
        Route::post('/symptom-check', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'symptomCheck']);
        Route::get('/history', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'history']);
        Route::get('/categories', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'categories']);
        Route::get('/stats', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'stats']);
    });

    // مدیریت فایل‌ها
    Route::prefix('files')->group(function () {
        Route::post('/upload', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'upload']);
        Route::get('/list', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'list']);
        Route::get('/download/{id}', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'download']);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'delete']);
    });
});
