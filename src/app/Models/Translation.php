<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = [
        'language_id',
        'group_name',
        'key_name',
        'value',
        'is_plural',
        'plural_rule',
    ];

    protected $casts = [
        'is_plural' => 'boolean',
    ];

    /**
     * زبان این ترجمه
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * دریافت ترجمه با fallback
     */
    public static function get(string $key, ?string $locale = null, array $replace = []): string
    {
        $locale = $locale ?? app()->getLocale();
        
        $language = Language::findByCode($locale);
        if (!$language) {
            $language = Language::getDefault();
            if (!$language) {
                return $key;
            }
        }

        // تجزیه key: group.key
        $parts = explode('.', $key, 2);
        $group = $parts[0] ?? 'messages';
        $keyName = $parts[1] ?? $key;

        // جستجوی ترجمه
        $translation = self::where('language_id', $language->id)
            ->where('group_name', $group)
            ->where('key_name', $keyName)
            ->first();

        if (!$translation) {
            // جستجوی fallback
            $fallback = $language->fallbacks()->first();
            if ($fallback) {
                $translation = self::where('language_id', $fallback->fallback_language_id)
                    ->where('group_name', $group)
                    ->where('key_name', $keyName)
                    ->first();
            }
        }

        $value = $translation ? $translation->value : $key;

        // جایگزینی مقادیر
        foreach ($replace as $search => $replaceValue) {
            $value = str_replace(':' . $search, $replaceValue, $value);
        }

        return $value;
    }

    /**
     * دریافت ترجمه به صورت JSON برای فرانت‌اند
     */
    public static function getTranslationsForFrontend(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $language = Language::findByCode($locale);
        
        if (!$language) {
            return [];
        }

        $translations = self::where('language_id', $language->id)->get();
        $result = [];

        foreach ($translations as $translation) {
            if (!isset($result[$translation->group_name])) {
                $result[$translation->group_name] = [];
            }
            $result[$translation->group_name][$translation->key_name] = $translation->value;
        }

        return $result;
    }
}
