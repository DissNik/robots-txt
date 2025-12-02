<?php

namespace DissNik\RobotsTxt\Tests\Feature;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;

class EnvironmentRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RobotsTxt::clear();
    }

    #[Test]
    public function applies_rules_for_specific_environment(): void
    {
        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        RobotsTxt::forEnvironment('production', function ($robots): void {
            $robots->forUserAgent('*', function ($context) {
                $context->allow('/');
            });
        })->forEnvironment('local', function ($robots): void {
            $robots->forUserAgent('*', function ($context) {
                $context->disallow('/');
            });
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Allow: /', $content);
        $this->assertStringNotContainsString('Disallow: /', $content);
    }

    #[Test]
    public function applies_rules_for_multiple_environments(): void
    {
        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('staging');

        RobotsTxt::forEnvironment(['staging', 'production'], function ($robots): void {
            $robots->forUserAgent('*', function ($context) {
                $context->disallow('/admin');
            });
        });

        $content = RobotsTxt::generate();

        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    #[Test]
    public function does_not_apply_rules_for_other_environments(): void
    {
        App::partialMock()
            ->shouldReceive('environment')
            ->andReturn('production');

        RobotsTxt::forEnvironment('local', function ($env): void {
            $env->forUserAgent('*', function ($context) {
                $context->disallow('/');
            });
        });

        $content = RobotsTxt::generate();

        $this->assertStringNotContainsString('Disallow: /', $content);
    }
}
