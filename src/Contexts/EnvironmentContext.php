<?php

namespace DissNik\RobotsTxt\Contexts;

use BadMethodCallException;
use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Services\EnvironmentRuleApplier;
use Illuminate\Support\Traits\Conditionable;

class EnvironmentContext
{
    use Conditionable;

    /** @var array<string> */
    private array $environments = [];

    public function __construct(
        private readonly RobotsTxtInterface $robotsManager,
        private readonly EnvironmentRuleApplier $environmentApplier,
    ) {
        //
    }

    public function forUserAgent(string $userAgent, callable $callback): self
    {
        $this->robotsManager->forUserAgent($userAgent, $callback);

        return $this;
    }

    public function sitemap(string $url): self
    {
        $this->robotsManager->sitemap($url);

        return $this;
    }

    public function host(string $host): self
    {
        $this->robotsManager->host($host);

        return $this;
    }

    public function cleanParam(string $param, ?string $path = null): self
    {
        $this->robotsManager->cleanParam($param, $path);

        return $this;
    }

    public function directive(string $directive, mixed $value): self
    {
        $this->robotsManager->directive($directive, $value);

        return $this;
    }

    public function blockAll(): self
    {
        $this->robotsManager->blockAll();

        return $this;
    }

    public function allowAll(): self
    {
        $this->robotsManager->allowAll();

        return $this;
    }

    public function apply(): void
    {
        $this->environmentApplier->addCallback(
            $this->environments,
            function (RobotsTxtInterface $robots): void {
                // All methods have already been called during configuration
                // This callback ensures the environment is registered
            },
        );
    }

    /**
     * @param array<mixed> $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (in_array($method, ['allow', 'disallow', 'crawlDelay'])) {
            throw new BadMethodCallException(
                "Method {$method}() cannot be called directly on EnvironmentContext. " .
                'You must call it inside a forUserAgent() callback: ' .
                "\$env->forUserAgent('*', fn(\$ctx) => \$ctx->{$method}(...))",
            );
        }

        if (method_exists($this, $method)) {
            return $this->{$method}(...$parameters);
        }

        if (method_exists($this->robotsManager, $method)) {
            $result = $this->robotsManager->{$method}(...$parameters);

            if ($result instanceof RobotsTxtInterface) {
                return $this;
            }

            return $result;
        }

        throw new BadMethodCallException(
            "Method {$method} does not exist on " . static::class,
        );
    }
}
