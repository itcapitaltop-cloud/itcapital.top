<?php

namespace App\Console\Commands;

use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupProcessingCommand extends Command
{
    /**
     * Сколько последних бэкапов хранить на Google диске.
     */
    private const KEEP_BACKUPS = 14;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-processing-command';

    protected $description = 'Создание ежедневного бэкапа базы Postgres и загрузка на Google диск, хранится 14 последних';

    protected GoogleDriveBackupUploaderContract $drive;

    public function __construct(GoogleDriveBackupUploaderContract $drive)
    {
        parent::__construct();
        $this->drive = $drive;
    }

    public function handle(): int
    {
        // 1. Имя и относительный путь к бэкапу
        $date = Carbon::now()->format('Y-m-d_H-i-s');

        $filename = "backup_{$date}.sql";
        $relativePath = 'backup/' . $filename;
        $absolutePath = storage_path("app/{$relativePath}");
        $errorPath = $absolutePath . '.err';

        // 2. Дамп Postgres
        $db = config('database.connections.pgsql.database');
        $user = config('database.connections.pgsql.username');
        $pass = config('database.connections.pgsql.password');
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port', 5432);

        $cmd = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -U %s -p %d %s > %s 2> %s',
            escapeshellarg($pass),
            escapeshellarg($host),
            escapeshellarg($user),
            $port,
            escapeshellarg($db),
            escapeshellarg($absolutePath),
            escapeshellarg($errorPath)
        );
        $result = null;

        exec($cmd, $output, $result);

        $stderr = trim((string) @file_get_contents($errorPath));
        @unlink($errorPath);

        if ($result !== 0) {
            $this->error("Ошибка при выполнении pg_dump (код {$result}): {$stderr}");

            Log::error('[BackupProcessingCommand.handle] pg_dump failed', [
                'exit_code' => $result,
                'stderr' => $stderr,
            ]);

            Storage::disk('local')->delete($relativePath);

            return self::FAILURE;
        }

        // 3. Загрузка на Google Drive
        $this->drive->uploadBackup($relativePath, $filename);

        // 4. Ротация: хранить только KEEP_BACKUPS последних
        $files = $this->drive->getBackupFiles();

        if (count($files) > self::KEEP_BACKUPS) {
            foreach (array_slice($files, 0, count($files) - self::KEEP_BACKUPS) as $file) {
                $this->drive->deleteFile($file['id']);
            }
        }

        // 5. Удалить локальный бэкап через Storage
        Storage::disk('local')->delete($relativePath);

        $this->info("Бэкап {$filename} создан и загружен на Google диск.");

        return self::SUCCESS;
    }
}
