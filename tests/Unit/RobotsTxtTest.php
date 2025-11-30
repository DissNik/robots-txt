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

        $rules = $this->robots->getRules();
        $this->assertArrayHasKey('Googlebot', $rules);
        $this->assertContains('/admin', $rules['Googlebot']->getDisallowRules());
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

        $rules = $this->robots->getRules();
        $this->assertArrayHasKey('Googlebot', $rules);
        $this->assertContains('/private', $rules['Googlebot']->getDisallowRules());
    }

    public function test_conditional_rules_with_when(): void
    {
        $this->robots->when(true, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/admin');
        })->when(false, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/never');
        });

        $rules = $this->robots->getRules();
        $this->assertContains('/admin', $rules['*']->getDisallowRules());
        $this->assertNotContains('/never', $rules['*']->getDisallowRules());
    }

    public function test_conditional_rules_with_unless(): void
    {
        $this->robots->unless(false, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/admin');
        })->unless(true, function ($robots): void {
            $robots->forUserAgent('*')->disallow('/never');
        });

        $rules = $this->robots->getRules();
        $this->assertContains('/admin', $rules['*']->getDisallowRules());
        $this->assertNotContains('/never', $rules['*']->getDisallowRules());
    }

    public function test_clears_all_rules(): void
    {
        $this->robots->forUserAgent('*')->disallow('/admin');
        $this->robots->sitemap('https://site.com/sitemap.xml');

        $this->assertNotEmpty($this->robots->getRules());
        $this->assertNotEmpty($this->robots->getSitemaps());

        $this->robots->clear();

        $this->assertEmpty($this->robots->getRules());
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
