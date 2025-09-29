<?php

namespace App\Models;

use App\Enums\CommonFund\PackageTypeEnum;
use App\Traits\Models\TransactionChildTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereUuid($value)
 * @property-read \App\Models\Transaction|null $transaction
 * @mixin \Eloquent
 */
class Package extends Model
{
    use HasFactory, TransactionChildTrait;

    protected $fillable = [
        'uuid', 'type'
    ];

    protected $casts = [
        'type' => PackageTypeEnum::class
    ];
}
