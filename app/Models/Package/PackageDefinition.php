<?php

declare(strict_types=1);

namespace App\Models\Package;

use App\Enums\Itc\PackageTypeEnum;
use App\Models\ItcPackage;
use Database\Factories\PackageDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property PackageTypeEnum $type
 * @property string $name
 * @property string $default_profit_percent
 * @property string $min_start_amount
 * @property int|null $duration_months
 * @property string|null $card_image_path
 * @property bool $is_active
 * @property int $sort_order
 */
final class PackageDefinition extends Model
{
    /** @use HasFactory<\Database\Factories\PackageDefinitionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'slug',
        'type',
        'name',
        'default_profit_percent',
        'min_start_amount',
        'duration_months',
        'card_image_path',
        'is_active',
        'sort_order',
    ];

    public function packages(): HasMany
    {
        return $this->hasMany(ItcPackage::class);
    }

    /**
     * @return Factory<PackageDefinition>
     */
    protected static function newFactory(): Factory
    {
        return PackageDefinitionFactory::new();
    }

    protected static function booted(): void
    {
        self::saving(function (PackageDefinition $definition): void {
            if (filled($definition->slug)) {
                return;
            }

            $definition->slug = self::makeUniqueSlug((string) $definition->name, $definition->id);
        });
    }

    /**
     * @param Builder<PackageDefinition> $query
     * @return Builder<PackageDefinition>
     */
    #[Scope]
    public function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PackageTypeEnum::class,
            'default_profit_percent' => 'decimal:2',
            'min_start_amount' => 'decimal:2',
            'duration_months' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    private static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'package';
        $slug = $baseSlug;
        $suffix = 2;

        while (self::query()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
