<?php

return [
    'cache' => [
        // Enable or disable caching for robots.txt responses
        'enabled' => env('ROBOTS_TXT_CACHE', true),

        // Cache duration in seconds (default: 1 hour)
        'duration' => env('ROBOTS_TXT_CACHE_DURATION', 3600),
    ],

    'route' => [
        // Automatically register a /robots.txt route
        'enabled' => true,

        // Middleware to apply to the robots.txt route
        'middleware' => ['robots.txt.cache'],
    ],

    // Default environment to use when current environment is not found in environments array
    'default_environment' => 'local',

    // Environment-specific robots.txt configurations
    'environments' => [
        'production' => [
            // Global directives for production environment
            'sitemap' => rtrim(env('APP_URL', 'http://localhost'), '/') . '/sitemap.xml',

            // User-agent specific rules for production
            'user_agents' => [
                // Rules for all user agents (wildcard)
                '*' => [
                    // Paths to disallow access to
                    'disallow' => [
                        '/admin',
                        '/private',
                    ],

                    // Paths to allow access to (takes precedence over disallow for same paths)
                    'allow' => [
                        '/',
                    ],

                    // Delay between requests in seconds
                    'crawl-delay' => 1.0,
                ],

                // Rules specific to Googlebot
                'Googlebot' => [
                    'disallow' => ['/private'],
                    'crawl-delay' => 1.0,
                ],
            ],
        ],

        // Local development environment configuration
        'local' => [
            'user_agents' => [
                // Block all access in local environment for safety
                '*' => [
                    'disallow' => ['/'],
                ],
            ],
        ],
    ],
];
