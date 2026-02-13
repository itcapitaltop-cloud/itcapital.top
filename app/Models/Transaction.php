<?php

namespace App\Models;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TransactionStatusEnum;
use App\Enums\Transactions\TrxTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $uuid
 * @property string $amount
 * @property int $user_id
 * @property string $balance_type
 * @property string $trx_type
 * @property float
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereBalanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTrxType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUuid($value)
 *
 * @property-read mixed $status
 * @property-read \App\Models\User|null $user
 *
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'amount', 'balance_type', 'trx_type', 'accepted_at', 'rejected_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'trx_type' => TrxTypeEnum::class,
        'balance_type' => BalanceTypeEnum::class,
        'amount' => 'decimal:2',
    ];

    public function getStatus(): TransactionStatusEnum
    {
        return TransactionStatusEnum::fromDates($this->accepted_at, $this->rejected_at);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => TransactionStatusEnum::fromDates($attributes['accepted_at'], $attributes['rejected_at'])
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profits()
    {
        return $this->hasManyThrough(
            PackageProfit::class, // целевая модель
            ItcPackage::class,    // промежуточная модель
            'uuid',               // foreign key у ItcPackage на Transaction
            'package_uuid',       // foreign key у PackageProfit на ItcPackage
            'uuid',               // local key Transaction
            'uuid'                // local key у ItcPackage
        );
    }

    public function itcPackage(): BelongsTo
    {
        return $this->belongsTo(ItcPackage::class, 'uuid', 'uuid');
    }

    public function partnerReward(): HasOne
    {
        return $this->hasOne(PartnerReward::class, 'uuid', 'uuid');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Transaction> $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Transaction>
     */
    #[Scope]
    public function deposits(Builder $query): Builder
    {
        return $query
            ->where('trx_type', TrxTypeEnum::DEPOSIT)
            ->whereNotNull('accepted_at');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function packageStaking(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->whereHas('itcPackage', function ($query) {
                $query->where('type', PackageTypeEnum::STAKING)
                    ->withSum(['profits as total_profit' => fn ($q) => $q->whereNull('deleted_at')], 'amount');
            });
    }

    /**
     * Исключить транзакции пользователей с is_true = true
     *
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Transaction> $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Transaction>
     */
    #[Scope]
    public function withoutTestUsers(Builder $query): Builder
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_test', false);
        });
    }
}
