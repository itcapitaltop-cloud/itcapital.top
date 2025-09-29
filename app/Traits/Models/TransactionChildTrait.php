<?php

namespace App\Traits\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TransactionChildTrait
{
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'uuid', 'uuid');
    }
}
