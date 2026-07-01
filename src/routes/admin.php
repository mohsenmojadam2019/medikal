<?php
// routes/admin.php

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
|
| تمام مسیرهای مدیریتی سیستم در این فایل قرار می‌گیرند
| همه مسیرها با middleware 'auth:sanctum' و 'role:admin|super_admin' محافظت می‌شوند
| پیشوند همه مسیرها: /api/admin
|



خلاصه مسیرهای ادمین (۳۱ گروه)
شماره	گروه مسیر	پیشوند	توضیح
1	Dashboard Management	/admin/dashboard/management	آمار و نمودارهای داشبورد مدیریت
2	Clinic Management	/admin/clinic	مدیریت اطلاعات کلینیک
3	User Management	/admin/users	مدیریت کاربران سیستم
4	Role Management	/admin/roles	مدیریت نقش‌ها
5	Permission Management	/admin/permissions	مدیریت مجوزها
6	Doctor Management	/admin/doctors	مدیریت پزشکان
7	Doctor Profile	/admin/doctors/profile	مدیریت پروفایل پزشکان
8	Patient Management	/admin/patients	مدیریت بیماران
9	Specialty Management	/admin/specialties	مدیریت تخصص‌ها
10	Drug Management	/admin/drugs	مدیریت داروها
11	Pharmacy Management	/admin/pharmacies	مدیریت داروخانه‌ها
12	Pharmacy Orders	/admin/pharmacy	مدیریت سفارشات داروخانه
13	Reports	/admin/reports	گزارشات
14	SEO Management	/admin/seo	مدیریت سئو
15	Notifications	/admin/notifications	مدیریت اعلان‌ها
16	Blog Management	/admin/blog	مدیریت وبلاگ
17	Wallet Management	/admin/wallet	مدیریت کیف پول
18	Insurance Management	/admin/insurance	مدیریت بیمه
19	Vaccination Management	/admin/vaccination	مدیریت واکسیناسیون
20	Survey Management	/admin/survey	مدیریت نظرسنجی‌ها
21	Event Management	/admin/events	مدیریت رویدادها
22	Campaign Management	/admin/campaigns	مدیریت کمپین‌ها
23	Medical Notes	/admin/medical-notes	مدیریت یادداشت‌های پزشکی
24	Laboratory Management	/admin/lab	مدیریت آزمایشگاه
25	Hospital Management	/admin/hospital	مدیریت بستری
26	Digital Forms	/admin/forms	مدیریت فرم‌های دیجیتال
27	Installment Management	/admin/installments	مدیریت اقساط
28	Webhook Management	/admin/webhook	مدیریت وب‌هوک
29	System Management	/admin/system	مدیریت سیستم
30	BI & Analytics	/admin/bi	هوش تجاری و پیش‌بینی
31	Super Admin	/admin/super-admin	مدیریت Tenantها (فقط سوپرادمین)
*/

