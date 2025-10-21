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
     * @throws \JsonException
     */
    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(json_decode(base64_decode(config('services.google.service_account')), true, 512, JSON_THROW_ON_ERROR));
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
