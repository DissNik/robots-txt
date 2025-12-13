<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Services;

class DirectiveManager
{
    private const GLOBAL_SINGLE_DIRECTIVES = ['host'];

    private const GLOBAL_MULTI_DIRECTIVES = ['sitemap', 'clean-param'];

    private const USER_AGENT_SINGLE_DIRECTIVES = [
        'crawl-delay', 'visit-time', 'request-rate', 'user-agent',
    ];

    private const USER_AGENT_MULTI_DIRECTIVES = [
        'allow', 'disallow', 'clean-param', 'noindex',
        'noimageindex', 'nofollow', 'noarchive',
    ];

    public function isGlobalSingleDirective(string $directive): bool
    {
        return in_array($this->normalizeDirective($directive), self::GLOBAL_SINGLE_DIRECTIVES, true);
    }

    public function isGlobalMultiDirective(string $directive): bool
    {
        return in_array($this->normalizeDirective($directive), self::GLOBAL_MULTI_DIRECTIVES, true);
    }

    public function isUserAgentSingleDirective(string $directive): bool
    {
        return in_array($this->normalizeDirective($directive), self::USER_AGENT_SINGLE_DIRECTIVES, true);
    }

    public function isUserAgentMultiDirective(string $directive): bool
    {
        return in_array($this->normalizeDirective($directive), self::USER_AGENT_MULTI_DIRECTIVES, true);
    }

    public function normalizeDirective(string $directive): string
    {
        return strtolower(trim($directive));
    }

    /**
     * @param  array<string, mixed>  $directives
     * @return array<string, mixed>
     */
    public function sortGlobalDirectives(array $directives): array
    {
        $order = ['host', 'sitemap', 'clean-param'];
        $sorted = [];

        foreach ($order as $directive) {
            if (isset($directives[$directive])) {
                $sorted[$directive] = $directives[$directive];
            }
        }

        foreach ($directives as $directive => $values) {
            if (! isset($sorted[$directive])) {
                $sorted[$directive] = $values;
            }
        }

        return $sorted;
    }

    /**
     * @param  array<string, mixed>  $directives
     * @return array<string, mixed>
     */
    public function sortUserAgentDirectives(array $directives): array
    {
        $order = ['allow', 'disallow', 'crawl-delay', 'clean-param', 'visit-time'];
        $sorted = [];

        foreach ($order as $directive) {
            if (isset($directives[$directive])) {
                $sorted[$directive] = $directives[$directive];
            }
        }

        foreach ($directives as $directive => $values) {
            if (! isset($sorted[$directive])) {
                $sorted[$directive] = $values;
            }
        }

        return $sorted;
    }

    public function normalizePath(string $path): string
    {
        $path = trim($path);

        $normalized = preg_replace('#/+#', '/', $path);

        $path = $normalized ?? $path;

        if (! empty($path) && $path !== '*' && ! str_starts_with($path, '/')) {
            return '/'.$path;
        }

        return $path;
    }

    public function pathsConflict(string $path1, string $path2): bool
    {
        $path1 = $this->normalizePath($path1);
        $path2 = $this->normalizePath($path2);

        if ($path1 === $path2) {
            return true;
        }

        $path1WithSlash = rtrim($path1, '/').'/';
        $path2WithSlash = rtrim($path2, '/').'/';

        return str_starts_with($path1WithSlash, $path2WithSlash) ||
            str_starts_with($path2WithSlash, $path1WithSlash);
    }

    /**
     * @param  array<string, mixed>  $globalDirectives
     */
    public function addGlobalDirective(string $directive, mixed $value, array &$globalDirectives): void
    {
        $directive = $this->normalizeDirective($directive);

        if ($this->isGlobalSingleDirective($directive)) {
            $globalDirectives[$directive] = $value;
        } else {
            if (! isset($globalDirectives[$directive])) {
                $globalDirectives[$directive] = [];
            }

            if (is_array($value)) {
                $globalDirectives[$directive] = array_merge(
                    $globalDirectives[$directive],
                    $value,
                );
            } else {
                $globalDirectives[$directive][] = $value;
            }

            $globalDirectives[$directive] = array_unique($globalDirectives[$directive]);
        }
    }

    /**
     * @param  array<string, mixed>  $globalDirectives
     */
    public function removeGlobalDirective(string $directive, mixed $value, array &$globalDirectives): void
    {
        $directive = $this->normalizeDirective($directive);

        if (! isset($globalDirectives[$directive])) {
            return;
        }

        if ($this->isGlobalSingleDirective($directive)) {
            unset($globalDirectives[$directive]);
        } elseif ($value === null) {
            unset($globalDirectives[$directive]);
        } else {
            $globalDirectives[$directive] = array_filter(
                $globalDirectives[$directive],
                fn ($item): bool => $item !== $value,
            );

            if (empty($globalDirectives[$directive])) {
                unset($globalDirectives[$directive]);
            }
        }
    }
}
