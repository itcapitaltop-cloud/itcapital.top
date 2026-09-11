<?php

declare(strict_types=1);

namespace App\Enums\Admin;

/**
 * Поля карточки пользователя в выгрузке `.xlsx`.
 *
 * Каждое поле — это отдельная строка листа «Главная»; номер строки фиксирован
 * эталоном («Светлана Волкова.xlsx») и не зависит от порядка чекбоксов в форме.
 * Невыбранное поле оставляет свою строку пустой, чтобы не сдвигать остальные
 * и не ломать формулы `SUM` в столбце итогов.
 */
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

    public function label(): string
    {
        return match ($this) {
            self::FULL_NAME => 'Фамилия Имя',
            self::USERNAME => 'Никнейм',
            self::LINE_NUMBER => 'Номер линии',
            self::REFERRER => 'Реферал',
            self::CITY => 'Город',
            self::PHONE => 'Телефон',
            self::SOCIAL_NETWORKS => 'Социальные сети',
            self::PACKAGES_TOTAL => 'Пакеты Сумма',
            self::TOKENS => 'Токены',
            self::EDUCATION => 'Обучение онлайн',
            self::RANK => 'Ранг',
        };
    }

    /**
     * Номер строки на листе «Главная». Строки 1–2 заняты оформительской шапкой.
     */
    public function rowIndex(): int
    {
        return match ($this) {
            self::FULL_NAME => 3,
            self::USERNAME => 4,
            self::LINE_NUMBER => 5,
            self::REFERRER => 6,
            self::CITY => 7,
            self::PHONE => 8,
            self::SOCIAL_NETWORKS => 9,
            self::PACKAGES_TOTAL => 10,
            self::TOKENS => 11,
            self::EDUCATION => 12,
            self::RANK => 13,
        };
    }

    /**
     * Заглушка для незаполненного значения.
     *
     * В эталоне пустых текстовых ячеек нет: там, где данных в системе нет,
     * стоит `?`, а в строке «Обучение онлайн» — подсказка менеджеру, что
     * отметить. Числовые строки заглушку не используют: у них всегда есть
     * значение, при отсутствии данных — `0`.
     */
    public function placeholder(): ?string
    {
        return match ($this) {
            self::EDUCATION => 'Проходил / не проходил',
            self::FULL_NAME,
            self::USERNAME,
            self::REFERRER,
            self::CITY,
            self::PHONE,
            self::SOCIAL_NETWORKS => '?',
            self::LINE_NUMBER,
            self::PACKAGES_TOTAL,
            self::TOKENS,
            self::RANK => null,
        };
    }

    /**
     * Оформление строки: отдельно для подписи (столбец A) и для значений
     * (столбцы владельца и рефералов). Цвета извлечены из `xl/styles.xml` эталона.
     *
     * @return array{
     *     label: array{fill: ?string, size: int, bold: bool},
     *     value: array{fill: ?string, size: int, bold: bool}
     * }
     */
    public function style(): array
    {
        return match ($this) {
            self::FULL_NAME => [
                'label' => ['fill' => 'FF00FFFF', 'size' => 12, 'bold' => true],
                'value' => ['fill' => 'FFFF00FF', 'size' => 12, 'bold' => true],
            ],
            self::USERNAME => [
                'label' => ['fill' => 'FFFF00FF', 'size' => 12, 'bold' => true],
                'value' => ['fill' => 'FF00FFFF', 'size' => 12, 'bold' => true],
            ],
            self::LINE_NUMBER => [
                'label' => ['fill' => 'FF6D9EEB', 'size' => 12, 'bold' => true],
                'value' => ['fill' => 'FF00FF00', 'size' => 11, 'bold' => true],
            ],
            self::REFERRER => [
                'label' => ['fill' => null, 'size' => 11, 'bold' => true],
                'value' => ['fill' => null, 'size' => 11, 'bold' => true],
            ],
            self::CITY, self::PHONE, self::SOCIAL_NETWORKS => [
                'label' => ['fill' => 'FF6AA84F', 'size' => 12, 'bold' => true],
                'value' => ['fill' => 'FF6AA84F', 'size' => 12, 'bold' => true],
            ],
            self::PACKAGES_TOTAL => [
                'label' => ['fill' => 'FFFFFF00', 'size' => 11, 'bold' => true],
                'value' => ['fill' => 'FFFFFF00', 'size' => 11, 'bold' => true],
            ],
            self::TOKENS => [
                'label' => ['fill' => 'FFD0E0E3', 'size' => 11, 'bold' => true],
                'value' => ['fill' => 'FFD0E0E3', 'size' => 11, 'bold' => true],
            ],
            /*
             * Значения «Обучение онлайн» в эталоне набраны 8 кеглем —
             * подпись «Проходил / не проходил» иначе не помещается в колонку.
             */
            self::EDUCATION => [
                'label' => ['fill' => 'FFE6B8AF', 'size' => 11, 'bold' => true],
                'value' => ['fill' => 'FFE6B8AF', 'size' => 8, 'bold' => true],
            ],
            /*
             * Подпись «Ранг» в эталоне залита темой (accent3 = FBBC04);
             * PhpSpreadsheet пишет только явные RGB, поэтому цвет развёрнут.
             */
            self::RANK => [
                'label' => ['fill' => 'FFFBBC04', 'size' => 11, 'bold' => true],
                'value' => ['fill' => 'FFF4CCCC', 'size' => 11, 'bold' => true],
            ],
        };
    }

    /**
     * Первая и последняя строки данных на листе «Главная».
     *
     * @return array{int, int}
     */
    public static function rowRange(): array
    {
        $rows = array_map(static fn (self $field): int => $field->rowIndex(), self::cases());

        return [min($rows), max($rows)];
    }

    /**
     * Поля, разложенные по номеру строки листа.
     *
     * @return array<int, self>
     */
    public static function byRow(): array
    {
        $byRow = [];

        foreach (self::cases() as $field) {
            $byRow[$field->rowIndex()] = $field;
        }

        return $byRow;
    }

    /**
     * Список полей для чекбоксов в модалке выгрузки.
     *
     * @return array<string, string> значение поля => подпись
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
