<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsCategoryEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'title',
        'mobile_preview',
        'web_preview',
        'content',
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
            'title' => 'array',
            'mobile_preview' => 'array',
            'web_preview' => 'array',
            'content' => 'array',
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function translation(string $field, ?string $locale = null): string
    {
        $values = $this->getAttributeValue($field);

        if (! is_array($values)) {
            return '';
        }

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

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image);
    }
}
