<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\NewsCategoryEnum;
use App\Models\News;
use App\MoonShine\Pages\News\NewsDetailPage;
use App\MoonShine\Pages\News\NewsFormPage;
use App\MoonShine\Pages\News\NewsIndexPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Fields\Field;
use MoonShine\Fields\Fields;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<News>
 */
class NewsResource extends ModelResource
{
    protected string $model = News::class;

    protected string $title = 'Новости';

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            NewsIndexPage::make($this->title()),
            NewsFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            NewsDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    /**
     * @param News $item
     * @return array<string, string[]|string>
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

    public function onSave(Field $field): \Closure
    {
        if (str_contains($field->column(), '.')) {
            return static fn (Model $item): Model => $item;
        }

        return parent::onSave($field);
    }

    protected function afterSave(Model $item, Fields $fields): Model
    {
        $item = parent::afterSave($item, $fields);

        /** @var News $item */
        $this->syncTranslations($item);

        return $item->load('translations');
    }

    private function syncTranslations(News $news): void
    {
        $locales = ['ru', 'en', 'zh'];

        foreach ($locales as $locale) {
            $news->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => (string) request()->input("title.$locale", ''),
                    'mobile_preview' => (string) request()->input("mobile_preview.$locale", ''),
                    'web_preview' => (string) request()->input("web_preview.$locale", ''),
                    'content' => (string) request()->input("content.$locale", ''),
                ],
            );
        }

        $news->translations()->whereNotIn('locale', $locales)->delete();
    }
}
