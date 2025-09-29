<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift query()
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DepositGift whereUuid($value)
 * @mixin \Eloquent
 */
class DepositGift extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'comment'
    ];
}
