<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *
 *
 * @property int $id
 * @property string $uuid
 * @property string $package_uuid
 * @property string $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitReinvest whereUuid($value)
 * @mixin \Eloquent
 */
class PackageProfitReinvest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'package_uuid',
        'amount',
        'matured_at'
    ];

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
}
