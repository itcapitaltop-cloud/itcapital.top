<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\News;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NewsList extends Component
{
    public function render(): View
    {
        $news = News::query()
            ->with(['translations' => fn ($query) => $query->where('locale', auth()->user()->locale ?? session()->get('locale'))])
            ->published()
            ->latest('published_at')
            ->get();

        return view('livewire.news-list', compact('news'));
    }
}
