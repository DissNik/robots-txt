<?php

namespace DissNik\RobotsTxt\Tests\Unit\Facades;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FacadeTest extends TestCase
{
    #[Test]
    public function facade_proxies_methods(): void
    {
        RobotsTxt::clear();
        RobotsTxt::forUserAgent('*', function ($context) {
            $context->disallow('/admin');
        });

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Disallow: /admin', $content);

        RobotsTxt::clear();
        RobotsTxt::sitemap('https://example.com/sitemap.xml');

        $content = RobotsTxt::generate();
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $content);
    }
}
