<?php

use App\Livewire\NewsList;
use App\Models\News;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['locale' => 'ru']));
});

it('initially loads only the first page of news', function () {
    News::factory()->count(8)->create();

    Livewire::test(NewsList::class)
        ->assertViewHas('news', fn ($news) => $news->count() === 5)
        ->assertSet('hasMorePages', true);
});

it('loads more news when scrolling', function () {
    News::factory()->count(8)->create();

    Livewire::test(NewsList::class)
        ->call('loadMore')
        ->assertViewHas('news', fn ($news) => $news->count() === 8)
        ->assertSet('hasMorePages', false);
});

it('reports no more pages when news fit on a single page', function () {
    News::factory()->count(3)->create();

    Livewire::test(NewsList::class)
        ->assertViewHas('news', fn ($news) => $news->count() === 3)
        ->assertSet('hasMorePages', false);
});
