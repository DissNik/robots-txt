<?php

namespace DissNik\RobotsTxt\Tests;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\RobotsTxtServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RobotsTxtServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'RobotsTxt' => RobotsTxt::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('robots-txt.cache.enabled', false);
        $app['config']->set('robots-txt.cache.duration', 3600);

        if (file_exists($configPath = __DIR__.'/../config/robots-txt.php')) {
            $app['config']->set('robots-txt', require $configPath);
        }
    }

    protected function defineEnvironment($app): void
    {
        $app['env'] = 'testing';
    }
}
