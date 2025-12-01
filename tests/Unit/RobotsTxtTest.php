<?php

namespace DissNik\RobotsTxt\Tests\Unit;

use DissNik\RobotsTxt\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Support\Facades\App;

class RobotsTxtTest extends TestCase
{
    private RobotsTxt $robots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->robots = new RobotsTxt;

        $this->robots->clear();
    }

    public function test_creates_rule_for_user_agent(): void
    {
        $this->robots->forUserAgent('Googlebot')
            ->disallow('/admin')
            ->allow('/public');

        $content = $this->robots->generate();
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /public', $content);
    }

    public function test_adds_sitemaps(): void
    {
        $this->robots->sitemap('https://site.com/sitemap.xml')
            ->sitemap('https://site.com/sitemap2.xml');

        $sitemaps = $this->robots->getSitemaps();
        $this->assertCount(2, $sitemaps);
        $this->assertContains('https://site.com/sitemap.xml', $sitemaps);
    }

    public function test_uses_fluent_interface(): void
    {
        $result = $this->robots->forUserAgent('*')
            ->disallow('/admin')
            ->allow('/public');

        $this->assertInstanceOf(RobotsTxt::class, $result);
    }

    public function test_group_creates_rules(): void
    {
        $this->robots->group('Googlebot', function ($robots): void {
            $robots->disallow('/private')
                ->crawlDelay(1.0);
        });

        $content = $this->robots->generate();
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
        $this->assertStringContainsString('Crawl-delay: 1', $content);
    }

    public function test_conditional_rules_with_when(): void
    {
        $this->robots->when(true, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/admin');
        })->when(false, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/never');
        });

        $content = $this->robots->generate();
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringNotContainsString('Disallow: /never', $content);
    }

    public function test_conditional_rules_with_unless(): void
    {
        $this->robots->unless(false, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/admin');
        })->unless(true, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/never');
        });

        $content = $this->robots->generate();
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringNotContainsString('Disallow: /never', $content);
    }

    public function test_clears_all_rules(): void
    {
        $this->robots->forUserAgent('Googlebot')->disallow('/admin');
        $this->robots->forUserAgent('Bingbot')->disallow('/private');
        $this->robots->sitemap('https://site.com/sitemap.xml');

        $rulesBefore = $this->robots->getRules();
        $this->assertNotEmpty($rulesBefore);
        $this->assertNotEmpty($this->robots->getSitemaps());

        $this->robots->clear();

        $rulesAfter = $this->robots->getRules();
        $this->assertCount(1, $rulesAfter);
        $this->assertArrayHasKey('*', $rulesAfter);

        $this->assertEmpty($this->robots->getSitemaps());
    }

    public function test_block_all_method(): void
    {
        $this->robots->blockAll();

        $content = $this->robots->generate();
        $this->assertStringContainsString('Disallow: /', $content);
    }

    public function test_allow_all_method(): void
    {
        $this->robots->allowAll();

        $content = $this->robots->generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    public function test_applies_environment_rules_correctly(): void
    {
        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('testing');

        $this->robots->forEnvironment('testing', function ($robots): void {
            $robots->forUserAgent('*')->disallow('/test');
        });

        $content = $this->robots->generate();

        $this->assertStringContainsString('Disallow: /test', $content);
    }
}
