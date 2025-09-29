<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class PartnerRankRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_rank_id', 'line', 'required_turnover', 'personal_deposit'
    ];

    protected $casts = [
        'required_turnover' => 'decimal:2',
        'personal_deposit'  => 'decimal:2',
    ];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(PartnerRank::class, 'partner_rank_id');
    }

    public static function asGridRows(?Collection $requirements = null, ?Collection $bonuses = null): array
    {
        $reqs = $requirements ?: self::all();

        $bonuses = $bonuses ?: PartnerRank::all(['id', 'bonus_usd']);

        $rows = [];
        foreach (range(1, 8) as $level) {
            $personalReq = $reqs->first(fn($el) => $el->partner_rank_id == $level && is_null($el->line));
            $bonus = $bonuses->first(fn($r) => $r->id == $level);

            $row = [
                'partner_rank_id'   => $level,
                'line_1'            => null,
                'line_2'            => null,
                'line_3'            => null,
                'line_4'            => null,
                'line_5'            => null,
                'personal_deposit'  => $personalReq?->personal_deposit,
                'bonus_usd'         => $bonus?->bonus_usd,
            ];

            foreach (range(1, 5) as $line) {
                $req = $reqs->first(fn($el) => $el->partner_rank_id == $level && $el->line == $line);
                $row["line_$line"] = $req?->required_turnover;
            }

            $rows[] = $row;
        }
        return $rows;
    }
}
