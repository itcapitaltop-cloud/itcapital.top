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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw wherePackageUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageWithdraw whereUuid($value)
 * @mixin \Eloquent
 */
class PackageWithdraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'package_uuid'
    ];
}
