<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationFallback extends Model
{
    protected $fillable = [
        'language_id',
        'fallback_language_id',
    ];

    /**
     * زبان مبدأ
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    /**
     * زبان fallback
     */
    public function fallbackLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'fallback_language_id');
    }
}
