# Robots.txt manager for Laravel

A powerful, flexible robots.txt management package for Laravel applications with environment-aware rules, caching, and fluent API.

---

## Features

- **Fluent API** - Easy-to-use chainable methods with context-aware syntax
- **Environment-based rules** - Different rules for local, staging, production
- **Smart caching** - Configurable HTTP caching for performance
- **Conflict resolution** - Automatic Allow/Disallow priority handling
- **Laravel integration** - Service provider, facades, and middleware
- **Extensible** - Custom rules and programmatic control

## Installation

```bash
composer require dissnik/robots-txt
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="DissNik\RobotsTxt\RobotsTxtServiceProvider" --tag="robots-txt-config"
```

## Configuration File (config/robots-txt.php)
```php
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
```

## Conflict Check

> [!WARNING]
> **File takes priority!** If a `public/robots.txt` file exists on your server,
> it will **OVERRIDE** the package's generated content.

```bash
php artisan robots-txt:check
```

This command will:

- Detect if a robots.txt file exists in public directory
- Show file details and potential conflicts
- Help you resolve conflicts

## Quick Start

### Basic Usage (Configuration Only)
For most use cases, you only need to configure the package. The robots.txt file will be automatically generated and served at /robots.txt.

1. Publish the configuration (if you want to customize):
```bash
php artisan vendor:publish --provider="DissNik\RobotsTxt\RobotsTxtServiceProvider" --tag="robots-txt-config"
```
2. Edit the configuration file (config/robots-txt.php) with your rules.
3. Access your robots.txt at https://your-domain.com/robots.txt

That's it! The package handles route registration, content generation, and caching automatically.

### Programmatic Usage

If you need dynamic rules or programmatic control, you can use the fluent API. Important: When using programmatic rules, you should disable the package's automatic route registration to avoid conflicts.

Step 1: Disable Automatic Route via Environment Variable
Add to your .env file:

```env
ROBOTS_TXT_ROUTE_ENABLED=false
```

Or modify directly in config/robots-txt.php:

```php
'route' => [
    'enabled' => false, // Disable automatic route when using programmatic API
    'middleware' => ['robots.txt.cache'],
],
```

Step 2: Define Your Custom Route
Create your own route in `routes/web.php`:

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

// Define your custom robots.txt route
Route::get('robots.txt', function () {
    // Generate robots.txt content programmatically
    $content = RobotsTxt::generate();

    return response($content, 200, [
        'Content-Type' => 'text/plain'
    ]);
})->name('robots-txt');
```

The package includes a caching middleware that's automatically applied to the robots.txt route:

```
Middleware alias: 'robots.txt.cache'
Automatically adds cache headers based on configuration
```

### Fluent API Examples

> [!NOTE]
> The examples below show advanced programmatic usage. For basic setup, you only need configuration.

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

// Basic rules - NOTE: Must use callbacks for user-agent specific directives
RobotsTxt::forUserAgent('*', function ($context) {
    $context->disallow('/admin')
            ->allow('/public')
            ->crawlDelay(1.0);
})->sitemap('https://example.com/sitemap.xml');

// Multiple user agents
RobotsTxt::forUserAgent('Googlebot', function ($context) {
    $context->disallow('/private')
            ->crawlDelay(2.0);
});

RobotsTxt::forUserAgent('Bingbot', function ($context) {
    $context->disallow('/secret');
});
```

### Environment-Specific Rules

```php
// Block all in local development
RobotsTxt::forEnvironment('local', function ($robots) {
    $robots->blockAll();
});

// Production rules
RobotsTxt::forEnvironment('production', function ($robots) {
    $robots->sitemap('https://example.com/sitemap.xml')
           ->forUserAgent('*', function ($context) {
               $context->allow('/')
                       ->disallow('/admin');
           });
});

// Multiple environments
RobotsTxt::forEnvironment(['staging', 'production'], function ($robots) {
    $robots->forUserAgent('*', function ($context) {
        $context->disallow('/debug');
    });
});
```

### Conditional Rules

```php
// Using when() and unless() methods
RobotsTxt::when($isMaintenanceMode, function ($robots) {
    $robots->blockAll();
})->unless($isMaintenanceMode, function ($robots) {
    $robots->forUserAgent('*', function ($context) {
        $context->allow('/');
    });
});
```

### Helper Methods

```php
// Block all crawlers
RobotsTxt::blockAll();

// Allow all crawlers
RobotsTxt::allowAll();

// Clear all rules and reload from config
RobotsTxt::reset();

// Clear only programmatic rules
RobotsTxt::clear();

// Clear cache
RobotsTxt::clearCache();
```

## Advanced Usage

> [!TIP]
> Most users only need configuration. The following sections are for advanced programmatic control.

