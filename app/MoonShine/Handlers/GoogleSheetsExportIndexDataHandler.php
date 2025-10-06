<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;
use Illuminate\Support\Facades\Storage;
use MoonShine\ImportExport\ExportHandler;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GoogleSheetsExportIndexDataHandler extends ExportHandler
{
    protected string $spreadsheetId;

    public function spreadsheetId(string $id): static
    {
        $this->spreadsheetId = $id;

        return $this;
    }

    public function handle(): MoonShineJsonResponse
    {
        if ($this->isQueue()) {
            return MoonShineJsonResponse::make()
                ->toast(__('moonshine::ui.resource.queued'))
                ->redirect(request()->headers->get('referer') ?? '/');
        }

        parent::handle();

        $fullPath = Storage::disk($this->disk)->path($this->dir);
        $fullName = $fullPath . $this->filename . '.xlsx';

        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($fullName);

        app(GoogleSheetsUploaderContract::class)->uploadSheets(
            $this->spreadsheetId,
            collect($spreadsheet->getAllSheets())
                ->map(fn ($sheet) => $sheet->toArray())
                ->toArray()
        );

        return MoonShineJsonResponse::make()
            ->toast('Экспорт в Google Sheets завершен')
            ->redirect(request()->headers->get('referer') ?? '/');
    }
}
