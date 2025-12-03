<?php

namespace DissNik\RobotsTxt\Tests\Architecture;

use BadMethodCallException;
use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
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

        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->allow('/')
                ->disallow('/admin')
                ->crawlDelay(1.0)
                ->when(app()->environment('production'), function ($ctx): void {
                    $ctx->allow('/api');
                });
        });

        $builder->forUserAgent('Googlebot', function ($ctx): void {
            $ctx->allow('/')
                ->disallow('/private')
                ->crawlDelay(0.5);
        });

        $builder->forUserAgent('Yandex', function ($ctx): void {
            $ctx->directive('host', 'yandex.example.com')
                ->allow('/')
                ->cleanParam('ref', '/search/')
                ->directive('visit-time', '0900-1800');
        });

        $builder->forEnvironment('production', function ($env): void {
            $env->sitemap('https://prod.example.com/sitemap.xml')
                ->forUserAgent('*', function ($ctx): void {
                    $ctx->allow('/api');
                });
        });

        $builder->forEnvironment(['local', 'staging'], function ($env): void {
            $env->forUserAgent('*', function ($ctx): void {
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
        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->allow('/');
        });

        $builder->clear();
        $builder->forEnvironment('prod', function ($env): void {
            $this->expectException(BadMethodCallException::class);
            $env->allow('/');

            $env->forUserAgent('*', function ($ctx): void {
                $ctx->allow('/');
            });
        });
    }

    #[Test]
    public function demonstrates_conditional_api(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->when(true, function ($robots): void {
            $robots->sitemap('active.xml');
        })->unless(false, function ($robots): void {
            $robots->host('active.com');
        });

        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->when(app()->environment('production'), function ($ctx): void {
                $ctx->allow('/api');
            })->unless(app()->environment('local'), function ($ctx): void {
                $ctx->disallow('/debug');
            });
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: active.xml', $content);
        $this->assertStringContainsString('Host: active.com', $content);
    }
}
