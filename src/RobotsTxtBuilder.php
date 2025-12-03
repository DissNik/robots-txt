<?php

namespace DissNik\RobotsTxt;

use BadMethodCallException;
use Closure;
use DissNik\RobotsTxt\Contexts\EnvironmentContext;
use DissNik\RobotsTxt\Contexts\UserAgentContext;
use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Services\ConfigLoader;
use DissNik\RobotsTxt\Services\ContentGenerator;
use DissNik\RobotsTxt\Services\DirectiveManager;
use DissNik\RobotsTxt\Services\EnvironmentRuleApplier;
use DissNik\RobotsTxt\Services\RuleManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Conditionable;

class RobotsTxtBuilder implements RobotsTxtInterface
{
    use Conditionable;

    /** @var array<string, mixed> */
    private array $globalDirectives = [];

    public function __construct(
        private ConfigLoader $configLoader,
        private RuleManager $ruleManager,
        private DirectiveManager $directiveManager,
        private EnvironmentRuleApplier $environmentApplier,
        private ContentGenerator $contentGenerator
    ) {
        $this->loadConfig();
    }

    protected function loadConfig(): void
    {
        $config = $this->configLoader->loadForCurrentEnvironment();

        $this->loadGlobalDirectives($config['global_directives'] ?? []);
        $this->loadUserAgentRules($config['user_agent_rules'] ?? []);
    }

    protected function loadGlobalDirectives(array $directives): void
    {
        foreach ($directives as $directive => $value) {
            if (! empty($value)) {
                $this->directive($directive, $value);
            }
        }
    }

    protected function loadUserAgentRules(array $rules): void
    {
        foreach ($rules as $userAgent => $agentRules) {
            if (! is_array($agentRules)) {
                continue;
            }

            $this->loadRulesForUserAgent($userAgent, $agentRules);
        }
    }

    protected function loadRulesForUserAgent(string $userAgent, array $rules): void
    {
        $this->forUserAgent($userAgent, function ($context) use ($rules): void {
            foreach ($rules as $directive => $values) {
                if (! empty($values)) {
                    if (is_array($values)) {
                        foreach ($values as $value) {
                            $context->directive($directive, $value);
                        }
                    } else {
                        $context->directive($directive, $values);
                    }
                }
            }
        });
    }

    public function forUserAgent(string $userAgent, callable $callback): self
    {
        $rule = $this->ruleManager->ensureRuleExists($userAgent);
        $context = new UserAgentContext($rule, $this->directiveManager);

        $callback($context);

        return $this;
    }

    public function forEnvironment(string|array $environments, callable $callback): self
    {
        $environments = (array) $environments;

        $this->environmentApplier->addCallback($environments, function (RobotsTxtInterface $robots) use ($callback): void {
            $context = new EnvironmentContext($robots, $this->environmentApplier, []);
            $callback($context);
        });

        return $this;
    }

    public function directive(string $directive, $value): self
    {
        $directive = $this->directiveManager->normalizeDirective($directive);

        $this->directiveManager->addGlobalDirective($directive, $value, $this->globalDirectives);

        return $this;
    }

    public function sitemap(string $url): self
    {
        $this->directive('sitemap', $url);

        return $this;
    }

    public function host(string $host): self
    {
        $this->directive('host', $host);

        return $this;
    }

    public function cleanParam(string $param, ?string $path = null): self
    {
        $value = $path ? "{$param} {$path}" : $param;
        $this->directive('clean-param', $value);

        return $this;
    }

    public function blockAll(): self
    {
        return $this->forUserAgent('*', function ($context): void {
            $context->blockAll();
        });
    }

    public function allowAll(): self
    {
        return $this->forUserAgent('*', function ($context): void {
            $context->allowAll();
        });
    }

    public function removeDirective(string $directive, $value = null): self
    {
        $this->directiveManager->removeGlobalDirective($directive, $value, $this->globalDirectives);

        return $this;
    }

    public function removeUserAgentDirective(string $userAgent, string $directive, $value = null): self
    {
        $this->ruleManager->removeUserAgentDirective($userAgent, $directive, $value);

        return $this;
    }

