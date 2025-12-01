<?php

namespace DissNik\RobotsTxt\Console\Commands;

use Illuminate\Console\Command;
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
                            {--delete : Automatically delete the file}';

    protected $description = 'Check for robots.txt conflicts and provide solutions';

    public function handle(): int
    {
        $filePath = public_path('robots.txt');

        if (! $this->fileExists($filePath)) {
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
        if (! file_exists($path)) {
            info('Package will work correctly.');

            return false;
        }

        return true;
    }

    protected function displayConflictWarning(string $path): void
    {
        error('ROBOTS.TXT CONFLICT DETECTED');

        $this->components->twoColumnDetail('<fg=yellow>⚠️ File</>', $path);
        $this->components->twoColumnDetail('❌ Package route', route('robots-txt', absolute: false));

        warning('This file will override package rules!');
    }

    protected function processAutoOptions(string $path): bool
    {
        if ($this->option('rename')) {
            $this->components->info('Renaming robots.txt file...');

            return $this->renameFile($path);
        }

        if ($this->option('delete')) {
            if (! $this->confirmDeletion()) {
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
                'rename' => 'Create backup and remove file',
                'delete' => 'Delete the file',
                'ignore' => 'Do nothing (⚠ package rules will not work!)',
            ],
            default: 'rename'
        );

        return match ($choice) {
            'rename' => $this->handleRename($path),
            'delete' => $this->handleDelete($path),
            'ignore' => $this->handleIgnore(),
        };
    }

    protected function handleRename(string $path): int
    {
        return $this->renameFile($path) ? self::SUCCESS : self::FAILURE;
    }

    protected function handleDelete(string $path): int
    {
        if (! $this->confirmDeletion()) {
            error('Operation cancelled.');

            return self::SUCCESS;
        }

        $backupPath = null;
        if ($this->confirmBackupCreation()) {
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

    protected function handleIgnore(): int
    {
        error('Conflict ignored');
        warning("Package rules will not work while file exists.\nRun this command again when ready to resolve.");

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
        $counter = 1;

        do {
            $backupPath = sprintf(
                '%s.backup_%s%s',
                $originalPath,
                $timestamp,
                $counter > 1 ? "_{$counter}" : ''
            );
            $counter++;
        } while (file_exists($backupPath));

        return $backupPath;
    }

    protected function createBackup(string $source, string $destination): bool
    {
        try {
            if (! copy($source, $destination)) {
                error('Failed to create backup file');

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
            if (! unlink($path)) {
                error('Failed to remove file');

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
        info($message);

        if ($backupPath) {
            $this->components->twoColumnDetail('📁 Backup file', url(basename($backupPath)));
        }

        if ($deletedFile) {
            $this->components->twoColumnDetail('🗑️ File deleted', $deletedFile);
        }

        $this->components->twoColumnDetail('✅ Package route', route('robots-txt'));
    }
}
