<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use App\Contracts\ExternalServices\GoogleSheetsUploaderContract;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MoonShine\Decorations\Grid;
use MoonShine\Enums\Layer;
use MoonShine\Enums\PageType;
use MoonShine\Exceptions\ActionException;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Metrics\ValueMetric;
use MoonShine\MoonShineUI;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use MoonShine\Handlers\Handler;

class SummarySheetsExportHandler extends ExportHandler
{
    /**
     * @throws ActionException
     */

    protected string $spreadsheetId;

    public function spreadsheetId(string $id): static
    {
        $this->spreadsheetId = $id;
        return $this;
    }

    public function handle(): Response
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

        $resource = $this->getResource();
        $page = $resource
            ->getPages()
            ->findByType(PageType::INDEX);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Общая сводка');

        $row = 1;
        foreach ($page->getLayerComponents(Layer::MAIN) as $component) {

            if ($component instanceof Grid) {
                foreach ($component->getFields() as $child) {
                    if ($child instanceof ValueMetric) {
                        $sheet->setCellValue("A{$row}", $child->label() . ' (пользователи)');
                        $sheet->setCellValue("B{$row}", $child->value);
                        $row++;
                    }
                }
            }
        }

        $row++;

        // 2) BottomLayer: просто выполняем запросы к БД
        // --- Депозиты ---
        $totalDepositsCount = Transaction::query()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->count();
        $totalDepositsSum   = Transaction::query()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->sum('amount');

        $weekDepositsCount  = Transaction::query()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '>=', now()->startOfWeek())
            ->count();
        $weekDepositsSum    = Transaction::query()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '>=', now()->startOfWeek())
            ->sum('amount');

        $monthDepositsCount = Transaction::query()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '>=', now()->startOfMonth())
            ->count();
        $monthDepositsSum   = Transaction::query()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '>=', now()->startOfMonth())
            ->sum('amount');

        // Записываем депозиты
        $sheet->setCellValue("A{$row}", 'Всего депозитов');
        $sheet->setCellValue("B{$row}", $totalDepositsCount);
        $sheet->setCellValue("C{$row}", $totalDepositsSum);
        $row++;

        $sheet->setCellValue("A{$row}", 'Новые за неделю (депозиты)');
        $sheet->setCellValue("B{$row}", $weekDepositsCount);
        $sheet->setCellValue("C{$row}", $weekDepositsSum);
        $row++;

        $sheet->setCellValue("A{$row}", 'Новые за месяц (депозиты)');
        $sheet->setCellValue("B{$row}", $monthDepositsCount);
        $sheet->setCellValue("C{$row}", $monthDepositsSum);
        $row++;

        // Пустая строка перед выводами
        $row++;

        // --- Выводы ---
        $totalWithdrawsCount = Transaction::query()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->count();
        $totalWithdrawsSum   = Transaction::query()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->sum('amount');

        $weekWithdrawsCount  = Transaction::query()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $weekWithdrawsSum    = Transaction::query()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('amount');

        $monthWithdrawsCount = Transaction::query()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $monthWithdrawsSum   = Transaction::query()
            ->where('trx_type', TrxTypeEnum::WITHDRAW->value)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        // Записываем выводы
        $sheet->setCellValue("A{$row}", 'Всего выводов');
        $sheet->setCellValue("B{$row}", $totalWithdrawsCount);
        $sheet->setCellValue("C{$row}", $totalWithdrawsSum);
        $row++;

        $sheet->setCellValue("A{$row}", 'За неделю (выводы)');
        $sheet->setCellValue("B{$row}", $weekWithdrawsCount);
        $sheet->setCellValue("C{$row}", $weekWithdrawsSum);
        $row++;

        $sheet->setCellValue("A{$row}", 'За месяц (выводы)');
        $sheet->setCellValue("B{$row}", $monthWithdrawsCount);
        $sheet->setCellValue("C{$row}", $monthWithdrawsSum);
        // 3) Сохраняем xlsx-файл локально
        $fullPath = Storage::disk($this->disk)->path($this->dir);
        $fullName = $fullPath . $this->filename . '.xlsx';
        (new Xlsx($spreadsheet))->save($fullName);

        // 4) Читаем и заливаем в Google Sheets
        $reader    = IOFactory::createReader('Xlsx');
        $forUpload = $reader->load($fullName);

        $rawSheets = collect($forUpload->getAllSheets())
            ->map(fn($sheet) => $sheet->toArray())
            ->toArray();

        app(GoogleSheetsUploaderContract::class)
            ->uploadSheets(
                $this->spreadsheetId,
                collect($forUpload->getAllSheets())
                    ->map(fn($sheet) => $sheet->toArray())
                    ->toArray()
            );

        return back();
    }
}
