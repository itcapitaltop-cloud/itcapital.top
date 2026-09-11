<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Admin\UserCardExportField;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Построитель книги карточки пользователя по эталону «Светлана Волкова.xlsx».
 *
 * Лист «Главная» транспонирован: строка — поле карточки, колонка — человек
 * (`C` — владелец, `D`, `E`, … — рефералы в порядке обхода дерева).
 * Лист «задачи» — пустой журнал встреч с выпадающими списками.
 */
final class UserCardSpreadsheetBuilder
{
    private const FONT_NAME = 'Arial';

    private const BORDER_COLOR = 'FF000000';

    /** Первая колонка с данными человека; `A` — подписи, `B` — итоги. */
    private const FIRST_PERSON_COLUMN = 3;

    private const HEADER_FILL = 'FFFFFFFF';

    private const ROW_HEIGHTS = [1 => 61.5, 2 => 79.5];

    private const DATA_ROW_HEIGHT = 20.25;

    private const LABEL_COLUMN_WIDTH = 45.75;

    private const PERSON_COLUMN_WIDTH = 19.88;

    private const DEFAULT_COLUMN_WIDTH = 12.63;

    /**
     * Оформительские полосы шапки: строка => объединения в терминах смещения
     * от первой колонки с данными. Эталон — `C1:BK1`, `C2:L2`, `R2:AT2`,
     * `AY2:BI2`, `BJ2:BK2`; при меньшем числе людей блоки обрезаются.
     *
     * @var array<int, list<array{int, int}>>
     */
    private const HEADER_MERGES = [
        1 => [[0, 60]],
        2 => [[0, 9], [15, 45], [50, 60], [61, 62]],
    ];

    private const TASKS_SHEET_TITLE = 'задачи';

    private const TASKS_LAST_ROW = 45;

    /** @var list<string> */
    private const TASKS_HEADERS = [
        'Дата встречи',
        'Приоритет',
        'Статус',
        'Встреча',
        'Коментарии и отчет о событии',
        'Дата следующего события',
        'Галочку ставить проверяющий',
        'Примечания',
    ];

    /** @var list<float> */
    private const TASKS_COLUMN_WIDTHS = [20.13, 17.88, 21.13, 17.25, 55.63, 21.38, 20.13, 28.25];

    private const TASKS_HEADER_ROW_HEIGHT = 22.5;

    private const TASKS_DATA_ROW_HEIGHT = 130.5;

    /**
     * @param array{
     *     owner: array{id: int, full_name: string, username: string, line: int, referrer: string, packages_total: float, tokens: float, rank: int, social: string},
     *     referrals: list<array{id: int, full_name: string, username: string, line: int, referrer: string, packages_total: float, tokens: float, rank: int, social: string}>
     * } $cardData
     * @param list<UserCardExportField> $selectedFields
     */
    public function build(array $cardData, array $selectedFields): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName(self::FONT_NAME);

        $people = [$cardData['owner'], ...$cardData['referrals']];

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Главная');

        $lastColumn = self::FIRST_PERSON_COLUMN + count($people) - 1;

        $this->fillMainSheet($sheet, $people, $selectedFields, $lastColumn);
        $this->styleMainSheet($sheet, $selectedFields, $lastColumn);
        $this->addTasksSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        Log::debug('[UserCardSpreadsheetBuilder.build] sheet built', [
            'columns' => count($people),
            'fields' => count($selectedFields),
        ]);

