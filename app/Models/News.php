<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsCategoryEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class News extends Model
{
    /** @use HasFactory<\Database\Factories\NewsFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'image',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NewsCategoryEnum::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $news): void {
            if (is_null($news->published_at)) {
                $news->published_at = now();
            }
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NewsTranslation::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function translation(string $field, ?string $locale = null): string
    {
        $values = $this->translatedAttribute($field);

        foreach ([$locale ?? app()->getLocale(), config('app.fallback_locale'), 'ru', 'en', 'zh'] as $candidate) {
            $value = data_get($values, $candidate);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return (string) collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->first('');
    }

    public function getTitleAttribute(): array
    {
        return $this->translatedAttribute('title');
    }

    public function getMobilePreviewAttribute(): array
    {
        return $this->translatedAttribute('mobile_preview');
    }

    public function getWebPreviewAttribute(): array
    {
        return $this->translatedAttribute('web_preview');
    }

    public function getContentAttribute(): array
    {
        return $this->translatedAttribute('content');
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image);
    }

    /**
     * @return array<string, string>
     */
    private function translatedAttribute(string $field): array
    {
        $this->loadMissing('translations');

        return $this->translations
            ->mapWithKeys(fn (NewsTranslation $translation): array => [
                $translation->locale => (string) $translation->{$field},
            ])
            ->all();
    }
}
