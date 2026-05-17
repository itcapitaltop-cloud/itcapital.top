<?php

namespace App\Models;

use App\Enums\Itc\PackageTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promo_code_id
 * @property int $user_id
 * @property PackageTypeEnum $package_type
 * @property Carbon $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PromoCode $promoCode
 * @property-read User $user
 */
class PromoCodeUsage extends Model
{
    protected $fillable = [
        'promo_code_id',
        'user_id',
        'package_type',
        'used_at',
    ];

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'package_type' => PackageTypeEnum::class,
            'used_at' => 'datetime',
        ];
    }
}
