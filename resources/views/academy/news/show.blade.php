<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $news->translation('title') }} | {{ __('academy_news_page_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
    @vite('resources/academy/style.css')
    <style>
        .news-article { max-width: 960px; margin: 0 auto; padding: 150px 20px 80px; }
        .news-article-back { margin-bottom: 24px; }
        .news-article-meta { color: #B4FF59; text-transform: uppercase; letter-spacing: 0.08em; font-size: 14px; font-weight: 700; margin-bottom: 16px; }
        .news-article-title { font-family: 'Dela Gothic One', cursive; font-size: 42px; line-height: 1.15; margin-bottom: 16px; }
        .news-article-preview { color: rgba(255, 255, 255, 0.72); font-size: 18px; margin-bottom: 24px; }
        .news-article-image { width: 100%; border-radius: 24px; margin-bottom: 32px; aspect-ratio: 1 / 1; object-fit: cover; }
        .news-article-content { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; padding: 32px; font-size: 18px; line-height: 1.8; color: rgba(255, 255, 255, 0.88); }
        .preview-mobile { display: none; }
        @media (max-width: 768px) {
            .news-article { padding-top: 130px; padding-bottom: 48px; }
            .news-article-title { font-size: 30px; }
            .news-article-content { padding: 24px 20px; font-size: 16px; }
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
            <a href="{{ route('academy.news.index') }}" class="btn-primary">{{ __('academy_news_all_articles') }}</a>
        </div>
    </div>
</header>
<main class="news-article">
    <div class="news-article-back">
        <a href="{{ route('academy.news.index') }}" class="nav-link">← {{ __('academy_news_back_to_list') }}</a>
    </div>
    <div class="news-article-meta">{{ $news->category->label() }} · {{ $news->published_at?->format('d.m.Y H:i') }}</div>
    <h1 class="news-article-title">{{ $news->translation('title') }}</h1>
    <p class="news-article-preview preview-web">{{ $news->translation('web_preview') }}</p>
    <p class="news-article-preview preview-mobile">{{ $news->translation('mobile_preview') }}</p>
    <img class="news-article-image" src="{{ $news->imageUrl() }}" alt="{{ $news->translation('title') }}">
    <article class="news-article-content">{!! nl2br(e($news->translation('content'))) !!}</article>
</main>
</body>
</html>
