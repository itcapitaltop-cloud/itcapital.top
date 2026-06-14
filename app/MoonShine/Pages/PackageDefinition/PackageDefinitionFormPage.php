<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\PackageDefinition;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\Package\PackageDefinition;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\FormPage;

class PackageDefinitionFormPage extends FormPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable()->hideOnForm(),
                Select::make('Системная категория', 'type')
                    ->options($this->packageTypeOptions())
                    ->required(),
                Text::make('Название', 'name')
                    ->required()
                    ->customAttributes(['maxlength' => 100]),
                Number::make('Процент прибыли по умолчанию', 'default_profit_percent')
                    ->customAttributes([
                        'min' => 0,
                        'max' => 999.99,
                        'step' => '0.01',
                    ])
                    ->required(),
                Number::make('Минимальная сумма старта', 'min_start_amount')
                    ->customAttributes([
                        'min' => 0,
                        'step' => '0.00000001',
                    ])
                    ->required(),
                Number::make('Срок работы, месяцев', 'duration_months')
                    ->required()
                    ->hint('Минимум 1 месяц.')
                    ->customAttributes([
                        'min' => 1,
                        'max' => 120,
                        'step' => 1,
                    ]),
                Image::make('Изображение карточки', 'card_image_path')
                    ->disk('public')
                    ->dir('package-definitions')
                    ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp']),
                Switcher::make('Доступен в ЛК', 'is_active'),
                Number::make('Порядок сортировки', 'sort_order')
                    ->customAttributes([
                        'min' => 0,
                        'step' => 1,
                    ]),
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function packageTypeOptions(): array
    {
        $options = [
            PackageTypeEnum::STANDARD->value => 'Обычный',
            PackageTypeEnum::PRESENT->value => 'Подарочный',
        ];

        $currentItem = $this->getResource()?->getItem();

        if ($currentItem instanceof PackageDefinition
            && $currentItem->type instanceof PackageTypeEnum
            && ! array_key_exists($currentItem->type->value, $options)
            && ! in_array($currentItem->type, [PackageTypeEnum::STAKING, PackageTypeEnum::ARCHIVE], true)
        ) {
            $options[$currentItem->type->value] = $currentItem->type->getName();
        }

        return $options;
    }
}
