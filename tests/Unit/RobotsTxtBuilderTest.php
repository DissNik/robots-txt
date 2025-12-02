<?php

namespace DissNik\RobotsTxt\Tests\Unit;

use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Services\ConfigLoader;
use DissNik\RobotsTxt\Services\ContentGenerator;
use DissNik\RobotsTxt\Services\DirectiveManager;
use DissNik\RobotsTxt\Services\EnvironmentRuleApplier;
use DissNik\RobotsTxt\Services\RuleManager;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtBuilderTest extends TestCase
{
    private RobotsTxtBuilder $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $configLoader = new ConfigLoader;
        $directiveManager = new DirectiveManager;
        $ruleManager = new RuleManager($directiveManager);
        $environmentApplier = new EnvironmentRuleApplier;
        $contentGenerator = new ContentGenerator($directiveManager);

        $this->manager = new RobotsTxtBuilder(
            $configLoader,
            $ruleManager,
            $directiveManager,
            $environmentApplier,
            $contentGenerator
        );

        $this->manager->clear();
    }

    #[Test]
    public function creates_manager(): void
    {
        $this->assertInstanceOf(RobotsTxtBuilder::class, $this->manager);
    }

    #[Test]
    public function adds_directives(): void
    {
        $this->manager->directive('sitemap', 'sitemap.xml');
        $directives = $this->manager->getDirectives();

        $this->assertArrayHasKey('sitemap', $directives);
    }

    #[Test]
    public function adds_user_agent_directives(): void
    {
        $this->manager->forUserAgent('*', function ($context) {
            $context->directive('disallow', '/admin');
        });
        $directives = $this->manager->getUserAgentDirectives('*');

        $this->assertArrayHasKey('disallow', $directives);
    }

    #[Test]
    public function convenience_methods_work(): void
    {
        $this->manager->forUserAgent('*', function ($context) {
            $context->allow('/')
                ->disallow('/admin')
                ->crawlDelay(1.5);
        });

        $this->manager->sitemap('sitemap.xml')
            ->host('example.com')
            ->cleanParam('ref', '/search');

        $content = $this->manager->generate();

        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
    }

    #[Test]
    public function conditional_methods_work(): void
    {
        $this->manager->when(true, function ($robots) {
            $robots->forUserAgent('*', function ($context) {
                $context->disallow('/test');
            });
        });

        $this->manager->when(false, function ($robots) {
            $robots->forUserAgent('*', function ($context) {
                $context->disallow('/never');
            });
        });

        $content = $this->manager->generate();
        $this->assertStringContainsString('Disallow: /test', $content);
        $this->assertStringNotContainsString('Disallow: /never', $content);
    }

    #[Test]
    public function environment_method_works(): void
    {
        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('testing');

        $this->manager->forEnvironment('testing', function ($robots) {
            $robots->forUserAgent('*', function ($context) {
                $context->disallow('/test');
            });
        });

        $rules = $this->manager->getEnvironmentRules();
        $this->assertNotEmpty($rules);
    }

    #[Test]
    public function clear_method_works(): void
    {
        $this->manager->forUserAgent('Googlebot', function ($context) {
            $context->disallow('/admin');
        });
        $this->assertNotEmpty($this->manager->getRules());

        $this->manager->clear();
        $rules = $this->manager->getRules();

        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('*', $rules);
    }

    #[Test]
    public function gets_sitemaps(): void
    {
        $this->manager->sitemap('sitemap1.xml')
            ->sitemap('sitemap2.xml');

        $sitemaps = $this->manager->getSitemaps();
        $this->assertCount(2, $sitemaps);
    }

    #[Test]
    public function helper_methods_work(): void
    {
        $this->manager->blockAll();
        $content = $this->manager->generate();
        $this->assertStringContainsString('Disallow: /', $content);

        $this->manager->clear();
        $this->manager->allowAll();
        $content = $this->manager->generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function removal_methods_work(): void
    {
        $this->manager->directive('sitemap', 'sitemap.xml');
        $this->manager->removeDirective('sitemap', 'sitemap.xml');

        $directives = $this->manager->getDirectives();
        $this->assertArrayNotHasKey('sitemap', $directives);
    }

    #[Test]
    public function check_conflicts(): void
    {
        $this->manager->forUserAgent('*', function ($context) {
            $context->disallow('/admin')
                ->allow('/admin');
        });

        $conflicts = $this->manager->checkConflicts();
        $this->assertArrayHasKey('*', $conflicts);
    }

    #[Test]
    public function gets_user_agents(): void
    {
        $this->manager->forUserAgent('Googlebot', function ($context) {});
        $this->manager->forUserAgent('Bingbot', function ($context) {});

        $agents = $this->manager->getUserAgents();
        $this->assertContains('Googlebot', $agents);
        $this->assertContains('Bingbot', $agents);
    }

    #[Test]
    public function checks_user_agent_existence(): void
    {
        $this->manager->forUserAgent('Googlebot', function ($context) {});

        $this->assertTrue($this->manager->hasUserAgent('Googlebot'));
        $this->assertFalse($this->manager->hasUserAgent('Bingbot'));
    }
}
