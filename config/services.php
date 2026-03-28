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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mimsms' => [
        'enabled' => env('MIM_SMS_ENABLED', false),
        'url' => env('MIM_SMS_API_URL', env('MIM_SMS_ENDPOINT', 'https://api.mimsms.com/api/SmsSending/SMS')),
        'user' => env('MIM_SMS_USER', env('MIM_SMS_USERNAME')),
        'api_key' => env('MIM_SMS_API_KEY'),
        'sender' => env('MIM_SMS_SENDER_NAME', env('MIM_SMS_SENDER_ID')),
        'transaction_type' => env('MIM_SMS_TRANSACTION_TYPE', 'T'),
        'campaign_id' => env('MIM_SMS_CAMPAIGN_ID', 'null'),
    ],

    'mim_sms' => [
        'enabled' => env('MIM_SMS_ENABLED', false),
        'endpoint' => env('MIM_SMS_ENDPOINT', env('MIM_SMS_API_URL', 'https://api.mimsms.com/api/SmsSending/SMS')),
        'username' => env('MIM_SMS_USERNAME', env('MIM_SMS_USER')),
        'api_key' => env('MIM_SMS_API_KEY'),
        'sender_name' => env('MIM_SMS_SENDER_NAME', env('MIM_SMS_SENDER_ID')),
        'transaction_type' => env('MIM_SMS_TRANSACTION_TYPE', 'T'),
        'campaign_id' => env('MIM_SMS_CAMPAIGN_ID', 'null'),
    ],

];
