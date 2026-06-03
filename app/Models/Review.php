<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'rating',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatusEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    public function approved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatusEnum::Approved);
    }

    #[Scope]
    public function pending(Builder $query): Builder
    {
        return $query->where('status', ReviewStatusEnum::Pending);
    }

    public static function averageRating(): float
    {
        $avg = self::query()->approved()->avg('rating');

        return round((float) $avg, 1);
    }

    public static function approvedCount(): int
    {
        return self::query()->approved()->count();
    }
}
