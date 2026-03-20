<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsTranslation extends Model
{
    /** @use HasFactory<\Database\Factories\NewsTranslationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'news_id',
        'locale',
        'title',
        'mobile_preview',
        'web_preview',
        'content',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
