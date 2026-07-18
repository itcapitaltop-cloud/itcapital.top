<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\Transaction;
use App\Models\UserSummary;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single writer of the user_summary projection.
 *
 * user_summary is a denormalized read model for the admin panel. It is derived
 * entirely from the transactions ledger (+ reinvest / partner tables) using the
 * same canonical formula as the user-facing balance (see UserBalanceCalculator).
 * There are no PL/pgSQL triggers maintaining it — this service is the only path.
 *
 * recompute() is idempotent and safe to call repeatedly (observers, reconcile
 * command, backfills).
 */
class UserSummaryService
{
    public function __construct(
        private readonly UserBalanceCalculator $balanceCalculator,
    ) {}

    /**
     * Compute the summary field values for a user from the ledger, without persisting.
     * Used by recompute() and by the reconcile command's --dry-run comparison.
     *
     * @return array{investments_sum: string, partner_balance: string, reinvests_sum: string, buy_packages_sum: string, partners_count: int, first_package_at: string|null, in_out_saldo: string}
     */
    public function computeFor(int $userId): array
    {
        return [
            // forceFresh: recompute is the authoritative write path and runs inside the
            // Transaction "created"/"updated" event — before Transaction::booted() forgets
            // the memoized balance on "saved". A memoized read here would persist a stale
            // pre-write balance into user_summary.
            'investments_sum' => $this->balanceCalculator->balanceFor($userId, BalanceTypeEnum::MAIN, forceFresh: true),
            'partner_balance' => $this->balanceCalculator->balanceFor($userId, BalanceTypeEnum::PARTNER, forceFresh: true),
            'reinvests_sum' => $this->reinvestsSum($userId),
            'buy_packages_sum' => $this->buyPackagesSum($userId),
            'partners_count' => $this->partnersCount($userId),
            'first_package_at' => $this->firstPackageAt($userId),
            'in_out_saldo' => $this->inOutSaldo($userId),
        ];
    }

    /**
     * Rebuild the user_summary row for a single user from the ledger.
     */
    public function recompute(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            $values = $this->computeFor($userId);

            /** @var UserSummary|null $existing */
            $existing = UserSummary::query()->find($userId);

            Log::debug('[UserSummaryService] recompute', [
                'user_id' => $userId,
                'old' => $existing?->only(array_keys($values)),
                'new' => $values,
            ]);

            UserSummary::query()->updateOrCreate(
                ['user_id' => $userId],
                $values,
            );
        });
    }

    /**
     * Resolve the owning user of a package and recompute their summary.
     * Used by reinvest / reinvest-withdraw observers where only the package uuid is known.
     */
    public function recomputeByPackageUuid(string $packageUuid): void
    {
        $userId = ItcPackage::query()
            ->where('itc_packages.uuid', $packageUuid)
            ->join('transactions', 'transactions.uuid', '=', 'itc_packages.uuid')
            ->value('transactions.user_id');

        if ($userId === null) {
            Log::warning('[UserSummaryService] recomputeByPackageUuid: owner not found', [
                'package_uuid' => $packageUuid,
            ]);

            return;
        }

        $this->recompute((int) $userId);
    }

    /**
     * Total reinvested for the user's packages, minus reinvest withdrawals.
     *
     * Soft-deleted reinvests are excluded so this figure matches what the user sees in
     * their cabinet (the `reinvestProfits` relation respects the soft-delete scope). Raw
     * query builder does not apply Eloquent global scopes, so the deleted_at guard is explicit.
     */
    private function reinvestsSum(int $userId): string
    {
        $reinvested = (string) DB::table('package_profit_reinvests as r')
            ->join('itc_packages as p', 'p.uuid', '=', 'r.package_uuid')
            ->join('transactions as t', 't.uuid', '=', 'p.uuid')
            ->where('t.user_id', $userId)
            ->whereNull('r.deleted_at')
            ->sum('r.amount');

        $withdrawn = (string) DB::table('package_profit_reinvest_withdraws as rw')
            ->join('package_profit_reinvests as r', 'r.uuid', '=', 'rw.reinvest_uuid')
            ->join('itc_packages as p', 'p.uuid', '=', 'r.package_uuid')
            ->join('transactions as t', 't.uuid', '=', 'p.uuid')
            ->where('t.user_id', $userId)
            ->whereNull('r.deleted_at')
            ->sum('r.amount');

        return (string) BigDecimal::of($reinvested === '' ? '0' : $reinvested)
            ->minus($withdrawn === '' ? '0' : $withdrawn);
    }

    /**
     * Sum of accepted package purchases, excluding archived packages.
     *
     * "buy_packages_sum" represents the user's current package volume as shown in
     * the admin panel, so archived packages (type = archive) are not counted.
     * whereDoesntHave keeps buy_package transactions that have no linked package row.
     */
    private function buyPackagesSum(int $userId): string
    {
        $sum = Transaction::query()
            ->where('user_id', $userId)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
            ->whereNotNull('accepted_at')
            ->whereDoesntHave('itcPackage', fn ($query) => $query->where('type', PackageTypeEnum::ARCHIVE))
            ->sum('amount');

        return (string) ($sum ?? 0);
    }

    /**
     * Accepted deposits minus accepted withdraws — the IN / OUT saldo shown in the
     * admin panel. Uses the same formula as the IN / OUT block on the user detail page.
     */
    private function inOutSaldo(int $userId): string
    {
        $in = (string) Transaction::query()
            ->where('user_id', $userId)
            ->where('trx_type', TrxTypeEnum::DEPOSIT)
            ->whereNotNull('accepted_at')
            ->sum('amount');

        $out = (string) Transaction::query()
            ->where('user_id', $userId)
            ->where('trx_type', TrxTypeEnum::WITHDRAW)
            ->whereNotNull('accepted_at')
            ->sum('amount');

        return (string) BigDecimal::of($in === '' ? '0' : $in)
            ->minus($out === '' ? '0' : $out);
    }

    private function partnersCount(int $userId): int
    {
        return DB::table('partners')
            ->where('partner_id', $userId)
            ->count();
    }

    private function firstPackageAt(int $userId): ?string
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
            ->whereNotNull('accepted_at')
            ->min('accepted_at');
    }
}
