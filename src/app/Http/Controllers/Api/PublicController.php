<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    use ApiResponse;

    public function doctorSeo($id)
    {
        $doctor = Doctor::with('seo')->find($id);

        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $seo = $doctor->seo;

        // اگر SEO وجود نداشت، ایجاد کن
        if (!$seo) {
            $seo = $doctor->seo()->create([
                'title' => "دکتر {$doctor->full_name} | سامانه سلامت",
                'description' => "ویزیت آنلاین و نوبت‌دهی دکتر {$doctor->full_name}، متخصص {$doctor->specialty?->name}",
                'keywords' => "دکتر {$doctor->full_name}, نوبت {$doctor->specialty?->name}, ویزیت آنلاین",
                'robots' => 'index, follow',
            ]);
        }

        return $this->success([
            'meta' => [
                'title' => $seo->title,
                'description' => $seo->description,
                'keywords' => $seo->keywords,
                'og_title' => $seo->og_title,
                'og_description' => $seo->og_description,
                'og_image' => $seo->og_image,
                'twitter_title' => $seo->twitter_title,
                'twitter_description' => $seo->twitter_description,
                'twitter_image' => $seo->twitter_image,
                'canonical_url' => $seo->canonical_url,
                'robots' => $seo->robots,
                'schema_json' => $seo->schema_json,
            ],
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'specialty' => $doctor->specialty?->name,
                'rating' => $doctor->rating,
                'experience_years' => $doctor->experience_years,
                'consultation_fee' => $doctor->consultation_fee,
            ]
        ]);
    }

    public function pageSeo(Request $request)
    {
        $page = $request->get('page', 'home');

        $seoData = [
            'home' => [
                'title' => 'سامانه جامع سلامت | نوبت‌دهی، نسخه‌نویسی و داروخانه آنلاین',
                'description' => 'سامانه جامع مدیریت سلامت با امکان نوبت‌دهی آنلاین، نسخه‌نویسی الکترونیک و سفارش دارو درب منزل',
                'keywords' => 'نوبت دکتر, نسخه الکترونیک, داروخانه آنلاین, سلامت, درمان',
            ],
            'doctors' => [
                'title' => 'لیست پزشکان متخصص | سامانه سلامت',
                'description' => 'لیست کامل پزشکان متخصص در تمامی رشته‌ها با امکان نوبت‌دهی آنلاین',
                'keywords' => 'پزشک, متخصص, نوبت پزشک, لیست پزشکان',
            ],
            'patients' => [
                'title' => 'مدیریت بیماران | سامانه سلامت',
                'description' => 'سامانه جامع مدیریت اطلاعات بیماران، سوابق پزشکی و پرونده سلامت',
                'keywords' => 'بیمار, پرونده سلامت, سوابق پزشکی',
            ],
            'appointments' => [
                'title' => 'نوبت‌دهی آنلاین | سامانه سلامت',
                'description' => 'رزرو نوبت آنلاین از پزشکان متخصص در کمترین زمان',
                'keywords' => 'نوبت‌دهی, رزرو نوبت, ویزیت آنلاین',
            ],
            'pharmacy' => [
                'title' => 'داروخانه آنلاین | سامانه سلامت',
                'description' => 'سفارش آنلاین دارو با نسخه الکترونیک و تحویل درب منزل',
                'keywords' => 'داروخانه, سفارش دارو, داروی آنلاین',
            ],
            'about' => [
                'title' => 'درباره سامانه سلامت | مدیریت هوشمند درمان',
                'description' => 'سامانه‌ای جامع برای مدیریت فرآیندهای درمانی از نوبت‌دهی تا نسخه‌نویسی',
                'keywords' => 'درباره ما, سامانه سلامت, مدیریت درمان',
            ],
            'contact' => [
                'title' => 'تماس با ما | سامانه سلامت',
                'description' => 'ارتباط با تیم پشتیبانی سامانه سلامت',
                'keywords' => 'تماس, پشتیبانی, ارتباط با ما',
            ],
        ];

        $data = $seoData[$page] ?? $seoData['home'];

        return $this->success([
            'meta' => $data,
            'page' => $page,
        ]);
    }
}
