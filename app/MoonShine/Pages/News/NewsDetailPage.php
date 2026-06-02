<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\News;

use App\Models\News;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Pages\Crud\DetailPage;
use Throwable;

class NewsDetailPage extends DetailPage
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
            Image::make('Изображение', 'image')->disk('public')->dir('news'),
            Textarea::make('Превью для мобильных', 'mobile_preview', formatted: fn (News $item): string => $item->translation('mobile_preview')),
            Textarea::make('Превью для веба', 'web_preview', formatted: fn (News $item): string => $item->translation('web_preview')),
            Textarea::make('Текст новости', 'content', formatted: fn (News $item): string => $item->translation('content')),
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
