<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Facades;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RobotsTxtInterface forEnvironment(string|array<string> $environments, callable $callback)
 * @method static RobotsTxtInterface forUserAgent(string $userAgent, callable $callback)
 * @method static RobotsTxtInterface directive(string $directive, mixed $value)
 * @method static RobotsTxtInterface sitemap(string $url)
 * @method static RobotsTxtInterface host(string $host)
 * @method static RobotsTxtInterface cleanParam(string $param, ?string $path = null)
 * @method static RobotsTxtInterface blockAll()
 * @method static RobotsTxtInterface allowAll()
 * @method static RobotsTxtInterface clear()
 * @method static RobotsTxtInterface reset()
 * @method static string generate()
 * @method static bool clearCache()
 * @method static array<string, mixed> getRules()
 * @method static array<string> getSitemaps()
 * @method static array<string, mixed> getDirectives()
 * @method static array<string, mixed> getUserAgentDirectives(string $userAgent)
 * @method static array<string, array{environments: array<string>, callback: string}> getEnvironmentRules()
 * @method static RobotsTxtInterface removeDirective(string $directive, mixed $value = null)
 * @method static RobotsTxtInterface removeUserAgentDirective(string $userAgent, string $directive, mixed $value = null)
 * @method static array<string, mixed> checkConflicts()
 * @method static array<string> getUserAgents()
 * @method static bool hasUserAgent(string $userAgent)
 *
 * @see \DissNik\RobotsTxt\Builders\RobotsTxtBuilder
 */
class RobotsTxt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RobotsTxtInterface::class;
    }
}
