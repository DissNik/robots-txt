<?php

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    #[Test]
    public function generates_basic_robots_txt(): void
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
    public function adds_sitemaps_to_content(): void
    {
        RobotsTxt::sitemap('https://site.com/sitemap.xml');

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
    }

    #[Test]
    public function generates_multiple_sitemaps(): void
    {
        RobotsTxt::sitemap('https://site.com/sitemap.xml')
            ->sitemap('https://site.com/sitemap-images.xml');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
        $this->assertStringContainsString('Sitemap: https://site.com/sitemap-images.xml', $content);

        $sitemapCount = substr_count($content, 'Sitemap:');
        $this->assertEquals(2, $sitemapCount);
    }

    #[Test]
    public function handles_multiple_user_agents(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        RobotsTxt::forUserAgent('Googlebot', function ($context): void {
            $context->disallow('/private')
                ->crawlDelay(1.0);
        });

        RobotsTxt::forUserAgent('Bingbot', function ($context): void {
            $context->disallow('/search');
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('User-agent: Bingbot', $content);

        $this->assertStringContainsString('Crawl-delay: 1', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
        $this->assertStringContainsString('Disallow: /search', $content);
    }

    #[Test]
    public function resolves_conflicts_between_allow_and_disallow(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin')
                ->allow('/admin/login');
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /admin/login', $content);
    }

    #[Test]
    public function generates_empty_robots_txt_when_no_rules(): void
    {
        $content = RobotsTxt::generate();

        $this->assertEquals('', trim($content));
    }

    #[Test]
    public function handles_crawl_delay_with_decimal(): void
    {
        RobotsTxt::forUserAgent('Googlebot', function ($context): void {
            $context->crawlDelay(1.5);
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
    }

    #[Test]
    public function adds_global_host_directive(): void
    {
        RobotsTxt::host('www.example.com');

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Host: www.example.com', $content);
    }

    #[Test]
    public function adds_clean_param_directive(): void
    {
        RobotsTxt::cleanParam('ref', '/search/');

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Clean-param: ref /search/', $content);
    }

    #[Test]
    public function generates_proper_format_with_line_breaks(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        RobotsTxt::forUserAgent('Googlebot', function ($context): void {
            $context->disallow('/private');
        });

        RobotsTxt::sitemap('https://site.com/sitemap.xml');

        $content = RobotsTxt::generate();

        $lines = explode("\n", $content);
        $this->assertGreaterThan(1, count($lines));
    }

    #[Test]
    public function generates_complete_robots_txt_example(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/')
                ->disallow('/admin/')
                ->disallow('/private/')
                ->crawlDelay(1.0);
        });

        RobotsTxt::forUserAgent('Googlebot', function ($context): void {
            $context->allow('/')
                ->disallow('/nogooglebot/')
                ->crawlDelay(2.0);
        });

        RobotsTxt::sitemap('https://www.example.com/sitemap.xml')
            ->sitemap('https://www.example.com/sitemap-images.xml')
            ->host('www.example.com');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
        $this->assertStringContainsString('Crawl-delay: 2', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);

        $sitemapCount = substr_count($content, 'Sitemap:');
        $this->assertEquals(2, $sitemapCount);
    }

    #[Test]
    public function handles_special_characters_in_paths(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/search?q=*')
                ->allow('/public/images/');
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Disallow: /search?q=*', $content);
        $this->assertStringContainsString('Allow: /public/images/', $content);
    }

    #[Test]
    public function generates_robots_txt_with_block_all(): void
    {
        RobotsTxt::blockAll();

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /', $content);
    }

    #[Test]
    public function generates_robots_txt_with_allow_all(): void
    {
        RobotsTxt::allowAll();

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function properly_handles_empty_paths(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('')
                ->allow('');
        });

        $content = RobotsTxt::generate();

        $this->assertStringNotContainsString('Disallow:', $content);
        $this->assertStringNotContainsString('Allow:', $content);
    }
}
