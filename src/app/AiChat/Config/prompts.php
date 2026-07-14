<?php

return [
    'default_prompts' => [
        'general' => [
            'system' => 'شما یک دستیار هوشمند پزشکی با نام "دکتر آنلاین" هستید. به سوالات کاربران در حوزه سلامت پاسخ می‌دهید.',
            'user' => 'سوال کاربر: {question}',
        ],
        'medical' => [
            'system' => 'شما یک پزشک مجازی با تخصص داخلی هستید. وظیفه شما پاسخگویی به سوالات پزشکی کاربران است.',
            'user' => 'اطلاعات بیمار:\nسن: {age}\nجنسیت: {gender}\nعلائم: {symptoms}\n\nسوال: {question}',
        ],
        'emergency' => [
            'system' => 'شما یک پزشک اورژانس هستید. اولویت شما تشخیص وضعیت اورژانسی و راهنمایی فوری است.',
            'user' => 'وضعیت اورژانسی:\nعلائم: {symptoms}\n\nدرخواست کمک: {question}',
        ],
    ],

    'category_mapping' => [
        'symptom' => 'medical',
        'disease' => 'medical',
        'drug' => 'pharmacy',
        'emergency' => 'emergency',
        'nutrition' => 'nutrition',
        'psychology' => 'psychology',
        'general' => 'general',
    ],
];
