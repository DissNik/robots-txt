<?php

namespace DissNik\RobotsTxt\Tests\Architecture;

use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SimpleTest extends TestCase
{
    #[Test]
    public function basic_usage_works(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->sitemap('sitemap.xml')
            ->host('example.com');

        $builder->forUserAgent('*', function ($ctx) {
            $ctx->allow('/')
                ->disallow('/admin');
        });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: sitemap.xml', $content);
        $this->assertStringContainsString('Host: example.com', $content);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function correct_usage_example(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->sitemap('global.xml')
            ->forUserAgent('*', function ($ctx) {
                $ctx->allow('/')
                    ->disallow('/admin');
            })
            ->forEnvironment('production', function ($env) {
                $env->sitemap('prod.xml')
                    ->forUserAgent('Googlebot', function ($ctx) {
                        $ctx->crawlDelay(0.5);
                    });
            });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: global.xml', $content);
        $this->assertStringContainsString('Sitemap: prod.xml', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Crawl-delay: 0.5', $content);
    }
}
