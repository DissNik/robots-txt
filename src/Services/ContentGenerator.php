<?php

namespace DissNik\RobotsTxt\Services;

class ContentGenerator
{
    public function __construct(private readonly DirectiveManager $directiveManager) {}

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
                    $lines[] = ucfirst((string) $directive).': '.$values;
                }
            } else {
                foreach ($values as $value) {
                    if (! empty($value)) {
                        $lines[] = ucfirst((string) $directive).': '.$value;
                    }
                }
            }
        }

        return trim(implode("\n\n", $lines));
    }
}
