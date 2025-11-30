<?php

namespace DissNik\RobotsTxt\Tests\Unit\Middleware;

use DissNik\RobotsTxt\Http\Middleware\CacheRobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;

class CacheRobotsTxtTest extends TestCase
{
    public function test_adds_cache_headers_for_robots_txt(): void
    {
        Config::set('robots-txt.cache.enabled', true);
        Config::set('robots-txt.cache.duration', 3600);

        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://localhost/robots.txt');

        $response = new Response('test content', 200);
        $next = (fn ($request): Response => $response);

        $result = $middleware->handle($request, $next);

        $this->assertEquals('text/plain', $result->headers->get('Content-Type'));
        $this->assertEquals(3600, $result->getMaxAge());
        $this->assertTrue($result->headers->hasCacheControlDirective('public'));
    }

    public function test_does_not_cache_non_robots_requests(): void
    {
        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://localhost/other');

        $response = new Response('test content', 200);
        $next = (fn ($request): Response => $response);

        $result = $middleware->handle($request, $next);

        $this->assertNotEquals('text/plain', $result->headers->get('Content-Type'));
        $this->assertNull($result->getMaxAge());
        $this->assertFalse($result->headers->hasCacheControlDirective('public'));
    }

    public function test_does_not_cache_when_disabled_in_config(): void
    {
        Config::set('robots-txt.cache.enabled', false);

        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://localhost/robots.txt');

        $response = new Response('test content', 200);
        $next = (fn ($request): Response => $response);

        $result = $middleware->handle($request, $next);

        $this->assertEquals('text/plain', $result->headers->get('Content-Type'));
        $this->assertNull($result->getMaxAge());
        $this->assertFalse($result->headers->hasCacheControlDirective('public'));
    }

    public function test_does_not_cache_non_200_responses(): void
    {
        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://localhost/robots.txt');

        $response = new Response('Not found', 404);
        $next = (fn ($request): Response => $response);

        $result = $middleware->handle($request, $next);

        $this->assertNull($result->getMaxAge());
        $this->assertFalse($result->headers->hasCacheControlDirective('public'));
    }
}
