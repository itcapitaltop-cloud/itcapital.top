<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Review;

use App\Models\Review;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class ReviewIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Автор', 'user_id', formatted: fn (Review $item): string => $item->user->first_name . ' ' . $item->user->last_name),
            Number::make('Рейтинг', 'rating')->sortable(),
            Text::make('Отзыв', 'body', formatted: fn (Review $item): string => mb_strimwidth($item->body, 0, 80, '...')),
            Text::make('Статус', 'status', formatted: fn (Review $item): string => $item->status->label())->sortable(),
            Date::make('Дата', 'created_at')->format('d.m.Y H:i')->sortable(),
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
