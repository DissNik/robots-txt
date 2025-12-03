<?php

namespace DissNik\RobotsTxt\Tests\Architecture;

use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use BadMethodCallException;
use PHPUnit\Framework\Attributes\Test;

class FinalApiTest extends TestCase
{
    #[Test]
    public function demonstrates_correct_api_usage(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->sitemap('https://example.com/sitemap.xml')
            ->host('www.example.com') // Глобальный Host
            ->cleanParam('ref', '/search/')
            ->directive('X-Robots-Tag', 'noindex');

        $builder->forUserAgent('*', function($ctx) {
            $ctx->allow('/')
                ->disallow('/admin')
                ->crawlDelay(1.0)
                ->when(app()->environment('production'), function($ctx) {
                    $ctx->allow('/api');
                });
        });

        $builder->forUserAgent('Googlebot', function($ctx) {
            $ctx->allow('/')
                ->disallow('/private')
                ->crawlDelay(0.5);
        });

        $builder->forUserAgent('Yandex', function($ctx) {
            $ctx->directive('host', 'yandex.example.com')
            ->allow('/')
                ->cleanParam('ref', '/search/')
                ->directive('visit-time', '0900-1800');
        });

        $builder->forEnvironment('production', function($env) {
            $env->sitemap('https://prod.example.com/sitemap.xml')
                ->forUserAgent('*', function($ctx) {
                    $ctx->allow('/api');
                });
        });

        $builder->forEnvironment(['local', 'staging'], function($env) {
            $env->forUserAgent('*', function($ctx) {
                $ctx->blockAll();
            });
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('User-agent: Yandex', $content);
        $this->assertStringContainsString('Host: yandex.example.com', $content);
    }

    #[Test]
    public function shows_common_errors(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $this->expectException(BadMethodCallException::class);
        $builder->allow('/');

        $this->expectException(BadMethodCallException::class);
        $builder->disallow('/admin');

        $this->expectException(BadMethodCallException::class);
        $builder->crawlDelay(1.0);

        $builder->clear();
        $builder->forUserAgent('*', function($ctx) {
            $ctx->allow('/');
        });

        $builder->clear();
        $builder->forEnvironment('prod', function($env) {
            $this->expectException(BadMethodCallException::class);
            $env->allow('/');

            $env->forUserAgent('*', function($ctx) {
                $ctx->allow('/');
            });
        });
    }

    #[Test]
    public function demonstrates_conditional_api(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->when(true, function($robots) {
            $robots->sitemap('active.xml');
        })->unless(false, function($robots) {
            $robots->host('active.com');
        });

        $builder->forUserAgent('*', function($ctx) {
            $ctx->when(app()->environment('production'), function($ctx) {
                $ctx->allow('/api');
            })->unless(app()->environment('local'), function($ctx) {
                $ctx->disallow('/debug');
            });
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: active.xml', $content);
        $this->assertStringContainsString('Host: active.com', $content);
    }
}
