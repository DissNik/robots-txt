<?php

namespace DissNik\RobotsTxt\Tests\Unit;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;
use DissNik\RobotsTxt\Tests\TestCase;

class RobotsTxtRuleTest extends TestCase
{
    public function test_creates_rule_with_user_agent(): void
    {
        $rule = new RobotsTxtRule('Googlebot');

        $this->assertEquals('Googlebot', $rule->getUserAgent());
    }

    public function test_adds_disallow_rules(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->disallow('/admin')
            ->disallow('/private');

        $this->assertEquals(['/admin', '/private'], $rule->getDisallowRules());
    }

    public function test_adds_allow_rules(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->allow('/public')
            ->allow('/images');

        $this->assertEquals(['/public', '/images'], $rule->getAllowRules());
    }

    public function test_sets_crawl_delay(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->crawlDelay(2.5);

        $this->assertEquals(2.5, $rule->getCrawlDelay());
    }

    public function test_generates_correct_content(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->disallow('/admin')
            ->allow('/public')
            ->crawlDelay(1.0);

        $content = $rule->generate();

        $expected = "User-agent: *\nDisallow: /admin\nAllow: /public\nCrawl-delay: 1";
        $this->assertEquals($expected, $content);
    }

    public function test_resolves_conflicts_with_allow_priority(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->disallow('/admin')
            ->allow('/admin')
            ->disallow('/public')
            ->allow('/public/images');

        $content = $rule->generate();

        $this->assertStringNotContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /admin', $content);
    }

    public function test_detects_conflicts(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->disallow('/admin')
            ->allow('/admin');

        $conflicts = $rule->hasConflicts();

        $this->assertCount(1, $conflicts);
        $this->assertEquals('/admin', $conflicts[0]['disallow']);
        $this->assertEquals('/admin', $conflicts[0]['allow']);
    }

    public function test_merges_rules(): void
    {
        $rule1 = new RobotsTxtRule('*');
        $rule1->disallow('/admin')->crawlDelay(1.0);

        $rule2 = new RobotsTxtRule('*');
        $rule2->disallow('/private')->allow('/public');

        $rule1->merge($rule2);

        $this->assertContains('/admin', $rule1->getDisallowRules());
        $this->assertContains('/private', $rule1->getDisallowRules());
        $this->assertContains('/public', $rule1->getAllowRules());
        $this->assertEquals(1.0, $rule1->getCrawlDelay());
    }
}
