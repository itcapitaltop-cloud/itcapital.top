<?php

use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Кладёт в PATH подставной pg_dump с заданным поведением.
 */
function fakePgDump(string $body): string
{
    $binDir = storage_path('framework/testing/fake-bin');

    File::ensureDirectoryExists($binDir);
    File::put($binDir . '/pg_dump', "#!/bin/sh\n" . $body . "\n");
    chmod($binDir . '/pg_dump', 0755);

    putenv('PATH=' . $binDir . ':' . getenv('PATH'));

    return $binDir;
}

beforeEach(function () {
    $this->originalPath = getenv('PATH');
    File::ensureDirectoryExists(storage_path('app/backup'));
});

afterEach(function () {
    putenv('PATH=' . $this->originalPath);
    File::deleteDirectory(storage_path('framework/testing/fake-bin'));
    File::delete(File::glob(storage_path('app/backup/backup_*')));
});

it('fails with a non-zero exit code and logs stderr when pg_dump fails', function () {
    fakePgDump('echo "pg_dump: error: connection failed" >&2; exit 1');

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === '[BackupProcessingCommand.handle] pg_dump failed'
                && $context['exit_code'] === 1
                && str_contains($context['stderr'], 'connection failed');
        });

    $drive = Mockery::mock(GoogleDriveBackupUploaderContract::class);
    $drive->shouldNotReceive('uploadBackup');
    $this->app->instance(GoogleDriveBackupUploaderContract::class, $drive);

    $this->artisan('app:backup-processing-command')->assertExitCode(1);

    expect(File::glob(storage_path('app/backup/backup_*')))->toBeEmpty();
});

it('fails when pg_dump is not installed', function () {
    putenv('PATH=' . storage_path('framework/testing/empty-bin'));

    Log::shouldReceive('error')->once();

    $drive = Mockery::mock(GoogleDriveBackupUploaderContract::class);
    $drive->shouldNotReceive('uploadBackup');
    $this->app->instance(GoogleDriveBackupUploaderContract::class, $drive);

    $this->artisan('app:backup-processing-command')->assertExitCode(1);
});

it('uploads the dump and removes the local copy on success', function () {
    fakePgDump('echo "-- dump"; exit 0');

    $drive = Mockery::mock(GoogleDriveBackupUploaderContract::class);
    $drive->shouldReceive('uploadBackup')
        ->once()
        ->withArgs(function (string $localPath, string $remoteName): bool {
            return str_starts_with($localPath, 'backup/backup_')
                && str_ends_with($remoteName, '.sql');
        });
    $drive->shouldReceive('getBackupFiles')->once()->andReturn([]);
    $drive->shouldNotReceive('deleteFile');
    $this->app->instance(GoogleDriveBackupUploaderContract::class, $drive);

    $this->artisan('app:backup-processing-command')->assertExitCode(0);

    expect(File::glob(storage_path('app/backup/backup_*')))->toBeEmpty();
});

it('deletes the oldest backups beyond the retention limit', function () {
    fakePgDump('echo "-- dump"; exit 0');

    $files = collect(range(1, 16))
        ->map(fn (int $i): array => ['id' => "file-{$i}", 'name' => "backup_{$i}.sql"])
        ->all();

    $drive = Mockery::mock(GoogleDriveBackupUploaderContract::class);
    $drive->shouldReceive('uploadBackup')->once();
    $drive->shouldReceive('getBackupFiles')->once()->andReturn($files);
    $drive->shouldReceive('deleteFile')->once()->with('file-1');
    $drive->shouldReceive('deleteFile')->once()->with('file-2');
    $this->app->instance(GoogleDriveBackupUploaderContract::class, $drive);

    $this->artisan('app:backup-processing-command')->assertExitCode(0);
});
