<?php

namespace DissNik\RobotsTxt\Services;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;

class ContentGenerator
{
    public function __construct(private readonly DirectiveManager $directiveManager)
    {
        //
    }

    /**
     * @param  array<int, RobotsTxtRule>  $userAgentRules
     * @param  array<string, mixed>  $globalDirectives
     */
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
