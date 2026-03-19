<?php

use App\Enums\NewsCategoryEnum;
use App\Models\News;

it('shows only published academy news on the public index page', function () {
    $publishedNews = News::factory()->create([
        'title' => [
            'ru' => 'Опубликованная новость',
            'en' => 'Published news',
            'zh' => '已发布新闻',
        ],
        'category' => NewsCategoryEnum::Analytics,
    ]);

    $draftNews = News::factory()->unpublished()->create([
        'title' => [
            'ru' => 'Черновик',
            'en' => 'Draft',
            'zh' => '草稿',
        ],
    ]);

    $response = $this->withHeader('Host', 'academy.' . config('app.domain'))
        ->get(route('academy.news.index'));

    $response->assertOk();
    $response->assertSee('Опубликованная новость');
    $response->assertDontSee('Черновик');
    expect($publishedNews->published_at)->not->toBeNull();
    expect($draftNews->fresh()->published_at)->toBeNull();
});

it('renders localized public academy news content', function () {
    $news = News::factory()->create([
        'title' => [
            'ru' => 'Русский заголовок',
            'en' => 'English title',
            'zh' => '中文标题',
        ],
        'mobile_preview' => [
            'ru' => 'Короткий русский анонс',
            'en' => 'Short English teaser',
            'zh' => '中文短预览',
        ],
        'web_preview' => [
            'ru' => 'Русский веб-анонс',
            'en' => 'English web teaser',
            'zh' => '中文网页预览',
        ],
        'content' => [
            'ru' => 'Русский текст новости',
            'en' => 'English news body',
            'zh' => '中文新闻正文',
        ],
        'category' => NewsCategoryEnum::ItCapitalNews,
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
