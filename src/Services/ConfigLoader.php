<?php

namespace DissNik\RobotsTxt\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ConfigLoader
{
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

    public function getCacheConfig(): array
    {
        return Config::get('robots-txt.cache', [
            'enabled' => true,
            'duration' => 3600,
        ]);
    }
}
