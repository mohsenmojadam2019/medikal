<?php
// routes/admin.php

use App\Http\Controllers\Admin\AdminAuthController;
use Illuminate\Support\Facades\Route;
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

use App\Http\Controllers\Api\Dashboard\ManagementDashboardController;
use App\Http\Controllers\Api\BI\BIController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\VaccinationController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\MedicalNoteController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PharmacyController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
// ==========================================
// تست - فقط برای بررسی لود شدن فایل
// ==========================================
Route::get('/test-route', function() {
    return response()->json(['message' => 'Admin routes loaded!']);
});

// ============================================================================
// ===========================  مسیرهای عمومی ادمین (بدون احراز هویت) ===========================
// ============================================================================

Route::post('/login', [AdminAuthController::class, 'loginWithEmail']);

// ============================================================================
// ===========================  مسیرهای احراز هویت ادمین (نیاز به توکن) ===========================
// ============================================================================

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);
});

// ============================================================
// 🔒 PROTECTED ADMIN ROUTES (با احراز هویت)
// ============================================================
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])
    ->group(function () {


        // -------- 1. DASHBOARD MANAGEMENT --------
        Route::prefix('dashboard/management')->group(function () {
            Route::get('/stats', [ManagementDashboardController::class, 'stats']);
            Route::get('/charts', [ManagementDashboardController::class, 'charts']);
            Route::get('/quick-stats', [ManagementDashboardController::class, 'quickStats']);
            Route::get('/recent-activities', [ManagementDashboardController::class, 'recentActivities']);
            Route::get('/top-doctors', [ManagementDashboardController::class, 'topDoctors']);
            Route::get('/summary', [ManagementDashboardController::class, 'summary']);
        });

        // -------- 2. CLINIC MANAGEMENT --------
        Route::prefix('clinic')->group(function () {
            Route::get('/', [ClinicController::class, 'show']);
            Route::put('/', [ClinicController::class, 'update']);
            Route::post('/upload-logo', [ClinicController::class, 'uploadLogo']);
            Route::post('/toggle-status', [ClinicController::class, 'toggleStatus']);
        });

        // -------- 3. USER MANAGEMENT --------
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
        });

        // -------- 4. ROLE MANAGEMENT --------
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('/{id}', [RoleController::class, 'show']);
            Route::put('/{id}', [RoleController::class, 'update']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
        });

        // -------- 5. PERMISSION MANAGEMENT --------
        Route::prefix('permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index']);
            Route::post('/', [PermissionController::class, 'store']);
            Route::put('/{id}', [PermissionController::class, 'update']);
            Route::delete('/{id}', [PermissionController::class, 'destroy']);
            Route::post('/assign-to-role', [PermissionController::class, 'assignToRole']);
        });

        // -------- 6. DOCTOR MANAGEMENT --------
        Route::prefix('doctors')->group(function () {
            Route::get('/', [DoctorController::class, 'index']);
            Route::post('/', [DoctorController::class, 'store']);
            Route::get('/{id}', [DoctorController::class, 'show']);
            Route::put('/{id}', [DoctorController::class, 'update']);
            Route::delete('/{id}', [DoctorController::class, 'destroy']);
            Route::post('/{id}/toggle-availability', [DoctorController::class, 'toggleAvailability']);
            Route::post('/{id}/verify', [DoctorController::class, 'verify']);
        });

        // -------- 7. DOCTOR PROFILE MANAGEMENT --------
        Route::prefix('doctors/profile')->group(function () {
            Route::put('/{id}', [DoctorProfileController::class, 'update']);
            Route::post('/{id}/verify', [DoctorProfileController::class, 'verify']);
            Route::post('/{id}/unverify', [DoctorProfileController::class, 'unverify']);
            Route::put('/{id}/location', [LocationController::class, 'updateDoctorLocation']);
        });

        // -------- 8. PATIENT MANAGEMENT --------
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

        // -------- 9. SPECIALTY MANAGEMENT --------
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

        // -------- 10. DRUG MANAGEMENT --------
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

        // -------- 11. PHARMACY MANAGEMENT --------
        Route::prefix('pharmacies')->group(function () {
            Route::get('/', [PharmacyManagementController::class, 'index']);
            Route::post('/', [PharmacyManagementController::class, 'store']);
            Route::get('/{id}', [PharmacyManagementController::class, 'show']);
            Route::put('/{id}', [PharmacyManagementController::class, 'update']);
            Route::delete('/{id}', [PharmacyManagementController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [PharmacyManagementController::class, 'toggleStatus']);
            Route::post('/{id}/toggle-online', [PharmacyManagementController::class, 'toggleOnline']);
        });

        // -------- 12. PHARMACY ORDERS --------
        Route::prefix('pharmacy')->group(function () {
            Route::get('/admin/orders', [PharmacyController::class, 'pharmacyOrders']);
        });

        // -------- 13. REPORTS --------
        Route::prefix('reports')->group(function () {
            Route::get('/types', [ReportController::class, 'types']);
            Route::post('/excel', [ReportController::class, 'excel']);
            Route::post('/pdf', [ReportController::class, 'pdf']);
            Route::post('/stream', [ReportController::class, 'stream']);
        });

        // -------- 14. SEO MANAGEMENT --------
        Route::prefix('seo')->group(function () {
            Route::get('/', [SeoController::class, 'index']);
            Route::post('/', [SeoController::class, 'store']);
            Route::get('/{id}', [SeoController::class, 'show']);
            Route::put('/{id}', [SeoController::class, 'update']);
            Route::delete('/{id}', [SeoController::class, 'destroy']);
            Route::get('/model', [SeoController::class, 'getByModel']);
        });

        // -------- 15. NOTIFICATIONS --------
        Route::prefix('notifications')->group(function () {
            Route::post('/send-to-user', [NotificationController::class, 'sendToUser']);
            Route::post('/send-to-users', [NotificationController::class, 'sendToUsers']);
            Route::post('/send-to-role', [NotificationController::class, 'sendToRole']);
            Route::post('/send-to-all', [NotificationController::class, 'sendToAll']);
            Route::post('/send-to-doctors', [NotificationController::class, 'sendToAllDoctors']);
            Route::post('/send-to-patients', [NotificationController::class, 'sendToAllPatients']);
            Route::post('/send-to-doctor-patients/{doctorId}', [NotificationController::class, 'sendToDoctorPatients']);
            Route::post('/send-filtered', [NotificationController::class, 'sendFiltered']);
            Route::get('/user/{userId}', [NotificationController::class, 'userNotifications']);
        });

        // -------- 16. BLOG MANAGEMENT --------
        Route::prefix('blog')->group(function () {
            // Posts
            Route::get('/posts', [BlogController::class, 'adminPosts']);
            Route::post('/posts', [BlogController::class, 'store']);
            Route::get('/posts/{id}', [BlogController::class, 'adminShow']);
            Route::put('/posts/{id}', [BlogController::class, 'update']);
            Route::delete('/posts/{id}', [BlogController::class, 'destroy']);
            Route::post('/posts/{id}/publish', [BlogController::class, 'publish']);
            Route::post('/posts/{id}/unpublish', [BlogController::class, 'unpublish']);

            // Categories
            Route::get('/categories', [BlogController::class, 'adminCategories']);
            Route::post('/categories', [BlogController::class, 'storeCategory']);
            Route::put('/categories/{id}', [BlogController::class, 'updateCategory']);
            Route::delete('/categories/{id}', [BlogController::class, 'deleteCategory']);

            // Tags
            Route::get('/tags', [BlogController::class, 'adminTags']);
            Route::post('/tags', [BlogController::class, 'storeTag']);
            Route::put('/tags/{id}', [BlogController::class, 'updateTag']);
            Route::delete('/tags/{id}', [BlogController::class, 'deleteTag']);

            // Comments
            Route::get('/comments', [BlogController::class, 'adminComments']);
            Route::post('/comments/{id}/approve', [BlogController::class, 'approveComment']);
            Route::post('/comments/{id}/reject', [BlogController::class, 'rejectComment']);
            Route::delete('/comments/{id}', [BlogController::class, 'deleteComment']);

            // Stats
            Route::get('/stats', [BlogController::class, 'stats']);
        });

        // -------- 17. WALLET MANAGEMENT --------
        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'index']);
            Route::get('/stats', [WalletController::class, 'stats']);
            Route::get('/{userId}', [WalletController::class, 'show']);
            Route::post('/{userId}/toggle-status', [WalletController::class, 'toggleStatus']);
            Route::post('/{userId}/add-bonus', [WalletController::class, 'addBonus']);
        });

        // -------- 18. INSURANCE MANAGEMENT --------
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

        // -------- 19. VACCINATION MANAGEMENT --------
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

        // -------- 20. SURVEY MANAGEMENT --------
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

        // -------- 21. EVENT MANAGEMENT --------
        Route::prefix('events')->group(function () {
            Route::get('/', [EventController::class, 'index']);
            Route::post('/', [EventController::class, 'store']);
            Route::get('/{id}', [EventController::class, 'show']);
            Route::put('/{id}', [EventController::class, 'update']);
            Route::delete('/{id}', [EventController::class, 'destroy']);
            Route::post('/{id}/publish', [EventController::class, 'publish']);
            Route::post('/{id}/complete', [EventController::class, 'complete']);

            // Registrations
            Route::get('/{eventId}/registrations', [EventController::class, 'eventRegistrations']);
            Route::post('/registrations/{id}/confirm', [EventController::class, 'confirmRegistration']);
            Route::post('/registrations/{id}/cancel', [EventController::class, 'cancelRegistration']);
            Route::post('/registrations/{id}/attendance', [EventController::class, 'markAttendance']);

            // Stats
            Route::get('/stats/overview', [EventController::class, 'stats']);
        });

        // -------- 22. CAMPAIGN MANAGEMENT --------
        Route::prefix('campaigns')->group(function () {
            Route::get('/', [CampaignController::class, 'index']);
            Route::post('/', [CampaignController::class, 'store']);
            Route::get('/{id}', [CampaignController::class, 'show']);
            Route::put('/{id}', [CampaignController::class, 'update']);
            Route::delete('/{id}', [CampaignController::class, 'destroy']);
            Route::post('/{id}/activate', [CampaignController::class, 'activate']);
            Route::post('/{id}/pause', [CampaignController::class, 'pause']);
            Route::post('/{id}/complete', [CampaignController::class, 'complete']);

            // Interactions
            Route::get('/{campaignId}/interactions', [CampaignController::class, 'interactions']);

            // Stats
            Route::get('/{id}/stats', [CampaignController::class, 'stats']);
            Route::get('/stats/overall', [CampaignController::class, 'overallStats']);
        });

        // -------- 23. MEDICAL NOTES --------
        Route::prefix('medical-notes')->group(function () {
            Route::get('/', [MedicalNoteController::class, 'index']);
            Route::post('/', [MedicalNoteController::class, 'store']);
            Route::get('/{id}', [MedicalNoteController::class, 'show']);
            Route::put('/{id}', [MedicalNoteController::class, 'update']);
            Route::delete('/{id}', [MedicalNoteController::class, 'destroy']);
            Route::post('/{id}/share', [MedicalNoteController::class, 'share']);
            Route::post('/{id}/unshare', [MedicalNoteController::class, 'unshare']);
            Route::get('/patients/{patientId}', [MedicalNoteController::class, 'patientNotes']);
            Route::get('/doctors/{doctorId}', [MedicalNoteController::class, 'doctorNotes']);
            Route::get('/patients/{patientId}/summary', [MedicalNoteController::class, 'summary']);
        });

        // -------- 24. LABORATORY MANAGEMENT --------
        Route::prefix('lab')->group(function () {
            // Tests
            Route::post('/tests', [LabController::class, 'storeTest']);
            Route::put('/tests/{id}', [LabController::class, 'updateTest']);
            Route::delete('/tests/{id}', [LabController::class, 'deleteTest']);
            Route::post('/tests/{id}/toggle-status', [LabController::class, 'toggleTestStatus']);

            // Categories
            Route::post('/categories', [LabController::class, 'storeCategory']);
            Route::put('/categories/{id}', [LabController::class, 'updateCategory']);
            Route::delete('/categories/{id}', [LabController::class, 'deleteCategory']);

            // Orders
            Route::get('/orders', [LabController::class, 'orders']);
        });

        // -------- 25. HOSPITAL MANAGEMENT --------
        Route::prefix('hospital')->group(function () {
            // Wards
            Route::post('/wards', [HospitalController::class, 'storeWard']);
            Route::put('/wards/{id}', [HospitalController::class, 'updateWard']);
            Route::delete('/wards/{id}', [HospitalController::class, 'deleteWard']);

            // Beds
            Route::post('/beds', [HospitalController::class, 'storeBed']);
            Route::put('/beds/{id}', [HospitalController::class, 'updateBed']);
            Route::delete('/beds/{id}', [HospitalController::class, 'deleteBed']);
            Route::post('/beds/{id}/status', [HospitalController::class, 'changeBedStatus']);
        });

        // -------- 26. DIGITAL FORMS MANAGEMENT --------
        Route::prefix('forms')->group(function () {
            Route::get('/', [FormController::class, 'forms']);
            Route::post('/', [FormController::class, 'storeForm']);
            Route::get('/{id}', [FormController::class, 'showForm']);
            Route::put('/{id}', [FormController::class, 'updateForm']);
            Route::delete('/{id}', [FormController::class, 'deleteForm']);
            Route::post('/{id}/publish', [FormController::class, 'publishForm']);
            Route::post('/{id}/archive', [FormController::class, 'archiveForm']);
            Route::post('/{id}/duplicate', [FormController::class, 'duplicateForm']);

            // Responses
            Route::get('/responses', [FormController::class, 'responses']);
            Route::get('/forms/{formId}/responses', [FormController::class, 'formResponses']);

            // Stats
            Route::get('/stats', [FormController::class, 'stats']);
        });

        // -------- 27. INSTALLMENT MANAGEMENT --------
        Route::prefix('installments')->group(function () {
            // Settings
            Route::get('/settings', [InstallmentController::class, 'getSettings']);
            Route::put('/settings', [InstallmentController::class, 'updateSettings']);
            Route::post('/settings/toggle', [InstallmentController::class, 'toggleInstallments']);

            // Contracts
            Route::get('/contracts', [InstallmentController::class, 'getContracts']);
            Route::get('/contracts/{id}', [InstallmentController::class, 'getContract']);
            Route::post('/contracts/{id}/activate', [InstallmentController::class, 'activateContract']);
            Route::post('/contracts/{id}/cancel', [InstallmentController::class, 'cancelContract']);
            Route::get('/patients/{patientId}/contracts', [InstallmentController::class, 'patientContracts']);

            // Installments
            Route::get('/installments', [InstallmentController::class, 'getInstallments']);
            Route::post('/installments/{id}/waive', [InstallmentController::class, 'waiveInstallment']);

            // Stats
            Route::get('/stats', [InstallmentController::class, 'stats']);
        });

        // -------- 28. WEBHOOK MANAGEMENT --------
        Route::prefix('webhook')->group(function () {
            Route::get('/status', [WebhookController::class, 'status']);
            Route::post('/toggle', [WebhookController::class, 'toggle']);
            Route::get('/logs', [WebhookController::class, 'logs']);
        });

        // -------- 29. SYSTEM MANAGEMENT --------
        Route::prefix('system')->group(function () {
            Route::get('/info', [SystemController::class, 'info']);
            Route::get('/logs', [SystemController::class, 'logs']);
            Route::get('/logs/{filename}', [SystemController::class, 'logContent']);
            Route::delete('/logs/{filename}', [SystemController::class, 'deleteLog']);
            Route::delete('/logs', [SystemController::class, 'clearLogs']);
            Route::post('/clear-cache', [SystemController::class, 'clearCache']);
        });

        // -------- 30. BI & PREDICTIVE ANALYTICS --------
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

        // -------- 31. SUPER ADMIN ROUTES --------
        Route::middleware(['role:super_admin'])
            ->prefix('super-admin')
            ->group(function () {
                // Tenant Management
                Route::get('/tenants', [TenantController::class, 'index']);
                Route::post('/tenants', [TenantController::class, 'store']);
                Route::get('/tenants/{id}', [TenantController::class, 'show']);
                Route::put('/tenants/{id}', [TenantController::class, 'update']);
                Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus']);
                Route::get('/stats', [TenantController::class, 'stats']);
                Route::get('/plans', [TenantController::class, 'plans']);
            });
    });
