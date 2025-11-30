<?php

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;

class RobotsTxtGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    public function test_generates_valid_robots_txt_content(): void
    {
        RobotsTxt::forUserAgent('*')
            ->disallow('/admin')
            ->allow('/public')
            ->sitemap('https://site.com/sitemap.xml');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /public', $content);
        $this->assertStringContainsString('Sitemap: https://site.com/sitemap.xml', $content);
    }

    public function test_generates_multiple_user_agents(): void
    {
        RobotsTxt::forUserAgent('*')
            ->disallow('/admin');

        RobotsTxt::forUserAgent('Googlebot')
            ->disallow('/private')
            ->crawlDelay(1.0);

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
    }

    public function test_handles_complex_rule_scenarios(): void
    {
        RobotsTxt::forUserAgent('*')
            ->disallow('/admin')
            ->disallow('/private')
            ->allow('/admin/login') // Конфликт с /admin
            ->allow('/public')
            ->sitemap('https://site.com/sitemap.xml')
            ->sitemap('https://site.com/sitemap2.xml');

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Allow: /admin/login', $content);
        $this->assertStringNotContainsString('Disallow: /admin/login', $content);

        $sitemapCount = substr_count($content, 'Sitemap:');
        $this->assertEquals(2, $sitemapCount);
    }
}