### Programmatic Rule Management
```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

// Get all rules as array
$rules = RobotsTxt::getRules();

// Get all sitemaps
$sitemaps = RobotsTxt::getSitemaps();

// Get all global directives
$directives = RobotsTxt::getDirectives();

// Get directives for specific user agent
$googlebotRules = RobotsTxt::getUserAgentDirectives('Googlebot');

// Check for conflicts (returns array of conflicts)
$conflicts = RobotsTxt::checkConflicts();

// Debug environment rules
$envRules = RobotsTxt::getEnvironmentRules();

// Get all defined user agents
$agents = RobotsTxt::getUserAgents();

// Check if user agent exists
if (RobotsTxt::hasUserAgent('Googlebot')) {
    // ...
}
```

### Rule Conflicts Resolution

The package automatically resolves conflicts where both Allow and Disallow rules exist for the same path (Allow has priority).

```php
// This will generate only "Allow: /admin" (Allow wins)
RobotsTxt::forUserAgent('*', function ($context) {
    $context->disallow('/admin')
            ->allow('/admin');
});
```

### Cache Management

```php
// Disable caching for current request
config(['robots-txt.cache.enabled' => false]);

// Or use environment variable
putenv('ROBOTS_TXT_CACHE=false');

// Clear cached robots.txt
RobotsTxt::clearCache();

// Custom cache duration (in seconds)
config(['robots-txt.cache.duration' => 7200]); // 2 hours

// Or use environment variable
putenv('ROBOTS_TXT_CACHE_DURATION=7200');

// Disable middleware caching in routes
Route::get('robots.txt', function () {
    return response(RobotsTxt::generate(), 200, [
        'Content-Type' => 'text/plain'
    ]);
})->withoutMiddleware('robots.txt.cache');
```

### Directive Removal

```php
// Remove global directive
RobotsTxt::removeDirective('sitemap', 'https://example.com/old-sitemap.xml');

// Remove user agent directive
RobotsTxt::removeUserAgentDirective('*', 'disallow', '/admin');

// Remove all sitemaps
RobotsTxt::removeDirective('sitemap');
```

## Examples

### Complete Production Setup

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

RobotsTxt::reset()
    ->forEnvironment('production', function ($robots) {
        $robots->sitemap('https://example.com/sitemap.xml')
               ->sitemap('https://example.com/sitemap-images.xml')
               ->host('www.example.com')
               ->forUserAgent('*', function ($context) {
                   $context->allow('/')
                           ->disallow('/admin')
                           ->disallow('/private')
                           ->disallow('/tmp')
                           ->crawlDelay(1.0);
               })
               ->forUserAgent('Googlebot-Image', function ($context) {
                   $context->allow('/images')
                           ->crawlDelay(2.0);
               });
    })
    ->forEnvironment('local', function ($robots) {
        $robots->blockAll();
    });
```

### E-commerce Site Example

```php
RobotsTxt::forUserAgent('*', function ($context) {
    $context->allow('/')
            ->allow('/products')
            ->allow('/categories')
            ->disallow('/checkout')
            ->disallow('/cart')
            ->disallow('/user')
            ->disallow('/api')
            ->crawlDelay(0.5);
})
->sitemap('https://store.com/sitemap-products.xml')
->sitemap('https://store.com/sitemap-categories.xml')
->cleanParam('sessionid', '/*')
->cleanParam('affiliate', '/products/*');
```

### Dynamic Rules Based on Conditions

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;
use Illuminate\Support\Facades\Auth;

// Different rules for authenticated users
RobotsTxt::when(Auth::check(), function ($robots) {
    $robots->forUserAgent('*', function ($context) {
        $context->disallow('/login')
                ->disallow('/register');
    });
})->unless(Auth::check(), function ($robots) {
    $robots->forUserAgent('*', function ($context) {
        $context->allow('/login')
                ->allow('/register');
    });
});

// Time-based rules
RobotsTxt::when(now()->hour >= 22 || now()->hour < 6, function ($robots) {
    $robots->forUserAgent('*', function ($context) {
        $context->crawlDelay(5.0); // Slower crawling at night
    });
});
```

## API Reference

### Main Methods

| Method                                                            | Description                                     | Returns  |
|:------------------------------------------------------------------|:------------------------------------------------|:--------:|
| `forUserAgent(string $userAgent, callable $callback)`             | Set user agent for subsequent rules             |  `self`  |
| `forEnvironment(string\|array $environments, callable $callback)` | Define environment-specific rules               |  `self`  |
| `directive(string $directive, mixed $value)`                      | Add global custom directive                     |  `self`  |
| `sitemap(string $url)`                                            | Add sitemap directive                           |  `self`  |
| `host(string $host)`                                              | Add host directive                              |  `self`  |
| `cleanParam(string $param, ?string $path = null)`                 | cleanParam(string $param, ?string $path = null) |  `self`  |
| `blockAll()`                                                      | Disallow all crawling for all user agents       |  `self`  |
| `allowAll()`                                                      | Allow all crawling for all user agents          |  `self`  |
| `clear()`                                                         | Clear all programmatic rules                    |  `self`  |
| `reset()`                                                         | Clear rules and reload from configuration       |  `self`  |
| `generate()`                                                      | Generate robots.txt content                     | `string` |
| `clearCache()`                                                    | Clear cached robots.txt content                 |  `bool`  |

