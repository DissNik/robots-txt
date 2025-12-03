<?php

namespace DissNik\RobotsTxt\Tests\Architecture;

use BadMethodCallException;
use DissNik\RobotsTxt\Contexts\EnvironmentContext;
use DissNik\RobotsTxt\Contexts\UserAgentContext;
use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NewArchitectureTest extends TestCase
{
    #[Test]
    public function user_agent_context_has_correct_methods(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forUserAgent('*', function ($ctx): void {
            $this->assertInstanceOf(UserAgentContext::class, $ctx);

            $this->assertTrue(method_exists($ctx, 'allow'));
            $this->assertTrue(method_exists($ctx, 'disallow'));
            $this->assertTrue(method_exists($ctx, 'crawlDelay'));
            $this->assertTrue(method_exists($ctx, 'cleanParam'));
            $this->assertTrue(method_exists($ctx, 'directive'));
            $this->assertTrue(method_exists($ctx, 'blockAll'));
            $this->assertTrue(method_exists($ctx, 'allowAll'));
            $this->assertTrue(method_exists($ctx, 'when'));
            $this->assertTrue(method_exists($ctx, 'unless'));

            $this->assertFalse(method_exists($ctx, 'sitemap'));
            $this->assertFalse(method_exists($ctx, 'host'));
        });
    }

    #[Test]
    public function environment_context_has_correct_methods(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forEnvironment('production', function ($env): void {
            $this->assertInstanceOf(EnvironmentContext::class, $env);

            $this->assertTrue(method_exists($env, 'userAgent'));
            $this->assertTrue(method_exists($env, 'sitemap'));
            $this->assertTrue(method_exists($env, 'host'));
            $this->assertTrue(method_exists($env, 'cleanParam'));
            $this->assertTrue(method_exists($env, 'directive'));
            $this->assertTrue(method_exists($env, 'blockAll'));
            $this->assertTrue(method_exists($env, 'allowAll'));
            $this->assertTrue(method_exists($env, 'when'));
            $this->assertTrue(method_exists($env, 'unless'));
        });
    }

    #[Test]
    public function global_methods_work_correctly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->sitemap('sitemap.xml')
            ->host('example.com')
            ->cleanParam('ref', '/search/')
            ->directive('Custom', 'value');

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: sitemap.xml', $content);
        $this->assertStringContainsString('Host: example.com', $content);
        $this->assertStringContainsString('Clean-param: ref /search/', $content);
        $this->assertStringContainsString('Custom: value', $content);
    }

    #[Test]
    public function user_agent_methods_throw_exception_on_global_level(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $this->expectException(BadMethodCallException::class);
        $builder->allow('/');

        $this->expectException(BadMethodCallException::class);
        $builder->disallow('/admin');

        $this->expectException(BadMethodCallException::class);
        $builder->crawlDelay(1.0);
    }

    #[Test]
    public function user_agent_methods_work_inside_context(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->allow('/')
                ->disallow('/admin')
                ->crawlDelay(1.0);
        });

        $content = $builder->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
    }

    #[Test]
    public function environment_context_works_correctly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        app()->instance('env', 'production');

        $builder->forEnvironment('production', function ($env): void {
            $env->sitemap('prod-sitemap.xml')
                ->host('prod.example.com')
                ->forUserAgent('*', function ($ctx): void {
                    $ctx->allow('/');
                });
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: prod-sitemap.xml', $content);
        $this->assertStringContainsString('Host: prod.example.com', $content);
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function conditional_methods_work(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->when(true, function ($robots): void {
            $robots->sitemap('sitemap.xml');
        })->when(false, function ($robots): void {
            $robots->sitemap('never.xml');
        })->unless(true, function ($robots): void {
            $robots->host('never.com');
        })->unless(false, function ($robots): void {
            $robots->host('example.com');
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: sitemap.xml', $content);
        $this->assertStringContainsString('Host: example.com', $content);
        $this->assertStringNotContainsString('sitemap: never.xml', $content);
        $this->assertStringNotContainsString('Host: never.com', $content);
    }

    #[Test]
    public function complex_example_works(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forEnvironment('dev', function ($env): void {
            $env->forUserAgent('*', function ($ctx): void {
                $ctx->allow('/admin/login')
                    ->disallow('/admin')
                    ->allow('/api/v1/users')
                    ->disallow('/api')
                    ->crawlDelay(0.7);
            });

            $env->forUserAgent('Google', function ($ctx): void {
                $ctx->allow('/admin/login')
                    ->disallow('/admin2')
                    ->allow('/api/v1/users')
                    ->disallow('/api')
                    ->crawlDelay(0.7)
                    ->directive('Sitemap', 'https://site.com/sitemap.xml'); // Через directive()
            });

            $env->forUserAgent('Yandex', function ($ctx): void {
                $ctx->allow('/admin/login')
                    ->disallow('/admin2')
                    ->allow('/api/v1/users')
                    ->disallow('/api')
                    ->crawlDelay(0.7);
            });
        });

        $content = $builder->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Google', $content);
        $this->assertStringContainsString('User-agent: Yandex', $content);

        $this->assertStringContainsString('Allow: /admin/login', $content);
        $this->assertStringContainsString('Allow: /api/v1/users', $content);

        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
    }
}
