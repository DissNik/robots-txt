<?php

return [
    'cache' => [
        'enabled' => env('ROBOTS_TXT_CACHE', true),
        'duration' => env('ROBOTS_TXT_CACHE_DURATION', 3600),
    ],

    'route' => [
        'enabled' => true,
        'middleware' => ['robots.txt.cache'],
    ],

    'default' => [
        'user_agent' => '*',
        'disallow' => [
            '/admin',
            '/private',
        ],
        'allow' => [
            '/',
        ],
    ],
];
