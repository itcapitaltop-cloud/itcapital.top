<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsCategoryEnum: string
{
    case NewsAndComments = 'news_and_comments';
    case CompanyNews = 'company_news';
    case CryptoNews = 'crypto_news';
    case InternationalMarketsNews = 'international_markets_news';
    case Analytics = 'analytics';
    case BondNews = 'bond_news';
    case ItCapitalNews = 'it_capital_news';

    /**
     * @return array<string, string>
     */
    public static function options(?string $locale = 'ru'): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category): array => [
                $category->value => $category->label($locale),
            ])
            ->all();
    }

    public function label(?string $locale = null): string
    {
        return match ($locale ?? app()->getLocale()) {
            'ru' => $this->labelRu(),
            'zh' => $this->labelZh(),
            default => $this->labelEn(),
        };
    }

    private function labelRu(): string
    {
        return match ($this) {
            self::NewsAndComments => 'Новости и комментарии',
            self::CompanyNews => 'Новости компаний',
            self::CryptoNews => 'Новости криптовалют',
            self::InternationalMarketsNews => 'Новости международных рынков',
            self::Analytics => 'Аналитика',
            self::BondNews => 'Новости по облигациям',
            self::ItCapitalNews => 'Новости IT Capital',
        };
    }

    private function labelEn(): string
    {
        return match ($this) {
            self::NewsAndComments => 'News & Commentary',
            self::CompanyNews => 'Company News',
            self::CryptoNews => 'Crypto News',
            self::InternationalMarketsNews => 'International Markets News',
            self::Analytics => 'Analytics',
            self::BondNews => 'Bond News',
            self::ItCapitalNews => 'IT Capital News',
        };
    }

    private function labelZh(): string
    {
        return match ($this) {
            self::NewsAndComments => '新闻与评论',
            self::CompanyNews => '公司新闻',
            self::CryptoNews => '加密货币新闻',
            self::InternationalMarketsNews => '国际市场新闻',
            self::Analytics => '市场分析',
            self::BondNews => '债券新闻',
            self::ItCapitalNews => 'IT Capital 新闻',
        };
    }
}
