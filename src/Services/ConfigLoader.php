<?php

namespace DissNik\RobotsTxt\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ConfigLoader
{
    /**
     * @return array{global_directives: array<string, mixed>, user_agent_rules: array<string, mixed>}
     */
    public function loadForCurrentEnvironment(): array
    {
        $currentEnv = App::environment();

        $envConfig = Config::get("robots-txt.environments.{$currentEnv}");

        if (! $envConfig) {
            $defaultEnv = Config::get('robots-txt.default_environment', 'production');
            $envConfig = Config::get("robots-txt.environments.{$defaultEnv}");
        }

        if (! $envConfig) {
            return [
                'global_directives' => [],
                'user_agent_rules' => [],
            ];
        }

        return $this->normalizeConfig($envConfig);
    }

    /**
     * @param array<string, mixed> $config
     * @return array{global_directives: array<string, mixed>, user_agent_rules: array<string, mixed>}
     */
    protected function normalizeConfig(array $config): array
    {
        $normalized = [
            'global_directives' => [],
            'user_agent_rules' => [],
        ];

        foreach ($config as $key => $value) {
            if ($key === 'user_agents') {
                if (is_array($value)) {
                    $normalized['user_agent_rules'] = $value;
                }
            } elseif (! empty($value)) {
                $normalized['global_directives'][$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array{enabled: bool, duration: int}
     */
    public function getCacheConfig(): array
    {
        /** @var array{enabled?: bool, duration?: int} */
        $cacheConfig = Config::get('robots-txt.cache', [
            'enabled' => true,
            'duration' => 3600,
        ]);

        return [
            'enabled' => $cacheConfig['enabled'] ?? true,
            'duration' => $cacheConfig['duration'] ?? 3600,
        ];
    }
}
