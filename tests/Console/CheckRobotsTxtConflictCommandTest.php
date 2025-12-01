<?php

namespace DissNik\RobotsTxt\Tests\Console;

use DissNik\RobotsTxt\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class CheckRobotsTxtConflictCommandTest extends TestCase
{
    protected string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilePath = public_path('robots.txt');
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    protected function cleanup(): void
    {
        if (File::exists($this->testFilePath)) {
            File::delete($this->testFilePath);
        }

        foreach (glob(public_path('robots.txt.backup_*')) as $file) {
            File::delete($file);
        }
    }

    #[Test]
    public function it_shows_success_when_no_file_exists(): void
    {
        $this->artisan('robots-txt:check')
            ->expectsOutputToContain('Package will work correctly')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_can_auto_rename_with_option(): void
    {
        $content = "User-agent: *\nAllow: /";
        File::put($this->testFilePath, $content);

        $this->artisan('robots-txt:check', ['--rename' => true])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->testFilePath);

        $backupFiles = glob(public_path('robots.txt.backup_*'));
        $this->assertCount(1, $backupFiles);

        $backupContent = File::get($backupFiles[0]);
        $this->assertEquals($content, $backupContent);
    }

    #[Test]
    public function it_can_auto_delete_with_option_and_confirmation(): void
    {
        File::put($this->testFilePath, 'Test content');

        $this->artisan('robots-txt:check', ['--delete' => true])
            ->expectsQuestion('Are you sure you want to delete the robots.txt file?', true)
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->testFilePath);
    }
}
