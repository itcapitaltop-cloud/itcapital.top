<?php

namespace App\Models;

use App\Enums\Partners\PartnerRewardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PartnerLevelPercent extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_level_id', 'bonus_type', 'line', 'percent'
    ];

    protected $casts = [
        'bonus_type' => PartnerRewardTypeEnum::class,
        'percent'    => 'decimal:2',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(PartnerLevel::class, 'partner_level_id');
    }

    public static function asGridRows(?Collection $override = null, bool $common = false): array
    {
        $data = $override ?: self::all();
        $rows = [];
        foreach (range(1, 8) as $level) {
            foreach (['start', 'regular'] as $bonusType) {
                $row = [
                    'partner_level_id' => $level,
                    'bonus_type' => $bonusType,
                ];
                foreach (range(1, $common ? 20 : 5) as $line) {
                    $percent = $data->first(fn($el) =>
                        $el->partner_level_id === $level &&
                        $el->bonus_type->value === $bonusType &&
                        $el->line === $line
                    );

                    $row["line_$line"] = $percent ? $percent->percent : null;
                }
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
