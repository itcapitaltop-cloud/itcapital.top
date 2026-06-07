<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Collection;

trait WithInfiniteFeed
{
    public int $feedPerPage = 20;

    protected int $feedStep = 20;

    public function loadMoreFeed(): void
    {
        $this->feedPerPage += $this->feedStep;
    }

    public function resetFeedPaging(): void
    {
        $this->feedPerPage = $this->feedStep;
    }

    protected function feedFetchLimit(): int
    {
        return $this->feedPerPage + 1;
    }

    /**
     * @template T
     *
     * @param Collection<int, T> $rows
     * @return array{0: Collection<int, T>, 1: bool}
     */
    protected function paginateFeed(Collection $rows): array
    {
        $hasMore = $rows->count() > $this->feedPerPage;

        return [$rows->take($this->feedPerPage)->values(), $hasMore];
    }
}
