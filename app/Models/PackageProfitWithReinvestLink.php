<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageProfitWithReinvestLink extends Model
{
    use HasFactory;

    protected $fillable = ['reinvest_uuid', 'profit_uuid'];

    public function reinvest(): BelongsTo
    {
        return $this->belongsTo(PackageProfitReinvest::class, 'reinvest_uuid', 'uuid');
    }

    public function profit(): BelongsTo
    {
        return $this->belongsTo(PackageProfit::class, 'profit_uuid', 'uuid');
    }
}
