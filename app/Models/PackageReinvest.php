<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string $package_uuid
 * @property \Illuminate\Support\Carbon $expire
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest whereExpire($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageReinvest whereUuid($value)
 * @mixin \Eloquent
 */
class PackageReinvest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'package_uuid', 'expire'
    ];

    protected $casts = [
        'expire' => 'datetime'
    ];
}
