<?php

namespace DissNik\RobotsTxt\Services;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;

class RuleManager
{
    /** @var array<string, RobotsTxtRule> */
    private array $userAgentRules = [];

    private DirectiveManager $directiveManager;

    public function __construct(DirectiveManager $directiveManager)
    {
        $this->directiveManager = $directiveManager;
        $this->ensureRuleExists('*');
    }

    public function ensureRuleExists(string $userAgent): RobotsTxtRule
    {
        if (! isset($this->userAgentRules[$userAgent])) {
            $this->userAgentRules[$userAgent] = new RobotsTxtRule($userAgent, $this->directiveManager);
        }

        return $this->userAgentRules[$userAgent];
    }

    public function getRule(string $userAgent): RobotsTxtRule
    {
        return $this->ensureRuleExists($userAgent);
    }

    public function getRules(): array
    {
        $result = [];
        foreach ($this->userAgentRules as $userAgent => $rule) {
            $result[$userAgent] = $rule->toArray();
        }

        return $result;
    }

    public function getRuleObjects(): array
    {
        return $this->userAgentRules;
    }

    public function getUserAgentDirectives(string $userAgent): array
    {
        return $this->ensureRuleExists($userAgent)->getDirectives() ?? [];
    }

    public function clear(): void
    {
        $this->userAgentRules = [];
        $this->ensureRuleExists('*');
    }

    public function checkConflicts(): array
    {
        $conflicts = [];
        foreach ($this->userAgentRules as $userAgent => $rule) {
            $ruleConflicts = $rule->hasConflicts();
            if (! empty($ruleConflicts)) {
                $conflicts[$userAgent] = $ruleConflicts;
            }
        }

        return $conflicts;
    }

    public function getUserAgents(): array
    {
        return array_keys($this->userAgentRules);
    }

    public function hasUserAgent(string $userAgent): bool
    {
        return isset($this->userAgentRules[$userAgent]);
    }

    public function removeUserAgentDirective(string $userAgent, string $directive, $value = null): self
    {
        if (! isset($this->userAgentRules[$userAgent])) {
            return $this;
        }

        $this->userAgentRules[$userAgent]->removeDirective($directive, $value);

        return $this;
    }
}
