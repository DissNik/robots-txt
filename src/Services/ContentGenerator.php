<?php

namespace DissNik\RobotsTxt\Services;

class ContentGenerator
{
    private DirectiveManager $directiveManager;

    public function __construct(DirectiveManager $directiveManager)
    {
        $this->directiveManager = $directiveManager;
    }

    public function generate(array $userAgentRules, array $globalDirectives): string
    {
        $lines = [];

        foreach ($userAgentRules as $rule) {
            $ruleContent = $rule->generate();
            if ($ruleContent !== "User-agent: {$rule->getUserAgent()}") {
                $lines[] = $ruleContent;
            }
        }

        $sortedGlobalDirectives = $this->directiveManager->sortGlobalDirectives($globalDirectives);

        foreach ($sortedGlobalDirectives as $directive => $values) {
            if ($this->directiveManager->isGlobalSingleDirective($directive)) {
                if (! empty($values)) {
                    $lines[] = ucfirst($directive).': '.$values;
                }
            } else {
                foreach ($values as $value) {
                    if (! empty($value)) {
                        $lines[] = ucfirst($directive).': '.$value;
                    }
                }
            }
        }

        return trim(implode("\n\n", $lines));
    }
}
