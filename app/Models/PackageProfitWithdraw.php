<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string $package_uuid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageProfitWithdraw whereUuid($value)
 * @mixin \Eloquent
 */
class PackageProfitWithdraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'package_uuid'
    ];
}
