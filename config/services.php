<?php

return [
 

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
    
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'version'=> env('WHATSAPP_VERSION'),
        'phone_id'=> env('WHATSAPP_PHONE_ID'),
        'myToken'=>env('MY_TOKEN'),
    ],

];
