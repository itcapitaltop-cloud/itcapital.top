<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\NewsCategoryEnum;
use App\Models\News;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<News>
 */
class NewsResource extends ModelResource
{
    protected string $model = News::class;

    protected string $title = 'Новости Academy';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        $fields = [
            Block::make([
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
                    ->required(fn (): bool => ! $this->getItemID())
                    ->hideOnIndex(),
            ]),
        ];

        if (moonshineRequest()->findPage()?->pageType() === \MoonShine\Enums\PageType::FORM) {
            $fields[] = Tabs::make([
                $this->makeLocaleTab('Русский', 'ru'),
                $this->makeLocaleTab('English', 'en'),
                $this->makeLocaleTab('中文', 'zh'),
            ]);
        }

        return $fields;
    }

    /**
     * @param News $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [
            'category' => ['required', Rule::enum(NewsCategoryEnum::class)],
            'image' => [
                $item->exists ? 'nullable' : 'required',
                'image',
                'max:3072',
                Rule::dimensions()->maxWidth(1200)->maxHeight(1200),
            ],
            'title.ru' => ['required', 'string', 'max:100'],
            'title.en' => ['required', 'string', 'max:100'],
            'title.zh' => ['required', 'string', 'max:100'],
            'mobile_preview.ru' => ['required', 'string', 'max:120'],
            'mobile_preview.en' => ['required', 'string', 'max:120'],
            'mobile_preview.zh' => ['required', 'string', 'max:120'],
            'web_preview.ru' => ['required', 'string', 'max:300'],
            'web_preview.en' => ['required', 'string', 'max:300'],
            'web_preview.zh' => ['required', 'string', 'max:300'],
            'content.ru' => ['required', 'string', 'max:2000'],
            'content.en' => ['required', 'string', 'max:2000'],
            'content.zh' => ['required', 'string', 'max:2000'],
        ];
    }

    public function query(): Builder
    {
        return parent::query()->latest('published_at');
    }

    private function makeLocaleTab(string $label, string $locale): Tab
    {
        return Tab::make($label, [
            Text::make('Заголовок', "title.$locale")
                ->required()
                ->hint('Максимум 100 символов')
                ->customAttributes(['maxlength' => 100]),
            Textarea::make('Превью для мобильных', "mobile_preview.$locale")
                ->required()
                ->hint('Максимум 120 символов')
                ->customAttributes(['maxlength' => 120, 'rows' => 3]),
            Textarea::make('Превью для веба', "web_preview.$locale")
                ->required()
                ->hint('Максимум 300 символов')
                ->customAttributes(['maxlength' => 300, 'rows' => 4]),
            Textarea::make('Текст новости', "content.$locale")
                ->required()
                ->hint('Максимум 2000 символов')
                ->customAttributes(['maxlength' => 2000, 'rows' => 12]),
        ]);
    }
}
