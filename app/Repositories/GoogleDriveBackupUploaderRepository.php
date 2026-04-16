<?php

namespace App\Repositories;

use App\Contracts\ExternalServices\GoogleDriveBackupUploaderContract;
use Google\Client;
use Google\Exception;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GoogleDriveBackupUploaderRepository implements GoogleDriveBackupUploaderContract
{
    protected ?Drive $service = null;

    protected ?string $folderId;

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function __construct()
    {
        $this->folderId = config('services.google.folder_id', null);

        $serviceAccount = config('services.google.service_account');

        if (! is_string($serviceAccount) || $serviceAccount === '') {
            return;
        }

        $decodedConfig = base64_decode($serviceAccount, true);

        if ($decodedConfig === false) {
            return;
        }

        $credentials = json_decode($decodedConfig, true);

        if (! is_array($credentials)) {
            return;
        }

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope(Drive::DRIVE);
        $this->service = new Drive($client);
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function uploadBackup(string $localPath, string $remoteName): void
    {
        $this->ensureConfigured();

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
            'fields' => 'id, name, createdTime',
        ]);
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function getBackupFiles(): array
    {
        $this->ensureConfigured();

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
        usort($files, fn ($a, $b) => strtotime($a->getCreatedTime()) <=> strtotime($b->getCreatedTime()));

        return array_map(fn ($f) => [
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
        $this->ensureConfigured();

        $this->service->files->delete($fileId);
    }

    private function ensureConfigured(): void
    {
        if ($this->service === null) {
            throw new RuntimeException('Google Drive is not configured.');
        }
    }
}
