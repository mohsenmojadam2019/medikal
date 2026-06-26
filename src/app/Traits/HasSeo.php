<?php

namespace App\Traits;

use App\Models\Seo;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    public function getSeoTitleAttribute()
    {
        return $this->seo?->title ?? $this->name ?? $this->title ?? 'سامانه سلامت';
    }

    public function getSeoDescriptionAttribute()
    {
        return $this->seo?->description ?? 'سامانه جامع مدیریت سلامت با امکان نوبت‌دهی، نسخه‌نویسی و داروخانه آنلاین';
    }

    public function getSeoKeywordsAttribute()
    {
        return $this->seo?->keywords ?? 'پزشک, نوبت, دارو, سلامت, درمان';
    }

    public function getSeoJsonAttribute()
    {
        return $this->seo?->schema_json ?? $this->generateDefaultSchema();
    }

    protected function generateDefaultSchema()
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'MedicalWebPage',
            'name' => $this->getSeoTitleAttribute(),
            'description' => $this->getSeoDescriptionAttribute(),
            'url' => url()->current(),
        ];
    }
}
