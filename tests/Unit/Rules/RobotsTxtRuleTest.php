<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Unit\Rules;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtRuleTest extends TestCase
{
    #[Test]
    public function creates_rule_with_user_agent(): void
    {
        $rule = new RobotsTxtRule('*');
        $this->assertEquals('*', $rule->getUserAgent());
    }

    #[Test]
    public function adds_directives(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin')
            ->directive('allow', '/public');

        $directives = $rule->getDirectives();
        $this->assertArrayHasKey('disallow', $directives);
        $this->assertArrayHasKey('allow', $directives);
    }

    #[Test]
    public function removes_directives(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin')
            ->directive('disallow', '/private')
            ->removeDirective('disallow', '/admin');

        $directives = $rule->getDirectives();
        $this->assertContains('/private', $directives['disallow']);
        $this->assertNotContains('/admin', $directives['disallow']);
    }

    #[Test]
    public function generates_content(): void
    {
        $rule = new RobotsTxtRule('Googlebot');
        $rule->directive('disallow', '/admin')
            ->directive('crawl-delay', '2.0');

        $content = $rule->generate();
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Crawl-delay: 2.0', $content);
    }

    #[Test]
    public function merges_rules(): void
    {
        $rule1 = new RobotsTxtRule('*');
        $rule1->directive('disallow', '/admin');

        $rule2 = new RobotsTxtRule('*');
        $rule2->directive('allow', '/public');

        $rule1->merge($rule2);
        $directives = $rule1->getDirectives();

        $this->assertArrayHasKey('disallow', $directives);
        $this->assertArrayHasKey('allow', $directives);
    }

    #[Test]
    public function converts_to_array(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin')
            ->directive('crawl-delay', '1.0');

        $array = $rule->toArray();
        $this->assertIsArray($array);
        $this->assertNotEmpty($array);
    }

    #[Test]
    public function checks_for_conflicts(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin')
            ->directive('allow', '/admin');

        $conflicts = $rule->hasConflicts();
        $this->assertCount(1, $conflicts);
    }

    #[Test]
    public function checks_if_empty(): void
    {
        $rule = new RobotsTxtRule('*');
        $this->assertTrue($rule->isEmpty());

        $rule->directive('disallow', '/admin');
        $this->assertFalse($rule->isEmpty());
    }
}
