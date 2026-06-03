<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\ReviewStatusEnum;
use App\Models\Review;
use App\MoonShine\Pages\Review\ReviewFormPage;
use App\MoonShine\Pages\Review\ReviewIndexPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Fields\Select;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<Review>
 */
class ReviewResource extends ModelResource
{
    protected string $model = Review::class;

    protected string $title = 'Отзывы';

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            ReviewIndexPage::make($this->title()),
            ReviewFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
        ];
    }

    /**
     * @return list<mixed>
     */
    public function filters(): array
    {
        return [
            Select::make('Статус', 'status')
                ->options(ReviewStatusEnum::options())
                ->nullable()
                ->onApply(function (Builder $query, mixed $value): void {
                    if ($value === '' || $value === null) {
                        return;
                    }

                    $query->where('status', $value);
                }),
        ];
    }

    /**
     * @param Review $item
     * @return array<string, string[]|string>
     */
    public function rules(Model $item): array
    {
        return [
            'body' => ['required', 'string', 'min:10', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'status' => ['required', Rule::enum(ReviewStatusEnum::class)],
        ];
    }
}
