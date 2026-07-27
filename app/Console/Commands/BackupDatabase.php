<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use SQLite3;

class BackupDatabase extends Command
{
    protected $signature = 'database:backup {--retention=14 : Number of days to retain backups}';

    protected $description = 'Create and verify a private, consistent SQLite database backup';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('Automatic database backups currently support SQLite only.');

            return self::FAILURE;
        }

        $sourcePath = config('database.connections.sqlite.database');
        $sourcePath = is_string($sourcePath) ? realpath($sourcePath) : false;

        if ($sourcePath === false || ! is_file($sourcePath)) {
            $this->error('The configured SQLite database could not be found.');

            return self::FAILURE;
        }

        $backupDirectory = storage_path('app/private/database-backups');
        File::ensureDirectoryExists($backupDirectory);

        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR
            .'progresslab-'.Carbon::now()->format('Y-m-d_H-i-s').'.sqlite';

        try {
            $source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
            $destination = new SQLite3($backupPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

            if (! $source->backup($destination)) {
                throw new RuntimeException('SQLite could not complete the backup.');
            }

            $source->close();

            $integrity = $destination->querySingle('PRAGMA integrity_check');
            $destination->close();

            if ($integrity !== 'ok') {
                File::delete($backupPath);
                throw new RuntimeException('The new backup failed its integrity check.');
            }
        } catch (\Throwable $exception) {
            File::delete($backupPath);
            report($exception);
            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $retentionDays = max(1, (int) $this->option('retention'));
        $deleteBefore = Carbon::now()->subDays($retentionDays);

        foreach (File::glob($backupDirectory.DIRECTORY_SEPARATOR.'progresslab-*.sqlite') as $file) {
            if ($file !== $backupPath && Carbon::createFromTimestamp(File::lastModified($file))->lt($deleteBefore)) {
                File::delete($file);
            }
        }

        $this->info('Verified database backup created: '.$backupPath);

        return self::SUCCESS;
    }
}
