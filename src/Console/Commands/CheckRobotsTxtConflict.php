<?php

namespace DissNik\RobotsTxt\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class CheckRobotsTxtConflict extends Command
{
    protected $signature = 'robots-txt:check
                            {--rename : Automatically rename the file}
                            {--delete : Automatically delete the file}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Check for robots.txt conflicts and provide solutions';

    public function handle(): int
    {
        $filePath = public_path('robots.txt');

        if (! $this->fileExists($filePath)) {
            info('✅ No robots.txt file found in public directory.');
            info('✅ Package will work correctly.');

            return self::SUCCESS;
        }

        $this->displayConflictWarning($filePath);

        if ($this->processAutoOptions($filePath)) {
            return self::SUCCESS;
        }

        return $this->handleInteractiveResolution($filePath);
    }

    protected function fileExists(string $path): bool
    {
        return file_exists($path) && is_file($path);
    }

    protected function displayConflictWarning(string $path): void
    {
        error('⚠️  ROBOTS.TXT CONFLICT DETECTED');

        $this->components->twoColumnDetail('File location', $path);
        $this->components->twoColumnDetail('File size', $this->formatBytes(filesize($path)));
        $this->components->twoColumnDetail('File modified', date('Y-m-d H:i:s', filemtime($path)));
        $this->components->twoColumnDetail('Package route', route('robots-txt', absolute: false));

        warning("\nThis file will override package rules! The package robots.txt will not be accessible.");
    }

    protected function processAutoOptions(string $path): bool
    {
        if ($this->option('rename')) {
            $this->components->info('Renaming robots.txt file...');

            return $this->renameFile($path);
        }

        if ($this->option('delete')) {
            if (! $this->option('force') && ! $this->confirmDeletion()) {
                info('Operation cancelled.');

                return true;
            }

            $this->components->info('Deleting robots.txt file...');

            return $this->deleteFile($path);
        }

        return false;
    }

    protected function handleInteractiveResolution(string $path): int
    {
        $choice = select(
            label: 'How would you like to resolve this conflict?',
            options: [
                'rename' => 'Create backup and remove file (recommended)',
                'delete' => 'Delete the file permanently',
                'view' => 'View file contents',
                'ignore' => 'Do nothing (package rules will not work!)',
            ],
            default: 'rename'
        );

        return match ($choice) {
            'rename' => $this->handleRename($path),
            'delete' => $this->handleDelete($path),
            'view' => $this->handleView($path),
            'ignore' => $this->handleIgnore(),
            default => self::FAILURE,
        };
    }

    protected function handleRename(string $path): int
    {
        return $this->renameFile($path) ? self::SUCCESS : self::FAILURE;
    }

    protected function handleDelete(string $path): int
    {
        if (! $this->option('force') && ! $this->confirmDeletion()) {
            error('Operation cancelled.');

            return self::SUCCESS;
        }

        $backupPath = null;
        if (! $this->option('force') && $this->confirmBackupCreation()) {
            $backupPath = $this->generateBackupPath($path);
            if (! $this->createBackup($path, $backupPath)) {
                if (! confirm('Continue without backup?', default: false)) {
                    return self::SUCCESS;
                }
                $backupPath = null;
            }
        }

        if (! $this->removeFile($path)) {
            return self::FAILURE;
        }

        $this->displaySuccess('Conflict resolved successfully!', $backupPath, $path);

        return self::SUCCESS;
    }

    protected function handleView(string $path): int
    {
        try {
            $content = File::get($path);
            $this->line("\n=== File contents of {$path} ===");
            $this->line($content);
            $this->line("=== End of file ===\n");

            return $this->handleInteractiveResolution($path);
        } catch (Throwable $e) {
            error('Error reading file: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function handleIgnore(): int
    {
        warning('⚠️  Conflict ignored');
        warning('Package rules will not work while file exists.');
        warning('Run `php artisan robots-txt:check` again when ready to resolve.');

        return self::SUCCESS;
    }

    protected function renameFile(string $path): bool
    {
        $backupPath = $this->generateBackupPath($path);

        if (! $this->createBackup($path, $backupPath)) {
            return false;
        }

        if (! $this->removeFile($path)) {
            if (file_exists($backupPath)) {
                @unlink($backupPath);
            }

            return false;
        }

        $this->displaySuccess('File renamed successfully', $backupPath, $path);

        return true;
    }

    protected function deleteFile(string $path): bool
    {
        if (! $this->removeFile($path)) {
            return false;
        }

        $this->displaySuccess('File deleted successfully', null, $path);

        return true;
    }

    protected function generateBackupPath(string $originalPath): string
    {
        $timestamp = date('Y-m-d_His');
        $baseName = basename($originalPath);
        $dirName = dirname($originalPath);
        $counter = 1;

        do {
            $backupName = sprintf(
                '%s.backup_%s%s',
                $baseName,
                $timestamp,
                $counter > 1 ? "_{$counter}" : ''
            );
            $backupPath = $dirName.'/'.$backupName;
            $counter++;
        } while (file_exists($backupPath));

        return $backupPath;
    }

    protected function createBackup(string $source, string $destination): bool
    {
        try {
            if (! File::copy($source, $destination)) {
                error('Failed to create backup file. Check directory permissions.');

                return false;
            }

            return true;
        } catch (Throwable $e) {
            error('Error creating backup: '.$e->getMessage());

            return false;
        }
    }

    protected function removeFile(string $path): bool
    {
        try {
            if (! File::delete($path)) {
                error('Failed to remove file. Check file permissions.');

                return false;
            }

            return true;
        } catch (Throwable $e) {
            error('Error removing file: '.$e->getMessage());

            return false;
        }
    }

    protected function confirmDeletion(string $message = 'Are you sure you want to delete the robots.txt file?'): bool
    {
        return confirm(label: $message, default: false);
    }

    protected function confirmBackupCreation(string $message = 'Create a backup before deleting?'): bool
    {
        return confirm(label: $message, default: true);
    }

    protected function displaySuccess(string $message, ?string $backupPath = null, ?string $deletedFile = null): void
    {
        info('✅ '.$message);

        if ($backupPath) {
            $this->components->twoColumnDetail('📁 Backup created', $backupPath);
        }

        if ($deletedFile) {
            $this->components->twoColumnDetail('🗑️ Original file', $deletedFile);
        }

        $this->components->twoColumnDetail('✅ Package route', route('robots-txt', absolute: false));
        info("\nThe package robots.txt is now accessible at: ".url('robots.txt'));
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision).' '.$units[$pow];
    }
}
