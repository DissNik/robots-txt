<?php

namespace DissNik\RobotsTxt\Tests;

use DissNik\RobotsTxt\Providers\RobotsTxtServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RobotsTxtServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('robots-txt', [
            'cache' => [
                'enabled' => false,
                'duration' => 3600,
            ],
            'environments' => [],
            'default_environment' => 'testing',
        ]);
    }
}
