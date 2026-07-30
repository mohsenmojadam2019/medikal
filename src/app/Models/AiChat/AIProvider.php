<?php

namespace App\Models\AiChat;

use Illuminate\Database\Eloquent\Model;

class AIProvider extends Model
{
    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'slug',
        'driver',
        'base_url',
        'api_key',
        'organization',
        'project',
        'default_model',
        'models',
        'options',
        'is_active',
        'is_default',
        'last_tested_at',
        'last_test_success',
        'last_test_message',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $appends = [
        'has_api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'models' => 'array',
            'options' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_test_success' => 'boolean',
        ];
    }

    public function getHasApiKeyAttribute(): bool
    {
        return filled($this->api_key);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
