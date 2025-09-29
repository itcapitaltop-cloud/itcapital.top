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
 * @property string $amount
 * @property string $package_uuid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit whereUpdatedAt($value)
 * @property string $uuid
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfit whereUuid($value)
 * @mixin \Eloquent
 */
class PackageProfit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'package_uuid', 'amount', 'uuid'
    ];

    public function reinvestLink(): HasOne
    {
        return $this->hasOne(PackageProfitWithReinvestLink::class, 'profit_uuid', 'uuid');
    }

    public function isReinvested(): bool
    {
        return $this->reinvestLink()->exists();
    }
}
