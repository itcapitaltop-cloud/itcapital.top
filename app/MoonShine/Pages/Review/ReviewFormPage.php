<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Review;

use App\Enums\ReviewStatusEnum;
use App\Models\Review;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Grid;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Pages\Crud\FormPage;
use Throwable;

class ReviewFormPage extends FormPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Grid::make([
                Column::make([
                    Block::make([
                        Textarea::make('Отзыв', 'body')
                            ->required()
                            ->customAttributes(['rows' => 6, 'maxlength' => 1000])
                            ->hint('Максимум 1000 символов'),
                    ]),
                ])->columnSpan(8, 12),
                Column::make([
                    Block::make([
                        ID::make()->hideOnForm(),
                        Text::make('Автор', 'user_id', formatted: fn (Review $item): string => $item->user->first_name . ' ' . $item->user->last_name)
                            ->hideOnForm(),
                        Date::make('Дата', 'created_at')
                            ->format('d.m.Y H:i')
                            ->hideOnForm(),
                        Number::make('Рейтинг', 'rating')
                            ->required()
                            ->min(1)
                            ->max(5)
                            ->hint('От 1 до 5'),
                        Select::make('Статус', 'status')
                            ->options(ReviewStatusEnum::options())
                            ->required()
                            ->changeFill(fn (Review $item): string => $item->status->value),
                    ]),
                ])->columnSpan(4, 12),
            ]),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<MoonShineComponent>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
