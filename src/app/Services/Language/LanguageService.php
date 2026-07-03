<?php

namespace App\Services\Language;

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationFallback;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LanguageService
{
    protected int $cacheTtl = 3600;

    public function getActiveLanguages(): Collection
    {
        return Cache::remember('languages.active', $this->cacheTtl, function () {
            return Language::where('is_active', true)->orderBy('sort_order')->get();
        });
    }

    public function getDefaultLanguage(): ?Language
    {
        return Cache::remember('languages.default', $this->cacheTtl, function () {
            return Language::where('is_default', true)->where('is_active', true)->first();
        });
    }

    public function getLanguageByCode(string $code): ?Language
    {
        return Cache::remember("language.{$code}", $this->cacheTtl, function () use ($code) {
            return Language::where('code', $code)->where('is_active', true)->first();
        });
    }

    public function createLanguage(array $data): Language
    {
        $language = Language::create($data);
        $this->clearCache();
        return $language;
    }

    public function updateLanguage(Language $language, array $data): Language
    {
        $language->update($data);
        $this->clearCache();
        return $language;
    }

    public function deleteLanguage(Language $language): bool
    {
        $result = $language->delete();
        $this->clearCache();
        return $result;
    }

    public function toggleLanguage(Language $language): Language
    {
        $language->update(['is_active' => !$language->is_active]);
        $this->clearCache();
        return $language;
    }

    public function setDefaultLanguage(Language $language): void
    {
        Language::where('is_default', true)->update(['is_default' => false]);
        $language->update(['is_default' => true]);
        $this->clearCache();
    }

    public function getTranslation(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = "translation.{$locale}.{$key}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key, $locale) {
            return Translation::get($key, $locale);
        });
    }

    public function getFrontendTranslations(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = "translations.frontend.{$locale}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($locale) {
            return Translation::getTranslationsForFrontend($locale);
        });
    }

    public function setTranslation(string $group, string $key, string $value, ?string $locale = null): Translation
    {
        $locale = $locale ?? app()->getLocale();
        $language = $this->getLanguageByCode($locale);

        if (!$language) {
            throw new \Exception("Language '{$locale}' not found");
        }

        $translation = Translation::updateOrCreate(
            [
                'language_id' => $language->id,
                'group_name' => $group,
                'key_name' => $key,
            ],
            ['value' => $value]
        );

        $this->clearTranslationCache($locale, $group, $key);
        return $translation;
    }

    public function deleteTranslation(int $id): bool
    {
        $translation = Translation::find($id);
        if (!$translation) {
            return false;
        }

        $result = $translation->delete();
        $this->clearTranslationCache();
        return $result;
    }

    public function setFallback(Language $language, Language $fallbackLanguage): TranslationFallback
    {
        TranslationFallback::where('language_id', $language->id)->delete();

        return TranslationFallback::create([
            'language_id' => $language->id,
            'fallback_language_id' => $fallbackLanguage->id,
        ]);
    }

    public function importFromFiles(string $locale): int
    {
        $language = $this->getLanguageByCode($locale);
        if (!$language) {
            throw new \Exception("Language '{$locale}' not found");
        }

        $langPath = resource_path("lang/{$locale}");
        if (!is_dir($langPath)) {
            throw new \Exception("Language directory not found: {$langPath}");
        }

        $count = 0;
        foreach (glob("{$langPath}/*.php") as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $translations = require $file;

            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $rule => $text) {
                        Translation::updateOrCreate(
                            [
                                'language_id' => $language->id,
                                'group_name' => $group,
                                'key_name' => $key,
                                'plural_rule' => $rule,
                            ],
                            [
                                'value' => $text,
                                'is_plural' => true,
                            ]
                        );
                    }
                } else {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $language->id,
                            'group_name' => $group,
                            'key_name' => $key,
                        ],
                        [
                            'value' => $value,
                            'is_plural' => false,
                        ]
                    );
                }
                $count++;
            }
        }

        $this->clearTranslationCache();
        return $count;
    }

    public function exportToFiles(string $locale): int
    {
        $language = $this->getLanguageByCode($locale);
        if (!$language) {
            throw new \Exception("Language '{$locale}' not found");
        }

        $translations = Translation::where('language_id', $language->id)->get();
        $groups = $translations->groupBy('group_name');

        $langPath = resource_path("lang/{$locale}");
        if (!is_dir($langPath)) {
            mkdir($langPath, 0755, true);
        }

        $count = 0;
        foreach ($groups as $group => $items) {
            $data = [];
            foreach ($items as $item) {
                if ($item->is_plural) {
                    if (!isset($data[$item->key_name])) {
                        $data[$item->key_name] = [];
                    }
                    $data[$item->key_name][$item->plural_rule] = $item->value;
                } else {
                    $data[$item->key_name] = $item->value;
                }
            }

            $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";
            file_put_contents("{$langPath}/{$group}.php", $content);
            $count += count($data);
        }

        return $count;
    }

    private function clearCache(): void
    {
        Cache::forget('languages.active');
        Cache::forget('languages.default');
        Cache::forget('translations.frontend.*');
    }

    private function clearTranslationCache(?string $locale = null, ?string $group = null, ?string $key = null): void
    {
        if ($locale && $group && $key) {
            Cache::forget("translation.{$locale}.{$group}.{$key}");
        } elseif ($locale) {
            Cache::forget("translations.frontend.{$locale}");
        } else {
            $this->clearCache();
        }
    }
}
