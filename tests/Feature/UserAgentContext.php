<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserAgentContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    #[Test]
    public function user_agent_context_allows_path(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/public');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /public', $content);
    }

    #[Test]
    public function user_agent_context_disallows_path(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function user_agent_context_sets_crawl_delay(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->crawlDelay(1.5);
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
    }

    #[Test]
    public function user_agent_context_sets_clean_param(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->cleanParam('session', '/shop/*');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Clean-param: session /shop/*', $content);
    }

    #[Test]
    public function user_agent_context_sets_custom_directive(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->directive('test-directive', 'test-value');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Test-directive: test-value', $content);
    }

    #[Test]
    public function user_agent_context_blocks_all(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->blockAll();
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /', $content);
    }

    #[Test]
    public function user_agent_context_allows_all(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allowAll();
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function user_agent_context_removes_directive(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/public')
                ->disallow('/admin')
                ->removeDirective('disallow', '/admin');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /public', $content);
        $this->assertStringNotContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function user_agent_context_supports_method_chaining(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/')
                ->disallow('/admin')
                ->crawlDelay(1.0);
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
    }

    #[Test]
    public function user_agent_context_can_be_used_with_conditionable_trait(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->when(true, function ($context): void {
                $context->allow('/conditional');
            });
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /conditional', $content);
    }

    #[Test]
    public function user_agent_context_normalizes_paths(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('public')
                ->disallow('admin/');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /public', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
    }

    #[Test]
    public function user_agent_context_handles_empty_paths(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('')
                ->disallow('');
        });

        $content = RobotsTxt::generate();
        $this->assertStringNotContainsString('Allow:', $content);
        $this->assertStringNotContainsString('Disallow:', $content);
    }

    #[Test]
    public function user_agent_context_handles_special_characters(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/search?q=*')
                ->allow('/path with spaces/');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /search?q=*', $content);
        $this->assertStringContainsString('Allow: /path with spaces/', $content);
    }

    #[Test]
    public function user_agent_context_get_rule_returns_rule_object(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $rule = $context->getRule();
            $this->assertNotNull($rule);
            $this->assertEquals('*', $rule->getUserAgent());
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('User-agent: *', $content);
    }
}
