<?php

namespace DissNik\RobotsTxt\Facades;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RobotsTxtInterface forEnvironment(string|array $environments, callable $callback)
 * @method static RobotsTxtInterface forUserAgent(string $userAgent, callable $callback)
 * @method static RobotsTxtInterface directive(string $directive, $value)
 * @method static RobotsTxtInterface sitemap(string $url)
 * @method static RobotsTxtInterface host(string $host)
 * @method static RobotsTxtInterface cleanParam(string $param, ?string $path = null)
 * @method static RobotsTxtInterface blockAll()
 * @method static RobotsTxtInterface allowAll()
 * @method static RobotsTxtInterface clear()
 * @method static RobotsTxtInterface reset()
 * @method static string generate()
 * @method static bool clearCache()
 * @method static array getRules()
 * @method static array getSitemaps()
 * @method static array getDirectives()
 * @method static array getUserAgentDirectives(string $userAgent)
 * @method static array getEnvironmentRules()
 * @method static RobotsTxtInterface removeDirective(string $directive, $value = null)
 * @method static RobotsTxtInterface removeUserAgentDirective(string $userAgent, string $directive, $value = null)
 * @method static array checkConflicts()
 * @method static array getUserAgents()
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