### Information Methods

| Method                                      | Description                               | Returns |
|:--------------------------------------------|:------------------------------------------|:-------:|
| `getRules()`                                | Get all defined rules as array            | `array` |
| `getSitemaps()`                             | Get all sitemap URLs                      | `array` |
| `getDirectives()`                           | Get all global directives                 | `array` |
| `getUserAgentDirectives(string $userAgent)` | Get directives for specific user agent    | `array` |
| `getEnvironmentRules()`                     | Get registered environment callbacks      | `array` |
| `checkConflicts()`                          | Check for rule conflicts (allow/disallow) | `array` |
| `getUserAgents()`                           | Get all defined user agents               | `array` |
| `hasUserAgent(string $userAgent)`           | Check if user agent is defined            | `bool`  |

### Modification Methods

| Method                                                                                | Description                 | Returns  |
|:--------------------------------------------------------------------------------------|:----------------------------|:--------:|
| `removeDirective(string $directive, mixed $value = null)`                             | Remove global directive     |  `self`  |
| `removeUserAgentDirective(string $userAgent, string $directive, mixed $value = null)` | Remove user agent directive |  `self`  |

### Context Methods (available inside callbacks)

| Method                                                    | Description          |
|:----------------------------------------------------------|:---------------------|
| `allow(string $path)`                                     | Add allow rule       |
| `disallow(string $path)`                                  | Add disallow rule    |
| `crawlDelay(float $delay)`                                | Set crawl delay      |
| `cleanParam(string $param, ?string $path = null)`         | Add clean-param      |
| `directive(string $directive, mixed $value)`              | Add custom directive |
| `blockAll()`                                              | Disallow all paths   |
| `allowAll()`                                              | Allow all paths      |
| `removeDirective(string $directive, mixed $value = null)` | Remove directive     |

### EnvironmentContext ($robots in forEnvironment() callbacks):

| Method                                                | Description                 |
|:------------------------------------------------------|:----------------------------|
| `forUserAgent(string $userAgent, callable $callback)` | Define user agent rules     |
| `sitemap(string $url)`                                | Add global sitemap          |
| `host(string $host)`                                  | Add global host             |
| `cleanParam(string $param, ?string $path = null)`     | Add global clean-param      |
| `directive(string $directive, mixed $value)`          | Add global custom directive |
| `blockAll()`                                          | Block all crawlers          |
| `allowAll()`                                          | Allow all crawlers          |

### Conditional Methods (via Conditionable trait)

| Method                                        | Description                   |
|:----------------------------------------------|:------------------------------|
| `when(bool $condition, callable $callback)`   | Execute if condition is true  |
| `unless(bool $condition, callable $callback)` | Execute if condition is false |

## Troubleshooting

### Common Issues
1. Rules not applying?
   - Make sure you're calling methods in the correct context
   - User-agent specific methods (allow(), disallow(), crawlDelay()) must be inside forUserAgent() callbacks
   - Check your current environment: dd(app()->environment())
   - **If your rules are not showing up in `/robots.txt`:**
     - Check if a `public/robots.txt` file exists (it overrides package rules)
     - Run the conflict check: `php artisan robots-txt:check`
     - The package-generated robots.txt will NOT work if a file exists in `public/robots.txt`

2. Caching issues?
   - Run RobotsTxt::clearCache() to clear cached content
   - Check config: config('robots-txt.cache.enabled')
   - Disable middleware caching in route if needed

3. Route not working?
   - Check if route is enabled: config('robots-txt.route.enabled') or env('ROBOTS_TXT_ROUTE_ENABLED')
   - Run php artisan route:list to see if route is registered
   - Make sure no physical public/robots.txt file exists

4. Configuration not loading?
   - Make sure you published config: `php artisan vendor:publish --tag=robots-txt-config`
   - Check config structure matches expected format
   - Verify environment is set correctly

### Debug Mode

```php
// Check generated content
$content = RobotsTxt::generate();
echo $content;

// Debug rules
dd(RobotsTxt::getRules());

// Check all directives
dd(RobotsTxt::getDirectives());

// Check environment detection
dd(app()->environment());

// Check if user agent exists
dd(RobotsTxt::hasUserAgent('Googlebot'));

// Check cache status
dd(config('robots-txt.cache'));

// Check route status
dd(config('robots-txt.route.enabled'));
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
