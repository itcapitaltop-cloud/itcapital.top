<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StakingPurchase extends Model
{
    /** @use HasFactory<\Database\Factories\StakingPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'itc_package_id',
        'user_id',
        'amount_usd',
        'token_amount',
        'purchase_rate',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'token_amount' => 'decimal:2',
            'purchase_rate' => 'decimal:6',
            'purchased_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ItcPackage::class, 'itc_package_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
