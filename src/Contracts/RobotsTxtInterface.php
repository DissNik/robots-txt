<?php

namespace DissNik\RobotsTxt\Contracts;

interface RobotsTxtInterface
{
    // Fluent interface методы
    public function forUserAgent(string $userAgent): self;

    public function disallow(string $path): self;

    public function allow(string $path): self;

    public function crawlDelay(float $delay): self;

    public function sitemap(string $url): self;

    // Группировка и условия
    public function group(string $userAgent, callable $callback): self;

    public function when(bool $condition, callable $callback): self;

    public function unless(bool $condition, callable $callback): self;

    public function forEnvironment($environments, callable $callback): self;

    // Основные методы
    public function generate(): string;

    public function clear(): self;

    public function clearCache(): bool;

    // Отладочные методы
    public function getRules(): array;

    public function getSitemaps(): array;

    public function getEnvironmentRules(): array;
}
