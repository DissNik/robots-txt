<?php

namespace DissNik\RobotsTxt;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Rules\RobotsTxtRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class RobotsTxt implements RobotsTxtInterface
{
    public array $directRules = []; // changed to public for simplicity

    public array $directSitemaps = []; // changed to public

    protected array $environmentRules = [];

    protected ?RobotsTxtRule $currentRule = null;

    public function __construct()
    {
        $this->setupDefaultRules();
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

    public function forEnvironment($environments, callable $callback): self
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

        $content = $rules->map(fn ($rule) => $rule->generate())->implode("\n\n");

        if ($sitemaps->isNotEmpty()) {
            $content .= "\n\n".$sitemaps->implode("\n");
        }

        return trim($content);
    }

    protected function buildRules(): Collection
    {
        $rules = Collection::make();

        // 1. Применяем environment rules
        $this->applyEnvironmentRules($rules);

        // 2. Добавляем direct rules
        $this->applyDirectRules($rules);

        return $rules;
    }

    protected function applyEnvironmentRules(Collection $rules): void
    {
        $currentEnv = App::environment();

        foreach ($this->environmentRules as $rule) {
            if (in_array($currentEnv, $rule['environments'])) {
                // Создаем временный экземпляр для environment rules
                $tempRobots = new RobotsTxt;
                $tempRobots->directRules = []; // Очищаем direct rules

                // Выполняем callback на временном объекте
                $rule['callback']($tempRobots);

                // Переносим правила в основную коллекцию
                foreach ($tempRobots->directRules as $userAgent => $tempRule) {
                    if ($rules->has($userAgent)) {
                        $rules->get($userAgent)->merge($tempRule);
                    } else {
                        $rules->put($userAgent, clone $tempRule);
                    }
                }
            }
        }
    }

    protected function applyDirectRules(Collection $rules): void
    {
        foreach ($this->directRules as $userAgent => $rule) {
            if ($rules->has($userAgent)) {
                $rules->get($userAgent)->merge($rule);
            } else {
                $rules->put($userAgent, clone $rule);
            }
        }
    }

    protected function buildSitemaps(): Collection
    {
        return Collection::make($this->directSitemaps)
            ->unique()
            ->map(fn ($url): string => "Sitemap: $url");
    }

    protected function getCurrentRule(): RobotsTxtRule
    {
        if (! $this->currentRule instanceof RobotsTxtRule) {
            $this->forUserAgent('*');
        }

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
        $this->currentRule = null;

        Cache::forget('robots_txt_content');

        return $this;
    }

    public function clearCache(): bool
    {
        return Cache::forget('robots_txt_content');
    }

    // Getters
    public function getRules(): array
    {
        return $this->directRules;
    }

    public function getSitemaps(): array
    {
        return $this->directSitemaps;
    }

    public function getEnvironmentRules(): array
    {
        return $this->environmentRules;
    }

    public function checkConflicts(): array
    {
        return Collection::make($this->directRules)
            ->map(fn ($rule) => $rule->hasConflicts())
            ->filter()
            ->all();
    }
}
