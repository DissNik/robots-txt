<?php

namespace DissNik\RobotsTxt\Tests\Architecture;

use BadMethodCallException;
use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CorrectUsageTest extends TestCase
{
    #[Test]
    public function demonstrates_new_api_correctly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->sitemap('https://site.com/sitemap.xml')
            ->host('www.site.com')
            ->cleanParam('ref', '/search/')
            ->directive('X-Custom', 'value');

        // 2. Правила для user-agent'ов
        $builder->forUserAgent('*', function ($ctx) {
            $ctx->allow('/')
                ->disallow('/admin')
                ->disallow('/private')
                ->crawlDelay(1.0)
                ->when(true, function ($ctx) {
                    $ctx->allow('/public');
                });
        });

        $builder->forUserAgent('Googlebot', function ($ctx) {
            $ctx->allow('/')
                ->disallow('/no-google')
                ->crawlDelay(0.5)
                ->directive('Googlebot-News', 'sitemap_news.xml');
        });

        $builder->forEnvironment('production', function ($env) {
            $env->sitemap('https://prod.site.com/sitemap.xml')
                // Не добавляем host здесь, так как он перезапишет глобальный
                ->forUserAgent('*', function ($ctx) {
                    $ctx->allow('/api'); // Только в production
                })
                ->when(app()->environment('production'), function ($env) {
                    $env->directive('X-Production', 'true');
                });
        });

        $builder->forEnvironment(['local', 'staging'], function ($env) {
            $env->forUserAgent('*', function ($ctx) {
                $ctx->blockAll(); // Блокируем всё на тестовых
            });
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
        $this->assertStringContainsString('Host: www.site.com', $content);
        $this->assertStringContainsString('Clean-param: ref /search/', $content);
        $this->assertStringContainsString('X-custom: value', $content);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
        $this->assertStringContainsString('Crawl-delay: 0.5', $content);
        $this->assertStringContainsString('Googlebot-news: sitemap_news.xml', $content);

        $this->assertStringNotContainsString('Host: prod.site.com', $content);
    }

    #[Test]
    public function shows_common_mistakes_and_corrections(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $this->expectException(BadMethodCallException::class);
        $builder->allow('/');

        $builder->forUserAgent('*', function ($ctx) {
            $ctx->allow('/');
        });

        $builder->clear();
        $builder->forEnvironment('prod', function ($env) {
            $this->expectException(BadMethodCallException::class);
            $env->allow('/');
        });

        $builder->clear();
        $builder->forEnvironment('prod', function ($env) {
            $env->forUserAgent('*', function ($ctx) {
                $ctx->allow('/');
            });
        });
    }

    #[Test]
    public function helper_methods_work(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->blockAll();
        $content = $builder->generate();
        $this->assertStringContainsString('Disallow: /', $content);

        $builder->clear();
        $builder->allowAll();
        $content = $builder->generate();
        $this->assertStringContainsString('Allow: /', $content);

        $builder->clear();
        $builder->forUserAgent('Googlebot', function ($ctx) {
            $ctx->blockAll();
        });
        $content = $builder->generate();
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /', $content);
    }
}
