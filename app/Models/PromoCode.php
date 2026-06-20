<?php

namespace App\Models;

use App\Models\Package\PackageDefinition;
use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use MoonShine\Permissions\Models\MoonshineUser;

/**
 * @property int $id
 * @property string $code
 * @property int|null $package_definition_id
 * @property string $reduced_minimum_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PackageDefinition|null $packageDefinition
 * @property-read Collection<int, PromoCodeUsage> $usages
 * @property-read User|null $createdByAdmin
 */
class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'code',
        'package_definition_id',
        'reduced_minimum_amount',
        'created_by_admin_id',
    ];

    public function packageDefinition(): BelongsTo
    {
        return $this->belongsTo(PackageDefinition::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(MoonshineUser::class, 'created_by_admin_id');
    }

    public function isUsedByUser(User $user, PackageDefinition $packageDefinition): bool
    {
        return $this->usages()
            ->where('user_id', $user->id)
            ->where('package_definition_id', $packageDefinition->id)
            ->exists();
    }

    public function isAvailableFor(User $user, PackageDefinition $packageDefinition): bool
    {
        return ! $this->isUsedByUser($user, $packageDefinition)
            && $this->package_definition_id === $packageDefinition->id;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reduced_minimum_amount' => 'decimal:8',
            'deleted_at' => 'datetime',
        ];
    }
}
