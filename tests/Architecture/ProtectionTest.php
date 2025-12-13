<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Architecture;

use BadMethodCallException;
use DissNik\RobotsTxt\RobotsTxtBuilder;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProtectionTest extends TestCase
{
    #[Test]
    public function allow_throws_exception_when_called_directly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method allow() can only be called inside forUserAgent() callback');

        $builder->allow('/');
    }

    #[Test]
    public function disallow_throws_exception_when_called_directly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method disallow() can only be called inside forUserAgent() callback');

        $builder->disallow('/admin');
    }

    #[Test]
    public function crawl_delay_throws_exception_when_called_directly(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method crawlDelay() can only be called inside forUserAgent() callback');

        $builder->crawlDelay(1.0);
    }

    #[Test]
    public function allow_works_inside_user_agent_context(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->allow('/');
        });

        $content = $builder->generate();
        $this->assertStringContainsString('Allow: /', $content);
    }

    #[Test]
    public function disallow_works_inside_user_agent_context(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->disallow('/admin');
        });

        $content = $builder->generate();
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function crawl_delay_works_inside_user_agent_context(): void
    {
        $builder = app(RobotsTxtBuilder::class);
        $builder->clear();

        $builder->forUserAgent('*', function ($ctx): void {
            $ctx->crawlDelay(1.5);
        });

        $content = $builder->generate();
        $this->assertStringContainsString('Crawl-delay: 1.5', $content);
    }
}
