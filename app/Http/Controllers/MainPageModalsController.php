<?php

namespace App\Http\Controllers;

use App\Models\PartnerLevelPercent;
use App\MoonShine\Resources\SummaryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\TableBuilder;
use MoonShine\Exceptions\FieldException;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Pages\PageComponents;
use Throwable;

class MainPageModalsController extends Controller
{
    /**
     * @throws FieldException
     * @throws \Throwable
     */
    public function percents(): string
    {
        $resource = app(SummaryResource::class);

        $formPercents = FormBuilder::make(
            action: route('modal.percents.save'),
        )
            ->name('global-percent-form')
            ->async()
            ->fields([
                TableBuilder::make()
                    ->editable()
                    ->fields([
                        Number::make('Ранг', 'partner_level_id')
                            ->customAttributes(['class' => 'input-invisible rank-width'])->readonly(),
                        Text::make('Тип премии', 'bonus_type')
                            ->customAttributes(['class' => 'input-invisible'])
                            ->readonly(),
                        Number::make('Линия 1', 'line_1')
                            ->step(0.01),
                        Number::make('Линия 2', 'line_2')
                            ->step(0.01),
                        Number::make('Линия 3', 'line_3')
                            ->step(0.01),
                        Number::make('Линия 4', 'line_4')
                            ->step(0.01),
                        Number::make('Линия 5', 'line_5')
                            ->step(0.01),
                        Number::make('Линия 6', 'line_6')
                            ->step(0.01),
                        Number::make('Линия 7', 'line_7')
                            ->step(0.01),
                        Number::make('Линия 8', 'line_8')
                            ->step(0.01),
                        Number::make('Линия 9', 'line_9')
                            ->step(0.01),
                        Number::make('Линия 10', 'line_10')
                            ->step(0.01),
                        Number::make('Линия 11', 'line_11')
                            ->step(0.01),
                        Number::make('Линия 12', 'line_12')
                            ->step(0.01),
                        Number::make('Линия 13', 'line_13')
                            ->step(0.01),
                        Number::make('Линия 14', 'line_14')
                            ->step(0.01),
                        Number::make('Линия 15', 'line_15')
                            ->step(0.01),
                        Number::make('Линия 16', 'line_16')
                            ->step(0.01),
                        Number::make('Линия 17', 'line_17')
                            ->step(0.01),
                        Number::make('Линия 18', 'line_18')
                            ->step(0.01),
                        Number::make('Линия 19', 'line_19')
                            ->step(0.01),
                        Number::make('Линия 20', 'line_20')
                            ->step(0.01),
                    ])
                    ->items(
                        PartnerLevelPercent::asGridRows(common: true)
                    )
                    ->customAttributes(
                        [
                            'class' => 'table-partners-percents',
                        ])
                    ->tdAttributes(function (mixed $data, int $row, int $cell, ComponentAttributeBag $attr) {
                        $class = match (true) {
                            $cell === 0 => 'col-sticky-0',
                            $cell === 1 => 'col-sticky-80 w-100',
                            default     => 'w-140',
                        };

                        $existing = trim((string) $attr->get('class', ''));
                        $attr->setAttributes(['class' => trim($existing . ' ' . $class)]);
                        return $attr;
                    })
                    ->sticky()
                    ->name('percentsCommon'),
            ])
            ->customAttributes(
                [
                    'data-name' => 'global-percent-form',
                ])
            ->submit('Сохранить');

        return $formPercents->render();
    }

    public function saveGlobalPercents(MoonShineRequest $request): MoonShineJsonResponse
    {
        try {
            $data = $request->all();
            $rows = $data['percentsCommon'] ?? [];
            PartnerLevelPercent::truncate();
//            Log::channel('source')->debug($data['percentsCommon']);
            $newRows = [];
            foreach ($rows as $row) {
                foreach (range(1, 20) as $line) {
                    if (!isset($row["line_$line"])) continue;
                    $percent = $row["line_$line"];
                    if ($percent === '' || $percent === false || $percent === null) {
                        continue;
                    }
                    $newRows[] = [
                        'partner_level_id' => $row['partner_level_id'],
                        'bonus_type'       => $row['bonus_type'],
                        'line'             => $line,
                        'percent'          => $percent,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                }
            }

            if ($newRows) {
                PartnerLevelPercent::insert($newRows);
            }

            return MoonShineJsonResponse::make()
                ->toast('Сохранено')
                ->redirect(request()->headers->get('referer') ?? '/');
        } catch (Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }
}
