<?php

use App\Http\Controllers\Api\AIChatController;
use App\Http\Controllers\Api\EmergencyController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\DoctorController as ApiDoctorController;
use App\Http\Controllers\Admin\PatientController as AdminPatientController;
use App\Http\Controllers\Api\PatientController as ApiPatientController;
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
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\MedicalNoteController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\QueueController;

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

// 2.2 DOCTORS (عمومی - API)
Route::prefix('doctors')->group(function () {
    Route::get('/', [ApiDoctorController::class, 'index']);
    Route::get('/nearby', [ApiDoctorController::class, 'nearby']);
    Route::get('/by-fee', [ApiDoctorController::class, 'byFee']);
    Route::get('/{id}', [ApiDoctorController::class, 'show']);
});

// 2.3 SPECIALTIES
Route::get('/specialties', [SpecialtyController::class, 'activeSpecialties']);

// 2.4 APPOINTMENTS (Available Slots)
Route::get('/appointments/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlots']);

// 2.5 PHARMACY (عمومی)
Route::prefix('pharmacy')->group(function () {
    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/pharmacies/{id}', [PharmacyController::class, 'showPharmacy']);
    Route::get('/pharmacies/{pharmacyId}/products', [PharmacyController::class, 'products']);
    Route::get('/pharmacies/{pharmacyId}/categories', [PharmacyController::class, 'categories']);
    Route::get('/products/search', [PharmacyController::class, 'search']);
    Route::get('/nearby', [PharmacyController::class, 'nearby']);
    Route::get('/contracted', [PharmacyController::class, 'contracted']);
});

// 2.6 CLINICS (عمومی)
Route::prefix('clinics')->group(function () {
    Route::get('/', [ClinicController::class, 'index']);
    Route::get('/settings', [ClinicController::class, 'settings']);
    Route::get('/provinces', [ClinicController::class, 'provinces']);
    Route::get('/provinces/{provinceId}/cities', [ClinicController::class, 'cities']);
    Route::get('/{id}', [ClinicController::class, 'show']);
});

// 2.7 DRUGS - PUBLIC
Route::get('/drugs/active', [DrugController::class, 'activeDrugs']);
Route::get('/drugs/{id}', [DrugController::class, 'show']);

// 2.8 LABORATORY (عمومی)
Route::prefix('lab')->group(function () {
    Route::get('/categories/active', [LabController::class, 'activeCategories']);
    Route::get('/tests/active', [LabController::class, 'activeTests']);
    Route::get('/tests/{id}', [LabController::class, 'showTest']);
});

