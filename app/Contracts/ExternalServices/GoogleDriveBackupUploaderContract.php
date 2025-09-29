<?php

namespace App\Contracts\ExternalServices;

interface GoogleDriveBackupUploaderContract
{
    public function uploadBackup(string $localPath, string $remoteName): void;

    /**
     * @return array Массив ["id" => ..., "name" => ..., "createdTime" => ...]
     */
    public function getBackupFiles(): array;

    public function deleteFile(string $fileId): void;
}
