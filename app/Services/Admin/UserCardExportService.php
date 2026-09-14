<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\User;
use App\Services\Package\Staking\StakingPerformanceService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сбор данных карточки пользователя для выгрузки `.xlsx`.
 *
 * В эталоне карточка — это владелец плюс всё его дерево рефералов, по колонке
 * на человека, поэтому «Пакеты Сумма» и «Токены» считаются пакетно на всё
 * дерево сразу: по одному запросу на тип пакетов вместо запроса на пользователя.
 */
final class UserCardExportService
{
    public function __construct(
        private readonly ReferralTreeService $referralTreeService,
        private readonly StakingPerformanceService $stakingPerformanceService,
    ) {}

    /**
     * @return array{
     *     owner: array{id: int, full_name: string, username: string, line: int, referrer: string, packages_total: float, tokens: float, rank: int, social: string},
     *     referrals: list<array{id: int, full_name: string, username: string, line: int, referrer: string, packages_total: float, tokens: float, rank: int, social: string}>
     * }
     */
    public function collect(User $user): array
    {
        $referrals = $this->referralTreeService->flattenWithUsers($user->id);

        $referralIds = array_map(static fn (array $referral): int => $referral['id'], $referrals);
        $userIds = [$user->id, ...$referralIds];

        $packagesTotals = $this->packagesTotals($userIds);
        $tokenTotals = $this->tokenTotals($userIds);

        $ownerName = trim("{$user->first_name} {$user->last_name}");

        $card = [
            'owner' => [
                'id' => $user->id,
                'full_name' => $ownerName === '' ? (string) $user->username : $ownerName,
                'username' => (string) $user->username,
                'line' => $this->ownerLine($user->id),
                'referrer' => (string) ($user->referrer?->username ?? ''),
                'packages_total' => $packagesTotals[$user->id] ?? 0.0,
                'tokens' => $tokenTotals[$user->id] ?? 0.0,
                'rank' => (int) $user->rank,
                'social' => trim((string) $user->telegram),
            ],
            'referrals' => array_map(
                static fn (array $referral): array => [
                    'id' => $referral['id'],
                    'full_name' => $referral['name'],
                    'username' => $referral['username'],
                    'line' => $referral['line'],
                    'referrer' => '',
                    'packages_total' => $packagesTotals[$referral['id']] ?? 0.0,
                    'tokens' => $tokenTotals[$referral['id']] ?? 0.0,
                    'rank' => $referral['rank'],
                    'social' => $referral['telegram'],
                ],
                $referrals
            ),
        ];

        Log::info('[UserCardExportService.collect] collected', [
            'user_id' => $user->id,
            'referrals' => count($referrals),
        ]);

        if ($referrals === []) {
            Log::warning('[UserCardExportService.collect] referral tree is empty', [
                'user_id' => $user->id,
            ]);
        }

        return $card;
    }

    /**
     * Глубина пользователя в структуре — та же величина, что показывает админка.
     */
    private function ownerLine(int $userId): int
    {
        return (int) PartnerClosure::query()
            ->where('descendant_id', $userId)
            ->max('depth');
    }

    /**
     * «Пакеты Сумма» повторяет «Сумму пакетов» дашборда /account
     * (App\Livewire\Account\Dashboard\Index): тело пакета
     * (amount + partnerTransfers + reinvestToBody − balanceWithdraws,
     * 0 для обнулённых PRESENT) плюс активные реинвесты.
     *
     * @param list<int> $userIds
     * @return array<int, float>
     */
    private function packagesTotals(array $userIds): array
    {
        $packages = ItcPackage::query()
            ->select('itc_packages.*')
            ->addSelect('transactions.user_id as card_user_id')
            ->join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->whereIn('transactions.user_id', $userIds)
            ->notActive()
            ->with(['transaction', 'zeroing'])
            ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->get();

        $totals = [];

        foreach ($packages as $package) {
            $body = $package->type === PackageTypeEnum::PRESENT && $package->zeroing
                ? 0.0
                : (float) $package->transaction->amount
                    + (float) $package->partner_transfers_sum_amount
                    + (float) $package->reinvest_to_body_sum_amount
                    - (float) $package->balance_withdraws_sum_amount;

            $ownerId = (int) $package->getAttribute('card_user_id');
            $totals[$ownerId] = ($totals[$ownerId] ?? 0.0) + $body + (float) $package->reinvest_profits_sum_amount;
        }

        return array_map(static fn (float $total): float => round($total, 2), $totals);
    }

    /**
     * Токены стейкинга, сгруппированные по владельцу пакета.
     *
     * @param list<int> $userIds
     * @return array<int, float>
     */
    private function tokenTotals(array $userIds): array
    {
        $packages = ItcPackage::query()
            ->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', fn ($q) => $q->whereIn('user_id', $userIds))
            ->with(['transaction', 'stakingTransactionAccruals', 'stakingPurchases', 'packageDefinition'])
            ->get();

        if ($packages->isEmpty()) {
            return [];
        }

        $totals = [];

        /** @var EloquentCollection<int, ItcPackage> $userPackages */
        foreach ($packages->groupBy(fn (ItcPackage $package): int => (int) $package->transaction->user_id) as $ownerId => $userPackages) {
            $totals[(int) $ownerId] = (float) $this->stakingPerformanceService->forPackages($userPackages)['total_tokens'];
        }

        return $totals;
    }
}
