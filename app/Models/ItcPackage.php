<?php

namespace App\Models;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\Package\Staking\StakingTransactionAccrual;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $uuid
 * @property string $month_profit_percent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereMonthProfitPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereUuid($value)
 *
 * @property-read \App\Models\Transaction|null $transaction
 * @property string $type
 * @property string $work_to
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereWorkTo($value)
 * @method static Builder|ItcPackage notActive()
 * @method static Builder|ItcPackage active(PackageTypeEnum ...$enum)
 * @method static Builder|ItcPackage userPackagesWithFinancials(int $userId)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageProfit> $profits
 * @property-read int|null $profits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageProfitReinvest> $reinvestProfits
 * @property-read int|null $reinvest_profits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $withdrawProfitsTransactions
 * @property-read int|null $withdraw_profits_transactions_count
 *
 * @mixin \Eloquent
 */
class ItcPackage extends Model
{
    use HasFactory;

    protected BigDecimal $currentProfitAmount;

    protected $fillable = [
        'uuid',
        'month_profit_percent',
        'type',
        'work_to',
        'duration_months',
        'created_at',
        'regular_percent',
        'start_bonus_percent',
    ];

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'uuid', 'uuid');
    }

    public function zeroing(): HasOne
    {
        return $this->hasOne(PackageZeroing::class, 'package_uuid', 'uuid');
    }

    public function profits(): HasMany
    {
        return $this->hasMany(PackageProfit::class, 'package_uuid', 'uuid');
    }

    public function stakingTransactionAccruals(): HasMany
    {
        return $this->hasMany(StakingTransactionAccrual::class);
    }

    public function stakingPurchases(): HasMany
    {
        return $this->hasMany(StakingPurchase::class);
    }

    public function reinvestProfitsAll(): HasMany
    {
        return $this->hasMany(PackageProfitReinvest::class, 'package_uuid', 'uuid');
    }

    public function reinvestProfits(): HasMany
    {
        return $this
            ->hasMany(PackageProfitReinvest::class, 'package_uuid', 'uuid')
            ->whereDoesntHave('withdraw');
    }

    public function reinvestProfitWithdraws(): HasManyThrough
    {
        return $this->hasManyThrough(
            PackageProfitReinvestWithdraw::class,
            PackageProfitReinvest::class,
            'package_uuid',
            'reinvest_uuid',
            'uuid',
            'uuid'
        );
    }

    public function withdrawProfitsTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            PackageProfitWithdraw::class,
            'package_uuid',
            'uuid',
            'uuid',
            'uuid'
        );
    }

    public function reinvestToBody(): HasMany
    {
        return $this->hasMany(ReinvestToPackageBody::class, 'package_uuid', 'uuid');
    }

    public function getCurrentProfitAmount(): BigDecimal
    {
        if (isset($this->currentProfitAmount)) {
            return $this->currentProfitAmount;
        }

        $this->currentProfitAmount = BigDecimal::of($this->profits_sum_amount ?? 0)
            ->minus($this->reinvest_profits_all_sum_amount ?? 0)
            ->minus($this->withdraw_profits_transactions_sum_amount ?? 0);

        return $this->currentProfitAmount;
    }

    public function isActive(): bool
    {
        return is_null($this->closed_at);
    }

    public function canProlong(): bool
    {
        return ! $this->isActive() && $this->residual_amount >= 100;
    }

    public function partnerTransfers(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            PackagePartnerTransfer::class,
            'package_uuid',
            'uuid',
            'uuid',
            'uuid'
        );
    }

    public function balanceWithdraws(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            PackageBalanceWithdraw::class,
            'package_uuid',
            'uuid',
            'uuid',
            'uuid'
        );
    }

    public function scopeByUuidWithSums(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid)
            ->whereNotIn('type', [PackageTypeEnum::ARCHIVE])
            ->with(['transaction'])
            ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount');
    }

    public function getTotalAmountAttribute(): float
    {
        return
            ($this->transaction->amount ?? 0) +
            ($this->partner_transfers_sum_amount ?? 0) +
            ($this->reinvest_to_body_sum_amount ?? 0) -
            ($this->balance_withdraws_sum_amount ?? 0);
    }

    protected $casts = [
        'work_to' => 'datetime',
        'closed_at' => 'datetime',
        'prolonged_to' => 'datetime',
        'type' => PackageTypeEnum::class,
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\ItcPackage> $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ItcPackage>
     */
    #[Scope]
    public function notActive(Builder $query): Builder
    {
        return $query->whereNotIn('type', [PackageTypeEnum::ARCHIVE, PackageTypeEnum::STAKING]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\ItcPackage> $query
     * @param \App\Enums\Itc\PackageTypeEnum ...$enum
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ItcPackage>
     */
    #[Scope]
    public function active(Builder $query, PackageTypeEnum ...$enum): Builder
    {
        return $query->whereIn('type', $enum);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\ItcPackage> $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ItcPackage>
     */
    #[Scope]
    public function userPackagesWithFinancials(Builder $query, int $userId): Builder
    {
        return $query
            ->whereHas('transaction', fn ($q) => $q->where('user_id', $userId))
            ->with(['transaction', 'zeroing'])
            ->withSum(['profits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfitsAll' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfits' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['withdrawProfitsTransactions' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['partnerTransfers' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestProfitWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['balanceWithdraws' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount')
            ->withSum(['reinvestToBody' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0)'))], 'amount');
    }

    #[Scope]
    public function withoutTestUsers(Builder $query): Builder
    {
        return $query->whereHas('transaction.user', function ($q) {
            $q->where('is_test', false);
        });
    }

    #[Scope]
    public function calculateStakingBalance(Builder $query, int $userId): Builder
    {
        return $query->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['transaction' => fn ($query) => $query->where('user_id', $userId)])
            ->withSum(['transaction as transaction_sum' => fn ($q) => $q->where('user_id', $userId)], 'amount')
            ->withSum(
                ['stakingTransactionAccruals as staking_accruals_sum' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0) / 100')),
                ],
                'amount'
            );
    }

    #[Scope]
    public function calculateStakingLastMonthProfitability(Builder $query, int $userId): Builder
    {
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();

        return $query->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['transaction' => fn ($query) => $query->where('user_id', $userId)])
            ->withSum(
                ['stakingTransactionAccruals as staking_accruals_sum' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0) / 100'))->whereNotIn('type', [StakingTransactionAccrualEnum::TopUpBonus])->whereBetween('created_at', [$start, $end]),
                ],
                'amount'
            );
    }

    #[Scope]
    public function calculateStakingTotalProfitability(Builder $query, int $userId): Builder
    {
        return $query->active(PackageTypeEnum::STAKING)
            ->whereHas('transaction', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['transaction' => fn ($query) => $query->where('user_id', $userId)])
            ->withSum(
                ['stakingTransactionAccruals as staking_accruals_sum' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(amount),0) / 100'))->whereNotIn('type', [StakingTransactionAccrualEnum::TopUpBonus]),
                ],
                'amount'
            );
    }
}
