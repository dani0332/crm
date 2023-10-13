<?php

return [

    'default' => env('DYNAMODB_CONNECTION', 'aws'),

    'connections' => [
        'aws' => [
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'token' => env('AWS_SESSION_TOKEN'),
            ],
            'region' => env('AWS_REGION'),
            'debug' => env('DYNAMODB_DEBUG'),
        ],
    ],

];
