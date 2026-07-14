<?php

return [
    'session' => [
        'lifetime' => env('CHAT_SESSION_LIFETIME', 1440),
        'auto_cleanup' => env('CHAT_AUTO_CLEANUP', true),
        'cleanup_days' => env('CHAT_CLEANUP_DAYS', 1),
    ],

    'models' => [
        'default' => env('CHAT_DEFAULT_MODEL', 'qwen3:14b'),
        'available' => ['qwen3:14b', 'qwen3:8b', 'llama3.1', 'gemma3:12b', 'phi4:mini'],
    ],

    'filter' => [
        'enabled' => env('CHAT_FILTER_ENABLED', true),
        'strict' => env('CHAT_FILTER_STRICT', true),
    ],

    'emergency' => [
        'enabled' => env('EMERGENCY_ENABLED', true),
        'phone' => env('EMERGENCY_PHONE', '115'),
    ],

    'file' => [
        'max_size' => env('CHAT_FILE_MAX_SIZE', 5120),
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'disk' => 'public',
        'path' => 'chat-files',
    ],

    'rate_limit' => [
        'per_minute' => env('CHAT_RATE_LIMIT_PER_MINUTE', 10),
        'per_hour' => env('CHAT_RATE_LIMIT_PER_HOUR', 100),
        'per_day' => env('CHAT_RATE_LIMIT_PER_DAY', 500),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://host.docker.internal:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:14b'),
        'timeout' => env('OLLAMA_TIMEOUT', 60),
        'max_retries' => env('OLLAMA_MAX_RETRIES', 3),
        'options' => [
            'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
            'top_p' => env('OLLAMA_TOP_P', 0.9),
            'max_tokens' => env('OLLAMA_MAX_TOKENS', 500),
        ],
    ],
];
