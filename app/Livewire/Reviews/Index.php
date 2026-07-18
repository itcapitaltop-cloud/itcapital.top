<?php

declare(strict_types=1);

namespace App\Livewire\Reviews;

use App\Models\Review;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        $reviews = Review::query()->approved()
            ->with('user')
            ->latest()
            ->paginate(10)
            ->onEachSide(1);

        $averageRating = Review::averageRating();
        $totalCount = Review::approvedCount();

        return view('livewire.reviews.index', [
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'totalCount' => $totalCount,
        ]);
    }
}
