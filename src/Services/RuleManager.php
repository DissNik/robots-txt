<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Services;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;

final class RuleManager
{
    /** @var array<string, RobotsTxtRule> */
    private array $userAgentRules = [];

    public function __construct(private readonly DirectiveManager $directiveManager)
    {
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

    /**
     * @return array<string, array<array{directive: string, value: mixed, single: bool}>>
     */
    public function getRules(): array
    {
        $result = [];
        foreach ($this->userAgentRules as $userAgent => $rule) {
            $result[$userAgent] = $rule->toArray();
        }

        return $result;
    }

    /**
     * @return array<string, RobotsTxtRule>
     */
    public function getRuleObjects(): array
    {
        return $this->userAgentRules;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserAgentDirectives(string $userAgent): array
    {
        return $this->ensureRuleExists($userAgent)->getDirectives();
    }

    public function clear(): void
    {
        $this->userAgentRules = [];
        $this->ensureRuleExists('*');
    }

    /**
     * @return array<string, array<array{disallow: string, allow: string}>>
     */
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

    /**
     * @return array<string>
     */
    public function getUserAgents(): array
    {
        return array_keys($this->userAgentRules);
    }

    public function hasUserAgent(string $userAgent): bool
    {
        return isset($this->userAgentRules[$userAgent]);
    }

    public function removeUserAgentDirective(string $userAgent, string $directive, mixed $value = null): self
    {
        if (! isset($this->userAgentRules[$userAgent])) {
            return $this;
        }

        $this->userAgentRules[$userAgent]->removeDirective($directive, $value);

        return $this;
    }
}
