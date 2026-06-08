<?php

namespace App\Models;

use App\Models\Package\PackageDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promo_code_id
 * @property int $user_id
 * @property int|null $package_definition_id
 * @property Carbon $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PromoCode $promoCode
 * @property-read User $user
 * @property-read PackageDefinition|null $packageDefinition
 */
class PromoCodeUsage extends Model
{
    protected $fillable = [
        'promo_code_id',
        'user_id',
        'package_definition_id',
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

    public function packageDefinition(): BelongsTo
    {
        return $this->belongsTo(PackageDefinition::class);
    }

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
