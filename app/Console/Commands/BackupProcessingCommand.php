<?php

namespace App\Console\Commands;

use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupProcessingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-processing-command';
    protected $description = 'Создание ежедневного бэкапа базы Postgres и загрузка на Google диск, хранится 5 последних';

    protected GoogleDriveBackupUploaderContract $drive;

    public function __construct(GoogleDriveBackupUploaderContract $drive)
    {
        parent::__construct();
        $this->drive = $drive;
    }

    public function handle()
    {
        // 1. Имя и относительный путь к бэкапу
        $date = Carbon::now()->format('Y-m-d_H-i-s');

        $anchor = CarbonImmutable::create(1970, 1, 5);

        $nowW   = now()->startOfWeek(CarbonInterface::MONDAY);

        $q = $anchor->diffInWeeks($nowW) % 2 === 0;

        $filename = "backup_{$date}.sql";
        $relativePath = 'backup/' . $filename;
        $absolutePath = storage_path("app/{$relativePath}");

        // 2. Дамп Postgres
        $db = config('database.connections.pgsql.database');
        $user = config('database.connections.pgsql.username');
        $pass = config('database.connections.pgsql.password');
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port', 5432);

        $cmd = 'PGPASSWORD=' . escapeshellarg($pass)
            . ' pg_dump'
            . ' -h ' . escapeshellarg($host)
            . ' -U ' . escapeshellarg($user)
            . ' -p ' . escapeshellarg($port)
            . ' ' . escapeshellarg($db)
            . ' > ' . escapeshellarg($absolutePath);

        $result = null;

        exec($cmd, $output, $result);
        if ($result !== 0) {
            $this->error("Ошибка при выполнении pg_dump");

            Storage::disk('local')->delete($relativePath);
            return;
        }


        // 3. Загрузка на Google Drive
        $this->drive->uploadBackup($relativePath, $filename);

        // 4. Ротация: хранить только 5 последних
        $files = $this->drive->getBackupFiles();
        if (count($files) > 14) {
            foreach (array_slice($files, 0, count($files) - 14) as $file) {
                $this->drive->deleteFile($file['id']);
            }
        }

        // 5. Удалить локальный бэкап через Storage
        Storage::disk('local')->delete($relativePath);

        $this->info("Бэкап {$filename} создан и загружен на Google диск.");
    }
}
