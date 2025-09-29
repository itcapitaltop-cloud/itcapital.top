<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevelOverride extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'partner_level_id', 'active_from', 'overridden_rank'];

    protected $casts = [
        'active_from' => 'datetime',
        'overridden_rank' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(PartnerLevel::class, 'partner_level_id');
    }
}
