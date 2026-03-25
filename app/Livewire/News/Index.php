<?php

namespace App\Livewire\News;

use App\Models\News;
use Livewire\Component;

class Index extends Component
{
    public string $position;

    public int $limit;

    public function render()
    {
        $news = News::query()
            ->with(['translations' => fn ($query) => $query->where('locale', auth()->user()->locale ?? session()->get('locale'))])
            ->published()
            ->latest('published_at')
            ->limit($this->limit)
            ->get();

        return view('livewire.news.index', compact('news'));
    }

    public function mount(int $limit = 3): void
    {
        $this->limit = $limit;
    }
}
