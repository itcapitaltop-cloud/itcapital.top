<?php

namespace App\Models;

use App\Enums\Partners\PartnerRewardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'from_user_id',
        'reward_type',
        'line',
        'amount',
    ];

    protected $casts = [
        'reward_type' => PartnerRewardTypeEnum::class,
        'amount'      => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'uuid', 'uuid');
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}
