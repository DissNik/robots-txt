<?php

namespace DissNik\RobotsTxt\Tests\Unit\Services;

use DissNik\RobotsTxt\Services\DirectiveManager;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DirectiveManagerTest extends TestCase
{
    private DirectiveManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new DirectiveManager();
    }

    #[Test]
    public function is_global_single_directive(): void
    {
        $this->assertTrue($this->manager->isGlobalSingleDirective('host'));
        $this->assertFalse($this->manager->isGlobalSingleDirective('sitemap'));
    }

    #[Test]
    public function is_global_multi_directive(): void
    {
        $this->assertTrue($this->manager->isGlobalMultiDirective('sitemap'));
        $this->assertFalse($this->manager->isGlobalMultiDirective('host'));
    }

    #[Test]
    public function is_user_agent_single_directive(): void
    {
        $this->assertTrue($this->manager->isUserAgentSingleDirective('crawl-delay'));
        $this->assertFalse($this->manager->isUserAgentSingleDirective('allow'));
    }

    #[Test]
    public function is_user_agent_multi_directive(): void
    {
        $this->assertTrue($this->manager->isUserAgentMultiDirective('allow'));
        $this->assertFalse($this->manager->isUserAgentMultiDirective('crawl-delay'));
    }

    #[Test]
    public function normalize_directive(): void
    {
        $this->assertEquals('crawl-delay', $this->manager->normalizeDirective('Crawl-Delay'));
        $this->assertEquals('allow', $this->manager->normalizeDirective(' ALLOW '));
    }

    #[Test]
    public function sort_global_directives(): void
    {
        $directives = [
            'clean-param' => ['test'],
            'host' => 'example.com',
            'sitemap' => ['sitemap.xml'],
        ];

        $sorted = $this->manager->sortGlobalDirectives($directives);
        $keys = array_keys($sorted);

        $this->assertEquals('host', $keys[0]);
        $this->assertEquals('sitemap', $keys[1]);
        $this->assertEquals('clean-param', $keys[2]);
    }

    #[Test]
    public function sort_user_agent_directives(): void
    {
        $directives = [
            'crawl-delay' => 1.0,
            'disallow' => ['/admin'],
            'allow' => ['/'],
        ];

        $sorted = $this->manager->sortUserAgentDirectives($directives);
        $keys = array_keys($sorted);

        $this->assertEquals('allow', $keys[0]);
        $this->assertEquals('disallow', $keys[1]);
        $this->assertEquals('crawl-delay', $keys[2]);
    }

    #[Test]
    public function normalize_path(): void
    {
        $this->assertEquals('/admin/', $this->manager->normalizePath('admin/'));
        $this->assertEquals('/admin/', $this->manager->normalizePath('//admin//'));
        $this->assertEquals('/', $this->manager->normalizePath('/'));
        $this->assertEquals('', $this->manager->normalizePath(''));
        $this->assertEquals('*', $this->manager->normalizePath('*'));
        $this->assertEquals('/admin', $this->manager->normalizePath('admin'));
        $this->assertEquals('/admin/dashboard', $this->manager->normalizePath('admin/dashboard'));
    }

    #[Test]
    public function paths_conflict(): void
    {
        $this->assertTrue($this->manager->pathsConflict('/admin', '/admin'));
        $this->assertTrue($this->manager->pathsConflict('/admin/', '/admin'));
        $this->assertTrue($this->manager->pathsConflict('/admin', '/admin/login'));
        $this->assertFalse($this->manager->pathsConflict('/admin', '/user'));
    }

    #[Test]
    public function adds_global_directive_for_single_value(): void
    {
        $directives = [];

        $this->manager->addGlobalDirective('host', 'example.com', $directives);

        $this->assertArrayHasKey('host', $directives);
        $this->assertEquals('example.com', $directives['host']);
    }

    #[Test]
    public function adds_global_directive_for_multi_value(): void
    {
        $directives = [];

        $this->manager->addGlobalDirective('sitemap', 'sitemap1.xml', $directives);
        $this->manager->addGlobalDirective('sitemap', 'sitemap2.xml', $directives);

        $this->assertArrayHasKey('sitemap', $directives);
        $this->assertIsArray($directives['sitemap']);
        $this->assertContains('sitemap1.xml', $directives['sitemap']);
        $this->assertContains('sitemap2.xml', $directives['sitemap']);
    }

    #[Test]
    public function adds_multiple_values_at_once(): void
    {
        $directives = [];

        $this->manager->addGlobalDirective('sitemap', ['sitemap1.xml', 'sitemap2.xml'], $directives);

        $this->assertCount(2, $directives['sitemap']);
        $this->assertContains('sitemap1.xml', $directives['sitemap']);
        $this->assertContains('sitemap2.xml', $directives['sitemap']);
    }

    #[Test]
    public function removes_duplicates_for_multi_value_directives(): void
    {
        $directives = [];

        $this->manager->addGlobalDirective('sitemap', 'sitemap.xml', $directives);
        $this->manager->addGlobalDirective('sitemap', 'sitemap.xml', $directives);

        $this->assertCount(1, $directives['sitemap']);
    }

    #[Test]
    public function removes_single_value_directive(): void
    {
        $directives = ['host' => 'example.com'];

        $this->manager->removeGlobalDirective('host', null, $directives);

        $this->assertArrayNotHasKey('host', $directives);
    }

    #[Test]
    public function removes_specific_value_from_multi_value_directive(): void
    {
        $directives = ['sitemap' => ['sitemap1.xml', 'sitemap2.xml']];

        $this->manager->removeGlobalDirective('sitemap', 'sitemap1.xml', $directives);

        $this->assertArrayHasKey('sitemap', $directives);
        $this->assertCount(1, $directives['sitemap']);
        $this->assertContains('sitemap2.xml', $directives['sitemap']);
        $this->assertNotContains('sitemap1.xml', $directives['sitemap']);
    }

    #[Test]
    public function removes_all_values_from_multi_value_directive(): void
    {
        $directives = ['sitemap' => ['sitemap1.xml', 'sitemap2.xml']];

        $this->manager->removeGlobalDirective('sitemap', null, $directives);

        $this->assertArrayNotHasKey('sitemap', $directives);
    }

    #[Test]
    public function does_nothing_when_removing_non_existent_directive(): void
    {
        $directives = ['host' => 'example.com'];

        $this->manager->removeGlobalDirective('sitemap', null, $directives);

        $this->assertArrayHasKey('host', $directives);
        $this->assertCount(1, $directives);
    }

    #[Test]
    public function normalizes_directive_names(): void
    {
        $directives = [];

        $this->manager->addGlobalDirective('SITEMAP', 'sitemap.xml', $directives);
        $this->manager->addGlobalDirective(' Clean-Param ', 'ref /search/', $directives);

        $this->assertArrayHasKey('sitemap', $directives);
        $this->assertArrayHasKey('clean-param', $directives);
    }
}
