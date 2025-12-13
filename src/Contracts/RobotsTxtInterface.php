<?php

namespace DissNik\RobotsTxt\Contracts;

use DissNik\RobotsTxt\Builders\RobotsTxtBuilder;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;

#[Bind(RobotsTxtBuilder::class)]
#[Scoped]
interface RobotsTxtInterface
{
    public function generate(): string;

    public function clear(): self;

    public function reset(): self;

    public function clearCache(): bool;

    public function sitemap(string $url): self;

    public function host(string $host): self;

    public function cleanParam(string $param, ?string $path = null): self;

    public function directive(string $directive, $value): self;

    public function forUserAgent(string $userAgent, callable $callback): self;

    public function forEnvironment(string|array $environments, callable $callback): self;

    public function blockAll(): self;

    public function allowAll(): self;

    public function removeDirective(string $directive, $value = null): self;

    public function removeUserAgentDirective(string $userAgent, string $directive, $value = null): self;

    public function getRules(): array;

    public function getSitemaps(): array;

    public function getDirectives(): array;

    public function getUserAgentDirectives(string $userAgent): array;

    public function getEnvironmentRules(): array;

    public function checkConflicts(): array;

    public function getUserAgents(): array;

    public function hasUserAgent(string $userAgent): bool;
}
