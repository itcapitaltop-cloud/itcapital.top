<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerLevel extends Model
{
    use HasFactory;

    protected $fillable = ['level', 'name'];

    public function percents(): HasMany
    {
        return $this->hasMany(PartnerLevelPercent::class);
    }
}
