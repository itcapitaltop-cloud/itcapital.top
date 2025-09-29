<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawFiatDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'sbp_phone',
        'bank_name',
        'recipient_name',
    ];

    public function withdraw(): BelongsTo
    {
        return $this->belongsTo(Withdraw::class, 'uuid', 'uuid');
    }
}
