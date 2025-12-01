<?php

namespace DissNik\RobotsTxt\Rules;

use Illuminate\Support\Collection;

class RobotsTxtRule
{
    /** @var array<int, string> */
    protected array $disallow = [];

    /** @var array<int, string> */
    protected array $allow = [];

    protected ?float $crawlDelay = null;

    public function __construct(protected string $userAgent = '*') {}

    public function disallow(string $path): self
    {
        if (! in_array($path, $this->disallow)) {
            $this->disallow[] = $path;
        }

        return $this;
    }

    public function allow(string $path): self
    {
        if (! in_array($path, $this->allow)) {
            $this->allow[] = $path;
        }

        return $this;
    }

    public function crawlDelay(float $delay): self
    {
        $this->crawlDelay = $delay;

        return $this;
    }

    public function generate(): string
    {
        $lines = ["User-agent: {$this->userAgent}"];

        [$disallowRules, $allowRules] = $this->resolveConflicts();

        foreach ($disallowRules as $path) {
            $lines[] = "Disallow: $path";
        }

        foreach ($allowRules as $path) {
            $lines[] = "Allow: $path";
        }

        if ($this->crawlDelay !== null) {
            $lines[] = "Crawl-delay: {$this->crawlDelay}";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{array<int, string>, array<int, string>}
     */
    protected function resolveConflicts(): array
    {
        $disallowRules = array_filter(
            $this->disallow,
            fn (string $disallow): bool => ! $this->hasAllowConflict($disallow)
        );

        return [array_values($disallowRules), array_unique($this->allow)];
    }

    protected function hasAllowConflict(string $disallowPath): bool
    {
        return Collection::make($this->allow)
            ->contains(fn (string $allowPath): bool => $this->pathsConflict($disallowPath, $allowPath));
    }

    protected function pathsConflict(string $path1, string $path2): bool
    {
        return $path1 === $path2
            || $path1 === '/'
            || $path2 === '/'
            || str_starts_with($path1, $path2)
            || str_starts_with($path2, $path1);
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return array<int, string>
     */
    public function getDisallowRules(): array
    {
        return $this->disallow;
    }

    /**
     * @return array<int, string>
     */
    public function getAllowRules(): array
    {
        return $this->allow;
    }

    public function getCrawlDelay(): ?float
    {
        return $this->crawlDelay;
    }

    public function merge(self $rule): self
    {
        foreach ($rule->getDisallowRules() as $path) {
            $this->disallow($path);
        }

        foreach ($rule->getAllowRules() as $path) {
            $this->allow($path);
        }

        if ($rule->getCrawlDelay() !== null) {
            $this->crawlDelay($rule->getCrawlDelay());
        }

        return $this;
    }

    /**
     * @return array<int, array{disallow: string, allow: string}>
     */
    public function hasConflicts(): array
    {
        $conflicts = [];

        foreach ($this->disallow as $disallow) {
            foreach ($this->allow as $allow) {
                if ($this->pathsConflict($disallow, $allow)) {
                    $conflicts[] = [
                        'disallow' => $disallow,
                        'allow' => $allow,
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * @return array<int, array{allow: bool, path: string}>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->disallow as $path) {
            $result[] = ['allow' => false, 'path' => $path];
        }

        foreach ($this->allow as $path) {
            $result[] = ['allow' => true, 'path' => $path];
        }

        return $result;
    }

    /**
     * @return array<int, array{allow: bool, path: string}>
     */
    public function getRulesArray(): array
    {
        return $this->toArray();
    }
}
