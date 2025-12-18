<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;

class ConfigIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->forgetInstance(RobotsTxtInterface::class);
    }

    #[Test]
    public function loads_rules_from_config(): void
    {
        Config::set('robots-txt.environments.production', [
            'sitemap' => ['https://example.com/sitemap.xml'],
            'user_agents' => [
                '*' => [
                    'disallow' => ['/admin', '/private'],
                    'allow' => ['/public'],
                    'crawl-delay' => 1.5,
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);
        $content = $robots->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
        $this->assertStringContainsString('Allow: /public', $content);
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
    }

    #[Test]
    public function falls_back_to_default_environment(): void
    {
        Config::set('robots-txt.environments.staging', [
            'user_agents' => [
                '*' => [
                    'disallow' => ['/staging-only'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'staging');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('unknown');

        $robots = app()->make(RobotsTxtInterface::class);
        $content = $robots->generate();

        $this->assertStringContainsString('Disallow: /staging-only', $content);
    }

    #[Test]
    public function config_rules_merge_with_programmatic(): void
    {
        Config::set('robots-txt.environments.production', [
            'user_agents' => [
                '*' => [
                    'disallow' => ['/admin'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);
        $robots->forUserAgent('*', function ($context): void {
            $context->disallow('/api');
        });

        $content = $robots->generate();

        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /api', $content);
    }

    #[Test]
    public function environment_specific_sitemaps(): void
    {
        Config::set('robots-txt.environments.production', [
            'sitemap' => [
                'https://example.com/sitemap.xml',
                'https://example.com/sitemap-images.xml',
            ],
            'user_agents' => [
                '*' => [
                    'allow' => ['/'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);
        $content = $robots->generate();

        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap-images.xml', $content);

        $sitemapCount = substr_count((string) $content, 'Sitemap:');
        $this->assertEquals(2, $sitemapCount);
    }

    #[Test]
    public function multiple_user_agents_from_config(): void
    {
        Config::set('robots-txt.environments.production', [
            'user_agents' => [
                '*' => [
                    'allow' => ['/'],
                    'disallow' => ['/admin'],
                ],
                'Googlebot' => [
                    'disallow' => ['/nogooglebot/'],
                    'crawl-delay' => 2.0,
                ],
                'Bingbot' => [
                    'disallow' => ['/search'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);
        $content = $robots->generate();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('User-agent: Bingbot', $content);
        $this->assertStringContainsString('Crawl-delay: 2', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /nogooglebot/', $content);
        $this->assertStringContainsString('Disallow: /search', $content);
    }

    #[Test]
    public function clear_only_clears_without_reloading_config(): void
    {
        Config::set('robots-txt.environments.production', [
            'user_agents' => [
                '*' => [
                    'disallow' => ['/from-config'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);

        $robots->forUserAgent('*', function ($context): void {
            $context->disallow('/programmatic');
        });

        $robots->clear();

        $content = $robots->generate();

        $this->assertEquals('', trim((string) $content));
        $this->assertStringNotContainsString('Disallow: /from-config', $content);
        $this->assertStringNotContainsString('Disallow: /programmatic', $content);
    }

    #[Test]
    public function reset_clears_and_reloads_config(): void
    {
        Config::set('robots-txt.environments.production', [
            'user_agents' => [
                '*' => [
                    'disallow' => ['/from-config'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);

        $robots->forUserAgent('*', function ($context): void {
            $context->disallow('/programmatic');
        });

        $robots->reset();

        $content = $robots->generate();

        $this->assertStringContainsString('Disallow: /from-config', $content);
        $this->assertStringNotContainsString('Disallow: /programmatic', $content);
    }

    #[Test]
    public function clear_and_then_reset_works_correctly(): void
    {
        Config::set('robots-txt.environments.production', [
            'user_agents' => [
                '*' => [
                    'disallow' => ['/from-config'],
                ],
            ],
        ]);

        Config::set('robots-txt.default_environment', 'production');

        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        $robots = app()->make(RobotsTxtInterface::class);

        $content = $robots->generate();
        $this->assertStringContainsString('Disallow: /from-config', $content);

        $robots->clear();
        $content = $robots->generate();
        $this->assertEquals('', trim((string) $content));

        $robots->reset();
        $content = $robots->generate();
        $this->assertStringContainsString('Disallow: /from-config', $content);
    }
}
