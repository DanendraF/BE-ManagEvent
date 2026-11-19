<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

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

    'whatsapp' => [
        'endpoint' => env('WHATSAPP_API_ENDPOINT', 'https://api.fonnte.com/send'),
        'token' => env('WHATSAPP_API_TOKEN', ''),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 10),
    ],

    'instagram' => [
        'account_id' => env('INSTAGRAM_ACCOUNT_ID', ''),
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN', ''),
        'graph_version' => env('INSTAGRAM_GRAPH_VERSION', 'v19.0'),
        'base_url' => env('INSTAGRAM_BASE_URL', 'https://graph.facebook.com'),
        'timeout' => (int) env('INSTAGRAM_TIMEOUT', 15),
    ],

];
