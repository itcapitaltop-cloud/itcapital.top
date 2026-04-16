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
    protected ?GoogleSheets $service = null;

    /**
     * @throws \Google\Exception
     */
    public function __construct()
    {
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
        $client->setScopes([GoogleSheets::SPREADSHEETS]);
        $this->service = new GoogleSheets($client);
    }

    /**
     * @throws Exception
     */
    public function uploadSheets(string $spreadsheetId, array $sheetsData): void
    {
        $this->ensureConfigured();

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

            $normalizedValues = $this->normalizeValues($values);

            $body = new ValueRange(['values' => $normalizedValues]);

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

    /**
     * Normalize values to ensure proper format for Google Sheets API
     */
    protected function normalizeValues(array $values): array
    {
        return array_map(function ($row) {
            if (! is_array($row)) {
                return [$row ?? ''];
            }

            // Convert null values to empty strings and re-index
            return array_map(fn ($cell) => $cell ?? '', array_values($row));
        }, array_values($values));
    }

    private function ensureConfigured(): void
    {
        if ($this->service === null) {
            throw new RuntimeException('Google Sheets is not configured.');
        }
    }
}
