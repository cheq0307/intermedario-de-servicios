<?php

return [
    'disk' => 'local',
    'page_size' => 30,
    'max_images' => 3,
    'max_image_kb' => 5120,
    'reverb' => [
        'host' => env('REVERB_PUBLIC_HOST', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        'port' => env('REVERB_PUBLIC_PORT', 443),
        'scheme' => env('REVERB_PUBLIC_SCHEME', 'https'),
    ],
];
