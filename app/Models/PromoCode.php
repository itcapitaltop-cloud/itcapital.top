<?php

namespace App\Models;

use App\Enums\Itc\PackageTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use MoonShine\Permissions\Models\MoonshineUser;

/**
 * @property int $id
 * @property string $code
 * @property PackageTypeEnum $package_type
 * @property string $reduced_minimum_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PromoCodeUsage> $usages
 * @property-read User|null $createdByAdmin
 */
class PromoCode extends Model
{
    /** @use HasFactory<\Database\Factories\PromoCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'package_type',
        'reduced_minimum_amount',
        'created_by_admin_id',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(MoonshineUser::class, 'created_by_admin_id');
    }

    public function isUsedByUser(User $user, PackageTypeEnum $packageType): bool
    {
        return $this->usages()
            ->where('user_id', $user->id)
            ->where('package_type', $packageType)
            ->exists();
    }

    public function isAvailableFor(User $user, PackageTypeEnum $packageType): bool
    {
        return ! $this->isUsedByUser($user, $packageType)
            && $this->package_type === $packageType;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'package_type' => PackageTypeEnum::class,
            'reduced_minimum_amount' => 'decimal:8',
        ];
    }
}
