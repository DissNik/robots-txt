<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Tests\Unit;

use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Providers\RobotsTxtServiceProvider;
use DissNik\RobotsTxt\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RobotsTxtServiceProviderTest extends TestCase
{
    #[Test]
    public function service_provider_registers_services(): void
    {
        $provider = new RobotsTxtServiceProvider(app());
        $provider->register();

        $this->assertTrue(app()->bound(RobotsTxtInterface::class));
        $this->assertTrue(app()->bound('robots-txt'));
    }

    #[Test]
    public function service_provider_boots(): void
    {
        $provider = new RobotsTxtServiceProvider(app());

        $this->expectNotToPerformAssertions();
        $provider->boot();
    }
}
