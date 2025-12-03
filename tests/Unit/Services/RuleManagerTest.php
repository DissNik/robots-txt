<?php

namespace DissNik\RobotsTxt\Tests\Unit\Services;

use DissNik\RobotsTxt\Services\DirectiveManager;
use DissNik\RobotsTxt\Services\RuleManager;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RuleManagerTest extends TestCase
{
    private RuleManager $ruleManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ruleManager = new RuleManager(new DirectiveManager);
    }

    #[Test]
    public function creates_rule_manager_with_default_wildcard(): void
    {
        $rules = $this->ruleManager->getRuleObjects();
        $this->assertArrayHasKey('*', $rules);
    }

    #[Test]
    public function ensures_rule_exists(): void
    {
        $rule = $this->ruleManager->getRule('Googlebot');
        $this->assertTrue($this->ruleManager->hasUserAgent('Googlebot'));
        $this->assertEquals('Googlebot', $rule->getUserAgent());
    }

    #[Test]
    public function adds_directive_to_rule(): void
    {
        $rule = $this->ruleManager->getRule('*');
        $rule->directive('allow', '/');

        $directives = $this->ruleManager->getUserAgentDirectives('*');
        $this->assertContains('/', $directives['allow']);
    }

    #[Test]
    public function gets_rules(): void
    {
        $rule = $this->ruleManager->getRule('*');
        $rule->directive('allow', '/');

        $rules = $this->ruleManager->getRules();
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('*', $rules);
    }

    #[Test]
    public function clears_all_rules(): void
    {
        $this->ruleManager->getRule('Googlebot')->directive('disallow', '/admin');
        $this->assertCount(2, $this->ruleManager->getUserAgents());

        $this->ruleManager->clear();
        $agents = $this->ruleManager->getUserAgents();
        $this->assertCount(1, $agents);
        $this->assertContains('*', $agents);
    }

    #[Test]
    public function checks_conflicts(): void
    {
        $rule = $this->ruleManager->getRule('*');
        $rule->directive('allow', '/admin')
            ->directive('disallow', '/admin');

        $conflicts = $this->ruleManager->checkConflicts();
        $this->assertArrayHasKey('*', $conflicts);
    }

    #[Test]
    public function gets_user_agents(): void
    {
        $this->ruleManager->getRule('Googlebot');
        $this->ruleManager->getRule('Bingbot');

        $agents = $this->ruleManager->getUserAgents();
        $this->assertContains('Googlebot', $agents);
        $this->assertContains('Bingbot', $agents);
        $this->assertContains('*', $agents);
    }

    #[Test]
    public function removes_user_agent_directive(): void
    {
        $rule = $this->ruleManager->getRule('*');
        $rule->directive('disallow', '/admin')
            ->directive('disallow', '/private');

        $this->ruleManager->removeUserAgentDirective('*', 'disallow', '/admin');

        $directives = $this->ruleManager->getUserAgentDirectives('*');
        $this->assertNotContains('/admin', $directives['disallow']);
        $this->assertContains('/private', $directives['disallow']);
    }

    #[Test]
    public function removes_all_user_agent_directives(): void
    {
        $rule = $this->ruleManager->getRule('*');
        $rule->directive('disallow', '/admin')
            ->directive('disallow', '/private');

        $this->ruleManager->removeUserAgentDirective('*', 'disallow');

        $directives = $this->ruleManager->getUserAgentDirectives('*');
        $this->assertArrayNotHasKey('disallow', $directives);
    }
}