        return $spreadsheet;
    }

    /**
     * @param list<array<string, mixed>> $people
     * @param list<UserCardExportField> $selectedFields
     */
    private function fillMainSheet(Worksheet $sheet, array $people, array $selectedFields, int $lastColumn): void
    {
        foreach ($selectedFields as $field) {
            $row = $field->rowIndex();
            $sheet->setCellValue([1, $row], $field->label());

            foreach ($people as $index => $person) {
                $column = self::FIRST_PERSON_COLUMN + $index;
                $value = $this->value($field, $person);

                if ($value === '') {
                    $placeholder = $field->placeholder();

                    if ($placeholder === null) {
                        continue;
                    }

                    $value = $placeholder;
                }

                if (is_string($value)) {
                    $sheet->setCellValueExplicit([$column, $row], $value, DataType::TYPE_STRING);

                    continue;
                }

                $sheet->setCellValue([$column, $row], $value);
            }
        }

        $this->addTotals($sheet, $selectedFields, $lastColumn);
        $this->addHeaderBands($sheet, $lastColumn);
        $this->applyDimensions($sheet, $lastColumn);

        $sheet->freezePane('B1');
    }

    /**
     * Столбец `B` — сумма по строке. Формула ставится только для выбранных
     * числовых полей, иначе в эталонной ячейке остался бы `SUM` по пустой строке.
     *
     * @param list<UserCardExportField> $selectedFields
     */
    private function addTotals(Worksheet $sheet, array $selectedFields, int $lastColumn): void
    {
        $firstColumnLetter = Coordinate::stringFromColumnIndex(self::FIRST_PERSON_COLUMN);
        $lastColumnLetter = Coordinate::stringFromColumnIndex($lastColumn);

        foreach ([UserCardExportField::PACKAGES_TOTAL, UserCardExportField::TOKENS] as $field) {
            if (! in_array($field, $selectedFields, true)) {
                continue;
            }

            $row = $field->rowIndex();
            $sheet->setCellValue([2, $row], sprintf('=SUM(%s%d:%s%d)', $firstColumnLetter, $row, $lastColumnLetter, $row));
        }
    }

    /**
     * Строки 1–2 — оформительские полосы эталона: высокие пустые блоки
     * с объединениями. Текст заказчик вписывает вручную после выгрузки.
     */
    private function addHeaderBands(Worksheet $sheet, int $lastColumn): void
    {
        foreach (self::HEADER_MERGES as $row => $merges) {
            foreach ($merges as [$startOffset, $endOffset]) {
                $start = self::FIRST_PERSON_COLUMN + $startOffset;
                $end = min(self::FIRST_PERSON_COLUMN + $endOffset, $lastColumn);

                if ($start > $lastColumn || $end <= $start) {
                    continue;
                }

                $sheet->mergeCells([$start, $row, $end, $row]);
            }
        }
    }

    private function applyDimensions(Worksheet $sheet, int $lastColumn): void
    {
        $sheet->getDefaultColumnDimension()->setWidth(self::DEFAULT_COLUMN_WIDTH);
        $sheet->getColumnDimension('A')->setWidth(self::LABEL_COLUMN_WIDTH)->setCollapsed(true);

        for ($column = 2; $column <= $lastColumn; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(self::PERSON_COLUMN_WIDTH);
        }

        /*
         * Колонка итогов служебная: заполнены только «Пакеты Сумма» и «Токены»,
         * остальные строки пустые. В эталоне она свёрнута в группу — раскрывается
         * кликом по «+», формулы при этом продолжают считаться.
         */
        $sheet->getColumnDimension('B')->setVisible(false)->setOutlineLevel(1);

        foreach (self::ROW_HEIGHTS as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }

        [$firstDataRow, $lastDataRow] = UserCardExportField::rowRange();

        for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(self::DATA_ROW_HEIGHT);
        }
    }

    /**
     * @param array<string, mixed> $person
     */
    private function value(UserCardExportField $field, array $person): string|int|float
    {
        return match ($field) {
            UserCardExportField::FULL_NAME => (string) $person['full_name'],
            UserCardExportField::USERNAME => (string) $person['username'],
            UserCardExportField::LINE_NUMBER => (int) $person['line'],
            UserCardExportField::REFERRER => (string) $person['referrer'],
            UserCardExportField::SOCIAL_NETWORKS => (string) $person['social'],
            UserCardExportField::PACKAGES_TOTAL => (float) $person['packages_total'],
            UserCardExportField::TOKENS => (float) $person['tokens'],
            UserCardExportField::RANK => (int) $person['rank'],
            // «Город», «Телефон» и «Обучение онлайн» в системе не хранятся —
            // выгрузка ставит заглушку, заказчик заполняет её вручную.
            UserCardExportField::CITY, UserCardExportField::PHONE, UserCardExportField::EDUCATION => '',
        };
    }

    /**
     * @param list<UserCardExportField> $selectedFields
     */
    private function styleMainSheet(Worksheet $sheet, array $selectedFields, int $lastColumn): void
    {
        $lastColumnLetter = Coordinate::stringFromColumnIndex($lastColumn);

        $this->styleHeaderBands($sheet, $lastColumnLetter);

        foreach ($selectedFields as $field) {
            $row = $field->rowIndex();
            $style = $field->style();

            $this->applyCellStyle($sheet, sprintf('A%d', $row), $style['label'], $row);
            $this->applyCellStyle(
                $sheet,
                sprintf('%s%d:%s%d', Coordinate::stringFromColumnIndex(2), $row, $lastColumnLetter, $row),
                $style['value'],
                $row
            );
        }
    }

    private function styleHeaderBands(Worksheet $sheet, string $lastColumnLetter): void
    {
        $sheet->getStyle(sprintf('A1:%s1', $lastColumnLetter))->applyFromArray([
            'font' => ['name' => self::FONT_NAME, 'size' => 18, 'bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle(sprintf('A2:%s2', $lastColumnLetter))->applyFromArray([
            'font' => ['name' => self::FONT_NAME, 'size' => 14, 'bold' => true, 'color' => ['argb' => 'FFFF0000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_FILL]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    /**
     * @param array{fill: ?string, size: int, bold: bool} $style
     */
    private function applyCellStyle(Worksheet $sheet, string $range, array $style, int $row): void
    {
        [$firstDataRow] = UserCardExportField::rowRange();

        /*
         * Верхняя граница первой строки данных в эталоне отсутствует —
         * она сливалась бы с оформительской полосой шапки.
         */
        $borders = [
            'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER_COLOR]],
            'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER_COLOR]],
            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER_COLOR]],
        ];

        if ($row > $firstDataRow) {
            $borders['top'] = ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER_COLOR]];
        }

        $definition = [
            'font' => ['name' => self::FONT_NAME, 'size' => $style['size'], 'bold' => $style['bold']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => $borders,
        ];

        if ($style['fill'] !== null) {
            $definition['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $style['fill']]];
        }

        $sheet->getStyle($range)->applyFromArray($definition);
    }

    private function addTasksSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(self::TASKS_SHEET_TITLE);

        foreach (self::TASKS_HEADERS as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))
                ->setWidth(self::TASKS_COLUMN_WIDTHS[$index]);
        }

        $sheet->getRowDimension(1)->setRowHeight(self::TASKS_HEADER_ROW_HEIGHT);

        for ($row = 2; $row <= self::TASKS_LAST_ROW; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(self::TASKS_DATA_ROW_HEIGHT);
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['name' => self::FONT_NAME, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle(sprintf('A1:H%d', self::TASKS_LAST_ROW))->applyFromArray([
            'font' => ['name' => self::FONT_NAME],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER_COLOR]],
            ],
        ]);

        // Колонка «Галочку ставить проверяющий» — отметка по центру ячейки.
        $sheet->getStyle(sprintf('G2:G%d', self::TASKS_LAST_ROW))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->addTasksValidations($sheet);

        $table = new Table(sprintf('A1:H%d', self::TASKS_LAST_ROW), 'Задачи');
        $table->setShowHeaderRow(true);
        $table->setStyle(
            (new TableStyle())
                ->setTheme(TableStyle::TABLE_STYLE_LIGHT9)
                ->setShowRowStripes(true)
                ->setShowFirstColumn(true)
                ->setShowLastColumn(true)
        );
        $sheet->addTable($table);

        $sheet->freezePane('A2');
    }

    private function addTasksValidations(Worksheet $sheet): void
    {
        $lists = [
            'B' => '"1 встреча,2 встреча,3 встреча"',
            'C' => '"Встреча онлайн,Встреча офлайн,Созвон"',
            'D' => '"Назначено,Выполняется,Перенесено,Выполнено"',
        ];

        foreach ($lists as $column => $formula) {
            $validation = $this->validation(DataValidation::TYPE_LIST);
            $validation->setFormula1($formula);
            $validation->setShowDropDown(true);

            $sheet->setDataValidation(
                sprintf('%s2:%s%d', $column, $column, self::TASKS_LAST_ROW),
                $validation
            );
        }

        /*
         * «Дата встречи» и «Дата следующего события»: эталон проверяет, что
         * значение разбирается как дата либо уже отформатировано как дата.
         */
        foreach (['A', 'F'] as $column) {
            $validation = $this->validation(DataValidation::TYPE_CUSTOM);
            $validation->setFormula1(sprintf(
                'OR(NOT(ISERROR(DATEVALUE(%s2))), AND(ISNUMBER(%s2), LEFT(CELL("format", %s2))="D"))',
                $column,
                $column,
                $column
            ));

            $sheet->setDataValidation(
                sprintf('%s2:%s%d', $column, $column, self::TASKS_LAST_ROW),
                $validation
            );
        }
    }

    private function validation(string $type): DataValidation
    {
        $validation = new DataValidation();
        $validation->setType($type);
        $validation->setAllowBlank(true);
        $validation->setShowErrorMessage(true);

        return $validation;
    }
}
