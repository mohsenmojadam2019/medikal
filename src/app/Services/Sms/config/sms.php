<?php

return [
    'default' => env('SMS_DEFAULT_GATEWAY', 'fake'),

    'gateways' => [
        'kavenegar' => [
            'api_key' => env('KAVENEGAR_API_KEY', ''),
            'sender' => env('KAVENEGAR_SENDER', '10004346'),
        ],
        'melipayamak' => [
            'api_key' => env('MELI_PAYAMAK_API_KEY', ''),
            'base_url' => env('MELI_PAYAMAK_BASE_URL', 'https://console.melipayamak.com'),
            'sender' => env('MELI_PAYAMAK_SENDER', '50004001231003'),
        ],
        'fake' => [
            'enabled' => env('SMS_FAKE_ENABLED', true),
        ],
    ],

    'patterns' => [
        'otp_login' => env('SMS_OTP_LOGIN_PATTERN', 'otp-login'),
        'otp_register' => env('SMS_OTP_REGISTER_PATTERN', 'otp-register'),
        'otp_password_reset' => env('SMS_OTP_PASSWORD_RESET_PATTERN', 'otp-reset'),
        'order_status' => env('SMS_ORDER_STATUS_PATTERN', 'order-status'),
    ],
];
