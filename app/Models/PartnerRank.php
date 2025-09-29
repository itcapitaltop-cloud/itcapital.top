<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerRank extends Model
{
    use HasFactory;

    protected $fillable = ['rank', 'bonus_usd'];

    public function requirements(): HasMany
    {
        return $this->hasMany(PartnerRankRequirement::class);
    }
}
