<?php

namespace App\Models;

use App\Enums\Itc\PackageTypeEnum;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 *
 *
 * @property int $id
 * @property string $uuid
 * @property string $month_profit_percent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereMonthProfitPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereUuid($value)
 * @property-read \App\Models\Transaction|null $transaction
 * @property string $type
 * @property string $work_to
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItcPackage whereWorkTo($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageProfit> $profits
 * @property-read int|null $profits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageProfitReinvest> $reinvestProfits
 * @property-read int|null $reinvest_profits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $withdrawProfitsTransactions
 * @property-read int|null $withdraw_profits_transactions_count
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
        'created_at'
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

        $this->currentProfitAmount = BigDecimal::of($this->profits_sum_amount)
            ->minus($this->reinvest_profits_all_sum_amount)->minus($this->withdraw_profits_transactions_sum_amount);

        return $this->currentProfitAmount;
    }

    public function isActive(): bool
    {
        return is_null($this->closed_at);
    }

    public function canProlong(): bool
    {
        return !$this->isActive() && $this->residual_amount >= 100;
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

    protected $casts = [
        'work_to' => 'datetime',
        'closed_at'    => 'datetime',
        'prolonged_to' => 'datetime',
        'type' => PackageTypeEnum::class
    ];


}
