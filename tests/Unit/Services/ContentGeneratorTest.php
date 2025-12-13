<?php

namespace DissNik\RobotsTxt\Tests\Unit\Services;

use DissNik\RobotsTxt\Rules\RobotsTxtRule;
use DissNik\RobotsTxt\Services\ContentGenerator;
use DissNik\RobotsTxt\Services\DirectiveManager;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ContentGeneratorTest extends TestCase
{
    private ContentGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ContentGenerator(new DirectiveManager());
    }

    #[Test]
    public function generates_content_for_single_user_agent(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin');
        $rule->directive('allow', '/');

        $userAgentRules = ['*' => $rule];
        $globalDirectives = [];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function generates_content_for_multiple_user_agents(): void
    {
        $rule1 = new RobotsTxtRule('*');
        $rule1->directive('disallow', '/admin');

        $rule2 = new RobotsTxtRule('Googlebot');
        $rule2->directive('disallow', '/private');

        $userAgentRules = ['*' => $rule1, 'Googlebot' => $rule2];
        $globalDirectives = [];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
    }

    #[Test]
    public function adds_global_directives(): void
    {
        $userAgentRules = [];
        $globalDirectives = [
            'sitemap' => ['https://example.com/sitemap.xml'],
            'host' => 'www.example.com',
        ];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);
    }

    #[Test]
    public function adds_multiple_global_directives(): void
    {
        $userAgentRules = [];
        $globalDirectives = [
            'sitemap' => [
                'https://example.com/sitemap1.xml',
                'https://example.com/sitemap2.xml',
            ],
        ];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertStringContainsString('Sitemap: https://example.com/sitemap1.xml', $content);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap2.xml', $content);
        $this->assertEquals(2, substr_count($content, 'Sitemap:'));
    }

    #[Test]
    public function skips_empty_user_agent_rules(): void
    {
        $rule = new RobotsTxtRule('*');

        $userAgentRules = ['*' => $rule];
        $globalDirectives = [];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertEquals('', trim($content));
    }

    #[Test]
    public function skips_empty_global_directives(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin');

        $userAgentRules = ['*' => $rule];
        $globalDirectives = [
            'sitemap' => [],
            'host' => '',
        ];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringNotContainsString('Sitemap:', $content);
        $this->assertStringNotContainsString('Host:', $content);
    }

    #[Test]
    public function orders_directives_correctly(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('crawl-delay', '1.0');
        $rule->directive('disallow', '/admin');
        $rule->directive('allow', '/');

        $userAgentRules = ['*' => $rule];
        $globalDirectives = [
            'clean-param' => ['ref /search/'],
            'host' => 'www.example.com',
            'sitemap' => ['https://example.com/sitemap.xml'],
        ];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);
        $lines = explode("\n", $content);

        $allowIndex = array_search('Allow: /', $lines);
        $disallowIndex = array_search('Disallow: /admin', $lines);
        $crawlDelayIndex = array_search('Crawl-delay: 1.0', $lines);

        $this->assertLessThan($disallowIndex, $allowIndex, 'Allow should come before Disallow');
        $this->assertLessThan($crawlDelayIndex, $disallowIndex, 'Disallow should come before Crawl-delay');
    }

    #[Test]
    public function trims_final_content(): void
    {
        $rule = new RobotsTxtRule('*');
        $rule->directive('disallow', '/admin');

        $userAgentRules = ['*' => $rule];
        $globalDirectives = [];

        $content = $this->generator->generate($userAgentRules, $globalDirectives);

        $this->assertStringNotContainsString("\n\n", trim($content));
        $this->assertEquals($content, trim($content));
    }
}
