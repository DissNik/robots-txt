<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Contexts;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;
use DissNik\RobotsTxt\Services\DirectiveManager;
use Illuminate\Support\Traits\Conditionable;

final class UserAgentContext
{
    use Conditionable;

    public function __construct(private RobotsTxtRule $rule, private DirectiveManager $directiveManager)
    {
        //
    }

    public function allow(string $path): self
    {
        $path = $this->directiveManager->normalizePath($path);
        $this->rule->directive('allow', $path);

        return $this;
    }

    public function disallow(string $path): self
    {
        $path = $this->directiveManager->normalizePath($path);
        $this->rule->directive('disallow', $path);

        return $this;
    }

    public function crawlDelay(float $delay): self
    {
        $this->rule->directive('crawl-delay', (string) $delay);

        return $this;
    }

    public function cleanParam(string $param, ?string $path = null): self
    {
        $value = $path ? "{$param} {$path}" : $param;
        $this->rule->directive('clean-param', $value);

        return $this;
    }

    public function directive(string $directive, mixed $value): self
    {
        $this->rule->directive($directive, $value);

        return $this;
    }

    public function blockAll(): self
    {
        return $this->disallow('/');
    }

    public function allowAll(): self
    {
        return $this->allow('/');
    }

    public function getRule(): RobotsTxtRule
    {
        return $this->rule;
    }

    public function removeDirective(string $directive, mixed $value = null): self
    {
        $this->rule->removeDirective($directive, $value);

        return $this;
    }
}