Route::middleware(['auth:sanctum', 'role:admin|super_admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        // ============================================================
        // 1. DASHBOARD MANAGEMENT
        // ============================================================
        Route::prefix('dashboard/management')->name('dashboard.management.')->group(function () {
            Route::get('/stats', [ManagementDashboardController::class, 'stats'])->name('stats');
            Route::get('/charts', [ManagementDashboardController::class, 'charts'])->name('charts');
            Route::get('/quick-stats', [ManagementDashboardController::class, 'quickStats'])->name('quick-stats');
            Route::get('/recent-activities', [ManagementDashboardController::class, 'recentActivities'])->name('recent-activities');
            Route::get('/top-doctors', [ManagementDashboardController::class, 'topDoctors'])->name('top-doctors');
            Route::get('/summary', [ManagementDashboardController::class, 'summary'])->name('summary');
        });

        // ============================================================
        // 2. CLINIC MANAGEMENT
        // ============================================================
        Route::prefix('clinic')->name('clinic.')->group(function () {
            Route::get('/', [ClinicController::class, 'show'])->name('show');
            Route::put('/', [ClinicController::class, 'update'])->name('update');
            Route::post('/upload-logo', [ClinicController::class, 'uploadLogo'])->name('upload-logo');
            Route::post('/toggle-status', [ClinicController::class, 'toggleStatus'])->name('toggle-status');
        });

        // ============================================================
        // 3. USER MANAGEMENT
        // ============================================================
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{id}', [UserController::class, 'show'])->name('show');
            Route::put('/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/assign-role', [UserController::class, 'assignRole'])->name('assign-role');
        });

        // ============================================================
        // 4. ROLE MANAGEMENT
        // ============================================================
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{id}', [RoleController::class, 'show'])->name('show');
            Route::put('/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
        });

        // ============================================================
        // 5. PERMISSION MANAGEMENT
        // ============================================================
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::put('/{id}', [PermissionController::class, 'update'])->name('update');
            Route::delete('/{id}', [PermissionController::class, 'destroy'])->name('destroy');
            Route::post('/assign-to-role', [PermissionController::class, 'assignToRole'])->name('assign-to-role');
        });

        // ============================================================
        // 6. DOCTOR MANAGEMENT
        // ============================================================
        Route::prefix('doctors')->name('doctors.')->group(function () {
            Route::get('/', [DoctorController::class, 'index'])->name('index');
            Route::post('/', [DoctorController::class, 'store'])->name('store');
            Route::get('/{id}', [DoctorController::class, 'show'])->name('show');
            Route::put('/{id}', [DoctorController::class, 'update'])->name('update');
            Route::delete('/{id}', [DoctorController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-availability', [DoctorController::class, 'toggleAvailability'])->name('toggle-availability');
            Route::post('/{id}/verify', [DoctorController::class, 'verify'])->name('verify');
        });

        // ============================================================
        // 7. DOCTOR PROFILE MANAGEMENT (Admin)
        // ============================================================
        Route::prefix('doctors/profile')->name('doctors.profile.')->group(function () {
            Route::put('/{id}', [DoctorProfileController::class, 'update'])->name('update');
            Route::post('/{id}/verify', [DoctorProfileController::class, 'verify'])->name('verify');
            Route::post('/{id}/unverify', [DoctorProfileController::class, 'unverify'])->name('unverify');
            Route::put('/{id}/location', [LocationController::class, 'updateDoctorLocation'])->name('update-location');
        });

        // ============================================================
        // 8. PATIENT MANAGEMENT
        // ============================================================
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::post('/', [PatientController::class, 'store'])->name('store');
            Route::get('/{id}', [PatientController::class, 'show'])->name('show');
            Route::put('/{id}', [PatientController::class, 'update'])->name('update');
            Route::delete('/{id}', [PatientController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [PatientController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/verify', [PatientController::class, 'verify'])->name('verify');
            Route::post('/{id}/unverify', [PatientController::class, 'unverify'])->name('unverify');
            Route::post('/{id}/assign-doctor', [PatientController::class, 'assignDoctor'])->name('assign-doctor');
            Route::get('/{id}/medical-history', [PatientController::class, 'medicalHistory'])->name('medical-history');
            Route::get('/{id}/statistics', [PatientController::class, 'statistics'])->name('statistics');
            Route::get('/without-doctor', [PatientController::class, 'withoutDoctor'])->name('without-doctor');
            Route::get('/top', [PatientController::class, 'topPatients'])->name('top');
        });

        // ============================================================
        // 9. SPECIALTY MANAGEMENT
        // ============================================================
        Route::prefix('specialties')->name('specialties.')->group(function () {
            Route::get('/', [SpecialtyController::class, 'index'])->name('index');
            Route::post('/', [SpecialtyController::class, 'store'])->name('store');
            Route::get('/{id}', [SpecialtyController::class, 'show'])->name('show');
            Route::put('/{id}', [SpecialtyController::class, 'update'])->name('update');
            Route::delete('/{id}', [SpecialtyController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle', [SpecialtyController::class, 'toggleStatus'])->name('toggle');

            // Specialty Media
            Route::post('/{id}/icon', [SpecialtyMediaController::class, 'uploadIcon'])->name('upload-icon');
            Route::delete('/{id}/icon', [SpecialtyMediaController::class, 'deleteIcon'])->name('delete-icon');
            Route::get('/{id}/icon', [SpecialtyMediaController::class, 'getIcon'])->name('get-icon');
        });

        // ============================================================
        // 10. DRUG MANAGEMENT
        // ============================================================
        Route::prefix('drugs')->name('drugs.')->group(function () {
            Route::get('/', [DrugController::class, 'index'])->name('index');
            Route::post('/', [DrugController::class, 'store'])->name('store');
            Route::get('/{id}', [DrugController::class, 'show'])->name('show');
            Route::put('/{id}', [DrugController::class, 'update'])->name('update');
            Route::delete('/{id}', [DrugController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [DrugController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/increase-stock', [DrugController::class, 'increaseStock'])->name('increase-stock');
            Route::post('/{id}/decrease-stock', [DrugController::class, 'decreaseStock'])->name('decrease-stock');
            Route::get('/categories', [DrugController::class, 'categories'])->name('categories');
        });

        // ============================================================
        // 11. PHARMACY MANAGEMENT
        // ============================================================
        Route::prefix('pharmacies')->name('pharmacies.')->group(function () {
            Route::get('/', [PharmacyManagementController::class, 'index'])->name('index');
            Route::post('/', [PharmacyManagementController::class, 'store'])->name('store');
            Route::get('/{id}', [PharmacyManagementController::class, 'show'])->name('show');
            Route::put('/{id}', [PharmacyManagementController::class, 'update'])->name('update');
            Route::delete('/{id}', [PharmacyManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [PharmacyManagementController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/toggle-online', [PharmacyManagementController::class, 'toggleOnline'])->name('toggle-online');
        });

        // ============================================================
        // 12. PHARMACY ORDERS (Admin)
        // ============================================================
        Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
            Route::get('/admin/orders', [PharmacyController::class, 'pharmacyOrders'])->name('admin-orders');
        });

        // ============================================================
        // 13. REPORTS
        // ============================================================
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/types', [ReportController::class, 'types'])->name('types');
            Route::post('/excel', [ReportController::class, 'excel'])->name('excel');
            Route::post('/pdf', [ReportController::class, 'pdf'])->name('pdf');
            Route::post('/stream', [ReportController::class, 'stream'])->name('stream');
        });

        // ============================================================
        // 14. SEO MANAGEMENT
        // ============================================================
        Route::prefix('seo')->name('seo.')->group(function () {
            Route::get('/', [SeoController::class, 'index'])->name('index');
            Route::post('/', [SeoController::class, 'store'])->name('store');
            Route::get('/{id}', [SeoController::class, 'show'])->name('show');
            Route::put('/{id}', [SeoController::class, 'update'])->name('update');
            Route::delete('/{id}', [SeoController::class, 'destroy'])->name('destroy');
            Route::get('/model', [SeoController::class, 'getByModel'])->name('model');
        });

        // ============================================================
        // 15. NOTIFICATIONS (Admin)
        // ============================================================
        Route::prefix('notifications')->name('notifications.')->group(function () {
            // ارسال به کاربر خاص
            Route::post('/send-to-user', [NotificationController::class, 'sendToUser'])->name('send-to-user');
            Route::post('/send-to-users', [NotificationController::class, 'sendToUsers'])->name('send-to-users');

            // ارسال به نقش
            Route::post('/send-to-role', [NotificationController::class, 'sendToRole'])->name('send-to-role');

            // ارسال به همه
            Route::post('/send-to-all', [NotificationController::class, 'sendToAll'])->name('send-to-all');
            Route::post('/send-to-doctors', [NotificationController::class, 'sendToAllDoctors'])->name('send-to-doctors');
            Route::post('/send-to-patients', [NotificationController::class, 'sendToAllPatients'])->name('send-to-patients');

            // ارسال به بیماران یک پزشک
            Route::post('/send-to-doctor-patients/{doctorId}', [NotificationController::class, 'sendToDoctorPatients'])->name('send-to-doctor-patients');

            // ارسال با فیلتر
            Route::post('/send-filtered', [NotificationController::class, 'sendFiltered'])->name('send-filtered');

            // مشاهده اعلان‌های کاربران
            Route::get('/user/{userId}', [NotificationController::class, 'userNotifications'])->name('user');
        });

        // ============================================================
        // 16. BLOG MANAGEMENT
        // ============================================================
        Route::prefix('blog')->name('blog.')->group(function () {
            // Posts
            Route::get('/posts', [BlogController::class, 'adminPosts'])->name('posts');
            Route::post('/posts', [BlogController::class, 'store'])->name('store');
            Route::get('/posts/{id}', [BlogController::class, 'adminShow'])->name('show');
            Route::put('/posts/{id}', [BlogController::class, 'update'])->name('update');
            Route::delete('/posts/{id}', [BlogController::class, 'destroy'])->name('destroy');
            Route::post('/posts/{id}/publish', [BlogController::class, 'publish'])->name('publish');
            Route::post('/posts/{id}/unpublish', [BlogController::class, 'unpublish'])->name('unpublish');

            // Categories
            Route::get('/categories', [BlogController::class, 'adminCategories'])->name('categories');
            Route::post('/categories', [BlogController::class, 'storeCategory'])->name('store-category');
            Route::put('/categories/{id}', [BlogController::class, 'updateCategory'])->name('update-category');
            Route::delete('/categories/{id}', [BlogController::class, 'deleteCategory'])->name('delete-category');

            // Tags
            Route::get('/tags', [BlogController::class, 'adminTags'])->name('tags');
            Route::post('/tags', [BlogController::class, 'storeTag'])->name('store-tag');
            Route::put('/tags/{id}', [BlogController::class, 'updateTag'])->name('update-tag');
            Route::delete('/tags/{id}', [BlogController::class, 'deleteTag'])->name('delete-tag');

            // Comments
            Route::get('/comments', [BlogController::class, 'adminComments'])->name('comments');
            Route::post('/comments/{id}/approve', [BlogController::class, 'approveComment'])->name('approve-comment');
            Route::post('/comments/{id}/reject', [BlogController::class, 'rejectComment'])->name('reject-comment');
            Route::delete('/comments/{id}', [BlogController::class, 'deleteComment'])->name('delete-comment');

            // Stats
            Route::get('/stats', [BlogController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 17. WALLET MANAGEMENT (Admin)
        // ============================================================
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletController::class, 'index'])->name('index');
            Route::get('/stats', [WalletController::class, 'stats'])->name('stats');
            Route::get('/{userId}', [WalletController::class, 'show'])->name('show');
            Route::post('/{userId}/toggle-status', [WalletController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{userId}/add-bonus', [WalletController::class, 'addBonus'])->name('add-bonus');
        });

        // ============================================================
        // 18. INSURANCE MANAGEMENT
        // ============================================================
        Route::prefix('insurance')->name('insurance.')->group(function () {
            Route::get('/', [InsuranceController::class, 'index'])->name('index');
            Route::post('/', [InsuranceController::class, 'store'])->name('store');
            Route::get('/{id}', [InsuranceController::class, 'show'])->name('show');
            Route::put('/{id}', [InsuranceController::class, 'update'])->name('update');
            Route::delete('/{id}', [InsuranceController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [InsuranceController::class, 'toggleStatus'])->name('toggle-status');

            // Patient Insurance
            Route::post('/assign-to-patient', [InsuranceController::class, 'assignToPatient'])->name('assign-to-patient');
            Route::get('/patients/{patientId}/insurances', [InsuranceController::class, 'patientInsurances'])->name('patient-insurances');
            Route::get('/patients/{patientId}/primary', [InsuranceController::class, 'patientPrimaryInsurance'])->name('patient-primary');
            Route::put('/patient-insurances/{id}', [InsuranceController::class, 'updatePatientInsurance'])->name('update-patient-insurance');
            Route::post('/patient-insurances/{id}/deactivate', [InsuranceController::class, 'deactivatePatientInsurance'])->name('deactivate-patient-insurance');

            // Appointment Insurance
            Route::post('/apply-to-appointment', [InsuranceController::class, 'applyToAppointment'])->name('apply-to-appointment');
            Route::get('/appointments/{appointmentId}', [InsuranceController::class, 'appointmentInsurance'])->name('appointment');

            // Claims
            Route::post('/claims/{id}/approve', [InsuranceController::class, 'approveClaim'])->name('approve-claim');
            Route::post('/claims/{id}/reject', [InsuranceController::class, 'rejectClaim'])->name('reject-claim');

            // Reports
            Route::get('/stats', [InsuranceController::class, 'stats'])->name('stats');
            Route::get('/reports/{insuranceId}', [InsuranceController::class, 'insuranceReport'])->name('report');
        });

        // ============================================================
        // 19. VACCINATION MANAGEMENT
        // ============================================================
        Route::prefix('vaccination')->name('vaccination.')->group(function () {
            Route::get('/', [VaccinationController::class, 'index'])->name('index');
            Route::post('/', [VaccinationController::class, 'store'])->name('store');
            Route::get('/{id}', [VaccinationController::class, 'show'])->name('show');
            Route::put('/{id}', [VaccinationController::class, 'update'])->name('update');
            Route::delete('/{id}', [VaccinationController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [VaccinationController::class, 'toggleStatus'])->name('toggle-status');

            // Patient Vaccinations
            Route::post('/record', [VaccinationController::class, 'record'])->name('record');
            Route::get('/patients/{patientId}/vaccinations', [VaccinationController::class, 'patientVaccinations'])->name('patient-vaccinations');
            Route::get('/patients/{patientId}/summary', [VaccinationController::class, 'patientSummary'])->name('patient-summary');
            Route::get('/patients/{patientId}/upcoming', [VaccinationController::class, 'upcoming'])->name('upcoming');
            Route::get('/patients/{patientId}/overdue', [VaccinationController::class, 'overdue'])->name('overdue');

            // Reminders
            Route::get('/patients/{patientId}/reminders', [VaccinationController::class, 'reminders'])->name('reminders');
            Route::post('/reminders/process', [VaccinationController::class, 'processReminders'])->name('process-reminders');

            // Stats
            Route::get('/stats', [VaccinationController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 20. SURVEY MANAGEMENT
        // ============================================================
        Route::prefix('survey')->name('survey.')->group(function () {
            Route::get('/', [SurveyController::class, 'index'])->name('index');
            Route::post('/', [SurveyController::class, 'store'])->name('store');
            Route::get('/{id}', [SurveyController::class, 'show'])->name('show');
            Route::put('/{id}', [SurveyController::class, 'update'])->name('update');
            Route::delete('/{id}', [SurveyController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [SurveyController::class, 'toggleStatus'])->name('toggle-status');

            // Responses
            Route::get('/{surveyId}/responses', [SurveyController::class, 'surveyResponses'])->name('responses');
            Route::get('/patients/{patientId}/responses', [SurveyController::class, 'patientResponses'])->name('patient-responses');

            // Feedback
            Route::get('/feedbacks', [SurveyController::class, 'feedbacks'])->name('feedbacks');
            Route::post('/feedbacks/{id}/reply', [SurveyController::class, 'replyFeedback'])->name('reply-feedback');
            Route::post('/feedbacks/{id}/resolve', [SurveyController::class, 'resolveFeedback'])->name('resolve-feedback');

            // Stats
            Route::get('/stats', [SurveyController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 21. EVENT MANAGEMENT
        // ============================================================
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::get('/{id}', [EventController::class, 'show'])->name('show');
            Route::put('/{id}', [EventController::class, 'update'])->name('update');
            Route::delete('/{id}', [EventController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/publish', [EventController::class, 'publish'])->name('publish');
            Route::post('/{id}/complete', [EventController::class, 'complete'])->name('complete');

            // Registrations
            Route::get('/{eventId}/registrations', [EventController::class, 'eventRegistrations'])->name('registrations');
            Route::post('/registrations/{id}/confirm', [EventController::class, 'confirmRegistration'])->name('confirm-registration');
            Route::post('/registrations/{id}/cancel', [EventController::class, 'cancelRegistration'])->name('cancel-registration');
            Route::post('/registrations/{id}/attendance', [EventController::class, 'markAttendance'])->name('mark-attendance');

            // Stats
            Route::get('/stats/overview', [EventController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 22. CAMPAIGN MANAGEMENT
        // ============================================================
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [CampaignController::class, 'index'])->name('index');
            Route::post('/', [CampaignController::class, 'store'])->name('store');
            Route::get('/{id}', [CampaignController::class, 'show'])->name('show');
            Route::put('/{id}', [CampaignController::class, 'update'])->name('update');
            Route::delete('/{id}', [CampaignController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/activate', [CampaignController::class, 'activate'])->name('activate');
            Route::post('/{id}/pause', [CampaignController::class, 'pause'])->name('pause');
            Route::post('/{id}/complete', [CampaignController::class, 'complete'])->name('complete');

            // Interactions
            Route::get('/{campaignId}/interactions', [CampaignController::class, 'interactions'])->name('interactions');

            // Stats
            Route::get('/{id}/stats', [CampaignController::class, 'stats'])->name('stats');
            Route::get('/stats/overall', [CampaignController::class, 'overallStats'])->name('overall-stats');
        });

        // ============================================================
        // 23. MEDICAL NOTES (Admin)
        // ============================================================
        Route::prefix('medical-notes')->name('medical-notes.')->group(function () {
            Route::get('/', [MedicalNoteController::class, 'index'])->name('index');
            Route::post('/', [MedicalNoteController::class, 'store'])->name('store');
            Route::get('/{id}', [MedicalNoteController::class, 'show'])->name('show');
            Route::put('/{id}', [MedicalNoteController::class, 'update'])->name('update');
            Route::delete('/{id}', [MedicalNoteController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/share', [MedicalNoteController::class, 'share'])->name('share');
            Route::post('/{id}/unshare', [MedicalNoteController::class, 'unshare'])->name('unshare');
            Route::get('/patients/{patientId}', [MedicalNoteController::class, 'patientNotes'])->name('patient-notes');
            Route::get('/doctors/{doctorId}', [MedicalNoteController::class, 'doctorNotes'])->name('doctor-notes');
            Route::get('/patients/{patientId}/summary', [MedicalNoteController::class, 'summary'])->name('summary');
        });

        // ============================================================
        // 24. LABORATORY MANAGEMENT (Admin)
        // ============================================================
        Route::prefix('lab')->name('lab.')->group(function () {
            // Tests
            Route::post('/tests', [LabController::class, 'storeTest'])->name('store-test');
            Route::put('/tests/{id}', [LabController::class, 'updateTest'])->name('update-test');
            Route::delete('/tests/{id}', [LabController::class, 'deleteTest'])->name('delete-test');
            Route::post('/tests/{id}/toggle-status', [LabController::class, 'toggleTestStatus'])->name('toggle-test-status');

            // Categories
            Route::post('/categories', [LabController::class, 'storeCategory'])->name('store-category');
            Route::put('/categories/{id}', [LabController::class, 'updateCategory'])->name('update-category');
            Route::delete('/categories/{id}', [LabController::class, 'deleteCategory'])->name('delete-category');

            // Orders
            Route::get('/orders', [LabController::class, 'orders'])->name('orders');
        });

        // ============================================================
        // 25. HOSPITAL MANAGEMENT (Admin)
        // ============================================================
        Route::prefix('hospital')->name('hospital.')->group(function () {
            // Wards
            Route::post('/wards', [HospitalController::class, 'storeWard'])->name('store-ward');
            Route::put('/wards/{id}', [HospitalController::class, 'updateWard'])->name('update-ward');
            Route::delete('/wards/{id}', [HospitalController::class, 'deleteWard'])->name('delete-ward');

            // Beds
            Route::post('/beds', [HospitalController::class, 'storeBed'])->name('store-bed');
            Route::put('/beds/{id}', [HospitalController::class, 'updateBed'])->name('update-bed');
            Route::delete('/beds/{id}', [HospitalController::class, 'deleteBed'])->name('delete-bed');
            Route::post('/beds/{id}/status', [HospitalController::class, 'changeBedStatus'])->name('change-bed-status');
        });

        // ============================================================
        // 26. DIGITAL FORMS MANAGEMENT (Admin)
        // ============================================================
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::get('/', [FormController::class, 'forms'])->name('index');
            Route::post('/', [FormController::class, 'storeForm'])->name('store');
            Route::get('/{id}', [FormController::class, 'showForm'])->name('show');
            Route::put('/{id}', [FormController::class, 'updateForm'])->name('update');
            Route::delete('/{id}', [FormController::class, 'deleteForm'])->name('destroy');
            Route::post('/{id}/publish', [FormController::class, 'publishForm'])->name('publish');
            Route::post('/{id}/archive', [FormController::class, 'archiveForm'])->name('archive');
            Route::post('/{id}/duplicate', [FormController::class, 'duplicateForm'])->name('duplicate');

            // Responses
            Route::get('/responses', [FormController::class, 'responses'])->name('responses');
            Route::get('/forms/{formId}/responses', [FormController::class, 'formResponses'])->name('form-responses');

            // Stats
            Route::get('/stats', [FormController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 27. INSTALLMENT MANAGEMENT (Admin)
        // ============================================================
        Route::prefix('installments')->name('installments.')->group(function () {
            // Settings
            Route::get('/settings', [InstallmentController::class, 'getSettings'])->name('settings');
            Route::put('/settings', [InstallmentController::class, 'updateSettings'])->name('update-settings');
            Route::post('/settings/toggle', [InstallmentController::class, 'toggleInstallments'])->name('toggle');

            // Contracts
            Route::get('/contracts', [InstallmentController::class, 'getContracts'])->name('contracts');
            Route::get('/contracts/{id}', [InstallmentController::class, 'getContract'])->name('contract-show');
            Route::post('/contracts/{id}/activate', [InstallmentController::class, 'activateContract'])->name('activate-contract');
            Route::post('/contracts/{id}/cancel', [InstallmentController::class, 'cancelContract'])->name('cancel-contract');
            Route::get('/patients/{patientId}/contracts', [InstallmentController::class, 'patientContracts'])->name('patient-contracts');

            // Installments
            Route::get('/installments', [InstallmentController::class, 'getInstallments'])->name('installments');
            Route::post('/installments/{id}/waive', [InstallmentController::class, 'waiveInstallment'])->name('waive-installment');

            // Stats
            Route::get('/stats', [InstallmentController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 28. WEBHOOK MANAGEMENT
        // ============================================================
        Route::prefix('webhook')->name('webhook.')->group(function () {
            Route::get('/status', [WebhookController::class, 'status'])->name('status');
            Route::post('/toggle', [WebhookController::class, 'toggle'])->name('toggle');
            Route::get('/logs', [WebhookController::class, 'logs'])->name('logs');
        });

        // ============================================================
        // 29. SYSTEM MANAGEMENT
        // ============================================================
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/info', [SystemController::class, 'info'])->name('info');
            Route::get('/logs', [SystemController::class, 'logs'])->name('logs');
            Route::get('/logs/{filename}', [SystemController::class, 'logContent'])->name('log-content');
            Route::delete('/logs/{filename}', [SystemController::class, 'deleteLog'])->name('delete-log');
            Route::delete('/logs', [SystemController::class, 'clearLogs'])->name('clear-logs');
            Route::post('/clear-cache', [SystemController::class, 'clearCache'])->name('clear-cache');
        });

        // ============================================================
        // 30. BI & PREDICTIVE ANALYTICS
        // ============================================================
        Route::prefix('bi')->name('bi.')->group(function () {
            // Predictive Analytics
            Route::get('/predict/appointments', [BIController::class, 'predictAppointments'])->name('predict-appointments');
            Route::get('/forecast/revenue', [BIController::class, 'forecastRevenue'])->name('forecast-revenue');
            Route::get('/segment/patients', [BIController::class, 'segmentPatients'])->name('segment-patients');
            Route::get('/analyze/doctors', [BIController::class, 'analyzeDoctors'])->name('analyze-doctors');
            Route::get('/analytics', [BIController::class, 'getAnalytics'])->name('analytics');

            // Custom Reports
            Route::get('/reports', [BIController::class, 'reports'])->name('reports');
            Route::post('/reports', [BIController::class, 'createReport'])->name('create-report');
            Route::put('/reports/{id}', [BIController::class, 'updateReport'])->name('update-report');
            Route::delete('/reports/{id}', [BIController::class, 'deleteReport'])->name('delete-report');
            Route::post('/reports/{id}/generate', [BIController::class, 'generateReport'])->name('generate-report');

            // Report Scheduling
            Route::post('/schedules', [BIController::class, 'createSchedule'])->name('create-schedule');
            Route::put('/schedules/{id}', [BIController::class, 'updateSchedule'])->name('update-schedule');
            Route::delete('/schedules/{id}', [BIController::class, 'deleteSchedule'])->name('delete-schedule');

            // Backup
            Route::post('/backup/database', [BIController::class, 'backupDatabase'])->name('backup-database');
            Route::post('/backup/files', [BIController::class, 'backupFiles'])->name('backup-files');
            Route::post('/backup/{id}/restore', [BIController::class, 'restoreBackup'])->name('restore-backup');
            Route::get('/backup/history', [BIController::class, 'backupHistory'])->name('backup-history');
            Route::delete('/backup/cleanup', [BIController::class, 'cleanupBackups'])->name('cleanup-backups');

            // Audit Log
            Route::get('/audit-logs', [BIController::class, 'auditLogs'])->name('audit-logs');
            Route::post('/audit-logs', [BIController::class, 'logActivity'])->name('log-activity');

            // Log Archive
            Route::post('/logs/archive', [BIController::class, 'archiveLogs'])->name('archive-logs');
            Route::get('/logs/archived', [BIController::class, 'archivedLogs'])->name('archived-logs');
            Route::post('/logs/archived/{id}/restore', [BIController::class, 'restoreArchivedLog'])->name('restore-archived-log');
            Route::delete('/logs/archived/cleanup', [BIController::class, 'cleanupArchivedLogs'])->name('cleanup-archived-logs');

            // Stats
            Route::get('/stats', [BIController::class, 'stats'])->name('stats');
        });

        // ============================================================
        // 31. SUPER ADMIN ROUTES
        // ============================================================
        Route::middleware(['role:super_admin'])
            ->prefix('super-admin')
            ->name('super-admin.')
            ->group(function () {
                // Tenant Management
                Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
                Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
                Route::get('/tenants/{id}', [TenantController::class, 'show'])->name('tenants.show');
                Route::put('/tenants/{id}', [TenantController::class, 'update'])->name('tenants.update');
                Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
                Route::get('/stats', [TenantController::class, 'stats'])->name('stats');
                Route::get('/plans', [TenantController::class, 'plans'])->name('plans');
            });

    });
