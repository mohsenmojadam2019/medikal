<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Rating;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    use ApiResponse;

    public function index()
    {
        // تعداد پزشکان - فقط verified
        $totalDoctors = Doctor::where('is_verified', true)->count();

        $stats = [
            'total_doctors' => $totalDoctors,
            'total_patients' => Patient::count(),
            'total_appointments' => Appointment::count(),
            'completed_appointments' => Appointment::where('status', 'completed')->count(),
        ];

        $topDoctors = Doctor::with(['user', 'specialty'])
            ->where('is_verified', true)
            ->orderBy('rating', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->full_name,
                    'specialty' => $doctor->specialty?->name,
                    'rating' => $doctor->rating ?? 0,
                    'total_reviews' => $doctor->total_reviews ?? 0,
                    'consultation_fee' => $doctor->consultation_fee,
                    'clinic_name' => $doctor->clinic_name,
                    'experience_years' => $doctor->experience_years,
                ];
            });

        $features = [
            [
                'icon' => 'fa-user-md',
                'title' => 'پزشکان مجرب',
                'description' => 'بیش از ' . $totalDoctors . ' پزشک متخصص در سامانه',
                'color' => 'blue',
            ],
            [
                'icon' => 'fa-calendar-check',
                'title' => 'نوبت‌دهی آسان',
                'description' => 'رزرو نوبت آنلاین در کمتر از ۲ دقیقه',
                'color' => 'green',
            ],
            [
                'icon' => 'fa-prescription',
                'title' => 'نسخه‌نویسی الکترونیک',
                'description' => 'نسخه‌های دیجیتال با کد رهگیری یکتا',
                'color' => 'purple',
            ],
            [
                'icon' => 'fa-truck',
                'title' => 'ارسال دارو به خانه',
                'description' => 'سفارش و دریافت دارو درب منزل',
                'color' => 'orange',
            ],
            [
                'icon' => 'fa-shield-alt',
                'title' => 'امنیت و حریم خصوصی',
                'description' => 'اطلاعات شما با بالاترین سطح امنیت محافظت می‌شود',
                'color' => 'red',
            ],
            [
                'icon' => 'fa-headset',
                'title' => 'پشتیبانی ۲۴/۷',
                'description' => 'تیم پشتیبانی همواره در کنار شماست',
                'color' => 'teal',
            ],
        ];

        $reviews = Rating::with(['patient.user', 'doctor.user'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'patient_name' => $rating->is_anonymous ? 'کاربر ناشناس' : ($rating->patient->full_name ?? 'کاربر'),
                    'doctor_name' => $rating->doctor->full_name ?? 'پزشک',
                    'score' => $rating->score,
                    'comment' => $rating->comment,
                    'created_at' => $rating->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'top_doctors' => $topDoctors,
                'features' => $features,
                'reviews' => $reviews,
                'app_name' => config('app.name', 'سامانه سلامت'),
                'version' => '2.0.0',
            ]
        ]);
    }

    public function stats()
    {
        return $this->success([
            'doctors' => Doctor::where('is_verified', true)->count(),
            'patients' => Patient::count(),
            'appointments' => Appointment::count(),
            'completed' => Appointment::where('status', 'completed')->count(),
            'rating' => round(Rating::avg('score') ?? 0, 1),
        ]);
    }

    public function topDoctors(Request $request)
    {
        $limit = $request->get('limit', 6);

        $doctors = Doctor::with(['user', 'specialty'])
            ->where('is_verified', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->full_name,
                    'specialty' => $doctor->specialty?->name,
                    'rating' => $doctor->rating ?? 0,
                    'total_reviews' => $doctor->total_reviews ?? 0,
                    'consultation_fee' => $doctor->consultation_fee,
                    'clinic_name' => $doctor->clinic_name,
                    'experience_years' => $doctor->experience_years,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($doctor->full_name) . '&background=2b6cb0&color=fff&size=100',
                ];
            });

        return $this->success($doctors);
    }

    public function recentReviews(Request $request)
    {
        $limit = $request->get('limit', 6);

        $reviews = Rating::with(['patient.user', 'doctor.user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'patient_name' => $rating->is_anonymous ? 'کاربر ناشناس' : ($rating->patient->full_name ?? 'کاربر'),
                    'doctor_name' => $rating->doctor->full_name ?? 'پزشک',
                    'score' => $rating->score,
                    'comment' => $rating->comment,
                    'created_at' => $rating->created_at->diffForHumans(),
                ];
            });

        return $this->success($reviews);
    }
}
