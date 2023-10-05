<?php
declare(strict_types=1);

use Illuminate\Support\Str;

return [
    'opensearch' => env('ELASTIC_OPENSEARCH', false),
    'ssl' => [
        'verify' => env('HTTP_CLIENT_VERIFY'),
        'cert' => env('ELASTIC_CERT'),
    ],
    'aws' => [
        'aoss' => env('AWS_AOSS', false),
        'region' => env('AWS_REGION'),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'token' => env('AWS_SESSION_TOKEN')
    ],
    'elasticsearch' => [
        'host' => env('ELASTIC_HOST'),
        'user' => env('ELASTIC_USER'),
        'password' => env('ELASTIC_PASSWORD'),
        'prefix' => Str::slug(env('ELASTIC_PREFIX', '')),
        'lifecycle' => env('ELASTIC_LIFECYCLE', '180-days-default',),
    ],

];
