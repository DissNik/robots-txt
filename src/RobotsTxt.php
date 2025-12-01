<?php

namespace DissNik\RobotsTxt;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Rules\RobotsTxtRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class RobotsTxt implements RobotsTxtInterface
{
    /** @var array<string, RobotsTxtRule> */
    public array $directRules = [];

    /** @var array<int, string> */
    public array $directSitemaps = [];

    /** @var array<string, array{environments: array<int, string>, callback: callable}> */
    protected array $environmentRules = [];

    protected RobotsTxtRule $currentRule;

    public function __construct()
    {
        $this->setupDefaultRules();
        $this->forUserAgent('*');
    }

    protected function setupDefaultRules(): void
    {
        $this->forEnvironment('local', function (): void {
            $this->blockAll();
        });

        $this->forEnvironment('production', function (): void {
            $this->allowAll();
        });
    }

    public function forUserAgent(string $userAgent): self
    {
        if (! isset($this->directRules[$userAgent])) {
            $this->directRules[$userAgent] = new RobotsTxtRule($userAgent);
        }

        $this->currentRule = $this->directRules[$userAgent];

        return $this;
    }

    public function disallow(string $path): self
    {
        $this->getCurrentRule()->disallow($path);

        return $this;
    }

    public function allow(string $path): self
    {
        $this->getCurrentRule()->allow($path);

        return $this;
    }

    public function crawlDelay(float $delay): self
    {
        $this->getCurrentRule()->crawlDelay($delay);

        return $this;
    }

    public function sitemap(string $url): self
    {
        $this->directSitemaps[] = $url;

        return $this;
    }

    public function group(string $userAgent, callable $callback): self
    {
        return $this->withCurrentRule($userAgent, $callback);
    }

    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function unless(bool $condition, callable $callback): self
    {
        if (! $condition) {
            $callback($this);
        }

        return $this;
    }

    public function forEnvironment(string|array $environments, callable $callback): self
    {
        $environments = (array) $environments;
        $key = implode('|', $environments);

        if (! isset($this->environmentRules[$key])) {
            $this->environmentRules[$key] = [
                'environments' => $environments,
                'callback' => $callback,
            ];
        }

        return $this;
    }

    public function generate(): string
    {
        $cacheKey = 'robots_txt_content';
        $cacheDuration = config('robots-txt.cache.duration', 3600);

        if (config('robots-txt.cache.enabled', true)) {
            return Cache::remember($cacheKey, $cacheDuration, fn (): string => $this->buildContent());
        }

        return $this->buildContent();
    }

    protected function buildContent(): string
    {
        $rules = $this->buildRules();
        $sitemaps = $this->buildSitemaps();

        $content = $rules->map(fn ($rule): string => $rule->generate())->implode("\n\n");

        if ($sitemaps->isNotEmpty()) {
            $content .= "\n\n".$sitemaps->implode("\n");
        }

        return trim($content);
    }

    /**
     * @return Collection<int, RobotsTxtRule>
     */
    protected function buildRules(): Collection
    {
        /** @var Collection<int, RobotsTxtRule> $rules */
        $rules = Collection::make();

        $this->applyEnvironmentRules($rules);

        $this->applyDirectRules($rules);

        return $rules;
    }

