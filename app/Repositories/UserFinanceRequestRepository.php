<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Dto\Finance\FinanceRequestFilterData;
use App\Enums\Transactions\TransactionStatusEnum;
use App\Models\Deposit;
use App\Models\Withdraw;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Заявки на ввод и вывод одного клиента для вкладок карточки пользователя в админке.
 *
 * Общие разделы «Ввод» и «Вывод» строят выборку через MoonShine-ресурсы; здесь та же
 * выборка нужна вне ресурса, поэтому она вынесена в репозиторий и переиспользуется
 * страницей карточки и тестами.
 */
final class UserFinanceRequestRepository
{
    public const DEPOSITS_PAGE_NAME = 'deposits_page';

    public const WITHDRAWS_PAGE_NAME = 'withdraws_page';

    /**
     * @return LengthAwarePaginator<int, Deposit>
     */
    public function paginateDeposits(int $userId, FinanceRequestFilterData $filter, int $perPage = 25): LengthAwarePaginator
    {
        return Deposit::query()
            ->with([
                'transaction',
                'paymentSource',
                // Карточка открывается и для забаненных клиентов, поэтому глобальный
                // скоуп notBanned снимается — иначе связь user придёт пустой.
                'transaction.user' => fn ($q) => $q->withoutGlobalScope('notBanned'),
            ])
            ->whereHas('transaction', function (Builder $query) use ($userId, $filter): void {
                $query->where('user_id', $userId);

                $this->applyStatus($query, $filter->status);
            })
            ->tap(fn (Builder $query) => $this->applyDates($query, 'deposits', $filter))
            ->orderByDesc('deposits.created_at')
            ->paginate($perPage, ['*'], self::DEPOSITS_PAGE_NAME)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Withdraw>
     */
    public function paginateWithdraws(int $userId, FinanceRequestFilterData $filter, int $perPage = 25): LengthAwarePaginator
    {
        return Withdraw::query()
            ->with([
                'transaction',
                'paymentSource',
                // Реквизиты фиатных выводов лежат в отдельной таблице, а колонки
                // «Адрес / Карта / Счёт» читают их построчно — без eager-load это N+1.
                'fiatDetail',
                'transaction.user' => fn ($q) => $q->withoutGlobalScope('notBanned'),
            ])
            ->whereHas('transaction', function (Builder $query) use ($userId, $filter): void {
                $query->where('user_id', $userId);

                $this->applyStatus($query, $filter->status);
            })
            ->tap(fn (Builder $query) => $this->applyDates($query, 'withdraws', $filter))
            ->orderByDesc('withdraws.created_at')
            ->paginate($perPage, ['*'], self::WITHDRAWS_PAGE_NAME)
            ->withQueryString();
    }

    /**
     * Статус заявки не хранится колонкой — он выводится из пары accepted_at/rejected_at.
     * Условия повторяют фильтр статуса общего раздела «Ввод» (DepositResource::filters()).
     */
    private function applyStatus(Builder $query, ?TransactionStatusEnum $status): void
    {
        if ($status === null) {
            return;
        }

        match ($status) {
            TransactionStatusEnum::MODERATE => $query->whereNull('accepted_at')->whereNull('rejected_at'),
            TransactionStatusEnum::ACCEPTED => $query->whereNotNull('accepted_at')->whereNull('rejected_at'),
            TransactionStatusEnum::REJECTED => $query->whereNotNull('rejected_at'),
        };
    }

    private function applyDates(Builder $query, string $table, FinanceRequestFilterData $filter): void
    {
        if ($filter->dateFrom !== null) {
            $query->where($table . '.created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->where($table . '.created_at', '<=', $filter->dateTo);
        }
    }
}
