<?php

namespace DissNik\RobotsTxt\Rules;

use DissNik\RobotsTxt\Services\DirectiveManager;

class RobotsTxtRule
{
    /** @var array<string, mixed> */
    protected array $directives = [];

    public function __construct(
        protected string $userAgent = '*',
        private readonly DirectiveManager $directiveManager = new DirectiveManager()
    ) {
        //
    }

    public function directive(string $directive, mixed $value): self
    {
        $directive = $this->directiveManager->normalizeDirective($directive);

        if ($this->directiveManager->isUserAgentSingleDirective($directive)) {
            $this->directives[$directive] = $value;
        } else {
            if (! isset($this->directives[$directive])) {
                $this->directives[$directive] = [];
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($directive === 'allow' || $directive === 'disallow') {
                        $item = $this->directiveManager->normalizePath($item);
                    }
                    $this->directives[$directive][] = $item;
                }
            } else {
                if ($directive === 'allow' || $directive === 'disallow') {
                    $value = $this->directiveManager->normalizePath($value);
                }
                $this->directives[$directive][] = $value;
            }

            $this->directives[$directive] = array_unique($this->directives[$directive]);
        }

        return $this;
    }

    public function removeDirective(string $directive, mixed $value = null): self
    {
        $directive = $this->directiveManager->normalizeDirective($directive);

        if (! isset($this->directives[$directive])) {
            return $this;
        }

        if ($this->directiveManager->isUserAgentSingleDirective($directive)) {
            unset($this->directives[$directive]);
        } elseif ($value === null) {
            unset($this->directives[$directive]);
        } else {
            if ($directive === 'allow' || $directive === 'disallow') {
                $value = $this->directiveManager->normalizePath($value);
            }

            $this->directives[$directive] = array_filter(
                $this->directives[$directive],
                fn ($item): bool => $item !== $value
            );

            if (empty($this->directives[$directive])) {
                unset($this->directives[$directive]);
            }
        }

        return $this;
    }

    public function generate(): string
    {
        $lines = ["User-agent: {$this->userAgent}"];

        $this->resolveConflicts();

        $sortedDirectives = $this->directiveManager->sortUserAgentDirectives($this->directives);

        foreach ($sortedDirectives as $directive => $values) {
            if ($this->directiveManager->isUserAgentSingleDirective($directive)) {
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

        return implode("\n", $lines);
    }

    protected function resolveConflicts(): void
    {
        if (! isset($this->directives['allow']) || ! isset($this->directives['disallow'])) {
            return;
        }

        $allow = $this->directives['allow'];
        $disallow = $this->directives['disallow'];

        $this->directives['disallow'] = array_filter(
            $disallow,
            fn ($path): bool => ! in_array($path, $allow, true)
        );

        foreach (['allow', 'disallow'] as $directive) {
            if (isset($this->directives[$directive])) {
                usort($this->directives[$directive], function ($a, $b): int {
                    $depthA = substr_count($a, '/');
                    $depthB = substr_count($b, '/');

                    return $depthB <=> $depthA;
                });
            }
        }
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    public function getDirective(string $directive): mixed
    {
        $directive = $this->directiveManager->normalizeDirective($directive);

        return $this->directives[$directive] ?? null;
    }

    public function merge(self $rule): self
    {
        foreach ($rule->getDirectives() as $directive => $values) {
            $this->directive($directive, $values);
        }

        return $this;
    }

    /**
     * @return array<array{directive: string, value: mixed, single: bool}>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->directives as $directive => $values) {
            $isSingle = $this->directiveManager->isUserAgentSingleDirective($directive);

            if ($isSingle) {
                $result[] = [
                    'directive' => $directive,
                    'value' => $values,
                    'single' => true,
                ];
            } else {
                foreach ($values as $value) {
                    $result[] = [
                        'directive' => $directive,
                        'value' => $value,
                        'single' => false,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @return array<array{disallow: string, allow: string}>
     */
    public function hasConflicts(): array
    {
        $conflicts = [];

        $allow = $this->getDirective('allow') ?? [];
        $disallow = $this->getDirective('disallow') ?? [];

        if (! is_array($allow)) {
            $allow = [$allow];
        }
        if (! is_array($disallow)) {
            $disallow = [$disallow];
        }

        foreach ($disallow as $disallowPath) {
            if (in_array($disallowPath, $allow, true)) {
                $conflicts[] = [
                    'disallow' => $disallowPath,
                    'allow' => $disallowPath,
                ];
            }
        }

        return $conflicts;
    }

    public function isEmpty(): bool
    {
        return $this->directives === [];
    }
}
