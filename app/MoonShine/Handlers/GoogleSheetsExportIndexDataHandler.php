<?php

//declare(strict_types=1);

namespace App\MoonShine\Handlers;

use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;
use Google\Exception;
use Google\Service\Sheets\Spreadsheet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MoonShine\Exceptions\ActionException;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineUI;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use MoonShine\Handlers\Handler;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Google\Client;
use Google\Service\Drive as DriveService;
use Google\Service\Sheets as GoogleSheets;
use Google_Service_Sheets_ValueRange;

class GoogleSheetsExportIndexDataHandler extends ExportHandler
{
    /**
     * @throws ActionException
     * @throws Exception
     */

    protected string $spreadsheetId;

    public function spreadsheetId(string $id): static
    {
        $this->spreadsheetId = $id;
        return $this;
    }

    public function handle(): RedirectResponse
    {
        if (! $this->hasResource()) {
            throw new ActionException('Resource is required for action');
        }

        if ($this->isQueue()) {
            // Job here

            MoonShineUI::toast(
                __('moonshine::ui.resource.queued')
            );

            return back();
        }

        parent::handle();

        $fullPath = Storage::disk($this->disk)->path($this->dir);

        $fullName = $fullPath.$this->filename.'.xlsx';

        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($fullName);

        $uploader = app(GoogleSheetsUploaderContract::class);

        $uploader->uploadSheets(
            $this->spreadsheetId,
            collect($spreadsheet->getAllSheets())
                ->map(fn($sheet) => $sheet->toArray())
                ->toArray()
        );

        return back();
    }


}