    public function generate(): string
    {
        if ($this->shouldCache()) {
            return $this->getCachedContent();
        }

        return $this->buildContent();
    }

    protected function shouldCache(): bool
    {
        $cacheConfig = $this->configLoader->getCacheConfig();

        return $cacheConfig['enabled'] ?? true;
    }

    protected function getCachedContent(): string
    {
        $cacheConfig = $this->configLoader->getCacheConfig();
        $cacheKey = 'robots_txt_content_'.App::environment();
        $cacheDuration = $cacheConfig['duration'] ?? 3600;

        return Cache::remember($cacheKey, $cacheDuration, fn (): string => $this->buildContent());
    }

    protected function buildContent(): string
    {
        $this->environmentApplier->applyCallbacks($this);

        return $this->contentGenerator->generate(
            $this->ruleManager->getRuleObjects(),
            $this->globalDirectives
        );
    }

    public function clear(): self
    {
        $this->globalDirectives = [];
        $this->ruleManager->clear();
        $this->environmentApplier->clearCallbacks();
        $this->clearCache();

        return $this;
    }

    public function reset(): self
    {
        $this->clear();
        $this->loadConfig();

        return $this;
    }

    public function clearCache(): bool
    {
        return Cache::forget('robots_txt_content_'.App::environment());
    }

    public function getRules(): array
    {
        return $this->ruleManager->getRules();
    }

    public function getSitemaps(): array
    {
        $sitemaps = $this->globalDirectives['sitemap'] ?? [];

        return is_array($sitemaps) ? $sitemaps : [];
    }

    public function getDirectives(): array
    {
        return $this->globalDirectives;
    }

    public function getUserAgentDirectives(string $userAgent): array
    {
        return $this->ruleManager->getUserAgentDirectives($userAgent);
    }

    public function getEnvironmentRules(): array
    {
        $callbacks = $this->environmentApplier->getCallbacks();
        $result = [];

        foreach ($callbacks as $key => $callbackData) {
            $result[$key] = [
                'environments' => $callbackData['environments'],
                'callback' => $this->getCallbackDescription($callbackData['callback']),
            ];
        }

        return $result;
    }

    protected function getCallbackDescription(callable $callback): string
    {
        if (is_array($callback) && count($callback) === 2) {
            return $callback[0]::class.'::'.$callback[1];
        }

        if ($callback instanceof Closure) {
            return 'Closure';
        }

        return 'Unknown';
    }

    public function checkConflicts(): array
    {
        return $this->ruleManager->checkConflicts();
    }

    public function getUserAgents(): array
    {
        return $this->ruleManager->getUserAgents();
    }

    public function hasUserAgent(string $userAgent): bool
    {
        return $this->ruleManager->hasUserAgent($userAgent);
    }

    /**
     * @throws BadMethodCallException
     */
    public function allow(string $path): self
    {
        throw new BadMethodCallException(
            'Method allow() can only be called inside forUserAgent() callback. '.
            'Usage: RobotsTxt::forUserAgent(\'*\', fn($ctx) => $ctx->allow(...))'
        );
    }

    /**
     * @throws BadMethodCallException
     */
    public function disallow(string $path): self
    {
        throw new BadMethodCallException(
            'Method disallow() can only be called inside forUserAgent() callback. '.
            'Usage: RobotsTxt::forUserAgent(\'*\', fn($ctx) => $ctx->disallow(...))'
        );
    }

    /**
     * @throws BadMethodCallException
     */
    public function crawlDelay(float $delay): self
    {
        throw new BadMethodCallException(
            'Method crawlDelay() can only be called inside forUserAgent() callback. '.
            'Usage: RobotsTxt::forUserAgent(\'*\', fn($ctx) => $ctx->crawlDelay(...))'
        );
    }

    public function getRuleManager(): RuleManager
    {
        return $this->ruleManager;
    }

    public function getDirectiveManager(): DirectiveManager
    {
        return $this->directiveManager;
    }
}
