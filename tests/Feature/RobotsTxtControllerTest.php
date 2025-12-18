<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    #[Test]
    public function controller_returns_robots_txt_content(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/')
                ->disallow('/admin');
        });

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

        $content = $response->content();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function controller_returns_empty_content_when_no_rules(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertEquals('', trim($response->content()));
    }

    #[Test]
    public function controller_includes_global_directives(): void
    {
        RobotsTxt::sitemap('https://example.com/sitemap.xml')
            ->host('www.example.com');

        $response = $this->get('/robots.txt');
        $content = $response->content();

        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
        $this->assertStringContainsString('Host: www.example.com', $content);
    }

    #[Test]
    public function controller_handles_multiple_user_agents(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->disallow('/admin');
        });

        RobotsTxt::forUserAgent('Googlebot', function ($context): void {
            $context->disallow('/private');
        });

        $response = $this->get('/robots.txt');
        $content = $response->content();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
    }

    #[Test]
    public function controller_works_with_block_all_and_allow_all(): void
    {
        RobotsTxt::blockAll();

        $response = $this->get('/robots.txt');
        $content = $response->content();
        $this->assertStringContainsString('Disallow: /', $content);

        RobotsTxt::clear();
        RobotsTxt::allowAll();

        $response = $this->get('/robots.txt');
        $content = $response->content();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function controller_response_is_plain_text_with_correct_encoding(): void
    {
        RobotsTxt::forUserAgent('*', function ($context): void {
            $context->allow('/');
        });

        $response = $this->get('/robots.txt');

        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertStringStartsWith('User-agent:', $response->content());
    }
}
