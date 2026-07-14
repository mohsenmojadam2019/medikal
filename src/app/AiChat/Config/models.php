<?php

return [
    'models' => [
        'qwen3:14b' => [
            'name' => 'Qwen3 14B',
            'max_tokens' => 8192,
            'context_length' => 128000,
            'recommended_temperature' => 0.7,
        ],
        'qwen3:8b' => [
            'name' => 'Qwen3 8B',
            'max_tokens' => 4096,
            'context_length' => 64000,
            'recommended_temperature' => 0.7,
        ],
        'llama3.1' => [
            'name' => 'Llama 3.1',
            'max_tokens' => 4096,
            'context_length' => 128000,
            'recommended_temperature' => 0.7,
        ],
        'phi4:mini' => [
            'name' => 'Phi 4 Mini',
            'max_tokens' => 2048,
            'context_length' => 32000,
            'recommended_temperature' => 0.7,
        ],
    ],
];
