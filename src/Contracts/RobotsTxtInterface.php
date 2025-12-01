<?php

namespace DissNik\RobotsTxt\Contracts;

interface RobotsTxtInterface
{
    public function forUserAgent(string $userAgent): self;

    public function disallow(string $path): self;

    public function allow(string $path): self;

    public function crawlDelay(float $delay): self;

    public function sitemap(string $url): self;

    public function group(string $userAgent, callable $callback): self;

    public function when(bool $condition, callable $callback): self;

    public function unless(bool $condition, callable $callback): self;

    /**
     * @param  string|array<int, string>  $environments
     */
    public function forEnvironment(string|array $environments, callable $callback): self;

    public function generate(): string;

    public function clear(): self;

    public function clearCache(): bool;

    /**
     * @return array<string, array<int, array{allow: bool, path: string}>>
     */
    public function getRules(): array;

    /**
     * @return array<int, string>
     */
    public function getSitemaps(): array;

    /**
     * @return array<string, array<string, array<int, array{allow: bool, path: string}>>>
     */
    public function getEnvironmentRules(): array;
}
