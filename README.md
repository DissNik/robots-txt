# Robots.txt manager for Laravel

A powerful, flexible robots.txt management package for Laravel applications with environment-aware rules, caching, and fluent API.

---

## Features

- **Fluent API** - Easy-to-use chainable methods
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
```

## Conflict Check

> [!WARNING]
> **File takes priority!** If a `public/robots.txt` file exists on your server,
> it will **OVERRIDE** the package's generated content.

```bash
php artisan robots-txt:check
```

This command will:
- Detect if a robots.txt file exists
- Help you choose the best resolution method

## Quick Start

### Basic Usage

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

// Generate robots.txt content
$content = RobotsTxt::generate();

// Or use in your controller
public function robots()
{
    return response(RobotsTxt::generate(), 200, [
        'Content-Type' => 'text/plain'
    ]);
}
```

### Fluent API Examples

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

// Basic rules
RobotsTxt::forUserAgent('*')
    ->disallow('/admin')
    ->allow('/public')
    ->crawlDelay(1.0)
    ->sitemap('https://example.com/sitemap.xml');

// Multiple user agents
RobotsTxt::forUserAgent('Googlebot')
    ->disallow('/private')
    ->crawlDelay(2.0);

RobotsTxt::forUserAgent('Bingbot')
    ->disallow('/secret');
```

### Environment-Specific Rules

```php
// Block all in local development
RobotsTxt::forEnvironment('local', function ($robots) {
    $robots->forUserAgent('*')->disallow('/');
});

// Production rules
RobotsTxt::forEnvironment('production', function ($robots) {
    $robots->forUserAgent('*')
        ->allow('/')
        ->disallow('/admin')
        ->sitemap('https://example.com/sitemap.xml');
});

// Multiple environments
RobotsTxt::forEnvironment(['staging', 'production'], function ($robots) {
    $robots->forUserAgent('*')->disallow('/debug');
});
```

### Conditional Rules

```php
RobotsTxt::when($isMaintenanceMode, function ($robots) {
    $robots->forUserAgent('*')->disallow('/');
})->unless($isMaintenanceMode, function ($robots) {
    $robots->forUserAgent('*')->allow('/');
});

// Group rules
RobotsTxt::group('Googlebot', function ($robots) {
    $robots->disallow('/private')
           ->crawlDelay(1.5)
           ->sitemap('https://example.com/sitemap-google.xml');
});
```

### Helper Methods

```php
// Block all crawlers
RobotsTxt::blockAll();

// Allow all crawlers
RobotsTxt::allowAll();

// Clear all rules
RobotsTxt::clear();

// Clear cache
RobotsTxt::clearCache();
```

## Route Integration

The package can automatically register a robots.txt route:

```php
// Enabled in config, automatically serves robots.txt
// Access at: https://yoursite.com/robots.txt
```

### Manual Route Setup

If you prefer manual control:

```php
// routes/web.php
Route::get('robots.txt', function () {
    return response(RobotsTxt::generate(), 200, [
        'Content-Type' => 'text/plain'
    ]);
})->middleware('robots.txt.cache');
```

## Advanced Usage

### Programmatic Rule Management
```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

// Get all rules
$rules = RobotsTxt::getRules();

// Get sitemaps
$sitemaps = RobotsTxt::getSitemaps();

// Check for conflicts
$conflicts = RobotsTxt::checkConflicts();

// Debug environment rules
$envRules = RobotsTxt::getEnvironmentRules();
```

### Custom Rule Conflicts Resolution

The package automatically resolves conflicts where both Allow and Disallow rules exist for the same path (Allow has priority).

```php
// This will generate only "Allow: /admin" (Allow wins)
RobotsTxt::forUserAgent('*')
    ->disallow('/admin')
    ->allow('/admin');
```

### Cache Management

```php
// Disable caching for current request
config(['robots-txt.cache.enabled' => false]);

// Clear cached robots.txt
RobotsTxt::clearCache();

// Custom cache duration
config(['robots-txt.cache.duration' => 7200]); // 2 hours
```

## Testing

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Code quality checks
composer qa

# Fix code style and refactor
composer fix
```

## Examples

### Complete Production Setup

```php
use DissNik\RobotsTxt\Facades\RobotsTxt;

RobotsTxt::clear()
    ->forEnvironment('production', function ($robots) {
        $robots->forUserAgent('*')
            ->allow('/')
            ->disallow('/admin')
            ->disallow('/private')
            ->disallow('/tmp')
            ->crawlDelay(1.0)
            ->sitemap('https://example.com/sitemap.xml')
            ->sitemap('https://example.com/sitemap-images.xml');

        $robots->forUserAgent('Googlebot-Image')
            ->allow('/images')
            ->crawlDelay(2.0);

    })->forEnvironment('local', function ($robots) {
        $robots->blockAll();
    });
```

### E-commerce Site Example

```php
RobotsTxt::forUserAgent('*')
    ->allow('/')
    ->allow('/products')
    ->allow('/categories')
    ->disallow('/checkout')
    ->disallow('/cart')
    ->disallow('/user')
    ->disallow('/api')
    ->crawlDelay(0.5)
    ->sitemap('https://store.com/sitemap-products.xml')
    ->sitemap('https://store.com/sitemap-categories.xml');
```

## API Reference

### Main Methods

|               Method               |             Description             |
|:----------------------------------:|:-----------------------------------:|
|  forUserAgent(string $userAgent)   | Set user agent for subsequent rules |
|       disallow(string $path)       |          Add disallow rule          |
|        allow(string $path)         |           Add allow rule            |
|      crawlDelay(float $delay)      |           Set crawl delay           |
|        sitemap(string $url)        |             Add sitemap             |
| group(string $userAgent, callable) |     Group rules for user agent      |
|        when(bool, callable)        |          Conditional rules          |
|       unless(bool, callable)       |     Negative conditional rules      |
|  forEnvironment(mixed, callable)   |     Environment-specific rules      |
|             generate()             |     Generate robots.txt content     |
|              clear()               |           Clear all rules           |
|            clearCache()            |        Clear cached content         |

### Helper Methods

|        Method         |       Description        |
|:---------------------:|:------------------------:|
|      blockAll()       |  Disallow all crawling   |
|      allowAll()       |    Allow all crawling    |
|      getRules()       |  Get all defined rules   |
|     getSitemaps()     |     Get all sitemaps     |
| getEnvironmentRules() |  Get environment rules   |
|   checkConflicts()    | Check for rule conflicts |

## Troubleshooting

### Common Issues
1. Rules not applying? Make sure you're in the correct environment
2. Caching issues? Run RobotsTxt::clearCache()
3. Route not working? Check if route is enabled in config

### Debug Mode

```php
// Check generated content
$content = RobotsTxt::generate();
echo $content;

// Debug rules
dd(RobotsTxt::getRules());

// Check environment detection
dd(app()->environment());
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
