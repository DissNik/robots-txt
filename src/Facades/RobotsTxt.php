<?php

namespace DissNik\RobotsTxt\Facades;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RobotsTxtInterface forUserAgent(string $userAgent)
 * @method static RobotsTxtInterface disallow(string $path)
 * @method static RobotsTxtInterface allow(string $path)
 * @method static RobotsTxtInterface crawlDelay(float $delay)
 * @method static RobotsTxtInterface sitemap(string $url)
 * @method static RobotsTxtInterface group(string $userAgent, callable $callback)
 * @method static RobotsTxtInterface when(bool $condition, callable $callback)
 * @method static RobotsTxtInterface unless(bool $condition, callable $callback)
 * @method static RobotsTxtInterface forEnvironment(string|array<int, string> $environments, callable $callback)
 * @method static string generate()
 * @method static RobotsTxtInterface clear()
 * @method static bool clearCache()
 * @method static array<string, array<int, array{allow: bool, path: string}>> getRules()
 * @method static array<int, string> getSitemaps()
 * @method static array<string, array<string, array<int, array{allow: bool, path: string}>>> getEnvironmentRules()
 *
 * @see \DissNik\RobotsTxt\RobotsTxt
 */
class RobotsTxt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RobotsTxtInterface::class;
    }
}
