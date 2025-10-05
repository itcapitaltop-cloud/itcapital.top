<?php

namespace App\Repositories;

use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;
use Google\Client;
use Google\Service\Exception;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\ValueRange;
use RuntimeException;

class GoogleSheetsUploaderRepository implements GoogleSheetsUploaderContract
{
    protected GoogleSheets $service;

    /**
     * @throws \Google\Exception
     */
    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig([
            'type' => config('services.google.type'),
            'project_id' => config('services.google.project_id'),
            'private_key_id' => config('services.google.private_key_id'),
            'private_key' => str_replace('\\n', "\n", config('services.google.private_key')),
            'client_email' => config('services.google.client_email'),
            'client_id' => config('services.google.client_id'),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => config('services.google.token_uri'),
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => config('services.google.client_x509_cert_url'),
            'client_secret' => null,
        ]);
        $client->setScopes([GoogleSheets::SPREADSHEETS]);
        $this->service = new GoogleSheets($client);

    }

    /**
     * @throws Exception
     */
    public function uploadSheets(string $spreadsheetId, array $sheetsData): void
    {
        // Fetch remote sheet titles
        $remote = $this->service->spreadsheets->get($spreadsheetId);
        $remoteTitles = array_map(
            fn ($s) => $s->getProperties()->getTitle(),
            $remote->getSheets()
        );

        foreach ($sheetsData as $idx => $values) {
            if (! isset($remoteTitles[$idx])) {
                throw new RuntimeException("Remote sheet at index {$idx} not found");
            }

            $title = $remoteTitles[$idx];
            $range = "'{$title}'!A1";

            $body = new ValueRange(['values' => $values]);

            $this->service
                ->spreadsheets_values
                ->update(
                    $spreadsheetId,
                    $range,
                    $body,
                    ['valueInputOption' => 'RAW']
                );
        }
    }
}
