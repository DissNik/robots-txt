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

    public function directive(string $directive, mixed $value): self;

    public function forUserAgent(string $userAgent, callable $callback): self;

    /**
     * @param string|array<string> $environments
     */
    public function forEnvironment(string|array $environments, callable $callback): self;

    public function blockAll(): self;

    public function allowAll(): self;

    public function removeDirective(string $directive, mixed $value = null): self;

    public function removeUserAgentDirective(string $userAgent, string $directive, mixed $value = null): self;

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array;

    /**
     * @return array<string>
     */
    public function getSitemaps(): array;

    /**
     * @return array<string, mixed>
     */
    public function getDirectives(): array;

    /**
     * @return array<string, mixed>
     */
    public function getUserAgentDirectives(string $userAgent): array;

    /**
     * @return array<string, array{environments: array<string>, callback: string}>
     */
    public function getEnvironmentRules(): array;

    /**
     * @return array<string, mixed>
     */
    public function checkConflicts(): array;

    /**
     * @return array<string>
     */
    public function getUserAgents(): array;

    public function hasUserAgent(string $userAgent): bool;
}
