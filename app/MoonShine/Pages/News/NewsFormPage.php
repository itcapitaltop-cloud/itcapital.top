<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\News;

use App\Enums\NewsCategoryEnum;
use App\Models\News;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Grid;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Markdown;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Pages\Crud\FormPage;
use Throwable;

class NewsFormPage extends FormPage
{
    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        $resource = $this->getResource();
        $item = $resource->getItem();

        $sidebar = Block::make([
            ID::make()->sortable()->hideOnForm(),
            Text::make('Заголовок', 'title', formatted: fn (News $item): string => $item->translation('title', 'ru'))
                ->hideOnForm(),
            Text::make('Категория', 'category', formatted: fn (News $item): string => $item->category->label('ru'))
                ->hideOnForm(),
            Date::make('Опубликовано', 'published_at')
                ->format('d.m.Y H:i')
                ->hideOnForm(),
            Select::make('Категория', 'category')
                ->options(NewsCategoryEnum::options('ru'))
                ->required()
                ->hideOnIndex(),
            Image::make('Изображение', 'image')
                ->disk('public')
                ->dir('news')
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp'])
                ->required(fn () => is_null($item) ? true : false)
                ->hideOnIndex(),
        ]);

        if (moonshineRequest()->findPage()?->pageType() !== \MoonShine\Enums\PageType::FORM) {
            return [$sidebar];
        }

        return [
            Grid::make([
                Column::make([
                    Tabs::make([
                        $this->makeLocaleTab('Русский', 'ru'),
                        $this->makeLocaleTab('English', 'en'),
                        $this->makeLocaleTab('中文', 'zh'),
                    ]),
                ])->columnSpan(8, 12),
                Column::make([
                    $sidebar,
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

    private function makeLocaleTab(string $label, string $locale): Tab
    {
        return Tab::make($label, [
            Text::make('Заголовок', "title.$locale")
                ->required()
                ->hint('Максимум 100 символов')
                ->customAttributes(['maxlength' => 100]),
            Textarea::make('Превью для мобильных', "mobile_preview.$locale")->required()
                ->hint('Максимум 120 символов')
                ->customAttributes(['maxlength' => 120, 'rows' => 3]),
            Textarea::make('Превью для веба', "web_preview.$locale")
                ->required()
                ->hint('Максимум 300 символов')
                ->customAttributes(['maxlength' => 300, 'rows' => 4]),
            Markdown::make('Текст новости', "content.$locale")
                ->required()
                ->hint('Максимум 2000 символов')
                ->customAttributes(['maxlength' => 2000, 'rows' => 12]),
        ]);
    }
}
