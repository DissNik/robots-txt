<?php

namespace DissNik\RobotsTxt\Services;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use Illuminate\Support\Facades\App;

class EnvironmentRuleApplier
{
    /** @var array<string, array{environments: array<string>, callback: callable}> */
    private array $environmentCallbacks = [];

    /**
     * @param string|array<string> $environments
     */
    public function addCallback(string|array $environments, callable $callback): string
    {
        $environments = (array) $environments;

        $callbackId = is_object($callback)
            ? spl_object_hash($callback)
            : md5(serialize($callback));

        $key = md5(serialize($environments) . $callbackId);

        $this->environmentCallbacks[$key] = [
            'environments' => $environments,
            'callback' => $callback,
        ];

        return $key;
    }

    public function applyCallbacks(RobotsTxtInterface $robotsManager): void
    {
        $currentEnv = App::environment();

        foreach ($this->environmentCallbacks as $callbackData) {
            if (in_array($currentEnv, $callbackData['environments'])) {
                $callbackData['callback']($robotsManager);
            }
        }
    }

    /**
     * @return array<string, array{environments: array<string>, callback: callable}>
     */
    public function getCallbacks(): array
    {
        return $this->environmentCallbacks;
    }

    public function clearCallbacks(): void
    {
        $this->environmentCallbacks = [];
    }
}
