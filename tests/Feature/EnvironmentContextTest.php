<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EnvironmentContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    #[Test]
    public function environment_context_proxies_for_user_agent_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->forUserAgent('*', function ($context): void {
                $context->disallow('/admin');
            });
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function environment_context_proxies_sitemap_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->sitemap('https://example.com/sitemap.xml');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
    }

    #[Test]
    public function environment_context_proxies_host_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->host('www.example.com');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Host: www.example.com', $content);
    }

    #[Test]
    public function environment_context_proxies_clean_param_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->cleanParam('session', '/shop/*');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Clean-param: session /shop/*', $content);
    }

    #[Test]
    public function environment_context_proxies_directive_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->directive('test-directive', 'test-value');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Test-directive: test-value', $content);
    }

    #[Test]
    public function environment_context_proxies_block_all_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->blockAll();
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /', $content);
    }

    #[Test]
    public function environment_context_proxies_allow_all_method(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->allowAll();
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function environment_context_delegates_allow_disallow_to_robots_txt_builder(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->forUserAgent('*', function ($context): void {
                $context->allow('/public')
                    ->disallow('/private');
            });
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /public', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
    }

    #[Test]
    public function environment_context_supports_method_chaining(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->forUserAgent('*', function ($context): void {
                $context->disallow('/admin');
            })
                ->sitemap('https://example.com/sitemap.xml')
                ->host('www.example.com');
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);
    }

    #[Test]
    public function environment_context_registers_callback_for_environment(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->forUserAgent('*', function ($context): void {
                $context->disallow('/test');
            });
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /test', $content);
    }

    #[Test]
    public function environment_context_handles_multiple_environments(): void
    {
        RobotsTxt::forEnvironment(['testing', 'staging'], function ($env): void {
            $env->forUserAgent('*', function ($context): void {
                $context->disallow('/shared');
            });
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /shared', $content);
    }

    #[Test]
    public function environment_context_can_be_used_with_conditionable_trait(): void
    {
        RobotsTxt::forEnvironment('testing', function ($env): void {
            $env->when(true, function ($env): void {
                $env->forUserAgent('*', function ($context): void {
                    $context->allow('/conditional');
                });
            });
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /conditional', $content);
    }
}
