<?php

declare(strict_types=1);

namespace App\Dto\Finance;

use App\Enums\Transactions\TransactionStatusEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Throwable;

/**
 * Фильтр вкладок «Ввод» и «Вывод» в карточке клиента.
 *
 * Обе вкладки живут на одном URL вместе с журналом и статистикой рефералов, поэтому
 * имена GET-параметров разводятся префиксом ($prefix = deposits|withdraws): без него
 * фильтр одной вкладки применялся бы и ко второй.
 */
final readonly class FinanceRequestFilterData
{
    public function __construct(
        public ?TransactionStatusEnum $status = null,
        public ?CarbonInterface $dateFrom = null,
        public ?CarbonInterface $dateTo = null,
    ) {}

    public static function fromRequest(string $prefix, ?Request $request = null): self
    {
        $request ??= request();

        return new self(
            status: TransactionStatusEnum::tryFrom((string) $request->input($prefix . '_status', '')),
            dateFrom: self::parseDate($request->input($prefix . '_date_from'))?->startOfDay(),
            dateTo: self::parseDate($request->input($prefix . '_date_to'))?->endOfDay(),
        );
    }

    public function isEmpty(): bool
    {
        return $this->status === null && $this->dateFrom === null && $this->dateTo === null;
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
     * пустая вкладка из-за опечатки в URL хуже, чем проигнорированный фильтр.
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
