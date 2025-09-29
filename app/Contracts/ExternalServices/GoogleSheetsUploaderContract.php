<?php

namespace App\Contracts\ExternalServices;

interface GoogleSheetsUploaderContract
{
    /**
     * Upload multiple sheets of data into an existing Google Spreadsheet.
     *
     * @param  string               $spreadsheetId  Google Sheets file ID
     * @param  array<int, array[]>  $sheetsData     Array of 2-dimensional arrays: [sheetIndex => [ [row1col1, …], … ], …]
     * @return void
     */
    public function uploadSheets(string $spreadsheetId, array $sheetsData): void;
}
