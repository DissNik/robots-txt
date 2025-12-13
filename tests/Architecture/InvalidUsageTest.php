<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Architecture;

use BadMethodCallException;
use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InvalidUsageTest extends TestCase
{
    #[Test]
    public function user_agent_methods_in_environment_without_context_throws_error(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forEnvironment('dev', function ($env): void {
            $env->forUserAgent('*', function ($ctx): void {
                $ctx->allow('/admin/login');
            });

            try {
                $env->allow('/admin/login');
                $this->fail('Expected BadMethodCallException was not thrown');
            } catch (BadMethodCallException $e) {
                $this->assertStringContainsString('Method allow()', $e->getMessage());
            }
        });
    }

    #[Test]
    public function mixing_contexts_works_correctly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->sitemap('global-sitemap.xml')
            ->forUserAgent('*', function ($ctx): void {
                $ctx->allow('/');
            })
            ->forEnvironment('production', function ($env): void {
                $env->sitemap('prod-sitemap.xml')
                    ->forUserAgent('Googlebot', function ($ctx): void {
                        $ctx->crawlDelay(0.5);
                    });
            });

        $content = $builder->generate();

        $this->assertStringContainsString('Sitemap: global-sitemap.xml', $content);
        $this->assertStringContainsString('Sitemap: prod-sitemap.xml', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Crawl-delay: 0.5', $content);
    }
}
