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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pesapal' => [
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'env' => env('PESAPAL_ENV', 'sandbox'),
        'ipn_id' => env('PESAPAL_IPN_ID'),
    ],

    'sms_gateway' => [
        'api_key' => env('SMS_GATEWAY_API_KEY'),
    ],

    'sms_gate' => [
        'server_url' => env('SMS_GATE_SERVER_URL', 'https://api.sms-gate.app'),
        'username' => env('SMS_GATE_USERNAME'),
        'password' => env('SMS_GATE_PASSWORD'),
        'device_id' => env('SMS_GATE_DEVICE_ID'),
        'sim_number' => env('SMS_GATE_SIM_NUMBER', 1),
    ],

    'payment_gateway' => env('PAYMENT_GATEWAY', 'harakapay'),

    'harakapay' => [
        'api_key' => env('HARAKAPAY_API_KEY'),
        'base_url' => env('HARAKAPAY_BASE_URL', 'https://harakapay.net'),
    ],

];
