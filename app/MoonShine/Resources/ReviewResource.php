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
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Enums\ToastType;
use MoonShine\Fields\Select;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
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
     * Quick-publish button rendered before the standard row buttons.
     *
     * @return list<ActionButton>
     */
    public function indexButtons(): array
    {
        return [
            ActionButton::make('')
                ->method('approve')
                ->icon('heroicons.check')
                ->success()
                ->customAttributes(['title' => 'Опубликовать отзыв'])
                ->canSee(fn (Review $item): bool => $item->status !== ReviewStatusEnum::Approved),
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

    /**
     * Publishes a review straight from the index table, without opening the form.
     */
    public function approve(MoonShineRequest $request): MoonShineJsonResponse
    {
        $reviewId = $request->get('resourceItem');
        $review = Review::query()->find($reviewId);

        if (is_null($review)) {
            return MoonShineJsonResponse::make()
                ->toast('Отзыв не найден', ToastType::ERROR)
                ->redirect($this->backToIndex());
        }

        if ($review->status === ReviewStatusEnum::Approved) {
            return MoonShineJsonResponse::make()
                ->toast('Отзыв уже опубликован', ToastType::INFO)
                ->redirect($this->backToIndex());
        }

        $review->update(['status' => ReviewStatusEnum::Approved]);

        return MoonShineJsonResponse::make()
            ->toast('Отзыв опубликован', ToastType::SUCCESS)
            ->redirect($this->backToIndex());
    }

    /**
     * Where the browser returns after a quick publish.
     *
     * The reviews list is not an async table (ModelResource::$isAsync is false),
     * so the page has to be reloaded for the row to show the new status. The
     * referer keeps the current page/filter; the index page is the fallback when
     * the header is absent.
     */
    private function backToIndex(): string
    {
        $referer = request()->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return $referer;
        }

        return (string) to_page(new ReviewIndexPage(), new self());
    }
}
