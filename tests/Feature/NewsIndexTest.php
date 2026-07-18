<?php

use App\Livewire\News\Index;
use App\Models\News;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['locale' => 'ru']));
});

it('links each sidebar news item to its anchor on the news page', function () {
    $news = News::factory()->create();

    Livewire::test(Index::class, ['position' => 'sidebar'])
        ->assertSeeHtml(route('news.index') . '#news-' . $news->id);
});

it('links each mobile menu news item to its anchor on the news page', function () {
    $news = News::factory()->create();

    Livewire::test(Index::class, ['position' => 'mobile-menu'])
        ->assertSeeHtml(route('news.index') . '#news-' . $news->id);
});
