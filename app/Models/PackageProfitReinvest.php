<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property string $package_uuid
 * @property string $amount
 * @property string|null $matured_at
 * @property \Illuminate\Support\Carbon|null $unlocked_at
 * @property \Illuminate\Support\Carbon|null $payout_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest query()
 * @method static Builder|PackageProfitReinvest unlockable()
 * @method static Builder|PackageProfitReinvest unlocked()
 * @method static Builder|PackageProfitReinvest duePayout()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereMaturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest wherePayoutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereUnlockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereUuid($value)
 *
 * @mixin \Eloquent
 */
class PackageProfitReinvest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'package_uuid',
        'amount',
        'matured_at',
        'unlocked_at',
        'payout_at',
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
        ];
    }

    /**
     * Реинвесты, которые пользователь может снять прямо сейчас: ещё не выплачены,
     * ещё не разблокированы, и срок заморозки уже прошёл.
     *
     * COALESCE(matured_at, created_at) обязателен: у легаси-строк дата разморозки
     * NULL, и без него они навсегда выпали бы из выборки.
     *
     * @param Builder<PackageProfitReinvest> $query
     * @return Builder<PackageProfitReinvest>
     */
    #[Scope]
    public function unlockable(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('withdraw')
            ->whereNull('unlocked_at')
            ->whereRaw('COALESCE(matured_at, created_at) <= ?', [now()]);
    }

    /**
     * Разблокированные реинвесты, деньги по которым ещё не дошли до основного баланса.
     *
     * @param Builder<PackageProfitReinvest> $query
     * @return Builder<PackageProfitReinvest>
     */
    #[Scope]
    public function unlocked(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('withdraw')
            ->whereNotNull('unlocked_at');
    }

    /**
     * Разблокировки, у которых календарный месяц ожидания уже истёк.
     *
     * @param Builder<PackageProfitReinvest> $query
     * @return Builder<PackageProfitReinvest>
     */
    #[Scope]
    public function duePayout(Builder $query): Builder
    {
        return $query
            ->unlocked()
            ->where('payout_at', '<=', now());
    }

    public function withdraw(): HasOne
    {
        return $this->hasOne(
            PackageProfitReinvestWithdraw::class,
            'reinvest_uuid',
            'uuid'
        );
    }

    public function profitLink(): HasOne
    {
        return $this->hasOne(PackageProfitWithReinvestLink::class, 'reinvest_uuid', 'uuid');
    }

    public function hasprofitLink(): bool
    {
        return $this->profitLink()->exists();
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ItcPackage::class, 'package_uuid', 'uuid');
    }
}
