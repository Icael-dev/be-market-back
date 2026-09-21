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

    'ligdicash' => [
        'api_key' => env('LIGDICASH_API_KEY'),
        'api_token' => env('LIGDICASH_API_TOKEN'),
        'base_url' => env('LIGDICASH_BASE_URL', 'https://app.ligdicash.com/pay/v01'),
        'callback_url' => env('PAYMENT_CALLBACK_URL'),
    ],

];
