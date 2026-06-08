<?php

use App\Enums\NewsCategoryEnum;
use App\Models\News;

it('shows only published academy news on the public index page', function () {
    $publishedNews = News::factory()->create([
        'category' => NewsCategoryEnum::Analytics,
    ]);
    $publishedNews->translations()->where('locale', 'ru')->update([
        'title' => 'Опубликованная новость',
    ]);

    $draftNews = News::factory()->unpublished()->create();
    $draftNews->translations()->where('locale', 'ru')->update([
        'title' => 'Черновик',
    ]);

    $response = $this->withSession(['locale' => 'ru'])
        ->withHeader('Host', 'academy.' . config('app.domain'))
        ->get(route('academy.news.index'));

    $response->assertOk();
    $response->assertSee('Опубликованная новость');
    $response->assertDontSee('Черновик');
    expect($publishedNews->published_at)->not->toBeNull();
    expect($draftNews->fresh()->published_at)->toBeNull();
});

it('renders localized public academy news content', function () {
    $news = News::factory()->create([
        'category' => NewsCategoryEnum::ItCapitalNews,
    ]);
    $news->translations()->where('locale', 'zh')->update([
        'title' => '中文标题',
        'mobile_preview' => '中文短预览',
        'web_preview' => '中文网页预览',
        'content' => '中文新闻正文',
    ]);

    $response = $this->withSession(['locale' => 'zh'])
        ->withHeader('Host', 'academy.' . config('app.domain'))
        ->get(route('academy.news.show', $news));

    $response->assertOk();
    $response->assertSee('中文标题');
    $response->assertSee('中文网页预览');
    $response->assertSee('中文新闻正文');
    $response->assertSee('IT Capital 新闻');
});