// 2.9 PATIENTS (عمومی - محدود)
Route::prefix('patients')->group(function () {
    Route::get('/nearby', [ApiPatientController::class, 'nearby']);
    Route::get('/{id}', [ApiPatientController::class, 'show']);
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

        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
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
        Route::put('/orders/{id}', [PharmacyController::class, 'update']); // ✅ موجود است

        // نسخه پزشکی
        Route::post('/orders/{id}/prescription', [PharmacyController::class, 'uploadPrescription']);
        Route::get('/orders/{id}/prescription-status', [PharmacyController::class, 'getPrescriptionStatus']);

        // نوتیفیکیشن‌ها
        Route::get('/notifications', [PharmacyController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [PharmacyController::class, 'markNotificationAsRead']);

        // آمار
        Route::get('/stats', [PharmacyController::class, 'stats']);
    });

    // ============================================================
    // 3.13 PATIENTS
    // ============================================================
    Route::prefix('patients')->group(function () {
        Route::get('/my', [ApiPatientController::class, 'myPatients']);
        Route::get('/my-profile', [ApiPatientController::class, 'myProfile']);
        Route::put('/my-profile', [ApiPatientController::class, 'updateMyProfile']);
    });


    // ============================================================
    // 3.15 MEDICAL NOTES
    // ============================================================
    Route::prefix('medical-notes')->group(function () {
        Route::get('/', [MedicalNoteController::class, 'index']);
        Route::post('/', [MedicalNoteController::class, 'store']);
        Route::get('/patient/{patientId}', [MedicalNoteController::class, 'patientNotes']);
        Route::get('/doctor/{doctorId}', [MedicalNoteController::class, 'doctorNotes']);
        Route::get('/summary/{patientId}', [MedicalNoteController::class, 'summary']);
        Route::get('/{id}', [MedicalNoteController::class, 'show']);
        Route::put('/{id}', [MedicalNoteController::class, 'update']);
        Route::delete('/{id}', [MedicalNoteController::class, 'destroy']);
        Route::post('/{id}/share', [MedicalNoteController::class, 'share']);
        Route::post('/{id}/unshare', [MedicalNoteController::class, 'unshare']);
        Route::post('/{id}/lab-request', [MedicalNoteController::class, 'addLabRequest']);
        Route::post('/{id}/imaging-request', [MedicalNoteController::class, 'addImagingRequest']);
        Route::post('/{id}/referral', [MedicalNoteController::class, 'addReferral']);
        Route::get('/my', [MedicalNoteController::class, 'myNotes']);
        Route::get('/appointment/{appointmentId}', [MedicalNoteController::class, 'appointmentNote']);
        Route::post('/{id}/finalize', [MedicalNoteController::class, 'finalize']);
        Route::get('/my-notes', [MedicalNoteController::class, 'myPatientNotes']);
    });

    // ============================================================
    // 3.16 LABORATORY
    // ============================================================
    Route::prefix('lab')->group(function () {
        // سفارشات
        Route::post('/orders', [LabController::class, 'createOrder']);
        Route::get('/my/orders', [LabController::class, 'myOrders']);
        Route::get('/orders/{id}', [LabController::class, 'showOrder']);
        Route::put('/orders/{id}/status', [LabController::class, 'updateOrderStatus']);

        // نتایج
        Route::post('/results', [LabController::class, 'addResult']);
        Route::post('/results/bulk', [LabController::class, 'addResults']);
        Route::post('/results/{id}/verify', [LabController::class, 'verifyResult']);
        Route::delete('/results/{id}', [LabController::class, 'deleteResult']);

        // آمار
        Route::get('/stats', [LabController::class, 'stats']);
        Route::get('/my/stats', [LabController::class, 'myStats']);
    });

    // ============================================================
    // 3.17 AiChat Routes - دکتر آنلاین
    // ============================================================
    Route::prefix('v1/chat')->group(function () {
        Route::post('/start', [App\Http\Controllers\Api\AiChat\ChatController::class, 'start']);
        Route::get('/active', [App\Http\Controllers\Api\AiChat\ChatController::class, 'active']);
        Route::post('/close', [App\Http\Controllers\Api\AiChat\ChatController::class, 'close']);
        Route::post('/extend', [App\Http\Controllers\Api\AiChat\ChatController::class, 'extend']);
        Route::delete('/destroy', [App\Http\Controllers\Api\AiChat\ChatController::class, 'destroy']);
        Route::post('/send', [App\Http\Controllers\Api\AiChat\ChatController::class, 'send']);
        Route::get('/history', [App\Http\Controllers\Api\AiChat\ChatController::class, 'history']);
        Route::post('/feedback', [App\Http\Controllers\Api\AiChat\ChatController::class, 'feedback']);

        Route::prefix('medical')->group(function () {
            Route::post('/ask', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'ask']);
            Route::post('/symptom-check', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'symptomCheck']);
            Route::get('/history', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'history']);
            Route::get('/categories', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'categories']);
            Route::get('/stats', [App\Http\Controllers\Api\AiChat\MedicalChatController::class, 'stats']);
        });

        Route::prefix('files')->group(function () {
            Route::post('/upload', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'upload']);
            Route::get('/list', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'list']);
            Route::get('/download/{id}', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'download']);
            Route::delete('/delete/{id}', [App\Http\Controllers\Api\AiChat\FileUploadController::class, 'delete']);
        });
    });

    // ============================================================
    // 3.18 PHARMACY REPORTS
    // ============================================================
    Route::prefix('pharmacy')->group(function () {
        Route::prefix('reports')->group(function () {
            Route::get('/overview/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'overview']);
            Route::get('/product-sales/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'productSales']);
            Route::get('/inventory/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'inventory']);
            Route::get('/financial/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'financial']);
            Route::get('/daily/{pharmacyId}', [App\Http\Controllers\Api\PharmacyReportController::class, 'daily']);
        });
    });

    // ============================================================
    // 3.19 EMERGENCY (اورژانس - کاربر)
    // ============================================================
    Route::prefix('emergency')->group(function () {
        Route::post('/request', [EmergencyController::class, 'store']);
        Route::get('/status/{id}', [EmergencyController::class, 'status']);
        Route::get('/history', [EmergencyController::class, 'history']);
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
    Route::get('/payment/callback', [PharmacyController::class, 'paymentCallback'])->name('pharmacy.payment.callback');
    Route::get('/payment/callback/{gateway}', [PharmacyController::class, 'paymentCallback']);
});

// ============================================================
// 6. ADMIN ROUTES (برای مدیریت)
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
            Route::get('/pharmacy/{pharmacyId}', [DrugController::class, 'getPharmacyDrugs']);
        });

        // ============================================================
        // 6.2 AI CHAT
        // ============================================================
        Route::prefix('ai-chat')->group(function () {
            Route::post('/start', [AIChatController::class, 'start']);
            Route::get('/active', [AIChatController::class, 'active']);
            Route::post('/close', [AIChatController::class, 'close']);
            Route::post('/extend', [AIChatController::class, 'extend']);
            Route::delete('/destroy', [AIChatController::class, 'destroy']);
            Route::post('/send', [AIChatController::class, 'send']);
            Route::get('/history', [AIChatController::class, 'history']);
            Route::post('/feedback', [AIChatController::class, 'feedback']);
            Route::get('/providers', [AIChatController::class, 'providers']);
        });

        // ============================================================
        // 6.3 PHARMACY MANAGEMENT
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
        // 6.4 PHARMACY ORDERS (ادمین) - فقط متدهای موجود
        // ============================================================
        Route::prefix('pharmacy-orders')->group(function () {
            Route::get('/', [PharmacyController::class, 'pharmacyOrders']); // ✅ موجود است
            Route::get('/{id}', [PharmacyController::class, 'show']); // ✅ موجود است
            Route::post('/{id}/approve-prescription', [PharmacyController::class, 'approvePrescription']); // ✅ موجود است
            Route::post('/{id}/reject-prescription', [PharmacyController::class, 'rejectPrescription']); // ✅ موجود است
        });

        // ============================================================
        // 6.5 DOCTORS MANAGEMENT (ادمین)
        // ============================================================
        Route::prefix('doctors')->group(function () {
            Route::get('/', [AdminDoctorController::class, 'index']);
            Route::post('/', [AdminDoctorController::class, 'store']);
            Route::get('/{id}', [AdminDoctorController::class, 'show']);
            Route::put('/{id}', [AdminDoctorController::class, 'update']);
            Route::delete('/{id}', [AdminDoctorController::class, 'destroy']);
            Route::post('/{id}/toggle-availability', [AdminDoctorController::class, 'toggleAvailability']);
            Route::post('/{id}/verify', [AdminDoctorController::class, 'verify']);
            Route::post('/{id}/set-fee', [AdminDoctorController::class, 'setAppointmentFee']);
            Route::get('/{id}/fee', [AdminDoctorController::class, 'getAppointmentFee']);
            Route::post('/{id}/set-free', [AdminDoctorController::class, 'setFree']);
            Route::post('/{id}/set-paid', [AdminDoctorController::class, 'setPaid']);
        });

        // ============================================================
        // 6.6 PATIENTS MANAGEMENT (ادمین)
        // ============================================================
        Route::prefix('patients')->group(function () {
            Route::get('/without-doctor', [AdminPatientController::class, 'withoutDoctor']);
            Route::get('/top', [AdminPatientController::class, 'topPatients']);
            Route::get('/my/patients', [AdminPatientController::class, 'myPatients']);
            Route::get('/', [AdminPatientController::class, 'index']);
            Route::post('/', [AdminPatientController::class, 'store']);
            Route::get('/{id}', [AdminPatientController::class, 'show']);
            Route::put('/{id}', [AdminPatientController::class, 'update']);
            Route::delete('/{id}', [AdminPatientController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [AdminPatientController::class, 'toggleStatus']);
            Route::post('/{id}/verify', [AdminPatientController::class, 'verify']);
            Route::post('/{id}/unverify', [AdminPatientController::class, 'unverify']);
            Route::post('/{id}/assign-doctor', [AdminPatientController::class, 'assignDoctor']);
            Route::get('/{id}/medical-history', [AdminPatientController::class, 'medicalHistory']);
            Route::get('/{id}/statistics', [AdminPatientController::class, 'statistics']);
            Route::get('/by-national-code', [AdminPatientController::class, 'findByNationalCode']);
            Route::get('/by-mobile', [AdminPatientController::class, 'findByMobile']);
        });

        // ============================================================
        // 6.7 LABORATORY MANAGEMENT (ادمین)
        // ============================================================
        Route::prefix('lab')->group(function () {
            Route::get('/categories', [LabController::class, 'categories']);
            Route::post('/categories', [LabController::class, 'storeCategory']);
            Route::put('/categories/{id}', [LabController::class, 'updateCategory']);
            Route::delete('/categories/{id}', [LabController::class, 'deleteCategory']);
            Route::get('/tests', [LabController::class, 'tests']);
            Route::post('/tests', [LabController::class, 'storeTest']);
            Route::put('/tests/{id}', [LabController::class, 'updateTest']);
            Route::delete('/tests/{id}', [LabController::class, 'deleteTest']);
            Route::post('/tests/{id}/toggle', [LabController::class, 'toggleTestStatus']);
            Route::get('/orders', [LabController::class, 'orders']);
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
