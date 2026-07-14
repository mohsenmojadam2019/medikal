<?php

return [
    /*
    |--------------------------------------------------------------------------
    | تنظیمات مدل‌های هوش مصنوعی
    |--------------------------------------------------------------------------
    */
    'models' => [
        'qwen3:14b' => [
            'name' => 'Qwen3 14B',
            'description' => 'مدل قدرتمند برای پاسخ‌دهی دقیق و حرفه‌ای',
            'max_tokens' => 8192,
            'context_length' => 128000,
            'recommended_temperature' => 0.7,
            'capabilities' => ['medical', 'reasoning', 'code', 'multi_lang'],
            'is_active' => true,
        ],
        
        'qwen3:8b' => [
            'name' => 'Qwen3 8B',
            'description' => 'نسخه سبک‌تر با سرعت بیشتر',
            'max_tokens' => 4096,
            'context_length' => 64000,
            'recommended_temperature' => 0.7,
            'capabilities' => ['medical', 'reasoning', 'multi_lang'],
            'is_active' => true,
        ],
        
        'llama3.1' => [
            'name' => 'Llama 3.1',
            'description' => 'مدل عمومی متا با عملکرد عالی',
            'max_tokens' => 4096,
            'context_length' => 128000,
            'recommended_temperature' => 0.7,
            'capabilities' => ['general', 'reasoning', 'code', 'multi_lang'],
            'is_active' => true,
        ],
        
        'gemma3:12b' => [
            'name' => 'Gemma 3 12B',
            'description' => 'مدل گوگل با دقت بالا',
            'max_tokens' => 4096,
            'context_length' => 64000,
            'recommended_temperature' => 0.7,
            'capabilities' => ['general', 'reasoning', 'multi_lang'],
            'is_active' => true,
        ],
        
        'phi4:mini' => [
            'name' => 'Phi 4 Mini',
            'description' => 'مدل مایکروسافت برای سیستم‌های ضعیف',
            'max_tokens' => 2048,
            'context_length' => 32000,
            'recommended_temperature' => 0.7,
            'capabilities' => ['general', 'reasoning'],
            'is_active' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | تنظیمات انتخاب خودکار مدل
    |--------------------------------------------------------------------------
    */
    'auto_select' => [
        'enabled' => env('MODEL_AUTO_SELECT_ENABLED', true),
        'criteria' => [
            'emergency' => 'qwen3:14b',
            'complex_medical' => 'qwen3:14b',
            'simple_medical' => 'qwen3:8b',
            'general' => 'llama3.1',
            'simple_query' => 'phi4:mini',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | تنظیمات کش مدل
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('MODEL_CACHE_ENABLED', true),
        'ttl' => env('MODEL_CACHE_TTL', 3600), // 1 ساعت
        'max_size' => env('MODEL_CACHE_MAX_SIZE', 1000),
    ],
];
