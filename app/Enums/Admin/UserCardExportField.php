<?php

declare(strict_types=1);

namespace App\Enums\Admin;

enum UserCardExportField: string
{
    case FULL_NAME = 'full_name';
    case USERNAME = 'username';
    case LINE_NUMBER = 'line_number';
    case REFERRER = 'referrer';
    case CITY = 'city';
    case PHONE = 'phone';
    case SOCIAL_NETWORKS = 'social_networks';
    case PACKAGES_TOTAL = 'packages_total';
    case TOKENS = 'tokens';
    case EDUCATION = 'education';
    case RANK = 'rank';
    case REFERRALS = 'referrals';

    public function label(): string
    {
        return match ($this) {
            self::FULL_NAME => 'Фамилия Имя',
            self::USERNAME => 'Никнейм',
            self::LINE_NUMBER => 'Номер линии',
            self::REFERRER => 'Кто пригласил',
            self::CITY => 'Город',
            self::PHONE => 'Телефон',
            self::SOCIAL_NETWORKS => 'Социальные сети',
            self::PACKAGES_TOTAL => 'Пакеты Сумма',
            self::TOKENS => 'Токены',
            self::EDUCATION => 'Обучение',
            self::RANK => 'Ранг',
            self::REFERRALS => 'Рефералы',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $field) {
            $options[$field->value] = $field->label();
        }

        return $options;
    }
}
