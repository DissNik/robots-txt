<?php

namespace DissNik\RobotsTxt\Tests\Unit\Services;

use DissNik\RobotsTxt\Services\ConfigLoader;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $configLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configLoader = new ConfigLoader;
    }

    #[Test]
    public function loads_config_for_current_environment(): void
    {
        App::shouldReceive('environment')->andReturn('production');
        Config::set('robots-txt.environments.production', [
            'sitemap' => ['https://example.com/sitemap.xml'],
            'user_agents' => [
                '*' => ['allow' => ['/']],
            ],
        ]);

        $config = $this->configLoader->loadForCurrentEnvironment();

        $this->assertArrayHasKey('global_directives', $config);
        $this->assertArrayHasKey('user_agent_rules', $config);
        $this->assertArrayHasKey('sitemap', $config['global_directives']);
    }

    #[Test]
    public function falls_back_to_default_environment(): void
    {
        App::shouldReceive('environment')->andReturn('unknown');
        Config::set('robots-txt.default_environment', 'staging');
        Config::set('robots-txt.environments.staging', [
            'user_agents' => [
                '*' => ['disallow' => ['/']],
            ],
        ]);

        $config = $this->configLoader->loadForCurrentEnvironment();

        $this->assertNotEmpty($config['user_agent_rules']);
    }

    #[Test]
    public function returns_empty_config_when_none_found(): void
    {
        App::shouldReceive('environment')->andReturn('unknown');
        Config::set('robots-txt.default_environment', 'nonexistent');

        $config = $this->configLoader->loadForCurrentEnvironment();

        $this->assertEquals([], $config['global_directives']);
        $this->assertEquals([], $config['user_agent_rules']);
    }

    #[Test]
    public function normalize_config_handles_user_agents(): void
    {
        $rawConfig = [
            'sitemap' => 'https://example.com/sitemap.xml',
            'user_agents' => [
                '*' => ['allow' => ['/']],
            ],
        ];

        $config = $this->invokeMethod($this->configLoader, 'normalizeConfig', [$rawConfig]);

        $this->assertArrayHasKey('global_directives', $config);
        $this->assertArrayHasKey('user_agent_rules', $config);
        $this->assertEquals('https://example.com/sitemap.xml', $config['global_directives']['sitemap']);
    }

    #[Test]
    public function get_cache_config_returns_default(): void
    {
        $cacheConfig = $this->configLoader->getCacheConfig();

        $this->assertArrayHasKey('enabled', $cacheConfig);
        $this->assertArrayHasKey('duration', $cacheConfig);
        $this->assertEquals(3600, $cacheConfig['duration']);
    }

    private function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $parameters);
    }
}
