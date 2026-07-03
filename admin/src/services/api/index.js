// src/services/api/index.js

export { default as client } from './client';
export { default as authService } from './auth';
export { default as languageService } from './language';
export { default as dashboardService } from './admin/dashboard';
export { default as doctorsService } from './admin/doctors';
export { default as patientsService } from './admin/patients';
export { default as appointmentsService } from './admin/appointments';
export { default as specialtiesService } from './admin/specialties';
export { default as schedulesService } from './admin/schedules';
export { default as prescriptionsService } from './admin/prescriptions';
export { default as drugsService } from './admin/drugs';
export { default as referralsService } from './admin/referrals';
export { default as ratingsService } from './admin/ratings';
export { default as invoicesService } from './admin/invoices';
export { default as walletService } from './admin/wallet';
export { default as paymentsService } from './admin/payments';
export { default as chatService } from './admin/chat';
export { default as notificationsService } from './admin/notifications';
export { default as usersService } from './admin/users';
// ✅ اصلاح: استفاده از Named Export
export {
    blogService,
    categoriesService,
    tagsService,
    commentsService
} from './admin/blog';

export { default as seoService } from './admin/seo';
export { default as landingService } from './admin/landing';
export { default as clinicService } from './admin/clinic';