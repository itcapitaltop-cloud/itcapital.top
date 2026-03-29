<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TokenRate extends Model
{
    /** @use HasFactory<\Database\Factories\TokenRateFactory> */
    use HasFactory;

    protected $fillable = [
        'effective_from',
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'rate' => 'decimal:6',
        ];
    }
}
