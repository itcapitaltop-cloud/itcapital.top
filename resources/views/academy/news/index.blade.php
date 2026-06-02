<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('academy_news_page_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
    @vite('resources/academy/style.css')
    <style>
        .news-page { max-width: 1200px; margin: 0 auto; padding: 150px 40px 80px; }
        .news-page-header { display: flex; justify-content: space-between; align-items: end; gap: 24px; margin-bottom: 32px; }
        .news-page-title { font-family: 'Dela Gothic One', cursive; font-size: 40px; line-height: 1.1; }
        .news-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
        .news-card { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; }
        .news-card-image { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; }
        .news-card-body { display: flex; flex-direction: column; gap: 14px; padding: 24px; flex: 1; }
        .news-card-category { color: #B4FF59; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
        .news-card-title { font-size: 24px; line-height: 1.25; font-weight: 700; }
        .news-card-preview { color: rgba(255, 255, 255, 0.72); font-size: 16px; }
        .news-card-meta { color: rgba(255, 255, 255, 0.56); font-size: 14px; }
        .news-card-actions { margin-top: auto; }
        .news-empty { padding: 48px 32px; border-radius: 24px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); text-align: center; color: rgba(255, 255, 255, 0.72); }
        .preview-mobile { display: none; }
        .news-pagination { display: flex; justify-content: center; gap: 12px; margin-top: 32px; flex-wrap: wrap; }
        .news-pagination-link { display: inline-flex; align-items: center; justify-content: center; min-width: 44px; padding: 12px 16px; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.12); color: white; text-decoration: none; background: rgba(255, 255, 255, 0.03); }
        .news-pagination-link.is-active { background: #B4FF59; color: #17162D; border-color: #B4FF59; }
        @media (max-width: 992px) { .news-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 768px) {
            .news-page { padding: 130px 20px 48px; }
            .news-page-header { flex-direction: column; align-items: start; }
            .news-page-title { font-size: 30px; }
            .news-grid { grid-template-columns: 1fr; }
            .preview-web { display: none; }
            .preview-mobile { display: block; }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="header-container">
        <div class="logo">
            <a href="{{ route('academy.landing') }}">
                <img src="{{ vite()->academy('logo.svg') }}" alt="IT Academy Logo">
            </a>
        </div>
        <nav class="nav">
            <a href="{{ route('academy.landing') }}" class="nav-link">{{ __('academy_news_back_to_academy') }}</a>
            <a href="{{ route('academy.news.index') }}" class="nav-link">{{ __('academy_news_all_articles') }}</a>
        </nav>
        <div class="header-button">
            <a href="{{ route('academy.landing') }}#start" class="btn-primary">{{ __('landing_btn_start_training') }}</a>
        </div>
    </div>
</header>
<main class="news-page">
    <div class="news-page-header">
        <div>
            <p class="hero-description">{{ __('academy_news_page_subtitle') }}</p>
            <h1 class="news-page-title">{{ __('academy_news_page_title') }}</h1>
        </div>
    </div>
    @if ($newsItems->isEmpty())
        <div class="news-empty">{{ __('academy_news_empty') }}</div>
    @else
        <section class="news-grid">
            @foreach ($newsItems as $news)
                <article class="news-card">
                    <img class="news-card-image" src="{{ $news->imageUrl() }}" alt="{{ $news->translation('title') }}">
                    <div class="news-card-body">
                        <div class="news-card-category">{{ $news->category->label() }}</div>
                        <h2 class="news-card-title">{{ $news->translation('title') }}</h2>
                        <p class="news-card-preview preview-web">{{ $news->translation('web_preview') }}</p>
                        <p class="news-card-preview preview-mobile">{{ $news->translation('mobile_preview') }}</p>
                        <div class="news-card-meta">{{ __('academy_news_published_at') }}: {{ $news->published_at?->format('d.m.Y H:i') }}</div>
                        <div class="news-card-actions">
                            <a href="{{ route('academy.news.show', $news) }}" class="btn-primary">{{ __('academy_news_read_more') }}</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
        @if ($newsItems->hasPages())
            <nav class="news-pagination" aria-label="Pagination">
                @if ($newsItems->onFirstPage())
                    <span class="news-pagination-link">‹</span>
                @else
                    <a class="news-pagination-link" href="{{ $newsItems->previousPageUrl() }}">‹</a>
                @endif
                @foreach ($newsItems->getUrlRange(1, $newsItems->lastPage()) as $page => $url)
                    <a class="news-pagination-link {{ $page === $newsItems->currentPage() ? 'is-active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                @endforeach
                @if ($newsItems->hasMorePages())
                    <a class="news-pagination-link" href="{{ $newsItems->nextPageUrl() }}">›</a>
                @else
                    <span class="news-pagination-link">›</span>
                @endif
            </nav>
        @endif
    @endif
</main>
</body>
</html>
