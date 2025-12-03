<?php

namespace DissNik\RobotsTxt\Tests\Unit\Middleware;

use DissNik\RobotsTxt\Http\Middleware\CacheRobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

class CacheRobotsTxtTest extends TestCase
{
    #[Test]
    public function middleware_adds_cache_headers(): void
    {
        config(['robots-txt.cache.enabled' => true]);
        config(['robots-txt.cache.duration' => 3600]);

        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://example.com/robots.txt');

        $response = $middleware->handle($request, fn ($req): Response => new Response('content', 200));

        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
        $this->assertEquals(3600, $response->getMaxAge());
    }

    #[Test]
    public function middleware_ignores_non_robots(): void
    {
        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://example.com/other');

        $response = $middleware->handle($request, fn ($req): Response => new Response('content', 200));

        $this->assertNull($response->getMaxAge());
    }

    #[Test]
    public function middleware_handles_non_200(): void
    {
        $middleware = new CacheRobotsTxt;
        $request = Request::create('http://example.com/robots.txt');

        $response = $middleware->handle($request, fn ($req): Response => new Response('Not found', 404));

        $this->assertNull($response->getMaxAge());
    }
}
