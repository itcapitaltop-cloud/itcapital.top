<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Часть тела пакета, которую пользователь разблокировал: она сразу выходит из базы
 * начисления дивидендов, а деньги поступают на основной баланс ровно через календарный
 * месяц. Операция необратима для пользователя; системно её снимает только закрытие пакета.
 *
 * @property int $id
 * @property string $uuid
 * @property string $package_uuid
 * @property string $amount
 * @property \Illuminate\Support\Carbon $unlocked_at
 * @property \Illuminate\Support\Carbon $payout_at
 * @property string|null $payout_transaction_uuid
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ItcPackage|null $package
 * @property-read Transaction|null $payoutTransaction
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock query()
 * @method static Builder|PackageBodyUnlock pending()
 * @method static Builder|PackageBodyUnlock duePayout()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock wherePayoutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock wherePayoutTransactionUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereUnlockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageBodyUnlock whereUuid($value)
 *
 * @mixin \Eloquent
 */
class PackageBodyUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'package_uuid',
        'amount',
        'unlocked_at',
        'payout_at',
        'payout_transaction_uuid',
        'cancelled_at',
    ];

    /**
     * `amount` намеренно без каста: суммы ходят по проекту строками и считаются
     * через BigDecimal, а decimal:N-каст спрятал бы младшие знаки numeric(16, 8).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'payout_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Разблокировки, деньги по которым ещё не дошли до баланса и не сняты закрытием пакета.
     *
     * @param Builder<PackageBodyUnlock> $query
     * @return Builder<PackageBodyUnlock>
     */
    #[Scope]
    public function pending(Builder $query): Builder
    {
        return $query
            ->whereNull('payout_transaction_uuid')
            ->whereNull('cancelled_at');
    }

    /**
     * Разблокировки, у которых календарный месяц ожидания уже истёк.
     *
     * @param Builder<PackageBodyUnlock> $query
     * @return Builder<PackageBodyUnlock>
     */
    #[Scope]
    public function duePayout(Builder $query): Builder
    {
        return $query
            ->pending()
            ->where('payout_at', '<=', now());
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ItcPackage::class, 'package_uuid', 'uuid');
    }

    public function payoutTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'payout_transaction_uuid', 'uuid');
    }
}
