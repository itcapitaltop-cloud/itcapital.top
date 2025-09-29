<?php

namespace App\Models;

use App\Enums\Partners\PartnerRewardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevelPercentOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_level_id', 'bonus_type', 'line', 'percent', 'user_id',
    ];

    protected $casts = [
        'bonus_type' => PartnerRewardTypeEnum::class,
        'percent'    => 'decimal:2',
    ];
}
