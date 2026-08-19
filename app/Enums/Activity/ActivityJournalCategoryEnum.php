<?php

declare(strict_types=1);

namespace App\Enums\Activity;

/**
 * Категории фильтра журнала карточки пользователя. Значения первых трёх совпадают
 * с ActivityFeedTypeEnum — по ним фильтруется properties->feeds. «Стейкинг» стоит
 * особняком: часть токеновых событий пишется без feeds и ловится по владельцу пакета.
 */
enum ActivityJournalCategoryEnum: string
{
    case Finance = 'finance';
    case Packages = 'packages';
    case Partners = 'partners';
    case Staking = 'staking';

    public function label(): string
    {
        return match ($this) {
            self::Finance => 'Финансы',
            self::Packages => 'Пакеты',
            self::Partners => 'Партнёры',
            self::Staking => 'Стейкинг',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category): array => [$category->value => $category->label()])
            ->prepend('Все события', '')
            ->all();
    }
}
