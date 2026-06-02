<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class NewsList extends Component
{
    public int $perPage = 5;

    public bool $hasMorePages = false;

    /**
     * Number of additional items to load on each scroll.
     */
    private const PER_PAGE_INCREMENT = 5;

    public function loadMore(): void
    {
        $this->perPage += self::PER_PAGE_INCREMENT;
    }

    /**
     * @return Collection<int, News>
     */
    private function news(): Collection
    {
        $locale = auth()->user()->locale ?? session()->get('locale');

        $news = News::query()
            ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
            ->published()
            ->latest('published_at')
            ->limit($this->perPage + 1)
            ->get();

        $this->hasMorePages = $news->count() > $this->perPage;

        return $news->take($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.news-list', [
            'news' => $this->news(),
        ]);
    }
}
