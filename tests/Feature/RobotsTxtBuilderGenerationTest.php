<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Feature;

use BadMethodCallException;
use DissNik\RobotsTxt\Builders\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtBuilderGenerationTest extends TestCase
{
    private RobotsTxtBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(RobotsTxtBuilder::class);
        $this->builder->clear();
    }

    #[Test]
    public function generates_basic_robots_txt(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->disallow('/admin')
                ->allow('/public');
        });

        $content = $this->builder->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /public', $content);
    }

    #[Test]
    public function adds_sitemaps(): void
    {
        $this->builder->sitemap('https://site.com/sitemap.xml');

        $content = $this->builder->generate();
        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
    }

    #[Test]
    public function generates_multiple_sitemaps(): void
    {
        $this->builder->sitemap('https://site.com/sitemap.xml')
            ->sitemap('https://site.com/sitemap-images.xml');

        $content = $this->builder->generate();

        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
        $this->assertStringContainsString('Sitemap: https://site.com/sitemap-images.xml', $content);

        $this->assertEquals(2, substr_count($content, 'Sitemap:'));
    }

    #[Test]
    public function handles_multiple_user_agents(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        $this->builder->forUserAgent('Googlebot', function ($context): void {
            $context->disallow('/private')
                ->crawlDelay(1.0);
        });

        $this->builder->forUserAgent('Bingbot', function ($context): void {
            $context->disallow('/search');
        });

        $content = $this->builder->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('User-agent: Bingbot', $content);

        $this->assertStringContainsString('Crawl-delay: 1', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
        $this->assertStringContainsString('Disallow: /search', $content);
    }

    #[Test]
    public function resolves_allow_disallow_conflicts(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->disallow('/admin')
                ->allow('/admin/login');
        });

        $content = $this->builder->generate();

        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /admin/login', $content);
    }

    #[Test]
    public function generates_empty_robots_txt_when_no_rules(): void
    {
        $content = $this->builder->generate();
        $this->assertEquals('', trim($content));
    }

    #[Test]
    public function handles_crawl_delay_with_decimal(): void
    {
        $this->builder->forUserAgent('Googlebot', function ($context): void {
            $context->crawlDelay(1.5);
        });

        $content = $this->builder->generate();
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
    }

    #[Test]
    public function adds_global_host_directive(): void
    {
        $this->builder->host('www.example.com');

        $content = $this->builder->generate();
        $this->assertStringContainsString('Host: www.example.com', $content);
    }

    #[Test]
    public function adds_clean_param_directive(): void
    {
        $this->builder->cleanParam('ref', '/search/');

        $content = $this->builder->generate();
        $this->assertStringContainsString('Clean-param: ref /search/', $content);
    }

    #[Test]
    public function generates_proper_format_with_line_breaks(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        $this->builder->forUserAgent('Googlebot', function ($context): void {
            $context->disallow('/private');
        });

        $this->builder->sitemap('https://site.com/sitemap.xml');

        $content = $this->builder->generate();
        $lines = explode("\n", $content);

        $this->assertGreaterThan(1, count($lines));
    }

    #[Test]
    public function generates_complete_robots_txt(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->allow('/')
                ->disallow('/admin/')
                ->disallow('/private/')
                ->crawlDelay(1.0);
        });

        $this->builder->forUserAgent('Googlebot', function ($context): void {
            $context->allow('/')
                ->disallow('/nogooglebot/')
                ->crawlDelay(2.0);
        });

        $this->builder->sitemap('https://www.example.com/sitemap.xml')
            ->sitemap('https://www.example.com/sitemap-images.xml')
            ->host('www.example.com');

        $content = $this->builder->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
        $this->assertStringContainsString('Crawl-delay: 2', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);

        $this->assertEquals(2, substr_count($content, 'Sitemap:'));
    }

    #[Test]
    public function handles_special_characters_in_paths(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->disallow('/search?q=*')
                ->allow('/public/images/');
        });

        $content = $this->builder->generate();

        $this->assertStringContainsString('Disallow: /search?q=*', $content);
        $this->assertStringContainsString('Allow: /public/images/', $content);
    }

    #[Test]
    public function generates_robots_txt_with_block_all(): void
    {
        $this->builder->blockAll();

        $content = $this->builder->generate();
        $this->assertStringContainsString('Disallow: /', $content);
    }

    #[Test]
    public function generates_robots_txt_with_allow_all(): void
    {
        $this->builder->allowAll();

        $content = $this->builder->generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function handles_empty_paths(): void
    {
        $this->builder->forUserAgent('*', function ($context): void {
            $context->disallow('')
                ->allow('');
        });

        $content = $this->builder->generate();

        $this->assertStringNotContainsString('Disallow:', $content);
        $this->assertStringNotContainsString('Allow:', $content);
    }

    #[Test]
    public function throws_exception_when_allow_called_outside_context(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method allow() can only be called inside forUserAgent() callback');

        $this->builder->allow('/');
    }

    #[Test]
    public function throws_exception_when_disallow_called_outside_context(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method disallow() can only be called inside forUserAgent() callback');

        $this->builder->disallow('/admin');
    }

    #[Test]
    public function throws_exception_when_crawl_delay_called_outside_context(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method crawlDelay() can only be called inside forUserAgent() callback');

        $this->builder->crawlDelay(1.0);
    }

    #[Test]
    public function allow_works_inside_user_agent_context(): void
    {
        $this->builder->forUserAgent('*', function ($ctx): void {
            $ctx->allow('/');
        });

        $content = $this->builder->generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function disallow_works_inside_user_agent_context(): void
    {
        $this->builder->forUserAgent('*', function ($ctx): void {
            $ctx->disallow('/admin');
        });

        $content = $this->builder->generate();
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function crawl_delay_works_inside_user_agent_context(): void
    {
        $this->builder->forUserAgent('*', function ($ctx): void {
            $ctx->crawlDelay(1.5);
        });

        $content = $this->builder->generate();
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
    }
}
