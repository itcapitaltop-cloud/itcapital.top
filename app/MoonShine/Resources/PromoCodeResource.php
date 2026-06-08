<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use App\Models\PromoCode;
use App\MoonShine\Pages\PromoCode\PromoCodeIndexPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<PromoCode>
 */
class PromoCodeResource extends ModelResource
{
    protected string $model = PromoCode::class;

    protected string $title = 'Промокоды';

    public function search(): array
    {
        return ['code'];
    }

    public function actions(): array
    {
        return [
            ActionButton::make('Сгенерировать промокод')
                ->inModal(
                    title: fn () => 'Генерация промокода',
                    content: fn () => Block::make([
                        FormBuilder::make()
                            ->action(route('admin.promo-codes.generate'))
                            ->fields([
                                Select::make('Пакет', 'package_definition_id')
                                    ->options(
                                        PackageDefinition::query()
                                            ->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING])
                                            ->orderBy('sort_order')
                                            ->orderBy('id')
                                            ->get()
                                            ->mapWithKeys(fn (PackageDefinition $definition) => [$definition->id => $definition->name . ' (' . $definition->slug . ')'])
                                            ->all()
                                    )
                                    ->required(),
                                Number::make('Снизить порог до суммы', 'reduced_minimum_amount')
                                    ->customAttributes([
                                        'min' => 0,
                                        'step' => 'any',
                                    ])
                                    ->required(),
                            ])
                            ->method('POST')
                            ->async()
                            ->submit('Сгенерировать'),
                    ])
                )
                ->success(),
        ];
    }

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            PromoCodeIndexPage::make($this->title()),
        ];
    }

    public function query(): Builder
    {
        return parent::query()
            ->with(['createdByAdmin'])
            ->latest('created_at');
    }

    public function getActiveActions(): array
    {
        return [];
    }

    /**
     * @param PromoCode $item
     * @return array<string, string[]|string>
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
