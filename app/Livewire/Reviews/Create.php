<?php

declare(strict_types=1);

namespace App\Livewire\Reviews;

use App\Enums\ReviewStatusEnum;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public int $rating = 0;

    public string $body = '';

    public bool $submitted = false;

    public function mount(): void
    {
        if ($this->userHasReview()) {
            $this->redirectRoute('reviews', navigate: true);
        }
    }

    /**
     * @var array<string, string|array<string>>
     */
    protected function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @var array<string, string>
     */
    protected function messages(): array
    {
        return [
            'rating.required' => 'Выберите рейтинг.',
            'rating.min' => 'Рейтинг должен быть не менее 1.',
            'rating.max' => 'Рейтинг должен быть не более 5.',
            'body.required' => 'Напишите отзыв.',
            'body.min' => 'Отзыв должен содержать не менее 10 символов.',
            'body.max' => 'Отзыв не должен превышать 1000 символов.',
        ];
    }

    public function submit(): void
    {
        if ($this->userHasReview()) {
            return;
        }

        $this->validate();

        $user = Auth::user();

        if (! $user) {
            return;
        }

        Review::create([
            'user_id' => $user->id,
            'rating' => $this->rating,
            'body' => $this->body,
            'status' => ReviewStatusEnum::Pending,
        ]);

        $this->body = '';
        $this->rating = 0;
        $this->submitted = true;
    }

    private function userHasReview(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return Review::query()->where('user_id', $user->id)->exists();
    }

    public function render(): View
    {
        return view('livewire.reviews.create');
    }
}