    /**
     * @param  Collection<int, RobotsTxtRule>  $rules
     */
    protected function applyEnvironmentRules(Collection $rules): void
    {
        $currentEnv = App::environment();

        // rector-ignore-next-line RemoveUnusedNonEmptyArrayBeforeForeachRector
        if (empty($this->environmentRules)) {
            return;
        }

        foreach ($this->environmentRules as $rule) {
            if (in_array($currentEnv, $rule['environments'])) {
                $tempRobots = new RobotsTxt;
                $tempRobots->directRules = [];

                $rule['callback']($tempRobots);

                // rector-ignore-next-line RemoveUnusedNonEmptyArrayBeforeForeachRector
                if (! empty($tempRobots->directRules)) {
                    foreach ($tempRobots->directRules as $tempRule) {
                        $existingRule = $rules->first(fn (RobotsTxtRule $r): bool => $r->getUserAgent() === $tempRule->getUserAgent());

                        if ($existingRule) {
                            $existingRule->merge($tempRule);
                        } else {
                            $rules->push(clone $tempRule);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<int, RobotsTxtRule>  $rules
     */
    protected function applyDirectRules(Collection $rules): void
    {
        // rector-ignore-next-line RemoveUnusedNonEmptyArrayBeforeForeachRector
        if (empty($this->directRules)) {
            return;
        }

        foreach ($this->directRules as $rule) {
            $existingRule = $rules->first(fn (RobotsTxtRule $r): bool => $r->getUserAgent() === $rule->getUserAgent());

            if ($existingRule) {
                $existingRule->merge($rule);
            } else {
                $rules->push(clone $rule);
            }
        }
    }

    /**
     * @return Collection<int, string>
     */
    protected function buildSitemaps(): Collection
    {
        return Collection::make($this->directSitemaps)
            ->unique()
            ->values()
            ->map(fn ($url): string => "Sitemap: $url");
    }

    protected function getCurrentRule(): RobotsTxtRule
    {
        return $this->currentRule;
    }

    protected function withCurrentRule(?string $userAgent, callable $callback): self
    {
        $previous = $this->currentRule;

        if ($userAgent) {
            $this->forUserAgent($userAgent);
        }

        $callback($this);
        $this->currentRule = $previous;

        return $this;
    }

    // Вспомогательные методы
    public function blockAll(): self
    {
        return $this->forUserAgent('*')->disallow('/');
    }

    public function allowAll(): self
    {
        return $this->forUserAgent('*')->allow('/');
    }

    public function clear(): self
    {
        $this->directRules = [];
        $this->directSitemaps = [];
        $this->environmentRules = [];
        $this->forUserAgent('*');

        Cache::forget('robots_txt_content');

        return $this;
    }

    public function clearCache(): bool
    {
        return Cache::forget('robots_txt_content');
    }

    /**
     * @return array<string, array<int, array{allow: bool, path: string}>>
     */
    public function getRules(): array
    {
        $result = [];

        // rector-ignore-next-line SimplifyEmptyCheckOnEmptyArrayRector
        if (empty($this->directRules)) {
            return $result;
        }

        foreach ($this->directRules as $userAgent => $rule) {
            $result[$userAgent] = $this->convertRuleToArray($rule);
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function getSitemaps(): array
    {
        return $this->directSitemaps;
    }

    /**
     * @return array<string, array<string, array<int, array{allow: bool, path: string}>>>
     */
    public function getEnvironmentRules(): array
    {
        $result = [];

        // rector-ignore-next-line SimplifyEmptyCheckOnEmptyArrayRector
        if (empty($this->environmentRules)) {
            return $result;
        }

        foreach ($this->environmentRules as $key => $ruleData) {
            $tempRobots = new RobotsTxt;
            $tempRobots->directRules = [];
            $ruleData['callback']($tempRobots);

            $environmentRules = [];

            // rector-ignore-next-line RemoveUnusedNonEmptyArrayBeforeForeachRector
            if (! empty($tempRobots->directRules)) {
                foreach ($tempRobots->directRules as $userAgent => $rule) {
                    $environmentRules[$userAgent] = $this->convertRuleToArray($rule);
                }
            }

            $result[$key] = $environmentRules;
        }

        return $result;
    }

    /**
     * @return array<int, array{allow: bool, path: string}>
     */
    private function convertRuleToArray(RobotsTxtRule $rule): array
    {
        return $rule->toArray();
    }

    /**
     * @return array<string, bool>
     */
    public function checkConflicts(): array
    {
        $conflicts = [];

        // rector-ignore-next-line SimplifyEmptyCheckOnEmptyArrayRector
        if (empty($this->directRules)) {
            return $conflicts;
        }

        foreach ($this->directRules as $userAgent => $rule) {
            if ($rule->hasConflicts()) {
                $conflicts[$userAgent] = true;
            }
        }

        return $conflicts;
    }
}
