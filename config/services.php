<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fedapay' => [
        'api_key' => env('FEDEPAY_API_KEY'),
        'base_url' => env('FEDEPAY_BASE_URL', 'https://api.fedapay.com/v1'),
        'sandbox_base_url' => env('FEDEPAY_SANDBOX_BASE_URL', 'https://sandbox-api.fedapay.com/v1'),
        'callback_url' => env('PAYMENT_CALLBACK_URL'),
    ],

];
