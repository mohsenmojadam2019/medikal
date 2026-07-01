<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * ترجمه‌های این زبان
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * Fallback های این زبان
     */
    public function fallbacks(): HasMany
    {
        return $this->hasMany(TranslationFallback::class, 'language_id');
    }

    /**
     * زبان‌هایی که این زبان برای آنها fallback است
     */
    public function fallbackFor(): HasMany
    {
        return $this->hasMany(TranslationFallback::class, 'fallback_language_id');
    }

    /**
     * دریافت زبان پیش‌فرض
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * دریافت زبان توسط کد
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->where('is_active', true)->first();
    }

    /**
     * دریافت تمام زبان‌های فعال
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)->orderBy('sort_order')->get();
    }
}
