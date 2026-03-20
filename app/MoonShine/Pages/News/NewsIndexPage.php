<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\News;

use App\Models\News;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Pages\Crud\IndexPage;
use Throwable;

class NewsIndexPage extends IndexPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Заголовок', 'title', formatted: fn (News $item): string => $item->translation('title')),
            Text::make('Категория', 'category', formatted: fn (News $item): string => $item->category->label('ru')),
            Date::make('Опубликовано', 'published_at')->format('d.m.Y H:i'),
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
