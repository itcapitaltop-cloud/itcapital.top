<?php

namespace App\Repositories;

use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use Google\Client;
use Google\Exception;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveBackupUploaderRepository implements GoogleDriveBackupUploaderContract
{
    protected Drive $service;
    protected ?string $folderId;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials'));
        $client->addScope(Drive::DRIVE);
        $this->service = new Drive($client);
        $this->folderId = config('services.google.folder_id', null);
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function uploadBackup(string $localPath, string $remoteName): void
    {
        $fileMetadata = new DriveFile([
            'name' => $remoteName,
        ]);
        if ($this->folderId) {
            $fileMetadata->setParents([$this->folderId]);
        }
        $content = Storage::disk('local')->get($localPath);
        $this->service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/sql',
            'uploadType' => 'multipart',
            'fields' => 'id, name, createdTime'
        ]);
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function getBackupFiles(): array
    {
        $q = "name contains 'backup_' and name contains '.sql'";
        if ($this->folderId) {
            $q .= " and '{$this->folderId}' in parents";
        }
        $list = $this->service->files->listFiles([
            'q' => $q,
            'orderBy' => 'createdTime',
            'fields' => 'files(id, name, createdTime)',
        ]);
        $files = $list->getFiles() ?: [];
        usort($files, fn($a, $b) => strtotime($a->getCreatedTime()) <=> strtotime($b->getCreatedTime()));
        return array_map(fn($f) => [
            'id' => $f->getId(),
            'name' => $f->getName(),
            'createdTime' => $f->getCreatedTime(),
        ], $files);
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function deleteFile(string $fileId): void
    {
        $this->service->files->delete($fileId);
    }
}
