<?php

declare(strict_types=1);

namespace App\Models\Package\Staking;

use App\Casts\MoneyCast;
use App\Enums\Itc\StakingTransactionAccrualEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StakingTransactionAccrual extends Model
{
    protected $table = 'staking_transaction_accruals';

    protected $fillable = [
        'itc_package_id',
        'user_id',
        'amount',
        'type',
        'line',
        'source_user_id',
        'status',
    ];

    protected $casts = [
        'amount' => MoneyCast::class,
        'type' => StakingTransactionAccrualEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }
}
