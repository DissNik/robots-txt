<?php

namespace DissNik\RobotsTxt\Services;

use Illuminate\Support\Facades\App;

class EnvironmentRuleApplier
{
    private array $environmentCallbacks = [];

    public function addCallback(string|array $environments, callable $callback): string
    {
        $environments = (array) $environments;
        $key = md5(serialize($environments).spl_object_hash($callback));

        $this->environmentCallbacks[$key] = [
            'environments' => $environments,
            'callback' => $callback,
        ];

        return $key;
    }

    public function applyCallbacks($robotsManager): void
    {
        $currentEnv = App::environment();

        foreach ($this->environmentCallbacks as $callbackData) {
            if (in_array($currentEnv, $callbackData['environments'])) {
                $callbackData['callback']($robotsManager);
            }
        }
    }

    public function getCallbacks(): array
    {
        return $this->environmentCallbacks;
    }

    public function clearCallbacks(): void
    {
        $this->environmentCallbacks = [];
    }
}
