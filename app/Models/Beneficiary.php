<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Beneficiary extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'social_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
