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

    'gigachat' => [
        'auth_key'   => env('GIGACHAT_AUTH_KEY'),
        'scope'      => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
        'model'      => env('GIGACHAT_MODEL', 'GigaChat'),
        'verify_ssl' => env('GIGACHAT_VERIFY_SSL', false),
    ],

    'contact' => [
        'rate_limit'   => env('CONTACT_RATE_LIMIT', 5),
        'rate_minutes' => env('CONTACT_RATE_LIMIT_MINUTES', 1),
    ],

];
