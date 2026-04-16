<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageProfitReinvestWithdraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'reinvest_uuid',
    ];

    public function reinvest(): BelongsTo
    {
        return $this->belongsTo(PackageProfitReinvest::class, 'reinvest_uuid', 'uuid');
    }
}
