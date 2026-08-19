<?php

declare(strict_types=1);

namespace App\Dto\Activity;

use App\Enums\Activity\ActivityJournalCategoryEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Throwable;

final readonly class JournalFilterData
{
    public function __construct(
        public ?ActivityJournalCategoryEnum $category = null,
        public ?CarbonInterface $dateFrom = null,
        public ?CarbonInterface $dateTo = null,
    ) {}

    public static function fromRequest(?Request $request = null): self
    {
        $request ??= request();

        return new self(
            category: ActivityJournalCategoryEnum::tryFrom((string) $request->input('journal_category', '')),
            dateFrom: self::parseDate($request->input('journal_date_from'))?->startOfDay(),
            dateTo: self::parseDate($request->input('journal_date_to'))?->endOfDay(),
        );
    }

    /**
     * Вкладка «Администратор» фильтруется только по периоду: у админских записей нет
     * фидов категорий, поэтому выбор «Финансы» дал бы там пустую таблицу.
     */
    public function withoutCategory(): self
    {
        return new self(dateFrom: $this->dateFrom, dateTo: $this->dateTo);
    }

    public function isEmpty(): bool
    {
        return $this->category === null && $this->dateFrom === null && $this->dateTo === null;
    }

    /**
     * Значение для <input type="date">, которому нужен строго формат Y-m-d.
     */
    public function dateInputValue(?CarbonInterface $date): string
    {
        return $date?->format('Y-m-d') ?? '';
    }

    /**
     * Дата приходит из строки запроса, поэтому любой мусор трактуется как «фильтр не задан»:
     * пустой журнал из-за опечатки в URL хуже, чем проигнорированный фильтр.
     */
    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
