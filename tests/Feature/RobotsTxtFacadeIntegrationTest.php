<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtFacadeIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    #[Test]
    public function facade_proxies_basic_methods(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin')
                ->allow('/public');
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /public', $content);
    }

    #[Test]
    public function facade_proxies_sitemap_methods(): void
    {
        RobotsTxt::sitemap('https://example.com/sitemap.xml')
            ->sitemap('https://example.com/sitemap-images.xml');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap-images.xml', $content);
        $this->assertEquals(2, substr_count($content, 'Sitemap:'));
    }

    #[Test]
    public function facade_proxies_global_directives(): void
    {
        RobotsTxt::host('www.example.com')
            ->cleanParam('ref', '/search/');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Host: www.example.com', $content);
        $this->assertStringContainsString('Clean-param: ref /search/', $content);
    }

    #[Test]
    public function facade_proxies_convenience_methods(): void
    {
        RobotsTxt::blockAll();
        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /', $content);

        RobotsTxt::clear();
        RobotsTxt::allowAll();
        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function facade_handles_multiple_user_agents(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        RobotsTxt::forUserAgent('Googlebot', function ($context): void {
            $context->disallow('/private')
                ->crawlDelay(2.5);
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
        $this->assertStringContainsString('Crawl-delay: 2.5', $content);
    }

    #[Test]
    public function facade_resets_state_with_clear(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        $content = RobotsTxt::generate();
        $this->assertNotEmpty($content);

        RobotsTxt::clear();

        $content = RobotsTxt::generate();
        $this->assertEquals('', trim($content));
    }

    #[Test]
    public function facade_handles_chaining(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        })
            ->sitemap('https://example.com/sitemap.xml')
            ->host('example.com');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Host: example.com', $content);
    }

    #[Test]
    public function facade_handles_complex_scenario(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/')
                ->disallow('/admin/')
                ->disallow('/private/')
                ->crawlDelay(1.0);
        });

        RobotsTxt::forUserAgent('Googlebot-Image', function ($context): void {
            $context->allow('/images/')
                ->disallow('/images/private/');
        });

        RobotsTxt::sitemap('https://www.example.com/sitemap.xml')
            ->sitemap('https://www.example.com/sitemap-images.xml')
            ->cleanParam('session', '/shop/*')
            ->host('www.example.com');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot-Image', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Allow: /images/', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /images/private/', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('Clean-param: session /shop/*', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);
    }
}
